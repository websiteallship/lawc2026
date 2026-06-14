<?php

namespace App\Services\Export;

use App\Domain\Leaderboard\Services\LeaderboardService;
use App\Models\Bet;
use App\Models\Season;
use App\Models\WalletLedger;
use Illuminate\Support\Collection;

/**
 * CsvExportService — xuất dữ liệu ra CSV.
 * Dùng generator để xử lý dataset lớn không tốn RAM.
 */
class CsvExportService
{
    public function __construct(
        private readonly LeaderboardService $leaderboardService,
    ) {}

    // ===================== BETS EXPORT =====================

    /**
     * @return iterable Từng dòng CSV (array), bao gồm header
     */
    public function betsRows(Season $season): iterable
    {
        yield ['public_code', 'user_id', 'user_name', 'match_code', 'market_type', 'period_type',
            'label', 'display_odds', 'stake', 'status', 'gross_payout', 'net_result',
            'profit_rate', 'placed_at', 'settled_at'];

        Bet::with(['user', 'market.match'])
            ->where('season_id', $season->id)
            ->orderBy('placed_at')
            ->chunk(500, function (Collection $bets) {
                foreach ($bets as $bet) {
                    yield [
                        $bet->public_code,
                        $bet->user_id,
                        $bet->user?->name,
                        $bet->market?->match?->match_code,
                        $bet->market_type_snapshot,
                        $bet->period_type_snapshot,
                        $bet->label_snapshot,
                        $bet->display_odds_snapshot,
                        $bet->stake,
                        is_object($bet->status) ? $bet->status->value : $bet->status,
                        $bet->gross_payout,
                        $bet->net_result,
                        $bet->profit_rate_snapshot,
                        $bet->placed_at?->toDateTimeString(),
                        $bet->settled_at?->toDateTimeString(),
                    ];
                }
            });
    }

    // ===================== LEDGER EXPORT =====================

    /**
     * @return iterable Từng dòng CSV
     */
    public function ledgerRows(Season $season): iterable
    {
        yield ['id', 'user_id', 'user_name', 'type', 'amount_available', 'amount_locked',
            'balance_available_after', 'balance_locked_after', 'reason', 'created_at'];

        WalletLedger::with('user')
            ->where('season_id', $season->id)
            ->orderBy('id')
            ->chunk(500, function (Collection $ledgers) {
                foreach ($ledgers as $ledger) {
                    yield [
                        $ledger->id,
                        $ledger->user_id,
                        $ledger->user?->name,
                        is_object($ledger->type) ? $ledger->type->value : $ledger->type,
                        $ledger->amount_available,
                        $ledger->amount_locked,
                        $ledger->balance_available_after,
                        $ledger->balance_locked_after,
                        $ledger->reason,
                        $ledger->created_at?->toDateTimeString(),
                    ];
                }
            });
    }

    // ===================== LEADERBOARD EXPORT =====================

    /**
     * @return iterable Từng dòng CSV
     */
    public function leaderboardRows(Season $season): iterable
    {
        yield ['rank', 'user_id', 'user_name', 'net_profit', 'roi', 'total_staked', 'total_payout',
            'total_bets', 'won_bets', 'lost_bets', 'push_bets', 'exact_score_wins', 'win_rate'];

        $entries = $this->leaderboardService->computeSeason($season);

        foreach ($entries as $entry) {
            yield [
                $entry->rank,
                $entry->userId,
                $entry->userName,
                $entry->netProfit,
                $entry->roi,
                $entry->totalStaked,
                $entry->totalPayout,
                $entry->totalBets,
                $entry->wonBets,
                $entry->lostBets,
                $entry->pushBets,
                $entry->exactScoreWins,
                $entry->winRate,
            ];
        }
    }

    /**
     * Ghi rows ra file CSV.
     *
     * @param  iterable  $rows  (bao gồm header là phần tử đầu tiên)
     * @param  string  $path  Đường dẫn file output
     */
    public function writeCsv(iterable $rows, string $path): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fp = fopen($path, 'w');
        // BOM UTF-8 cho Excel
        fwrite($fp, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);
    }
}
