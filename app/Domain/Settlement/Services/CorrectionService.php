<?php

namespace App\Domain\Settlement\Services;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Settlement\Calculators\AsianHandicapSettlementCalculator;
use App\Domain\Settlement\Calculators\ExactScoreSettlementCalculator;
use App\Domain\Settlement\Calculators\OverUnderSettlementCalculator;
use App\Domain\Settlement\Data\MatchResult;
use App\Domain\Wallet\Services\WalletService;
use App\Enums\BetStatus;
use App\Jobs\RebuildLeaderboardJob;
use App\Models\Bet;
use App\Models\Settlement;
use App\Models\SettlementCorrection;
use App\Models\SettlementItem;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class CorrectionService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly ExactScoreSettlementCalculator $exactScore,
        private readonly AsianHandicapSettlementCalculator $asianHandicap,
        private readonly OverUnderSettlementCalculator $overUnder,
        private readonly AuditLogService $auditLog,
    ) {}

    public function createCorrection(int $settlementId, array $newResults, string $reason, ?User $creator = null): SettlementCorrection
    {
        $settlement = Settlement::findOrFail($settlementId);

        return SettlementCorrection::create([
            'settlement_id' => $settlement->id,
            'old_results' => [
                'home_score' => $settlement->result_home_score,
                'away_score' => $settlement->result_away_score,
            ],
            'new_results' => $newResults,
            'reason' => $reason,
            'status' => 'PENDING',
            'created_by' => $creator?->id,
        ]);
    }

    public function executeCorrection(SettlementCorrection $correction, ?User $executor = null): void
    {
        if ($correction->status === 'EXECUTED') {
            throw new \Exception('Correction đã được execute.');
        }

        DB::transaction(function () use ($correction, $executor) {
            $settlement = Settlement::lockForUpdate()->findOrFail($correction->settlement_id);
            $market = $settlement->market;

            $matchResult = new MatchResult(
                homeScore: $correction->new_results['home_score'],
                awayScore: $correction->new_results['away_score']
            );

            // Cập nhật lại kết quả của settlement
            $settlement->update([
                'result_home_score' => $matchResult->homeScore,
                'result_away_score' => $matchResult->awayScore,
                'reason' => 'CORRECTED: ' . $correction->reason,
            ]);

            $settlementItems = SettlementItem::where('settlement_id', $settlement->id)
                ->with(['bet', 'user', 'bet.wallet'])
                ->lockForUpdate()
                ->get();

            $totalPayoutDiff = 0;

            foreach ($settlementItems as $item) {
                $bet = $item->bet;
                $oldGrossPayout = $item->gross_payout;

                // Tính toán lại
                $result = match ($market->market_type) {
                    'EXACT_SCORE' => $this->exactScore->calculate($bet, $matchResult),
                    'ASIAN_HANDICAP' => $this->asianHandicap->calculate($bet, $matchResult),
                    'OVER_UNDER' => $this->overUnder->calculate($bet, $matchResult),
                    default => throw new \Exception("Unsupported market type: {$market->market_type}"),
                };

                $newGrossPayout = $result->grossPayout;
                $adjustment = $newGrossPayout - $oldGrossPayout;
                $totalPayoutDiff += $adjustment;

                // Update Wallet (Lock wallet first)
                $wallet = Wallet::lockForUpdate()->findOrFail($bet->wallet_id);
                if ($adjustment !== 0) {
                    $this->walletService->correctSettlement(
                        $wallet,
                        $bet,
                        $adjustment,
                        "Điều chỉnh kết quả vé cược #{$bet->public_code}: {$correction->reason}"
                    );
                }

                // Cập nhật Bet
                $bet->update([
                    'status' => BetStatus::CORRECTED->value,
                    'gross_payout' => $newGrossPayout,
                    'net_result' => $result->netResult,
                ]);

                // Cập nhật SettlementItem
                $item->update([
                    'result_status' => $result->status->value,
                    'gross_payout' => $newGrossPayout,
                    'net_result' => $result->netResult,
                    'calculation_detail' => $result->calculationDetail,
                ]);
            }

            $settlement->update([
                'total_payout' => $settlement->total_payout + $totalPayoutDiff,
            ]);

            $correction->update([
                'status' => 'EXECUTED',
                'executed_by' => $executor?->id,
                'executed_at' => now(),
            ]);
            
            // Audit Log
            $this->auditLog->log(
                event: 'SETTLEMENT_CORRECTION_EXECUTED',
                properties: [
                    'correction_id' => $correction->id,
                    'old_results' => $correction->old_results,
                    'new_results' => $correction->new_results,
                    'total_payout_diff' => $totalPayoutDiff,
                ],
                subject: $settlement,
                causer: $executor,
                description: "Thực thi correction cho settlement #{$settlement->id}"
            );
        });

        // Rebuild Leaderboard (async)
        $seasonId = $correction->settlement->market->match->season_id;
        dispatch(new RebuildLeaderboardJob($seasonId));
    }
}
