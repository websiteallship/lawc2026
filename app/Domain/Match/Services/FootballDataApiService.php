<?php

namespace App\Domain\Match\Services;

use App\Domain\Match\Data\ApiMatchDto;
use App\Settings\ApiSettings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FootballDataApiService
{
    private string $baseUrl = 'https://api.football-data.org/v4';

    public function __construct(
        private readonly ApiSettings $apiSettings,
    ) {}

    private function getToken(): string
    {
        return $this->apiSettings->football_data_api_token ?? '';
    }

    /**
     * @return array<ApiMatchDto>
     *
     * @throws \Exception
     */
    public function fetchCompetitionMatches(?string $status = null): array
    {
        $competitionId = $this->apiSettings->competition_id ?: '2000';
        $query = [];
        if ($status) {
            $query['status'] = $status;
        }

        $response = Http::withHeaders([
            'X-Auth-Token' => $this->getToken(),
        ])
            ->retry(3, 1000)
            ->timeout(10)
            ->get("{$this->baseUrl}/competitions/{$competitionId}/matches", $query);

        if ($response->failed()) {
            $this->handleApiError('fetchCompetitionMatches', $response);
        }

        $matches = $response->json('matches', []);

        return array_map(fn ($data) => ApiMatchDto::fromArray($data), $matches);
    }

    /**
     * @return array<ApiMatchDto>
     *
     * @throws \Exception
     */
    public function fetchLiveMatches(): array
    {
        $competitionId = $this->apiSettings->competition_id ?: '2000';

        $response = Http::withHeaders([
            'X-Auth-Token' => $this->getToken(),
        ])
            ->retry(3, 1000)
            ->timeout(10)
            ->get("{$this->baseUrl}/competitions/{$competitionId}/matches", [
                'status' => 'IN_PLAY,PAUSED,FINISHED,AWARDED,POSTPONED,CANCELLED,SUSPENDED',
                // We fetch broader status because we need to catch FINISHED transitions
            ]);

        if ($response->failed()) {
            $this->handleApiError('fetchLiveMatches', $response);
        }

        $matches = $response->json('matches', []);

        return array_map(fn ($data) => ApiMatchDto::fromArray($data), $matches);
    }

    /**
     * Lấy chi tiết 1 trận đấu
     *
     * @throws \Exception
     */
    public function fetchMatchDetails(string $apiMatchId): ?ApiMatchDto
    {
        $response = Http::withHeaders([
            'X-Auth-Token' => $this->getToken(),
        ])
            ->retry(3, 1000)
            ->timeout(5)
            ->get("{$this->baseUrl}/matches/{$apiMatchId}");

        if ($response->failed()) {
            if ($response->status() === 404) {
                return null;
            }
            $this->handleApiError("fetchMatchDetails({$apiMatchId})", $response);
        }

        return ApiMatchDto::fromArray($response->json());
    }

    public function mapApiStatusToDomain(string $apiStatus): string
    {
        return match ($apiStatus) {
            'IN_PLAY', 'PAUSED' => 'LIVE',
            'FINISHED', 'AWARDED' => 'FINISHED',
            'POSTPONED', 'CANCELLED', 'SUSPENDED' => 'CANCELLED',
            default => 'SCHEDULED', // TIMED, SCHEDULED
        };
    }

    /**
     * @throws \Exception
     */
    private function handleApiError(string $context, Response $response): void
    {
        Log::error("FootballData API {$context} failed", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->status() === 429) {
            throw new \Exception('Football-Data API Rate Limit Exceeded (429)');
        }

        throw new \Exception("Failed to fetch from Football-Data API: {$response->status()}");
    }
}
