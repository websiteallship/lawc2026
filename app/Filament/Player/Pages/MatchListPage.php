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

    public function mount(): void
    {
        $userId = auth()->id();
        if ($userId) {
            $featureKey = 'feature_bracket_2026_06_22';
            $firstSeenKey = "{$featureKey}_first_seen_{$userId}";
            $countKey = "{$featureKey}_count_{$userId}";

            $firstSeen = cache()->get($firstSeenKey);

            if (! $firstSeen) {
                $firstSeen = now();
                cache()->put($firstSeenKey, $firstSeen, now()->addDays(2));
            }

            if (now()->diffInHours($firstSeen) < 24) {
                $count = cache()->get($countKey, 0);
                if ($count < 3) {
                    cache()->put($countKey, $count + 1, now()->addDays(1));
                    $this->showFeaturePopup = true;
                }
            }
        }
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
     * Knockout Bracket view: matches by stage in tournament order
     */
    public function getKnockoutDataProperty(): \Illuminate\Support\Collection
    {
        $stageOrder = [
            'ROUND_OF_32'         => 1,
            'ROUND_OF_16'         => 2,
            'QUARTER_FINAL'       => 3,
            'SEMI_FINAL'          => 4,
            'THIRD_PLACE_PLAYOFF' => 5,
            'FINAL'               => 6,
        ];

        $matches = FootballMatch::query()
            ->whereIn('stage', array_keys($stageOrder))
            ->withCount(['markets' => fn ($q) => $q->where('status', 'OPEN')])
            ->orderByRaw("CASE stage
                WHEN 'ROUND_OF_32' THEN 1
                WHEN 'ROUND_OF_16' THEN 2
                WHEN 'QUARTER_FINAL' THEN 3
                WHEN 'SEMI_FINAL' THEN 4
                WHEN 'THIRD_PLACE_PLAYOFF' THEN 5
                WHEN 'FINAL' THEN 6
                ELSE 99 END")
            ->orderBy('bracket_position')
            ->orderBy('kickoff_at')
            ->get();

        return $matches->groupBy('stage');
    }
}
