<?php

namespace App\Domain\Market\Services;

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiQuotaService
{
    public const CACHE_KEY_REMAINING = 'api_sports_quota_remaining';
    public const CACHE_KEY_LIMIT = 'api_sports_quota_limit';
    public const CACHE_KEY_ALERT_SENT = 'api_sports_quota_alert_sent';

    public function processResponse(Response $response): void
    {
        $remaining = $response->header('x-ratelimit-requests-remaining');
        $limit = $response->header('x-ratelimit-requests-limit');

        if ($remaining !== null) {
            $remainingInt = (int) $remaining;
            Cache::put(self::CACHE_KEY_REMAINING, $remainingInt, now()->addDays(7));

            if ($limit !== null) {
                Cache::put(self::CACHE_KEY_LIMIT, (int) $limit, now()->addDays(7));
                $this->checkQuotaAndAlert($remainingInt, (int) $limit);
            }
        }
    }

    public function getQuotaRemaining(): ?int
    {
        return Cache::get(self::CACHE_KEY_REMAINING);
    }

    public function getQuotaLimit(): ?int
    {
        return Cache::get(self::CACHE_KEY_LIMIT);
    }

    private function checkQuotaAndAlert(int $remaining, int $limit): void
    {
        if ($limit <= 0) {
            return;
        }

        $percentageRemaining = ($remaining / $limit) * 100;

        if ($percentageRemaining <= 10) {
            // Check if alert was already sent today
            if (! Cache::has(self::CACHE_KEY_ALERT_SENT)) {
                $this->sendAlertToAdmins($remaining, $limit);
                // Mark alert as sent for the next 24 hours
                Cache::put(self::CACHE_KEY_ALERT_SENT, true, now()->addHours(24));
            }
        }
    }

    private function sendAlertToAdmins(int $remaining, int $limit): void
    {
        Log::warning("API-Sports Quota Alert: {$remaining} requests remaining out of {$limit}");

        $admins = User::role(['super_admin', 'admin'])->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Cảnh báo Quota API-Sports')
            ->body("Quota API-Sports chỉ còn {$remaining} / {$limit} requests. Vui lòng kiểm tra lại cấu hình hoặc nâng cấp gói.")
            ->warning()
            ->sendToDatabase($admins);
    }
}
