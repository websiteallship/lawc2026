<?php

namespace App\Domain\Modal\Checkers;

use App\Domain\Modal\Contracts\MatchReminderCheckerInterface;
use App\Models\User;
use App\Models\FootballMatch;
use App\Settings\AppSettings;
use App\Enums\MarketStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MatchReminderChecker implements MatchReminderCheckerInterface
{
    /** @var array<int, Collection> Cache per user_id để tránh N+1 trong cùng 1 request */
    private array $matchCache = [];

    public function shouldShow(User $user): bool
    {
        return $this->getMatches($user)->isNotEmpty();
    }

    public function getData(User $user): array
    {
        return [
            'matches' => $this->getMatches($user),
        ];
    }

    public function markAsSeen(User $user): void
    {
        $matches = $this->getMatches($user);
        if ($matches->isEmpty()) return;

        $now = now();
        $inserts = $matches->map(fn($match) => [
            'user_id'      => $user->id,
            'match_id'     => $match->id,
            'reminded_at'  => $now,
            'snoozed_until'=> null,
            'dismissed_at' => null,
            'created_at'   => $now,
            'updated_at'   => $now,
        ])->toArray();

        DB::table('match_reminders')->upsert(
            $inserts,
            ['user_id', 'match_id'],
            ['reminded_at', 'updated_at']
        );

        // Invalidate cache sau khi mark seen
        unset($this->matchCache[$user->id]);
    }

    /**
     * Cache kết quả trong cùng 1 request để shouldShow() + getData() + markAsSeen()
     * không gây 3 lần query riêng biệt.
     */
    private function getMatches(User $user): Collection
    {
        if (isset($this->matchCache[$user->id])) {
            return $this->matchCache[$user->id];
        }

        $settings = app(AppSettings::class);
        if (!$settings->match_reminder_enabled) {
            return $this->matchCache[$user->id] = collect();
        }

        $windowHours  = max(1, $settings->match_reminder_window_hours ?: 5);
        $cooldownHours = max(1, $settings->match_reminder_cooldown_hours ?: 4);

        return $this->matchCache[$user->id] = FootballMatch
            ::whereBetween('kickoff_at', [now(), now()->addHours($windowHours)])
            ->whereHas('markets', fn($q) => $q->where('status', MarketStatus::OPEN->value))
            ->whereNotExists(fn($q) => $q
                ->select(DB::raw(1))
                ->from('bets')
                ->whereColumn('bets.match_id', 'matches.id')
                ->where('bets.user_id', $user->id)
            )
            ->whereNotExists(fn($q) => $q
                ->select(DB::raw(1))
                ->from('match_reminders')
                ->whereColumn('match_reminders.match_id', 'matches.id')
                ->where('match_reminders.user_id', $user->id)
                ->where(fn($sub) => $sub
                    ->whereNotNull('dismissed_at')
                    ->orWhere('snoozed_until', '>', now())
                    ->orWhere('reminded_at', '>', now()->subHours($cooldownHours))
                )
            )
            ->orderBy('kickoff_at', 'asc')
            ->limit(5)
            ->get();
    }
}
