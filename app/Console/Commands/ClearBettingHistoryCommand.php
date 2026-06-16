<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\WalletLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearBettingHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bets:clear-history {--force : Force delete without asking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all betting history of players EXCEPT for pending (unsettled) bets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('This command will completely ERASE the betting history (Bets and related Wallet Ledgers).');
        $this->warn('It will ONLY KEEP bets that are currently PENDING (chờ kết quả).');
        $this->warn('The current wallet balance of players will NOT be modified.');

        if (!$this->option('force')) {
            if (!$this->confirm('Are you absolutely sure you want to proceed?')) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        try {
            DB::beginTransaction();

            // Delete the bets
            $deletedBets = Bet::where('status', '!=', 'PENDING')->forceDelete();
            $this->info("Permanently deleted {$deletedBets} bet records.");

            // Find valid PENDING bet IDs
            $pendingBetIds = Bet::where('status', 'PENDING')->pluck('id')->toArray();

            // Delete ALL ledgers EXCEPT "Lá khởi đầu mùa giải" and PENDING bet_placed ledgers
            $deletedLedgers = WalletLedger::where(function($query) use ($pendingBetIds) {
                $query->whereNotIn('bet_id', $pendingBetIds)
                      ->orWhereNull('bet_id');
            })
            ->where('reason', '!=', 'Lá khởi đầu mùa giải')
            ->forceDelete();

            $this->info("Permanently deleted {$deletedLedgers} wallet ledger records.");

            // Recalculate wallet balances based on remaining ledgers
            $wallets = \App\Models\Wallet::all();
            foreach ($wallets as $wallet) {
                $available = $wallet->ledgers()->sum('amount_available') ?? 0;
                $locked = $wallet->ledgers()->sum('amount_locked') ?? 0;
                
                // Recalculate stats
                // Total staked is the sum of ABS(amount_available) for BET_PLACED ledgers
                $totalStaked = abs($wallet->ledgers()->where('type', 'BET_PLACED')->sum('amount_available') ?? 0);
                
                $wallet->update([
                    'available_balance' => $available,
                    'locked_balance' => $locked,
                    'total_staked' => $totalStaked,
                    'total_payout' => 0,
                    'net_profit' => -$totalStaked,
                ]);
            }
            $this->info("Recalculated all wallet balances successfully.");

            DB::commit();

            $this->info('Betting history successfully cleared!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }
}
