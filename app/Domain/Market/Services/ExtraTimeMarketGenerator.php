<?php

namespace App\Domain\Market\Services;

use App\Models\Bet;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MarketOutcome;
use App\Models\MatchEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Auto-generate Extra Time & Penalty markets cho các trận knockout.
 *
 * Khi API không trả về odds cho ET/Penalty, service này tự tính profit_rate
 * dựa trên thống kê 90 phút (bàn thắng, thẻ đỏ, momentum, market sentiment).
 *
 * Luồng:
 *   - Gọi từ MatchSyncService::autoManageMarkets() khi phát hiện trận knockout hòa
 *   - Tạo markets với status DRAFT → MatchSyncService sẽ chuyển sang OPEN
 *   - Idempotent: không tạo trùng nếu đã tồn tại
 */
class ExtraTimeMarketGenerator
{
    /**
     * Sinh kèo Asian Handicap (line 0) + Over/Under (line 0.5) cho Hiệp phụ.
     */
    public function generateExtraTimeMarkets(FootballMatch $match): void
    {
        if ($this->isGroupStage($match)) {
            return;
        }

        // Guard: đã có ET market → skip
        if (Market::where('match_id', $match->id)->where('period_type', 'EXTRA_TIME')->exists()) {
            return;
        }

        $ftResult = $match->periodResults()->where('period_type', 'FULL_TIME')->first();
        $totalGoals90 = $ftResult ? $ftResult->home_score + $ftResult->away_score : 0;

        $strength = $this->calculateStrengthScore($match);
        $ahOdds = $this->strengthToOdds($strength['score']);
        $ouOdds = $this->calculateAdaptiveEtOuOdds($totalGoals90);

        $closeAt = $match->kickoff_at->clone()->addMinutes(112);

        DB::transaction(function () use ($match, $ahOdds, $ouOdds, $closeAt, $strength) {
            // 1. Asian Handicap ET (line 0)
            $ahMarket = Market::create([
                'match_id' => $match->id,
                'period_type' => 'EXTRA_TIME',
                'market_type' => 'ASIAN_HANDICAP',
                'name' => 'Kèo chấp (AH) - Hiệp phụ',
                'status' => 'DRAFT',
                'open_at' => now(),
                'close_at' => $closeAt,
                'display_order' => 10,
            ]);

            MarketOutcome::create([
                'market_id' => $ahMarket->id,
                'label' => 'Đội Nhà',
                'selection_side' => 'HOME',
                'line_value' => 0,
                'profit_rate' => $ahOdds['home'],
                'decimal_odds' => round(1 + $ahOdds['home'], 4),
                'status' => 'ACTIVE',
                'display_order' => 1,
            ]);

            MarketOutcome::create([
                'market_id' => $ahMarket->id,
                'label' => 'Đội Khách',
                'selection_side' => 'AWAY',
                'line_value' => 0,
                'profit_rate' => $ahOdds['away'],
                'decimal_odds' => round(1 + $ahOdds['away'], 4),
                'status' => 'ACTIVE',
                'display_order' => 2,
            ]);

            // 2. Over/Under ET (line 0.5)
            $ouMarket = Market::create([
                'match_id' => $match->id,
                'period_type' => 'EXTRA_TIME',
                'market_type' => 'OVER_UNDER',
                'name' => 'Tài/Xỉu (O/U) - Hiệp phụ',
                'status' => 'DRAFT',
                'open_at' => now(),
                'close_at' => $closeAt,
                'display_order' => 11,
            ]);

            MarketOutcome::create([
                'market_id' => $ouMarket->id,
                'label' => 'Tài',
                'selection_side' => 'OVER',
                'line_value' => 0.5,
                'profit_rate' => $ouOdds['over'],
                'decimal_odds' => round(1 + $ouOdds['over'], 4),
                'status' => 'ACTIVE',
                'display_order' => 1,
            ]);

            MarketOutcome::create([
                'market_id' => $ouMarket->id,
                'label' => 'Xỉu',
                'selection_side' => 'UNDER',
                'line_value' => 0.5,
                'profit_rate' => $ouOdds['under'],
                'decimal_odds' => round(1 + $ouOdds['under'], 4),
                'status' => 'ACTIVE',
                'display_order' => 2,
            ]);

            Log::info("Auto-generated ET markets for match {$match->match_code}", [
                'match_id' => $match->id,
                'strength_score' => $strength['score'],
                'ah_odds' => $ahOdds,
                'ou_odds' => $ouOdds,
            ]);
        });
    }

