<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\Wallet;
use App\Models\WalletLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixWalletsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallets:fix-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix wallet stats (net_profit, total_staked) and remove orphaned ledgers.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Bắt đầu fix dữ liệu Wallet...');

        try {
            DB::beginTransaction();

            // 1. Xoá các orphaned ledgers (những ledger có bet_id nhưng bet đó đã bị xoá)
            $validBetIds = Bet::pluck('id')->toArray();
            
            $orphanedLedgersQuery = WalletLedger::whereNotNull('bet_id');
            if (!empty($validBetIds)) {
                $orphanedLedgersQuery->whereNotIn('bet_id', $validBetIds);
            }
            
            $deletedOrphaned = $orphanedLedgersQuery->forceDelete();
            
            $this->info("Đã xoá {$deletedOrphaned} orphaned wallet ledgers.");

            // 2. Tính lại chỉ số cho từng Wallet dựa trên ledgers & bets còn hợp lệ
            $wallets = Wallet::all();
            
            foreach ($wallets as $wallet) {
                // Tính available & locked dựa trên sum của tất cả ledgers hợp lệ còn lại
                $available = $wallet->ledgers()->sum('amount_available') ?? 0;
                $locked = $wallet->ledgers()->sum('amount_locked') ?? 0;

                // Tính total_staked từ bảng Bet (ngoại trừ VOIDED)
                $totalStaked = Bet::where('wallet_id', $wallet->id)
                    ->where('status', '!=', 'VOIDED')
                    ->sum('stake') ?? 0;

                // Tính total_payout từ bảng Bet
                $totalPayout = Bet::where('wallet_id', $wallet->id)
                    ->whereIn('status', ['WON', 'LOST', 'HALF_WON', 'HALF_LOST', 'PUSH'])
                    ->sum('gross_payout') ?? 0;

                // Cộng thêm correction vào payout nếu có
                $correctionPayout = $wallet->ledgers()
                    ->where('type', 'SETTLEMENT_CORRECTION')
                    ->sum('amount_available') ?? 0;

                $totalPayout += $correctionPayout;

                $wallet->update([
                    'available_balance' => $available,
                    'locked_balance' => $locked,
                    'total_staked' => $totalStaked,
                    'total_payout' => $totalPayout,
                    'net_profit' => $totalPayout - $totalStaked,
                ]);
            }

            DB::commit();

            $this->info('Đã fix dữ liệu Wallet thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Lỗi: ' . $e->getMessage());
        }
    }
}
