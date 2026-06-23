<?php

namespace App\Domain\Betting\Services;

use App\Domain\Betting\Data\PlaceBetInput;
use App\Domain\Betting\Exceptions\BetPlacementException;
use App\Domain\Wallet\Services\WalletService;
use App\Models\Bet;
use App\Models\Market;
use App\Models\MarketOutcome;
use App\Models\User;
use App\Models\Wallet;
use App\Settings\AppSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * BetPlacementService — đặt vé dự đoán.
 *
 * Thực hiện 14 bước theo AGENTS.md rule 6 + implementation_plan v0.7.0.
 * MỌI logic đều nằm trong service này, KHÔNG tính toán ở Filament/Controller.
 */
class BetPlacementService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    /**
     * Đặt dự đoán. Trả về Bet đã tạo, hoặc throw BetPlacementException.
     *
     * @throws BetPlacementException
     */
    public function placeBet(PlaceBetInput $input): Bet
    {
        return DB::transaction(function () use ($input) {
            // ---- Bước 2: Lock wallet FOR UPDATE ----
            $wallet = Wallet::lockForUpdate()->findOrFail($input->wallet->id);

            // ---- Bước 3: User active? (re-fetch để tránh stale state) ----
            $user = User::findOrFail($input->user->id);
            if ($user->status !== 'ACTIVE') {
                throw new BetPlacementException(
                    'Tài khoản không hoạt động. Không thể đặt dự đoán.',
                    'USER_INACTIVE'
                );
            }

            // ---- Bước 4: Market tồn tại + trạng thái OPEN? ----
            // Re-fetch trong transaction để tránh stale read
            $market = Market::lockForUpdate()->findOrFail($input->market->id);

            if ($market->status !== 'OPEN') {
                throw new BetPlacementException(
                    "Kèo [{$market->name}] không ở trạng thái OPEN (hiện tại: {$market->status}).",
                    'MARKET_NOT_OPEN'
                );
            }

            // ---- Bước 5: now < close_at? ----
            if (now()->gte($market->close_at)) {
                throw new BetPlacementException(
                    "Kèo [{$market->name}] đã hết giờ đặt (close_at: {$market->close_at->format('d/m/Y H:i')}).",
                    'MARKET_CLOSED'
                );
            }

            // ---- Bước 6: Outcome active? ----
            $outcome = MarketOutcome::findOrFail($input->outcome->id);

            if ($outcome->status !== 'ACTIVE') {
                throw new BetPlacementException(
                    "Lựa chọn [{$outcome->label}] không còn khả dụng.",
                    'OUTCOME_INACTIVE'
                );
            }

            // Outcome phải thuộc market này
            if ($outcome->market_id !== $market->id) {
                throw new BetPlacementException(
                    'Lựa chọn không thuộc kèo này.',
                    'OUTCOME_MARKET_MISMATCH'
                );
            }

            // ---- Bước 7: stake >= min_stake? ----
            $minStake = $this->getSystemSetting('min_stake', 10);
            if ($input->stake < $minStake) {
                throw new BetPlacementException(
                    "Số lá tối thiểu là {$minStake}. Bạn nhập {$input->stake}.",
                    'STAKE_TOO_LOW'
                );
            }

            // ---- Bước 8: stake <= max_stake_per_bet? ----
            $maxPerBet = $this->getSystemSetting('max_stake_per_bet', 200);
            if ($input->stake > $maxPerBet) {
                throw new BetPlacementException(
                    "Số lá tối đa mỗi vé là {$maxPerBet}. Bạn nhập {$input->stake}.",
                    'STAKE_EXCEEDS_MAX_PER_BET'
                );
            }

            // ---- Bước 9: total_match_stake <= max_stake_per_match? ----
            $maxPerMatch = $this->getSystemSetting('max_stake_per_match', 500);
            $existingMatchStake = Bet::where('user_id', $input->user->id)
                ->where('match_id', $market->match_id)
                ->whereIn('status', ['PENDING', 'WON', 'LOST', 'PUSH', 'HALF_WON', 'HALF_LOST'])
                ->sum('stake');

            if (($existingMatchStake + $input->stake) > $maxPerMatch) {
                $remaining = $maxPerMatch - $existingMatchStake;
                throw new BetPlacementException(
                    "Đã đặt {$existingMatchStake} lá cho trận này. Tối đa {$maxPerMatch}. Còn có thể đặt: {$remaining} lá.",
                    'STAKE_EXCEEDS_MAX_PER_MATCH'
                );
            }

            // ---- Bước 10: available_balance >= stake? ----
            if ($wallet->available_balance < $input->stake) {
                throw new BetPlacementException(
                    "Số lá không đủ. Hiện có: {$wallet->available_balance}. Cần: {$input->stake}.",
                    'INSUFFICIENT_BALANCE'
                );
            }

            // ---- Bước 11: Snapshot tất cả giá trị cần lưu ----
            $profitRate = (float) $outcome->profit_rate;
            $displayLabel = $outcome->label;
            if (str_starts_with(strtolower($displayLabel), 'over ')) {
                $displayLabel = preg_replace('/^over /i', 'Tài ', $displayLabel);
            } elseif (str_starts_with(strtolower($displayLabel), 'under ')) {
                $displayLabel = preg_replace('/^under /i', 'Xỉu ', $displayLabel);
            } elseif (strtolower($displayLabel) === 'over') {
                $displayLabel = 'Tài';
            } elseif (strtolower($displayLabel) === 'under') {
                $displayLabel = 'Xỉu';
            }

            if (in_array($market->market_type, ['ASIAN_HANDICAP', 'OVER_UNDER']) && !is_null($outcome->line_value)) {
                $displayLine = (float) $outcome->line_value;
                if ($market->market_type === 'ASIAN_HANDICAP') {
                    if ($displayLine > 0) {
                        $displayLine = '+' . $displayLine;
                    }
                    $displayLabel = $outcome->selection_side === 'HOME' ? 'Đội Nhà' : 'Đội Khách';
                }
                
                if (str_starts_with($displayLabel, 'Tài') || str_starts_with($displayLabel, 'Xỉu')) {
                    $labelPrefix = str_starts_with($displayLabel, 'Tài') ? 'Tài' : 'Xỉu';
                    $displayOdds = "{$labelPrefix} {$displayLine} ăn ".number_format($profitRate, 2);
                } else {
                    $displayOdds = "{$displayLabel} {$displayLine} ăn ".number_format($profitRate, 2);
                }
            } else {
                $displayOdds = "{$displayLabel} ăn ".number_format($profitRate, 2);
            }

            // ---- Bước 12: Tạo Bet PENDING ----
            $bet = Bet::create([
                'public_code' => $this->generatePublicCode(),
                'user_id' => $input->user->id,
                'wallet_id' => $wallet->id,
                'season_id' => $wallet->season_id,
                'match_id' => $market->match_id,
                'market_id' => $market->id,
                'outcome_id' => $outcome->id,
                'stake' => $input->stake,
                'profit_rate_snapshot' => $profitRate,
                'line_snapshot' => $outcome->line_value,
                'label_snapshot' => $outcome->label,
                'display_odds_snapshot' => $displayOdds,
                'close_at_snapshot' => $market->close_at,
                'market_type_snapshot' => $market->market_type,
                'period_type_snapshot' => $market->period_type,
                'selection_side_snapshot' => $outcome->selection_side,
                'status' => 'PENDING',
                'placed_at' => now(),
            ]);

            // ---- Bước 13: WalletService::lockStake() ----
            // wallet đã lockForUpdate — gọi trực tiếp, không mở transaction con
            $this->walletService->lockStake($wallet, $input->stake, $bet);

            event(new \App\Events\BetPlaced($bet));

            // ---- Bước 14: Commit (tự động khi closure kết thúc) ----
            return $bet->fresh();
        });
    }

    /**
     * Lấy cài đặt hệ thống từ AppSettings.
     */
    private function getSystemSetting(string $key, int $default): int
    {
        try {
            $settings = app(AppSettings::class);

            return (int) ($settings->{$key} ?? $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Sinh mã công khai duy nhất cho vé.
     * Format: DL-YYYYMMDD-XXXXX (DL = Dự Đoán Lá)
     */
    private function generatePublicCode(): string
    {
        do {
            $code = 'DL-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (Bet::where('public_code', $code)->exists());

        return $code;
    }
}
