<?php
/**
 * Script tự động sinh kèo Hiệp phụ (Extra Time) và Luân lưu (Penalty)
 * cho 2 trận tranh hạng 3 (M103) và chung kết (M104) 
 * dựa vào công thức tính toán tự động của hệ thống (ExtraTimeMarketGenerator).
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FootballMatch;
use App\Models\Market;
use App\Domain\Market\Services\ExtraTimeMarketGenerator;
use Illuminate\Support\Facades\DB;

$matchCodes = ['M103', 'M104'];
$matches = FootballMatch::whereIn('match_code', $matchCodes)->get();

if ($matches->isEmpty()) {
    echo "ERROR: Không tìm thấy các trận đấu " . implode(', ', $matchCodes) . "\n";
    exit(1);
}

$generator = app(ExtraTimeMarketGenerator::class);

foreach ($matches as $match) {
    echo "Đang xử lý trận {$match->match_code}...\n";
    
    // Xóa kèo cũ nếu đã tồn tại để tạo lại mới nhất
    $oldMarkets = Market::where('match_id', $match->id)
        ->whereIn('period_type', ['EXTRA_TIME', 'PENALTY'])
        ->get();

    if ($oldMarkets->count() > 0) {
        echo "  - Đang xóa {$oldMarkets->count()} kèo ET/Penalty cũ...\n";
        foreach ($oldMarkets as $old) {
            $old->outcomes()->delete();
            $old->delete();
        }
    }
    
    // Sinh kèo mới bằng Generator của hệ thống
    $generator->generateExtraTimeMarkets($match);
    $generator->generatePenaltyMarkets($match);
    
    // Mặc định Generator tạo kèo DRAFT, ta sẽ update thành OPEN luôn
    $updated = Market::where('match_id', $match->id)
        ->whereIn('period_type', ['EXTRA_TIME', 'PENALTY'])
        ->update(['status' => 'OPEN']);
        
    echo "  ✓ Đã sinh kèo Hiệp phụ & Luân lưu tự động thành công (tổng cộng {$updated} kèo đã được MỞ).\n";
}

echo "\nHoàn tất!\n";
