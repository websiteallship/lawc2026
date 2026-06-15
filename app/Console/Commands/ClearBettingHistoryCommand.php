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

            // Find all bets that are NOT PENDING
            $betsToDelete = Bet::where('status', '!=', 'PENDING')->pluck('id')->toArray();
            
            if (empty($betsToDelete)) {
                $this->info('No settled/historical bets found to delete.');
                DB::rollBack();
                return;
            }

            $this->info('Found ' . count($betsToDelete) . ' historical bets to delete.');

            // Delete wallet ledgers associated with these bets
            $deletedLedgers = WalletLedger::whereIn('bet_id', $betsToDelete)->forceDelete();
            $this->info("Permanently deleted {$deletedLedgers} associated wallet ledger records.");

            // Delete the bets
            $deletedBets = Bet::whereIn('id', $betsToDelete)->forceDelete();
            $this->info("Permanently deleted {$deletedBets} bet records.");

            DB::commit();

            $this->info('Betting history successfully cleared!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }
}
