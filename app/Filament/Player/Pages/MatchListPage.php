<?php

namespace App\Filament\Player\Pages;

use App\Models\FootballMatch;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class MatchListPage extends Page
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calendar-days';
    }

    protected static ?string $navigationLabel = 'Trận đấu';

    protected static ?string $title = 'Danh sách trận đấu';

    protected static ?string $slug = 'matches';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.player.pages.match-list';

    public bool $showFeaturePopup = false;
    public bool $showDismissButton = false;

    public function dismissFeaturePopupForever(): void
    {
        $userId = auth()->id();
        if ($userId) {
            $featureKey   = 'feature_bracket_2026_06_22';
            $dismissedKey = "{$featureKey}_dismissed_{$userId}";
            cache()->put($dismissedKey, true, now()->addYears(1));
            // Xóa luôn các key tracking để không bao giờ hiện lại
            cache()->forget("{$featureKey}_first_seen_{$userId}");
            cache()->forget("{$featureKey}_count_{$userId}");
        }
        $this->showFeaturePopup = false;
    }

    public function mount(): void
    {
        // Tắt popup thông báo tính năng mới
        $this->showFeaturePopup = false;
        $this->showDismissButton = false;
    }

    // View modes: 'list' | 'groups' | 'bracket'
    public string $viewMode = 'list';

    public string $activeTab = 'open'; // all, live, open, upcoming, finished

    public string $activeStage = 'all';

    public function getMatchesProperty(): Collection
    {
        $query = FootballMatch::query()
            ->withCount(['markets' => function ($q) {
                $q->where('status', 'OPEN');
            }])
            ->withMin(['markets as next_close_at' => function ($q) {
                $q->where('status', 'OPEN');
            }], 'close_at')
            ->orderBy('kickoff_at', 'asc');

        if ($this->activeStage !== 'all') {
            $query->where('stage', $this->activeStage);
        }

        switch ($this->activeTab) {
            case 'open':
                $query->whereHas('markets', function ($q) {
                    $q->where('status', 'OPEN');
                })->where('status', '!=', 'FINISHED');
                break;
            case 'live':
                $query->where('status', 'LIVE');
                break;
            case 'upcoming':
                $query->where('kickoff_at', '>', now())->where('status', '!=', 'FINISHED');
                break;
            case 'finished':
                $query->where('status', 'FINISHED');
                break;
        }

        return $query->get();
    }

    /**
     * Group Stage view: all matches grouped by group name, sorted by kickoff
     */
    public function getGroupStageDataProperty(): \Illuminate\Support\Collection
    {
        $matches = FootballMatch::query()
            ->where('stage', 'GROUP_STAGE')
            ->whereNotNull('group')
            ->withCount(['markets' => fn ($q) => $q->where('status', 'OPEN')])
            ->withMin(['markets as next_close_at' => fn ($q) => $q->where('status', 'OPEN')], 'close_at')
            ->orderBy('group')
            ->orderBy('kickoff_at')
            ->get();

        return $matches->groupBy('group')->sortKeys();
    }

    /**
     * Knockout Bracket view: matches by stage in tournament order.
     *
     * Sorting strategy:
     *   1. bracket_position (populated by API sync from "Round of 16 - 3" etc.)
     *      This is the AUTHORITATIVE visual bracket position from FIFA API.
     *   2. Fallback to kickoff_at when bracket_position is NULL (pre-sync).
     */
    public function getKnockoutDataProperty(): \Illuminate\Support\Collection
    {
        $matches = FootballMatch::query()
            ->whereIn('stage', [
                'ROUND_OF_32', 'ROUND_OF_16', 'QUARTER_FINAL',
                'SEMI_FINAL', 'THIRD_PLACE_PLAYOFF', 'FINAL',
            ])
            ->withCount(['markets' => fn ($q) => $q->where('status', 'OPEN')])
            ->get();

        // Correct visual bracket order for WC 2026.
        // Each pair of R32 matches must be adjacent so CSS nth-child lines connect them
        // to the correct R16 match. Order verified from actual match results on VPS.
        // R32: pairs (1,2), (3,4), (5,6), (7,8), (9,10), (11,12), (13,14), (15,16)
        // feed R16 positions 1,2,3,4,5,6,7,8 respectively.
        $displayOrder = [
            // R32 — pairs feed R16 in this order
            'M073' => 1,  'M076' => 2,  // → M089
            'M075' => 3,  'M078' => 4,  // → M090
            'M074' => 5,  'M077' => 6,  // → M091
            'M079' => 7,  'M080' => 8,  // → M092
            'M083' => 9,  'M084' => 10, // → M093
            'M081' => 11, 'M082' => 12, // → M094
            'M086' => 13, 'M088' => 14, // → M095
            'M085' => 15, 'M087' => 16, // → M096
            // R16
            'M089' => 1,  'M090' => 2,  // → M097
            'M091' => 3,  'M092' => 4,  // → M098 (verify QF pairing)
            'M093' => 5,  'M094' => 6,  // → M099
            'M095' => 7,  'M096' => 8,  // → M100
            // QF
            'M097' => 1,  'M098' => 2,
            'M099' => 3,  'M100' => 4,
            // SF
            'M101' => 1,  'M102' => 2,
            // 3rd place & Final
            'M103' => 1,
            'M104' => 1,
        ];

        $grouped = $matches->groupBy('stage');

        foreach ($grouped as $stage => $stageMatches) {
            $grouped[$stage] = $stageMatches->sortBy(function ($match) use ($displayOrder) {
                return $displayOrder[$match->match_code] ?? 999;
            })->values();
        }

        return $grouped;
    }
}
