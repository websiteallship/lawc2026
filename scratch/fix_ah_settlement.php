<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Bet;
use App\Domain\Wallet\Services\WalletService;
use App\Domain\Settlement\Calculators\AsianHandicapSettlementCalculator;
use App\Domain\Settlement\Data\MatchResult;
use Illuminate\Support\Facades\DB;

$calculator = app(AsianHandicapSettlementCalculator::class);
$walletService = app(WalletService::class);

// Tìm các vé cược Handicap Châu Á cho kèo Dưới (AWAY) đã quyết toán
$bets = Bet::with(['market.match', 'wallet'])
    ->where('market_type_snapshot', 'ASIAN_HANDICAP')
    ->where('selection_side_snapshot', 'AWAY')
    ->whereNotNull('settled_at')
    ->whereIn('status', ['WON', 'HALF_WON', 'LOST', 'HALF_LOST', 'PUSH'])
    ->get();

$count = 0;
$totalAdjustment = 0;

foreach ($bets as $bet) {
    $match = $bet->market->match;
    if ($match->home_score === null || $match->away_score === null) continue;

    // Tính toán lại kết quả đúng
    $matchResult = new MatchResult(
        $match->home_score, 
        $match->away_score, 
        $match->home_score_pen, 
        $match->away_score_pen
    );
    
    $correctResult = $calculator->calculate($bet, $matchResult);
    
    // Nếu kết quả hiện tại khác với kết quả đúng
    if ($bet->status->value !== $correctResult->status->value || $bet->gross_payout !== $correctResult->payout) {
        $oldPayout = $bet->gross_payout;
        $newPayout = $correctResult->payout;
        $adjustment = $newPayout - $oldPayout;

        echo "Vé {$bet->public_code}: Đang {$bet->status->value} (nhận {$oldPayout}) -> Đúng: {$correctResult->status->value} (nhận {$newPayout}). Lệch: {$adjustment} lá\n";

        DB::transaction(function () use ($bet, $correctResult, $adjustment, $walletService) {
            // Cập nhật ví (trừ phần trả dư hoặc cộng phần trả thiếu)
            $walletService->correctSettlement(
                $bet->wallet, 
                $bet, 
                $adjustment, 
                "Điều chỉnh lỗi tính sai kèo Handicap Châu Á cửa Dưới"
            );

            // Cập nhật lại status và gross_payout cho vé
            $bet->update([
                'status' => $correctResult->status->value,
                'gross_payout' => $correctResult->payout,
                'net_result' => $correctResult->payout - $bet->stake
            ]);
        });

        $count++;
        $totalAdjustment += $adjustment;
    }
}

echo "Đã sửa thành công {$count} vé. Tổng lá điều chỉnh: {$totalAdjustment}\n";
