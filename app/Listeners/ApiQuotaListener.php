<?php

namespace App\Listeners;

use App\Domain\Market\Services\ApiQuotaService;
use App\Settings\ApiSettings;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Str;

class ApiQuotaListener
{
    public function __construct(
        protected ApiQuotaService $apiQuotaService,
        protected ApiSettings $apiSettings
    ) {}

    public function handle(ResponseReceived $event): void
    {
        $url = $event->request->url();
        $rapidApiHost = $this->apiSettings->rapidapi_host ?: 'v3.football.api-sports.io';

        // Check if the request is destined for RapidAPI / API-Sports
        if (Str::contains($url, $rapidApiHost) || Str::contains($url, 'api-sports.io')) {
            $this->apiQuotaService->processResponse($event->response);
        }
    }
}
