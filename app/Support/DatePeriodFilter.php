<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Shared date-range period resolution for filters.
 *
 * Supports:
 *  today, yesterday, this_week, last_week, this_month, 30, 90, season, custom
 *
 * Usage in ApexChartWidget or Livewire:
 *   use App\Support\DatePeriodFilter;
 *
 *   protected function getFilters(): ?array { return DatePeriodFilter::options(); }
 *   [$start, $end, $days] = DatePeriodFilter::resolve($this->filter);
 */
class DatePeriodFilter
{
    /**
     * Returns the canonical filter options for use in getFilters().
     */
    public static function options(): array
    {
        return [
            'today'     => 'Hôm nay',
            'yesterday' => 'Hôm qua',
            'this_week' => 'Tuần này',
            'last_week' => 'Tuần trước',
            'this_month'=> 'Tháng này',
            '30'        => '30 ngày qua',
            '90'        => '90 ngày qua',
            'season'    => 'Toàn mùa',
        ];
    }

    /**
     * Resolve a filter key into [Carbon $startDate, Carbon $endDate, int $days].
     *
     * @param  string|null  $filter
     * @param  Carbon|null  $seasonStart  Fallback start when filter = 'season'
     * @return array{0: Carbon, 1: Carbon, 2: int}
     */
    public static function resolve(?string $filter, ?Carbon $seasonStart = null): array
    {
        $now = now()->setTimezone('Asia/Ho_Chi_Minh');
        $end = $now->copy()->endOfDay();

        switch ($filter) {
            case 'today':
                $start = $now->copy()->startOfDay();
                break;

            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end   = $now->copy()->subDay()->endOfDay();
                break;

            case 'this_week':
                $start = $now->copy()->startOfWeek(Carbon::MONDAY);
                break;

            case 'last_week':
                $start = $now->copy()->subWeek()->startOfWeek(Carbon::MONDAY);
                $end   = $now->copy()->subWeek()->endOfWeek(Carbon::SUNDAY);
                break;

            case 'this_month':
                $start = $now->copy()->startOfMonth();
                break;

            case '30':
                $start = $now->copy()->subDays(29)->startOfDay();
                break;

            case '90':
                $start = $now->copy()->subDays(89)->startOfDay();
                break;

            case 'season':
            default:
                $start = $seasonStart
                    ? $seasonStart->copy()->startOfDay()
                    : $now->copy()->startOfYear()->startOfDay();
                break;
        }

        $days = max(1, (int) $start->copy()->diffInDays($end) + 1);

        return [$start, $end, $days];
    }

    /**
     * Generate an ordered list of date-string labels between $start and $end.
     *
     * @return Carbon[]
     */
    public static function dateRange(Carbon $start, Carbon $end): array
    {
        return collect(
            CarbonPeriod::create($start->copy()->startOfDay(), '1 day', $end->copy()->startOfDay())
        )->all();
    }

    /**
     * Determine whether to use weekly grouping (> 60 days).
     */
    public static function useWeekly(int $days): bool
    {
        return $days > 60;
    }
}
