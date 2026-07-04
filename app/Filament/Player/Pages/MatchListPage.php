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
     * Knockout Bracket view: matches sorted for correct visual bracket display.
     *
     * Order is inferred dynamically from DB data — no hardcoding:
     *   - For each parent-round match (R16, QF, SF, Final), we find which
     *     previous-round matches fed into it by:
     *     (a) Matching team names to previous-round participants, OR
     *     (b) Parsing "Match N winners" placeholder text → match_code lookup
     *   - Previous-round matches are then placed adjacent (pairs) in the order
     *     their parent-round match appears.
     *   - Remaining unlinked matches (future rounds) fall to kickoff_at order.
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

        $byId       = $matches->keyBy('id');
        $byCode     = $matches->keyBy('match_code');

        // Stage progression: child stage → parent stage
        $stageChain = [
            'ROUND_OF_32'   => 'ROUND_OF_16',
            'ROUND_OF_16'   => 'QUARTER_FINAL',
            'QUARTER_FINAL' => 'SEMI_FINAL',
            'SEMI_FINAL'    => 'FINAL',
        ];

        $grouped = $matches->groupBy('stage');

        // Build stage-aware team→match lookup: teamName → [stage → match]
        // This prevents later stages from overwriting earlier entries
        // (e.g., "Canada" exists in both V32 and V16)
        $teamToMatchByStage = []; // teamName => [stage => match]
        foreach ($matches as $m) {
            if ($m->home_team) {
                $teamToMatchByStage[$m->home_team][$m->stage] = $m;
            }
            if ($m->away_team) {
                $teamToMatchByStage[$m->away_team][$m->stage] = $m;
            }
        }

        // Build match_code number lookup: "73" => FootballMatch(M073)
        $codeNumToMatch = [];
        foreach ($matches as $m) {
            if (preg_match('/M0*(\d+)/', $m->match_code, $x)) {
                $codeNumToMatch[$x[1]] = $m;
            }
        }

        // For each stage, compute visual sort positions by walking parent→child.
        // We assign positions to the CHILD stage based on the parent stage's order.
        $sortPositions = []; // match id => sort position

        // Sort each parent stage by kickoff_at first, then infer child order
        foreach ($stageChain as $childStage => $parentStage) {
            if (!$grouped->has($parentStage)) continue;

            $parentMatches = $grouped[$parentStage]->sortBy('kickoff_at')->values();
            $pos = 1;
            $assigned = [];

            foreach ($parentMatches as $parent) {
                // Find the two child matches that fed into this parent match
                $feeders = $this->resolveFeeders($parent, $teamToMatchByStage, $codeNumToMatch, $childStage);

                foreach ($feeders as $feeder) {
                    if ($feeder && !isset($assigned[$feeder->id])) {
                        $sortPositions[$feeder->id] = $pos++;
                        $assigned[$feeder->id] = true;
                    }
                }
            }

            // Remaining child matches with no parent link → append in kickoff_at order
            if ($grouped->has($childStage)) {
                $remaining = $grouped[$childStage]
                    ->filter(fn ($m) => !isset($assigned[$m->id]))
                    ->sortBy('kickoff_at');
                foreach ($remaining as $m) {
                    $sortPositions[$m->id] = $pos++;
                }
            }
        }

        // Apply computed sort positions; fallback to kickoff_at for stages with no parent
        foreach ($grouped as $stage => $stageMatches) {
            $grouped[$stage] = $stageMatches->sortBy(function ($m) use ($sortPositions) {
                return $sortPositions[$m->id] ?? $m->kickoff_at->timestamp;
            })->values();
        }

        return $grouped;
    }

    /**
     * Given a parent-round match, find the two child-round matches that fed into it.
     *
     * Strategy:
     *   1. If home_team is a real name (not placeholder) → look it up in teamToMatchByStage
     *      to find which child-stage match contained that team.
     *   2. If home_team is "Match N winners" → parse N, look up by match_code.
     */
    private function resolveFeeders(
        FootballMatch $parent,
        array $teamToMatchByStage,
        array $codeNumToMatch,
        string $childStage
    ): array {
        $feeders = [];

        foreach ([$parent->home_team, $parent->away_team] as $teamField) {
            if (!$teamField) continue;

            // Case A: Placeholder "Match 73 winners" or "Match 73 winner"
            if (preg_match('/Match\s+(\d+)\s+winner/i', $teamField, $m)) {
                $num     = $m[1];
                $feeder  = $codeNumToMatch[$num] ?? null;
                $feeders[] = ($feeder && $feeder->stage === $childStage) ? $feeder : null;
                continue;
            }

            // Case B: Real team name — find the child-stage match that had this team
            $stageMatches = $teamToMatchByStage[$teamField] ?? [];
            $candidate = $stageMatches[$childStage] ?? null;
            if ($candidate) {
                $feeders[] = $candidate;
            } else {
                $feeders[] = null;
            }
        }

        return $feeders;
    }
}
