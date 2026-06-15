<?php

namespace Tests\Feature;

use App\Domain\Market\Services\ApiQuotaService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiQuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::clear();
    }

    public function test_listener_captures_headers_and_updates_cache(): void
    {
        Http::fake([
            'api-sports.io/*' => Http::response('ok', 200, [
                'x-ratelimit-requests-remaining' => '7490',
                'x-ratelimit-requests-limit' => '7500',
            ]),
        ]);

        Http::get('https://v3.football.api-sports.io/status');

        $remaining = Cache::get(ApiQuotaService::CACHE_KEY_REMAINING);
        $limit = Cache::get(ApiQuotaService::CACHE_KEY_LIMIT);

        $this->assertEquals(7490, $remaining);
        $this->assertEquals(7500, $limit);
    }

    public function test_it_sends_alert_when_quota_below_10_percent(): void
    {
        // Create an admin user to receive notification
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'super_admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertDatabaseCount('notifications', 0);

        Http::fake([
            'api-sports.io/*' => Http::response('ok', 200, [
                'x-ratelimit-requests-remaining' => '749',
                'x-ratelimit-requests-limit' => '7500',
            ]),
        ]);

        Http::get('https://v3.football.api-sports.io/status');

        // Should have sent a notification
        $this->assertDatabaseCount('notifications', 1);

        // Check if alert sent flag is cached
        $this->assertTrue(Cache::has(ApiQuotaService::CACHE_KEY_ALERT_SENT));

        // Send another request with even lower quota
        Http::fake([
            'api-sports.io/*' => Http::response('ok', 200, [
                'x-ratelimit-requests-remaining' => '500',
                'x-ratelimit-requests-limit' => '7500',
            ]),
        ]);

        Http::get('https://v3.football.api-sports.io/status');

        // Should NOT send another notification today
        $this->assertDatabaseCount('notifications', 1);
    }
}
