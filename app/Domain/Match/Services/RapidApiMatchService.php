<?php

namespace App\Domain\Match\Services;

use App\Domain\Match\Data\ApiMatchDto;
use App\Settings\ApiSettings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RapidApiMatchService
{
    private string $baseUrl = 'https://v3.football.api-sports.io';

    public function __construct(
        private readonly ApiSettings $apiSettings,
    ) {
        if ($this->apiSettings->rapidapi_host) {
            $this->baseUrl = 'https://' . $this->apiSettings->rapidapi_host;
        }
    }

    private function getHeaders(): array
    {
        return [
            'X-RapidAPI-Key' => $this->apiSettings->rapidapi_key ?? '',
            'X-RapidAPI-Host' => $this->apiSettings->rapidapi_host ?? 'v3.football.api-sports.io',
        ];
    }

    /**
     * @return array<ApiMatchDto>
     *
     * @throws \Exception
     */
    public function fetchLiveMatches(): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->retry(3, 1000)
            ->timeout(10)
            ->get("{$this->baseUrl}/fixtures", [
                'league' => 1, // World Cup
                'season' => 2026,
                'live' => 'all', // Get all live matches
            ]);

        if ($response->failed()) {
            $this->handleApiError('fetchLiveMatches', $response);
        }

        $matches = $response->json('response', []);

        return array_map(fn ($data) => ApiMatchDto::fromRapidApiArray($data), $matches);
    }

    /**
     * @return array<ApiMatchDto>
     *
     * @throws \Exception
     */
    public function fetchAllMatches(): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->retry(3, 1000)
            ->timeout(10)
            ->get("{$this->baseUrl}/fixtures", [
                'league' => 1, // World Cup
                'season' => 2026,
            ]);

        if ($response->failed()) {
            $this->handleApiError("fetchAllMatches", $response);
        }

        $matches = $response->json('response', []);

        return array_map(fn ($data) => ApiMatchDto::fromRapidApiArray($data), $matches);
    }

    /**
     * @throws \Exception
     */
    public function fetchMatchesByIds(array $apiIds): array
    {
        if (empty($apiIds)) {
            return [];
        }

        $results = [];
        // RapidAPI allows maximum 20 ids per request separated by dash
        $chunks = array_chunk($apiIds, 20);

        foreach ($chunks as $chunk) {
            $response = Http::withHeaders($this->getHeaders())
                ->retry(3, 1000)
                ->timeout(10)
                ->get("{$this->baseUrl}/fixtures", [
                    'ids' => implode('-', $chunk),
                ]);

            if ($response->failed()) {
                $this->handleApiError("fetchMatchesByIds", $response);
            }

            $matches = $response->json('response', []);
            $dtos = array_map(fn ($data) => ApiMatchDto::fromRapidApiArray($data), $matches);
            $results = array_merge($results, $dtos);
        }

        return $results;
    }

    /**
     * @throws \Exception
     */
    public function fetchMatchById(string $apiId): ?ApiMatchDto
    {
        $response = Http::withHeaders($this->getHeaders())
            ->retry(3, 1000)
            ->timeout(10)
            ->get("{$this->baseUrl}/fixtures", [
                'id' => $apiId,
            ]);

        if ($response->failed()) {
            $this->handleApiError("fetchMatchById", $response);
        }

        $matches = $response->json('response', []);

        if (empty($matches)) {
            return null;
        }

        return ApiMatchDto::fromRapidApiArray($matches[0]);
    }

    /**
     * @throws \Exception
     */
    private function handleApiError(string $context, Response $response): void
    {
        Log::error("RapidAPI {$context} failed", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->status() === 429) {
            throw new \Exception('RapidAPI Rate Limit Exceeded (429)');
        }

        throw new \Exception("Failed to fetch from RapidAPI: {$response->status()}");
    }
}