    /**
     * Sinh kèo Asian Handicap (line 0) + Over/Under (line 7.5) cho Penalty.
     *
     * Best Practice: Mở SỚM cùng lúc với kèo ET (ngay khi 90p hòa).
     * Nếu trận phân thắng bại trong ET → hệ thống sẽ VOID kèo Penalty.
     */
    public function generatePenaltyMarkets(FootballMatch $match): void
    {
        if ($this->isGroupStage($match)) {
            return;
        }

        // Guard: đã có Penalty market → skip
        if (Market::where('match_id', $match->id)->where('period_type', 'PENALTY')->exists()) {
            return;
        }

        $ftResult = $match->periodResults()->where('period_type', 'FULL_TIME')->first();
        $totalGoals90 = $ftResult ? $ftResult->home_score + $ftResult->away_score : 0;

        $strength = $this->calculateStrengthScore($match);
        $ahOdds = $this->strengthToOdds($strength['score']);
        $ouOdds = $this->calculatePenaltyOuOdds($totalGoals90);

        $closeAt = $match->kickoff_at->clone()->addMinutes(148);

        DB::transaction(function () use ($match, $ahOdds, $ouOdds, $closeAt, $strength) {
            // 1. Asian Handicap Penalty (line 0) — đội mạnh ăn ít, đội yếu ăn nhiều
            $ahMarket = Market::create([
                'match_id' => $match->id,
                'period_type' => 'PENALTY',
                'market_type' => 'ASIAN_HANDICAP',
                'name' => 'Kèo chấp (AH) - Penalty',
                'status' => 'DRAFT',
                'open_at' => now(),
                'close_at' => $closeAt,
                'display_order' => 20,
            ]);

            MarketOutcome::create([
                'market_id' => $ahMarket->id,
                'label' => 'Đội Nhà',
                'selection_side' => 'HOME',
                'line_value' => 0,
                'profit_rate' => $ahOdds['home'],
                'decimal_odds' => round(1 + $ahOdds['home'], 4),
                'status' => 'ACTIVE',
                'display_order' => 1,
            ]);

            MarketOutcome::create([
                'market_id' => $ahMarket->id,
                'label' => 'Đội Khách',
                'selection_side' => 'AWAY',
                'line_value' => 0,
                'profit_rate' => $ahOdds['away'],
                'decimal_odds' => round(1 + $ahOdds['away'], 4),
                'status' => 'ACTIVE',
                'display_order' => 2,
            ]);

            // 2. Over/Under Penalty (line 7.5 quả)
            $ouMarket = Market::create([
                'match_id' => $match->id,
                'period_type' => 'PENALTY',
                'market_type' => 'OVER_UNDER',
                'name' => 'Tài/Xỉu (O/U) - Penalty',
                'status' => 'DRAFT',
                'open_at' => now(),
                'close_at' => $closeAt,
                'display_order' => 21,
            ]);

            MarketOutcome::create([
                'market_id' => $ouMarket->id,
                'label' => 'Tài',
                'selection_side' => 'OVER',
                'line_value' => 7.5,
                'profit_rate' => $ouOdds['over'],
                'decimal_odds' => round(1 + $ouOdds['over'], 4),
                'status' => 'ACTIVE',
                'display_order' => 1,
            ]);

            MarketOutcome::create([
                'market_id' => $ouMarket->id,
                'label' => 'Xỉu',
                'selection_side' => 'UNDER',
                'line_value' => 7.5,
                'profit_rate' => $ouOdds['under'],
                'decimal_odds' => round(1 + $ouOdds['under'], 4),
                'status' => 'ACTIVE',
                'display_order' => 2,
            ]);

            Log::info("Auto-generated Penalty markets for match {$match->match_code}", [
                'match_id' => $match->id,
                'strength_score' => $strength['score'],
                'ah_odds' => $ahOdds,
                'ou_odds' => $ouOdds,
            ]);
        });
    }

