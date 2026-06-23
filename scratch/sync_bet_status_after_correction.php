<?php
/**
 * Script đồng bộ lại bet status/gross_payout dựa trên ledger SETTLEMENT_CORRECTION
 * đã tồn tại trong DB.
 *
 * Dành cho trường hợp: wallet đã bị điều chỉnh (ledger SETTLEMENT_CORRECTION tồn tại)
 * nhưng bet record vẫn còn status WON/LOST/PUSH/HALF_WON/HALF_LOST (chưa update thành CORRECTED).
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Bet;
use App\Models\WalletLedger;
use App\Domain\Settlement\Calculators\AsianHandicapSettlementCalculator;
use App\Domain\Settlement\Data\MatchResult;
use App\Enums\BetStatus;
use Illuminate\Support\Facades\DB;

echo "=== KIỂM TRA BET RECORDS CHƯA ĐỒNG BỘ SAU CORRECTION ===\n\n";

// Tìm các vé AWAY đã có ledger SETTLEMENT_CORRECTION
// nhưng bet->status vẫn là trạng thái cũ (chưa phải CORRECTED)
$correctedBetIds = WalletLedger::where('type', 'SETTLEMENT_CORRECTION')
    ->whereNotNull('bet_id')
    ->pluck('bet_id')
    ->unique();

$bets = Bet::with(['market.match', 'wallet'])
    ->whereIn('id', $correctedBetIds)
    ->where('market_type_snapshot', 'ASIAN_HANDICAP')
    ->where('selection_side_snapshot', 'AWAY')
    ->whereNotIn('status', [BetStatus::CORRECTED->value, BetStatus::VOIDED->value])
    ->get();

echo "Tìm thấy {$bets->count()} vé cần đồng bộ.\n\n";

if ($bets->isEmpty()) {
    echo "Không có vé nào cần xử lý.\n";
    exit(0);
}

$calculator = app(AsianHandicapSettlementCalculator::class);
$updated = 0;
$skipped = 0;

foreach ($bets as $bet) {
    $match = $bet->market?->match;
    if (!$match || $match->home_score === null || $match->away_score === null) {
        echo "  [SKIP] Vé {$bet->public_code}: không có kết quả trận.\n";
        $skipped++;
        continue;
    }

    $matchResult = new MatchResult(
        $match->home_score,
        $match->away_score,
        $match->home_score_pen,
        $match->away_score_pen
    );

    $correctResult = $calculator->calculate($bet, $matchResult);

    // Tổng adjustment đã thực hiện trong ledger
    $totalLedgerAdjustment = WalletLedger::where('bet_id', $bet->id)
        ->where('type', 'SETTLEMENT_CORRECTION')
        ->sum('amount_available');

    $expectedNewPayout = $bet->gross_payout + $totalLedgerAdjustment;

    echo "Vé {$bet->public_code} (#{$bet->id}):\n";
    echo "  Status hiện tại: {$bet->status->value}, gross_payout DB: {$bet->gross_payout}\n";
    echo "  Status đúng    : {$correctResult->status->value}, gross_payout đúng: {$correctResult->grossPayout}\n";
    echo "  Ledger đã điều chỉnh: {$totalLedgerAdjustment}\n";
    echo "  Payout sau điều chỉnh (tính từ ledger): {$expectedNewPayout}\n";

    // Chỉ update bet record, KHÔNG chạm ví (ví đã được điều chỉnh qua ledger trước đó)
    DB::transaction(function () use ($bet, $correctResult) {
        $bet->update([
            'status'       => BetStatus::CORRECTED->value,
            'gross_payout' => $correctResult->grossPayout,
            'net_result'   => $correctResult->grossPayout - $bet->stake,
        ]);
    });

    echo "  ✓ Đã update bet -> CORRECTED, gross_payout = {$correctResult->grossPayout}\n\n";
    $updated++;
}

echo "=== KẾT QUẢ ===\n";
echo "Đã cập nhật: {$updated} vé\n";
echo "Bỏ qua     : {$skipped} vé\n";
