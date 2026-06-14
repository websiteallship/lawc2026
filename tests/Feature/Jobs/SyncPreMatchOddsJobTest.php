<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SyncPreMatchOddsJob;
use App\Models\FootballMatch;
use App\Models\Season;
use App\Settings\ApiSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncPreMatchOddsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_pre_match_odds_and_persists_to_db()
    {
        $settings = app(ApiSettings::class);
        $settings->rapidapi_key = 'test_key';
        $settings->rapidapi_host = 'api-football-v1.p.rapidapi.com';
        $settings->bookmaker_id = 8;
        $settings->save();

        $season = Season::create([
            'name' => 'World Cup 2026',
            'code' => 'WC2026',
            'status' => 'ACTIVE',
        ]);

        $targetDate = now()->addHours(12);

        $match = FootballMatch::create([
            'season_id' => $season->id,
            'match_code' => 'TEST01',
            'home_team' => 'Vietnam',
            'away_team' => 'Thailand',
            'stage' => 'Group A',
            'kickoff_at' => $targetDate,
            'status' => 'SCHEDULED',
        ]);

        $date = $targetDate->toDateString();

        Http::fake([
            "https://{$settings->rapidapi_host}/fixtures*" => Http::response([
                'response' => [
                    [
                        'fixture' => ['id' => 12345],
                        'teams' => [
                            'home' => ['name' => 'Vietnam'],
                            'away' => ['name' => 'Thailand'],
                        ]
                    ]
                ]
            ]),
            "https://{$settings->rapidapi_host}/odds*" => Http::response([
                'response' => [
                    [
                        'fixture' => ['id' => 12345],
                        'update' => now()->toIso8601String(),
                        'bookmakers' => [
                            [
                                'id' => 8,
                                'name' => 'Bet365',
                                'bets' => [
                                    [
                                        'id' => 4, // Asian Handicap
                                        'name' => 'Asian Handicap',
                                        'values' => [
                                            ['value' => 'Home -1.5', 'odd' => '1.95'],
                                            ['value' => 'Away +1.5', 'odd' => '1.85'],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ])
        ]);

        dispatch_sync(new SyncPreMatchOddsJob());

        $this->assertDatabaseHas('markets', [
            'match_id' => $match->id,
            'market_type' => 'ASIAN_HANDICAP',
            'period_type' => 'FULL_TIME',
            'status' => 'OPEN',
        ]);

        $this->assertDatabaseHas('market_outcomes', [
            'label' => 'Home -1.5',
            'line_value' => -1.5,
            'profit_rate' => 0.95,
            'selection_side' => 'HOME',
        ]);

        $this->assertDatabaseHas('market_outcomes', [
            'label' => 'Away +1.5',
            'line_value' => 1.5,
            'profit_rate' => 0.85,
            'selection_side' => 'AWAY',
        ]);
        
        $match->refresh();
        $this->assertContains('pre_match', $match->odds_fetch_status ?? []);
    }
}
