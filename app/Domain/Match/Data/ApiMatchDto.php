<?php

namespace App\Domain\Match\Data;

use Carbon\Carbon;

class ApiMatchDto
{
    public function __construct(
        public readonly string $apiId,
        public readonly string $homeTeamName,
        public readonly string $awayTeamName,
        public readonly Carbon $kickoffAt,
        public readonly string $status,
        public readonly ?ApiScoreDto $fullTime,
        public readonly ?ApiScoreDto $halfTime,
        public readonly ?ApiScoreDto $extraTime,
        public readonly ?ApiScoreDto $penalties,
        public readonly array $goals = [],
        public readonly array $bookings = [],
        public readonly array $substitutions = [],
        public readonly bool $hasEventsData = true,
        public readonly string $detailedStatus = '',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            apiId: (string) $data['id'],
            homeTeamName: $data['homeTeam']['name'] ?? 'TBD',
            awayTeamName: $data['awayTeam']['name'] ?? 'TBD',
            kickoffAt: Carbon::parse($data['utcDate'])->setTimezone('Asia/Ho_Chi_Minh'),
            status: $data['status'],
            fullTime: ApiScoreDto::fromArray($data['score']['fullTime'] ?? null),
            halfTime: ApiScoreDto::fromArray($data['score']['halfTime'] ?? null),
            extraTime: ApiScoreDto::fromArray($data['score']['extraTime'] ?? null),
            penalties: ApiScoreDto::fromArray($data['score']['penalties'] ?? null),
            goals: $data['goals'] ?? [],
            bookings: $data['bookings'] ?? [],
            substitutions: $data['substitutions'] ?? [],
            hasEventsData: true,
            detailedStatus: $data['status'] ?? '',
        );
    }

    /**
     * Lấy tổng tỉ số hiện tại (hoặc cuối cùng) của trận đấu.
     * Thường football-data API dùng trường fullTime cho cả thời điểm đang Live.
     */
    public function getCurrentHomeScore(): ?int
    {
        return $this->fullTime?->home;
    }

    public function getCurrentAwayScore(): ?int
    {
        return $this->fullTime?->away;
    }

    public static function fromRapidApiArray(array $data): self
    {
        $fixture = $data['fixture'] ?? [];
        $teams = $data['teams'] ?? [];
        $score = $data['score'] ?? [];
        $events = $data['events'] ?? [];

        // Parse events
        $goals = [];
        $bookings = [];
        $substitutions = [];

        foreach ($events as $event) {
            $type = strtolower($event['type'] ?? '');
            $minute = $event['time']['elapsed'] ?? 0;
            $injuryTime = $event['time']['extra'] ?? null;
            $teamName = $event['team']['name'] ?? '';
            $playerName = $event['player']['name'] ?? null;
            $assistName = $event['assist']['name'] ?? null;
            $detail = $event['detail'] ?? '';

            if ($type === 'goal') {
                $goals[] = [
                    'minute' => $minute,
                    'injuryTime' => $injuryTime,
                    'team' => ['name' => $teamName],
                    'scorer' => ['name' => $playerName],
                    'assist' => ['name' => $assistName],
                    'type' => $detail,
                ];
            } elseif ($type === 'card') {
                $bookings[] = [
                    'minute' => $minute,
                    'team' => ['name' => $teamName],
                    'player' => ['name' => $playerName],
                    'card' => strtoupper($detail),
                ];
            } elseif ($type === 'subst') {
                $substitutions[] = [
                    'minute' => $minute,
                    'team' => ['name' => $teamName],
                    'playerIn' => ['name' => $playerName],
                    'playerOut' => ['name' => $assistName], // RapidAPI assist field holds the replaced player
                ];
            }
        }

        // Fix status: map RapidAPI status to Football-Data expected status
        $statusShort = $fixture['status']['short'] ?? '';
        $mappedStatus = match ($statusShort) {
            '1H', '2H', 'HT', 'ET', 'BT', 'P', 'INT' => 'IN_PLAY',
            'FT', 'AET', 'PEN' => 'FINISHED',
            'CANC', 'POST', 'SUSP', 'ABD' => 'CANCELLED',
            'TBD', 'NS' => 'SCHEDULED',
            'AWD', 'WO' => 'AWARDED',
            default => 'SCHEDULED',
        };

        // For LIVE score, rapidAPI puts current score in $data['goals'] instead of fullTime. We'll map $data['goals'] to fullTime so it matches Football-Data logic.
        $goalsData = $data['goals'] ?? [];
        $fullTimeScore = [
            'home' => $goalsData['home'] ?? $score['fulltime']['home'] ?? null,
            'away' => $goalsData['away'] ?? $score['fulltime']['away'] ?? null,
        ];

        return new self(
            apiId: (string) ($fixture['id'] ?? ''),
            homeTeamName: $teams['home']['name'] ?? 'TBD',
            awayTeamName: $teams['away']['name'] ?? 'TBD',
            kickoffAt: Carbon::parse($fixture['date'] ?? now())->setTimezone('Asia/Ho_Chi_Minh'),
            status: $mappedStatus,
            fullTime: ApiScoreDto::fromArray($fullTimeScore),
            halfTime: ApiScoreDto::fromArray($score['halftime'] ?? null),
            extraTime: ApiScoreDto::fromArray($score['extratime'] ?? null),
            penalties: ApiScoreDto::fromArray($score['penalty'] ?? null),
            goals: $goals,
            bookings: $bookings,
            substitutions: $substitutions,
            hasEventsData: array_key_exists('events', $data),
            detailedStatus: $statusShort,
        );
    }
}
