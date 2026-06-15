<?php

namespace App\Domain\Match\Services;

use App\Domain\Market\Services\MarketLockService;
use App\Domain\Match\Data\ApiMatchDto;
use App\Domain\Wallet\Services\WalletService;
use App\Enums\BetStatus;
use App\Enums\MarketStatus;
use App\Events\MatchStatusChanged;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MatchEvent;
use App\Models\MatchPeriodResult;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Settings\ApiSettings;

class MatchSyncService
{
    public function __construct(
        private readonly FootballDataApiService $apiService,
        private readonly RapidApiMatchService $rapidApiService,
        private readonly MarketLockService $marketLockService,
        private readonly WalletService $walletService,
        private readonly ApiSettings $apiSettings,
    ) {}

    /**
     * Đồng bộ lịch thi đấu và mapping 104 trận
     */
    public function syncSchedules(): void
    {
        $provider = $this->apiSettings->live_score_provider ?? 'rapidapi_fallback_footballdata';
        
        if ($provider === 'football_data') {
            $apiMatches = $this->apiService->fetchCompetitionMatches();
        } else {
            $apiMatches = $this->rapidApiService->fetchAllMatches();
        }

        foreach ($apiMatches as $apiMatch) {
            // For RapidAPI, status is mapped inside RapidApiMatchService, 
            // but we might need to map it if using FootballDataApiService
            $status = $provider === 'football_data' ? $this->apiService->mapApiStatusToDomain($apiMatch->status) : $apiMatch->status;

            $match = FootballMatch::where('api_id', $apiMatch->apiId)->first();

            // Auto-mapping logic cho 104 trận có sẵn
            if (! $match) {
                $match = FootballMatch::where(function ($q) use ($apiMatch) {
                        $q->where('home_team', 'LIKE', '%'.$this->normalizeName($apiMatch->homeTeamName).'%')
                          ->where('away_team', 'LIKE', '%'.$this->normalizeName($apiMatch->awayTeamName).'%');
                    })->first();



                if ($match) {
                    $match->api_id = $apiMatch->apiId;
                    $match->save();
                }
            }

            if ($match) {
                try {
                    $this->updateMatchFromApi($match, $apiMatch);
                } catch (\Exception $e) {
                    Log::error("Failed to update match {$match->id} from schedule bulk data: ".$e->getMessage());
                }
            }
        }
    }