    /**
     * Tính điểm sức mạnh tương đối giữa 2 đội dựa trên dữ liệu 90 phút.
     *
     * Score > 0 = Home mạnh hơn, < 0 = Away mạnh hơn.
     * Dựa trên: momentum (bàn thắng muộn), bàn thắng từ chơi mở,
     * thẻ đỏ, và market sentiment (tổng lá đặt).
     */
    public function calculateStrengthScore(FootballMatch $match): array
    {
        $score = 0.0;

        $events = MatchEvent::where('match_id', $match->id)->get();

        // 1. Momentum: Đội ghi bàn SAU (muộn hơn trong 90p) có momentum
        $homeGoalMinutes = $events->where('type', 'GOAL')->where('team_type', 'HOME')->pluck('minute')->toArray();
        $awayGoalMinutes = $events->where('type', 'GOAL')->where('team_type', 'AWAY')->pluck('minute')->toArray();

        $lastHomeGoalMin = ! empty($homeGoalMinutes) ? max($homeGoalMinutes) : 0;
        $lastAwayGoalMin = ! empty($awayGoalMinutes) ? max($awayGoalMinutes) : 0;

        if ($lastHomeGoalMin > $lastAwayGoalMin + 15) {
            $score += 1.0;
        }
        if ($lastAwayGoalMin > $lastHomeGoalMin + 15) {
            $score -= 1.0;
        }

        // 2. Bàn thắng từ chơi mở (không penalty, không own goal)
        $homeRegularGoals = $events->where('type', 'GOAL')->where('team_type', 'HOME')
            ->whereIn('detail', ['REGULAR', 'Normal Goal', null])->count();
        $awayRegularGoals = $events->where('type', 'GOAL')->where('team_type', 'AWAY')
            ->whereIn('detail', ['REGULAR', 'Normal Goal', null])->count();

        $score += ($homeRegularGoals - $awayRegularGoals) * 0.5;

        // 3. Thẻ đỏ: đội bị thẻ đỏ yếu hơn đáng kể
        $homeReds = $events->where('type', 'CARD')->where('team_type', 'HOME')
            ->whereIn('detail', ['RED', 'Red Card'])->count();
        $awayReds = $events->where('type', 'CARD')->where('team_type', 'AWAY')
            ->whereIn('detail', ['RED', 'Red Card'])->count();

        $score -= $homeReds * 2;
        $score += $awayReds * 2;

        // 4. Market sentiment: tổng lá đặt vào mỗi đội (kèo FULL_TIME AH)
        $ahMarket = Market::where('match_id', $match->id)
            ->where('period_type', 'FULL_TIME')
            ->where('market_type', 'ASIAN_HANDICAP')
            ->first();

        if ($ahMarket) {
            $homeStake = Bet::where('market_id', $ahMarket->id)
                ->where('selection_side_snapshot', 'HOME')
                ->whereNotIn('status', ['VOIDED'])
                ->sum('stake');

            $awayStake = Bet::where('market_id', $ahMarket->id)
                ->where('selection_side_snapshot', 'AWAY')
                ->whereNotIn('status', ['VOIDED'])
                ->sum('stake');

            $totalStake = $homeStake + $awayStake;
            if ($totalStake > 0) {
                $sentiment = ($homeStake - $awayStake) / $totalStake;
                $score += $sentiment * 1.5;
            }
        }

        // Clamp score: -5 đến +5
        $score = max(-5.0, min(5.0, $score));

        return [
            'score' => round($score, 2),
            'home_stronger' => $score > 0.5,
            'away_stronger' => $score < -0.5,
        ];
    }

    /**
     * Map strength score → profit_rate cho 2 đội.
     *
     * Đội mạnh: profit_rate thấp (0.60 - 0.85)
     * Đội yếu: profit_rate cao (0.95 - 1.20)
     * Cân bằng: 0.88 / 0.88
     */
    public function strengthToOdds(float $score): array
    {
        $absScore = abs($score);

        [$strongRate, $weakRate] = match (true) {
            $absScore >= 3.0 => [0.60, 1.20],
            $absScore >= 2.0 => [0.65, 1.10],
            $absScore >= 1.0 => [0.70, 1.00],
            $absScore >= 0.5 => [0.80, 0.95],
            default => [0.88, 0.88],
        };

        // score > 0 = Home mạnh hơn
        if ($score > 0.5) {
            return ['home' => $strongRate, 'away' => $weakRate];
        } elseif ($score < -0.5) {
            return ['home' => $weakRate, 'away' => $strongRate];
        }

        return ['home' => 0.88, 'away' => 0.88];
    }

    /**
     * Tính Over/Under odds cho Extra Time dựa trên tổng bàn thắng 90 phút.
     *
     * Line 0.5 (có bàn hay không trong 30 phút hiệp phụ).
     */
    private function calculateAdaptiveEtOuOdds(int $totalGoals90): array
    {
        if ($totalGoals90 >= 4) {
            // Trận nhiều bàn → ET cũng có xu hướng có bàn
            return ['over' => 0.80, 'under' => 0.85];
        } elseif ($totalGoals90 <= 1) {
            // Trận ít bàn → ET khả năng cao 0 bàn
            return ['over' => 0.60, 'under' => 1.05];
        }

        // Mặc định
        return ['over' => 0.70, 'under' => 0.95];
    }

    /**
     * Tính Over/Under odds cho Penalty dựa trên tổng bàn thắng 90 phút.
     *
     * Line 7.5 quả (loạt sút trung bình 8-10 quả).
     */
    private function calculatePenaltyOuOdds(int $totalGoals90): array
    {
        if ($totalGoals90 >= 4) {
            // Trận tấn công → loạt sút có thể nhiều quả (nhiều bàn = tự tin sút)
            return ['over' => 0.85, 'under' => 0.80];
        } elseif ($totalGoals90 <= 1) {
            // Trận phòng ngự → có thể ít quả sút thành công
            return ['over' => 0.80, 'under' => 0.85];
        }

        // Mặc định
        return ['over' => 0.85, 'under' => 0.85];
    }

    /**
     * Kiểm tra trận đấu có phải vòng bảng không.
     * Vòng bảng không có hiệp phụ/penalty.
     */
    private function isGroupStage(FootballMatch $match): bool
    {
        $stage = strtoupper($match->stage ?? '');

        return in_array($stage, ['GROUP_STAGE', 'GROUP', 'GROUP_A', 'GROUP_B', 'GROUP_C', 'GROUP_D', 'GROUP_E', 'GROUP_F', 'GROUP_G', 'GROUP_H', 'GROUP_I', 'GROUP_J', 'GROUP_K', 'GROUP_L'])
            || str_starts_with($stage, 'GROUP');
    }
}
