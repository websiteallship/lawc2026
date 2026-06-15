<?php

namespace App\Domain\Market\Services;

use App\Domain\Market\DTOs\OddsResponseDto;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MarketOutcome;
use Illuminate\Support\Facades\DB;

class MarketSyncService
{
    /**
     * Chỉ hỗ trợ:
     */
    private const ALLOWED_BET_IDS = [4, 19, 5, 6, 10, 26, 104, 65, 66, 68];

    public function syncOddsForMatch(FootballMatch $match, OddsResponseDto $dto): void
    {
        DB::transaction(function () use ($match, $dto) {
            foreach ($dto->markets as $marketDto) {
                if (! in_array($marketDto->id, self::ALLOWED_BET_IDS)) {
                    continue; // Lọc dữ liệu (Whitelist)
                }

                $marketType = $this->mapMarketType($marketDto->id);
                $periodType = $this->mapPeriodType($marketDto->id);

                if (! $marketType || ! $periodType) {
                    continue;
                }

                $status = 'OPEN';
                $openAt = now();
                $closeAt = $match->kickoff_at;

                if ($periodType === 'SECOND_HALF') {
                    $closeAt = $match->kickoff_at->clone()->addMinutes(90);
                } elseif ($periodType === 'EXTRA_TIME') {
                    $status = 'DRAFT'; // Sẽ mở khi hòa 90 phút
                    $openAt = $match->kickoff_at->clone()->addMinutes(105); // Tạm tính
                    $closeAt = $match->kickoff_at->clone()->addMinutes(120);
                } elseif ($periodType === 'PENALTY') {
                    $status = 'DRAFT'; // Sẽ mở khi hòa 120 phút
                    $openAt = $match->kickoff_at->clone()->addMinutes(135); // Tạm tính
                    $closeAt = $match->kickoff_at->clone()->addMinutes(145);
                }

                // Tìm hoặc tạo kèo mới
                $market = Market::firstOrCreate(
                    [
                        'match_id' => $match->id,
                        'market_type' => $marketType,
                        'period_type' => $periodType,
                    ],
                    [
                        'name' => $marketDto->name,
                        'status' => $status,
                        'open_at' => $openAt,
                        'close_at' => $closeAt,
                        'display_order' => 1,
                    ]
                );

                // Nếu kèo đã bị khóa hoặc xử lý, không cập nhật tỷ lệ nữa
                if (in_array($market->status, ['LOCKED', 'SETTLING', 'SETTLED', 'VOIDED', 'CANCELLED'])) {
                    continue;
                }

                foreach ($marketDto->outcomes as $outcomeDto) {
                    $parsed = $this->parseOutcomeValue($marketType, $outcomeDto->value);
                    if (! $parsed) {
                        continue;
                    }

                    $profitRate = $this->calculateProfitRate($outcomeDto->odd);

                    // UpdateOrCreate theo Snapshot rule (đè profit_rate)
                    MarketOutcome::updateOrCreate(
                        [
                            'market_id' => $market->id,
                            'selection_side' => $parsed['selection_side'] ?? null,
                            'line_value' => $parsed['line_value'] ?? null,
                            'score_home' => $parsed['score_home'] ?? null,
                            'score_away' => $parsed['score_away'] ?? null,
                        ],
                        [
                            'label' => $outcomeDto->value,
                            'profit_rate' => $profitRate,
                            'decimal_odds' => $outcomeDto->odd,
                            'status' => 'ACTIVE',
                            'display_order' => 1,
                        ]
                    );
                }

                // Cập nhật timestamp của market để hiển thị cho user biết là đã đồng bộ
                $market->touch();
            }
        });
    }

    private function mapMarketType(int $betId): ?string
    {
        return match ($betId) {
            4, 19, 104, 65 => 'ASIAN_HANDICAP',
            5, 6, 26, 66 => 'OVER_UNDER',
            10 => 'EXACT_SCORE',
            68 => 'PENALTY_WINNER',
            default => null,
        };
    }

    private function mapPeriodType(int $betId): ?string
    {
        return match ($betId) {
            4, 5, 10 => 'FULL_TIME',
            19, 6 => 'FIRST_HALF',
            26, 104 => 'SECOND_HALF',
            65, 66 => 'EXTRA_TIME',
            68 => 'PENALTY',
            default => null,
        };
    }

    private function calculateProfitRate(float $odd): float
    {
        if ($odd > 1.0) {
            return round($odd - 1, 3);
        }

        return round($odd, 3);
    }

    private function parseOutcomeValue(string $marketType, string $value): ?array
    {
        if ($marketType === 'ASIAN_HANDICAP') {
            if (preg_match('/^(Home|Away)\s+([+-]?\d+(\.\d+)?)$/i', $value, $matches)) {
                $selectionSide = strtoupper($matches[1]);
                $rawLine = (float) $matches[2];
                
                // API thường trả về market line cho cả 2 (VD: Home -1.25, Away -1.25)
                // Cần đảo dấu cho Away (thành +1.25)
                if ($selectionSide === 'AWAY') {
                    $rawLine = -$rawLine;
                }

                $normalizedLine = $this->normalizeLine($rawLine, 'ASIAN_HANDICAP');

                return $normalizedLine !== null ? [
                    'selection_side' => $selectionSide,
                    'line_value' => $normalizedLine,
                ] : null;
            }
        } elseif ($marketType === 'OVER_UNDER') {
            if (preg_match('/^(Over|Under)\s+([+-]?\d+(\.\d+)?)$/i', $value, $matches)) {
                $normalizedLine = $this->normalizeLine((float) $matches[2], 'OVER_UNDER');

                return $normalizedLine !== null ? [
                    'selection_side' => strtoupper($matches[1]),
                    'line_value' => $normalizedLine,
                ] : null;
            }
        } elseif ($marketType === 'EXACT_SCORE') {
            if (preg_match('/^(\d+)[:\-](\d+)$/', trim($value), $matches)) {
                return [
                    'score_home' => (int) $matches[1],
                    'score_away' => (int) $matches[2],
                ];
            }
        } elseif ($marketType === 'PENALTY_WINNER') {
            if (preg_match('/^(Home|Away)$/i', trim($value), $matches)) {
                return [
                    'selection_side' => strtoupper($matches[1]),
                ];
            }
        }

        return null;
    }

    private function normalizeLine(float $rawLine, string $marketType): ?float
    {
        $normalized = round($rawLine * 4) / 4;

        if ($marketType === 'ASIAN_HANDICAP') {
            $absLine = abs($normalized);
            if ($absLine > 3.0) {
                return null; // Chỉ hỗ trợ tối đa +-3
            }
        } elseif ($marketType === 'OVER_UNDER') {
            if ($normalized < 0.5) {
                return null; // Tối thiểu 0.5
            }
        }

        return $normalized;
    }
}
