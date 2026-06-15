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

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.player.pages.match-list';

    public string $activeTab = 'open'; // all, live, open, upcoming, finished

    public string $activeStage = 'all';

    public function getMatchesProperty(): Collection
    {
        $query = FootballMatch::query()
            ->withCount(['markets' => function ($q) {
                $q->where('status', 'OPEN');
            }])
            // Fetch the earliest close_at from open markets
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
}
