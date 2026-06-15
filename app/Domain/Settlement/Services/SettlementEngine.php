<?php

namespace App\Domain\Settlement\Services;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Settlement\Calculators\AsianHandicapSettlementCalculator;
use App\Domain\Settlement\Calculators\ExactScoreSettlementCalculator;
use App\Domain\Settlement\Calculators\OverUnderSettlementCalculator;
use App\Domain\Settlement\Data\MatchResult;
use App\Domain\Settlement\Data\SettlementResult;
use App\Domain\Settlement\Exceptions\SettlementException;
use App\Domain\Wallet\Services\WalletService;
use App\Enums\BetStatus;
use App\Enums\MarketStatus;
use App\Jobs\RebuildLeaderboardJob;
use App\Models\Bet;
use App\Models\Market;
use App\Models\Settlement;
use App\Models\SettlementItem;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

/**
 * SettlementEngine — orchestrate toàn bộ quá trình settle một market.
 *
 * preview(): Tính trước kết quả, không thay đổi DB.
 * execute(): Transaction, lock, payout từng bet, ledger, idempotent.
 */
class SettlementEngine
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly ExactScoreSettlementCalculator $exactScore,
        private readonly AsianHandicapSettlementCalculator $asianHandicap,
        private readonly OverUnderSettlementCalculator $overUnder,
        private readonly AuditLogService $auditLog,
    ) {}

    // ===================== PREVIEW =====================

    /**
     * Tính preview settlement — chỉ tính toán, KHÔNG ghi DB.
     *
     * @return array<int, array{bet: Bet, result: SettlementResult}>
     */
    public function preview(Market $market, MatchResult $matchResult): array
    {
        $bets = Bet::where('market_id', $market->id)
            ->whereIn('status', [BetStatus::PENDING->value])
            ->with(['outcome', 'wallet.user'])
            ->get();

        $previews = [];
        foreach ($bets as $bet) {
            $result = $this->calculateForBet($bet, $matchResult, $market->market_type);
            $previews[] = ['bet' => $bet, 'result' => $result];
        }

        return $previews;
    }

    // ===================== EXECUTE =====================

    /**
     * Thực thi settlement — idempotent, có transaction, có ledger.
     *
     * @throws SettlementException
     */
    public function execute(
        Market $market,
        MatchResult $matchResult,
        ?User $executedBy = null,
        string $reason = ''
    ): Settlement {
        $bets = collect();
        $settlement = DB::transaction(function () use ($market, $matchResult, $executedBy, $reason, &$bets) {
            // Lock market row
            $market = Market::lockForUpdate()->findOrFail($market->id);

            // ---- Idempotency: chỉ cho phép 1 EXECUTED settlement per market ----
            $existingSettlement = Settlement::where('market_id', $market->id)
                ->where('status', 'EXECUTED')
                ->first();

            if ($existingSettlement) {
                throw new SettlementException(
                    "Market [{$market->id}] đã được settle (Settlement #{$existingSettlement->id}). Không thể execute lại.",
                    'ALREADY_SETTLED'
                );
            }

            // ---- Kiểm tra trạng thái market phải là LOCKED ----
            if ($market->status !== MarketStatus::LOCKED->value && $market->status !== 'SETTLING') {
                throw new SettlementException(
                    "Market phải ở trạng thái LOCKED hoặc SETTLING để settle. Hiện tại: {$market->status}.",
                    'MARKET_NOT_LOCKED'
                );
            }

            // ---- Chuyển market sang SETTLING ----
            $market->update(['status' => MarketStatus::SETTLING->value]);

            // ---- Lấy tất cả PENDING bets ----
            $bets = Bet::where('market_id', $market->id)
                ->where('status', BetStatus::PENDING->value)
                ->with(['outcome', 'wallet'])
                ->lockForUpdate()
                ->get();

            // ---- Tạo Settlement record ----
            $settlement = Settlement::create([
                'market_id' => $market->id,
                'match_id' => $market->match_id,
                'period_type' => $market->period_type,
                'status' => 'PROCESSING',
                'result_home_score' => $matchResult->homeScore,
                'result_away_score' => $matchResult->awayScore,
                'total_bets' => $bets->count(),
                'total_stake' => $bets->sum('stake'),
                'total_payout' => 0, // cập nhật sau
                'executed_by' => $executedBy?->id,
                'executed_at' => now(),
                'reason' => $reason,
            ]);

            $totalPayout = 0;

            // ---- Settle từng bet ----
            foreach ($bets as $bet) {
                $result = $this->calculateForBet($bet, $matchResult, $market->market_type);

                // Lock wallet
                $wallet = Wallet::lockForUpdate()->findOrFail($bet->wallet_id);

                // Ghi WalletService — giải phóng locked + cộng payout
                $this->walletService->settleBet($wallet, $bet, $result->grossPayout, $result->status);

                // Cập nhật Bet
                $bet->update([
                    'status' => $result->status->value,
                    'gross_payout' => $result->grossPayout,
                    'net_result' => $result->netResult,
                    'settled_at' => now(),
                ]);

                // Gửi thông báo kết quả vé cược
                app(\App\Domain\Notification\Services\NotificationService::class)->notifyBetSettled($bet->wallet->user, $bet);

                // Tạo SettlementItem
                SettlementItem::create([
                    'settlement_id' => $settlement->id,
                    'bet_id' => $bet->id,
                    'user_id' => $bet->user_id,
                    'stake' => $bet->stake,
                    'profit_rate_snapshot' => $bet->profit_rate_snapshot,
                    'result_status' => $result->status->value,
                    'gross_payout' => $result->grossPayout,
                    'net_result' => $result->netResult,
                    'calculation_detail' => $result->calculationDetail,
                ]);

                $totalPayout += $result->grossPayout;
            }

            // ---- Finalize settlement ----
            $settlement->update([
                'status' => 'EXECUTED',
                'total_payout' => $totalPayout,
            ]);

            // ---- Chuyển market sang SETTLED ----
            $market->update([
                'status' => MarketStatus::SETTLED->value,
                'settled_at' => now(),
            ]);

            return $settlement->fresh();
        });

        // Dispatch leaderboard rebuild NGOÀI transaction — không block settle
        $seasonId = $market->loadMissing('match')->match->season_id;
        dispatch(new RebuildLeaderboardJob($seasonId));

        // Audit log SETTLEMENT_EXECUTED
        $this->auditLog->logSettlement(
            $settlement,
            $settlement->total_bets,
            $settlement->total_payout,
            $executedBy,
        );

        // Lấy danh sách user_id unique từ các bet để check thành tựu
        $userIds = $bets->pluck('user_id')->unique();
        foreach ($userIds as $userId) {
            event(new \App\Events\SettlementCompleted($userId));
        }

        return $settlement;
    }

    // ===================== INTERNAL =====================

    private function calculateForBet(Bet $bet, MatchResult $matchResult, string $marketType): SettlementResult
    {
        return match ($marketType) {
            'EXACT_SCORE' => $this->exactScore->calculate($bet, $matchResult),
            'ASIAN_HANDICAP' => $this->asianHandicap->calculate($bet, $matchResult),
            'OVER_UNDER' => $this->overUnder->calculate($bet, $matchResult),
            default => throw new SettlementException(
                "Market type không hỗ trợ: {$marketType}.",
                'UNSUPPORTED_MARKET_TYPE'
            ),
        };
    }
}
