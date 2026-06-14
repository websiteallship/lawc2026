<?php

namespace App\Domain\Market\Services;

use App\Domain\Market\DTOs\MarketDataDto;
use App\Domain\Market\DTOs\OddsResponseDto;
use App\Domain\Market\DTOs\OutcomeDataDto;
use App\Settings\ApiSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OddsIntegrationService
{
    public function __construct(
        protected ApiSettings $apiSettings
    ) {}

    /**
     * Fetch all WC odds for a specific date
     *
     * @param  string  $date  Format YYYY-MM-DD
     * @return OddsResponseDto[]
     */
    public function fetchDailyOdds(string $date): array
    {
        $key = $this->apiSettings->rapidapi_key;
        $host = $this->apiSettings->rapidapi_host;
        $bookmakerId = $this->apiSettings->bookmaker_id;

        if (empty($key) || empty($host)) {
            Log::warning("RapidAPI settings are missing. Cannot fetch odds for date {$date}.");

            return [];
        }

        try {
            // Bước 1: Gọi /fixtures để lấy mapping team name
            $fixturesResponse = Http::withHeaders([
                'X-RapidAPI-Key' => $key,
                'X-RapidAPI-Host' => $host,
            ])->timeout(10)->retry(3, 1000)->get("https://{$host}/fixtures", [
                'league' => 1, // World Cup
                'season' => 2026,
                'date' => $date,
            ]);

            $teamMap = [];
            if ($fixturesResponse->successful()) {
                foreach ($fixturesResponse->json('response', []) as $fix) {
                    $id = $fix['fixture']['id'] ?? 0;
                    $teamMap[$id] = [
                        'home' => $fix['teams']['home']['name'] ?? '',
                        'away' => $fix['teams']['away']['name'] ?? '',
                    ];
                }
            }

            // Bước 2: Gọi /odds
            $response = Http::withHeaders([
                'X-RapidAPI-Key' => $key,
                'X-RapidAPI-Host' => $host,
            ])
                ->timeout(10)
                ->retry(3, 1000)
                ->get("https://{$host}/odds", [
                    'league' => 1, // World Cup
                    'season' => 2026,
                    'date' => $date,
                    'bookmaker' => $bookmakerId,
                ]);

            if ($response->failed()) {
                if ($response->status() === 429) {
                    activity()
                        ->event('API_RATE_LIMIT')
                        ->log("RapidAPI Rate Limit Exceeded (429) when fetching odds for date {$date}");
                    Log::error("RapidAPI Rate Limit (429) for date {$date}");
                } else {
                    Log::error("Failed to fetch RapidAPI odds for date {$date}. Status: ".$response->status());
                }

                return [];
            }

            $data = $response->json('response');
            if (empty($data)) {
                return [];
            }

            $results = [];

            foreach ($data as $oddsData) {
                $fixtureId = $oddsData['fixture']['id'] ?? 0;
                $updateAt = $oddsData['update'] ?? now()->toIso8601String();

                $homeTeam = $teamMap[$fixtureId]['home'] ?? '';
                $awayTeam = $teamMap[$fixtureId]['away'] ?? '';

                $marketsDto = [];
                $bookmakers = $oddsData['bookmakers'] ?? [];

                if (! empty($bookmakers)) {
                    $bookmakerData = $bookmakers[0];
                    foreach ($bookmakerData['bets'] ?? [] as $betData) {
                        $outcomesDto = [];
                        foreach ($betData['values'] ?? [] as $valueData) {
                            $outcomesDto[] = new OutcomeDataDto(
                                value: (string) ($valueData['value'] ?? ''),
                                odd: (float) ($valueData['odd'] ?? 0)
                            );
                        }
                        $marketsDto[] = new MarketDataDto(
                            id: (int) ($betData['id'] ?? 0),
                            name: (string) ($betData['name'] ?? ''),
                            outcomes: $outcomesDto
                        );
                    }
                }

                $results[] = new OddsResponseDto(
                    fixtureId: $fixtureId,
                    homeTeam: $homeTeam,
                    awayTeam: $awayTeam,
                    updateAt: $updateAt,
                    markets: $marketsDto
                );
            }

            return $results;

        } catch (\Exception $e) {
            Log::error("Exception while fetching odds for date {$date}: ".$e->getMessage());

            return [];
        }
    }
}