    /**
     * Lấy các trận đấu đang LIVE từ DB và đồng bộ tỉ số
     */
    public function syncLiveScores(): void
    {
        $liveDbMatches = FootballMatch::whereNotNull('api_id')
            ->where('kickoff_at', '<=', now())
            ->where('status', '!=', 'CANCELLED')
            ->where(function ($q) {
                $q->where('status', '!=', 'FINISHED')
                    ->orWhereNull('home_score')
                    ->orWhereNull('finished_at');
            })
            ->get();

        if ($liveDbMatches->isEmpty()) {
            return;
        }

        // TỐI ƯU QUOTA: Thay vì gọi từng trận (/matches/{id}) tốn nhiều request,
        // Ta gọi 1 request duy nhất lấy toàn bộ các trận đang LIVE/vừa FINISHED của giải đấu.
        $provider = $this->apiSettings->live_score_provider ?? 'rapidapi_fallback_footballdata';
        $apiLiveMatches = null;

        try {
            if ($provider === 'rapidapi' || $provider === 'rapidapi_fallback_footballdata') {
                try {
                    $apiLiveMatches = collect($this->rapidApiService->fetchLiveMatches())->keyBy('apiId');
                } catch (\Exception $e) {
                    Log::error('RapidAPI fetch live matches failed: '.$e->getMessage());
                    if ($provider === 'rapidapi_fallback_footballdata') {
                        Log::info('Falling back to Football-Data API for live scores.');
                        $apiLiveMatches = collect($this->apiService->fetchLiveMatches())->keyBy('apiId');
                    } else {
                        throw $e;
                    }
                }
            } else {
                $apiLiveMatches = collect($this->apiService->fetchLiveMatches())->keyBy('apiId');
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch bulk live matches: '.$e->getMessage());
            if (str_contains($e->getMessage(), '429')) {
                throw $e;
            }

            return;
        }

        $missingMatches = [];
        foreach ($liveDbMatches as $match) {
            $apiMatch = $apiLiveMatches->get($match->api_id);
            if (! $apiMatch) {
                // Trận đấu không có trong luồng LIVE (có thể do API bị delay hoặc đã kết thúc)
                $missingMatches[] = $match;
                continue;
            }

            try {
                $this->updateMatchFromApi($match, $apiMatch);
            } catch (\Exception $e) {
                Log::error("Failed to update match {$match->id} from bulk data: ".$e->getMessage());
            }
        }

        // TỐI ƯU REQUEST: Gộp các trận bị thiếu vào 1 request duy nhất thay vì quét toàn giải
        if (!empty($missingMatches)) {
            $missingIds = collect($missingMatches)->pluck('api_id')->filter()->toArray();
            if (!empty($missingIds) && ($provider === 'rapidapi' || $provider === 'rapidapi_fallback_footballdata')) {
                try {
                    $missingApiMatches = collect($this->rapidApiService->fetchMatchesByIds($missingIds))->keyBy('apiId');
                    
                    foreach ($missingMatches as $match) {
                        $apiMatch = $missingApiMatches->get($match->api_id);
                        if ($apiMatch) {
                            $this->updateMatchFromApi($match, $apiMatch);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to fetch missing matches by IDs: '.$e->getMessage());
                }
            }
        }
    }

    /**
     * Kéo lại toàn bộ dữ liệu từ RapidAPI và ghi đè
     */
    public function syncAllHistoricMatches(): void
    {
        $provider = $this->apiSettings->live_score_provider ?? 'rapidapi_fallback_footballdata';
        if ($provider === 'football_data') {
            $apiMatches = $this->apiService->fetchCompetitionMatches();
        } else {
            $apiMatches = $this->rapidApiService->fetchAllMatches();
        }

        foreach ($apiMatches as $apiMatch) {
            $match = FootballMatch::where('api_id', $apiMatch->apiId)->first();
            
            if (! $match) {
                $match = FootballMatch::where(function ($q) use ($apiMatch) {
                        $q->where('home_team', 'LIKE', '%'.$this->normalizeName($apiMatch->homeTeamName).'%')
                          ->where('away_team', 'LIKE', '%'.$this->normalizeName($apiMatch->awayTeamName).'%');
                    })->first();



                if ($match) {
                    $match->api_id = $apiMatch->apiId;
                    $match->save();
                }
            }

            if ($match) {
                try {
                    // Nếu trận đấu đã kết thúc, xóa Period Results để ghi đè (Events được xóa trong syncMatchEvents)
                    if ($this->apiService->mapApiStatusToDomain($apiMatch->status) === 'FINISHED') {
                        $match->periodResults()->delete();
                    }

                    $this->updateMatchFromApi($match, $apiMatch);
                } catch (\Exception $e) {
                    Log::error("Failed to sync past match {$match->id}: ".$e->getMessage());
                }
            }
        }
    }

    public function syncMatchById(FootballMatch $match): void
    {
        if (! $match->api_id) {
            throw new \Exception('Trận đấu chưa có API ID');
        }

        $apiMatch = $this->rapidApiService->fetchMatchById($match->api_id);

        if (! $apiMatch) {
            throw new \Exception('Không tìm thấy dữ liệu trận đấu trên API');
        }

        $this->updateMatchFromApi($match, $apiMatch);
    }

    /**
     * Kéo chi tiết (bao gồm events) cho TẤT CẢ các trận đã có API ID để tiết kiệm request
     * RapidAPI cho phép truyền tối đa 20 IDs mỗi request thông qua param `ids`
     */
    public function syncAllMatchDetailsInChunks(): void
    {
        $matches = FootballMatch::whereNotNull('api_id')->get();
        if ($matches->isEmpty()) {
            return;
        }

        // Chunk by 20 to respect RapidAPI `ids` limit
        $chunks = $matches->chunk(20);

        foreach ($chunks as $chunk) {
            $apiIds = $chunk->pluck('api_id')->toArray();
            
            try {
                $apiMatches = $this->rapidApiService->fetchMatchesByIds($apiIds);
                $apiMatchesMap = collect($apiMatches)->keyBy('apiId');

                foreach ($chunk as $match) {
                    $apiMatch = $apiMatchesMap->get($match->api_id);
                    if ($apiMatch) {
                        try {
                            $this->updateMatchFromApi($match, $apiMatch);
                        } catch (\Exception $e) {
                            Log::error("Failed to sync match detail for {$match->id} in chunk: ".$e->getMessage());
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to sync match details chunk: ".$e->getMessage());
            }

            // Sleep 1s to avoid rate limits if there are multiple chunks
            sleep(1);
        }
    }

    private function updateMatchFromApi(FootballMatch $match, ApiMatchDto $apiMatch): void
    {
        $status = $this->apiService->mapApiStatusToDomain($apiMatch->status);
        $oldStatus = $match->status;
        $match->status = $status;

        if ($oldStatus !== $status) {
            MatchStatusChanged::dispatch($match, $oldStatus);
        }
        $match->home_score = $apiMatch->getCurrentHomeScore() ?? $match->home_score;
        $match->away_score = $apiMatch->getCurrentAwayScore() ?? $match->away_score;
        $match->detailed_status = $apiMatch->detailedStatus;
        $match->elapsed_minutes = $apiMatch->elapsed;

        // Cập nhật lại giờ bắt đầu nếu có sự thay đổi từ API (Ví dụ: bị dời lại 15 phút)
        if ($match->kickoff_at->notEqualTo($apiMatch->kickoffAt)) {
            $match->kickoff_at = $apiMatch->kickoffAt;
            // Tự động dời giờ đóng kèo (nếu kèo đang mở và chưa bị chốt cứng)
            $this->updateMarketCloseTimes($match);
        }

        if ($status === 'FINISHED' && $match->finished_at === null) {
            $match->finished_at = now();
        }

        $match->save();

        // Auto manage markets using detailed API status
        $this->autoManageMarkets($match, $apiMatch);

        // Lưu sự kiện trận đấu (goals, cards, subs)
        $this->syncMatchEvents($match, $apiMatch);

        if ($status === 'FINISHED') {
            $this->autoPopulatePeriodResults($match, $apiMatch);
            $this->marketLockService->lockExpiredMarkets();
        } elseif ($status === 'CANCELLED') {
            $this->handleCancelledMatch($match);
        }
    }

    private function updateMarketCloseTimes(FootballMatch $match): void
    {
        $markets = Market::where('match_id', $match->id)
            ->whereIn('status', [MarketStatus::DRAFT->value, MarketStatus::OPEN->value, MarketStatus::LOCKED->value])
            ->get();

        foreach ($markets as $market) {
            // Cập nhật lại close_at cho bằng với giờ kickoff mới nhất
            $market->update([
                'close_at' => $match->kickoff_at,
            ]);
        }
    }

    private function autoPopulatePeriodResults(FootballMatch $match, ApiMatchDto $apiMatch): void
    {
        if ($apiMatch->fullTime) {
            MatchPeriodResult::updateOrCreate(
                ['match_id' => $match->id, 'period_type' => 'FULL_TIME'],
                [
                    'home_score' => $apiMatch->fullTime->home,
                    'away_score' => $apiMatch->fullTime->away,
                    'status' => 'CONFIRMED',
                    'source_note' => 'Auto-populated from API',
                ]
            );
        }

        if ($apiMatch->halfTime) {
            MatchPeriodResult::updateOrCreate(
                ['match_id' => $match->id, 'period_type' => 'FIRST_HALF'],
                [
                    'home_score' => $apiMatch->halfTime->home,
                    'away_score' => $apiMatch->halfTime->away,
                    'status' => 'CONFIRMED',
                    'source_note' => 'Auto-populated from API',
                ]
            );

            // Tính điểm hiệp 2 (Full Time - Half Time) nếu không có hiệp phụ/penalty trong tỉ số Full Time.
            // API Football-Data thông thường fullTime ĐÃ BAO GỒM Extra Time, cần lưu ý.
            // Tuy nhiên, basic formula:
            if ($apiMatch->fullTime) {
                $homeSecondHalf = max(0, $apiMatch->fullTime->home - $apiMatch->halfTime->home);
                $awaySecondHalf = max(0, $apiMatch->fullTime->away - $apiMatch->halfTime->away);
                MatchPeriodResult::updateOrCreate(
                    ['match_id' => $match->id, 'period_type' => 'SECOND_HALF'],
                    [
                        'home_score' => $homeSecondHalf,
                        'away_score' => $awaySecondHalf,
                        'status' => 'CONFIRMED',
                        'source_note' => 'Calculated (Full - Half)',
                    ]
                );
            }
        }

        if ($apiMatch->extraTime) {
            MatchPeriodResult::updateOrCreate(
                ['match_id' => $match->id, 'period_type' => 'EXTRA_TIME'],
                [
                    'home_score' => $apiMatch->extraTime->home,
                    'away_score' => $apiMatch->extraTime->away,
                    'status' => 'CONFIRMED',
                    'source_note' => 'Auto-populated from API',
                ]
            );
        }

        if ($apiMatch->penalties) {
            MatchPeriodResult::updateOrCreate(
                ['match_id' => $match->id, 'period_type' => 'PENALTY'],
                [
                    'home_score' => $apiMatch->penalties->home,
                    'away_score' => $apiMatch->penalties->away,
                    'status' => 'CONFIRMED',
                    'source_note' => 'Auto-populated from API',
                ]
            );
        }

        // Khóa tất cả các market chưa khóa
        $markets = Market::where('match_id', $match->id)->where('status', MarketStatus::OPEN->value)->get();
        foreach ($markets as $market) {
            $this->marketLockService->transition($market, MarketStatus::LOCKED);
        }

        // Tự động trigger settlement cho toàn bộ market của trận đấu này
        Artisan::queue('settle:markets', [
            'match_code' => $match->match_code,
        ]);
    }

    private function handleCancelledMatch(FootballMatch $match): void
    {
        $markets = Market::where('match_id', $match->id)
            ->whereNotIn('status', ['VOIDED', 'SETTLED', 'CANCELLED'])
            ->get();

        foreach ($markets as $market) {
            try {
                DB::transaction(function () use ($market) {
                    $this->marketLockService->transition($market, MarketStatus::VOIDED, 'Match postponed/cancelled by API');

                    $pendingBets = $market->bets()->where('status', BetStatus::PENDING->value)->get();
                    foreach ($pendingBets as $bet) {
                        $bet->update([
                            'status' => BetStatus::VOIDED->value,
                            'voided_at' => now(),
                        ]);
                        $this->walletService->voidBet($bet->wallet, $bet);
                    }
                });
            } catch (\Exception $e) {
                Log::error("Failed to void market {$market->id} on cancelled match: ".$e->getMessage());
            }
        }
    }

    private function normalizeName(string $name): string
    {
        $name = str_ireplace(['Czechia'], 'Czech', $name);
        $name = str_ireplace(['Türkiye', 'Turkiye'], 'Turkey', $name);
        $name = str_ireplace(['Curaçao'], 'Curacao', $name);
        $name = str_ireplace(['Cape Verde Islands'], 'Cape Verde', $name);
        $name = str_ireplace(['Congo DR'], 'DR Congo', $name);
        return trim(str_ireplace(['FC', 'Team', 'National', 'Republic'], '', $name));
    }

    private function syncMatchEvents(FootballMatch $match, ApiMatchDto $apiMatch): void
    {
        if (! $apiMatch->hasEventsData) {
            return;
        }

        // Xóa events cũ (để ghi đè bản mới nhất từ API)
        $match->events()->delete();

        $eventsToInsert = [];

        foreach ($apiMatch->goals as $goal) {
            $eventsToInsert[] = [
                'match_id' => $match->id,
                'minute' => $goal['minute'] ?? 0,
                'injury_time' => $goal['injuryTime'] ?? null,
                'type' => 'GOAL',
                'team_type' => isset($goal['team']['name']) && stripos($match->home_team, $this->normalizeName($goal['team']['name'])) !== false ? 'HOME' : 'AWAY',
                'player_name' => $goal['scorer']['name'] ?? null,
                'related_player_name' => $goal['assist']['name'] ?? null,
                'detail' => $goal['type'] ?? 'REGULAR',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($apiMatch->bookings as $booking) {
            $eventsToInsert[] = [
                'match_id' => $match->id,
                'minute' => $booking['minute'] ?? 0,
                'injury_time' => null,
                'type' => 'CARD',
                'team_type' => isset($booking['team']['name']) && stripos($match->home_team, $this->normalizeName($booking['team']['name'])) !== false ? 'HOME' : 'AWAY',
                'player_name' => $booking['player']['name'] ?? null,
                'related_player_name' => null,
                'detail' => $booking['card'] ?? 'YELLOW',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($apiMatch->substitutions as $sub) {
            $eventsToInsert[] = [
                'match_id' => $match->id,
                'minute' => $sub['minute'] ?? 0,
                'injury_time' => null,
                'type' => 'SUB',
                'team_type' => isset($sub['team']['name']) && stripos($match->home_team, $this->normalizeName($sub['team']['name'])) !== false ? 'HOME' : 'AWAY',
                'player_name' => $sub['playerIn']['name'] ?? null,
                'related_player_name' => $sub['playerOut']['name'] ?? null,
                'detail' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($eventsToInsert)) {
            MatchEvent::insert($eventsToInsert);
        }
    }

    private function autoManageMarkets(FootballMatch $match, ApiMatchDto $apiMatch): void
    {
        $statusShort = $apiMatch->detailedStatus;
        $isDraw = $apiMatch->getCurrentHomeScore() === $apiMatch->getCurrentAwayScore();

        // 1. Kèo Hiệp 1 & Cả trận: Đóng khi trận đấu bắt đầu (1H)
        if ($statusShort === '1H') {
            $this->lockMarkets($match, ['FIRST_HALF', 'FULL_TIME'], 'Auto-locked due to 1H start');
        }

        // 2. Kèo Hiệp 2: Đóng khi hiệp 2 bắt đầu (2H)
        if ($statusShort === '2H') {
            $this->lockMarkets($match, ['SECOND_HALF'], 'Auto-locked due to 2H start');
        }

        // 3. Kèo Hiệp phụ (EXTRA_TIME): 
        // MỞ: Khi nghỉ hết 90 phút (HT/BT) hoặc kết thúc 90p hòa
        if (in_array($statusShort, ['FT', 'BT']) && $isDraw) {
            $this->openMarkets($match, ['EXTRA_TIME']);
        }
        // ĐÓNG: Khi hiệp phụ bắt đầu (ET)
        if ($statusShort === 'ET') {
            $this->lockMarkets($match, ['EXTRA_TIME'], 'Auto-locked due to ET start');
        }

        // 4. Kèo Penalty (PENALTY):
        // MỞ: Khi chuẩn bị sút penalty hoặc nghỉ hết hiệp phụ hòa
        if (in_array($statusShort, ['BT', 'AET']) && $isDraw) {
            $this->openMarkets($match, ['PENALTY']);
        }
        // ĐÓNG: Khi penalty bắt đầu (P)
        if ($statusShort === 'P') {
            $this->lockMarkets($match, ['PENALTY'], 'Auto-locked due to Penalty start');
        }
    }

    private function lockMarkets(FootballMatch $match, array $periodTypes, string $reason): void
    {
        $markets = Market::where('match_id', $match->id)
            ->whereIn('period_type', $periodTypes)
            ->where('status', MarketStatus::OPEN->value)
            ->get();

        foreach ($markets as $market) {
            $this->marketLockService->transition($market, MarketStatus::LOCKED, $reason);
        }
    }

    private function openMarkets(FootballMatch $match, array $periodTypes): void
    {
        $markets = Market::where('match_id', $match->id)
            ->whereIn('period_type', $periodTypes)
            ->where('status', MarketStatus::DRAFT->value)
            ->get();

        foreach ($markets as $market) {
            $market->update([
                'status' => MarketStatus::OPEN->value,
                'open_at' => now(),
            ]);
            Log::info("Auto-opened {$market->period_type} market {$market->id} for match {$match->id}");
        }
    }
}
