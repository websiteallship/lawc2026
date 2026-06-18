<?php
namespace App\Livewire\Player;

use Livewire\Component;
use App\Domain\Modal\Contracts\CelebrationCheckerInterface;
use App\Domain\Modal\Contracts\SettlementSummaryCheckerInterface;
use App\Domain\Modal\Contracts\DailyBriefingCheckerInterface;
use App\Domain\Modal\Contracts\DailyRankingCheckerInterface;
use App\Domain\Modal\Contracts\MatchReminderCheckerInterface;
use App\Domain\Modal\Contracts\ReengagementCheckerInterface;
use Illuminate\Support\Facades\DB;

class ModalOrchestrator extends Component
{
    public ?string $currentModal = null;
    public array $queue = [];
    public array $modalData = [];

    public bool $isDevModeOpen = false;

    /** Các modal type hợp lệ (thứ tự = priority) */
    private const MODAL_TYPES = [
        'celebration',
        'settlement',
        'daily',
        'ranking',
        'reminder',
        'reengagement',
    ];

    public function mount(): void
    {
        if (!auth()->check()) return;
        $this->buildQueue();
        $this->showNext();
    }

    /**
     * Dev-only: force show một hoặc tất cả modal để test.
     * Security: chỉ chạy được khi APP_ENV != production.
     */
    public function forceShowModal(string $type): void
    {
        // Guard: không cho chạy trong production
        if (app()->environment('production')) {
            abort(403, 'Dev mode disabled in production.');
        }

        $user = auth()->user();
        if (!$user) return;

        // Validate type hợp lệ
        if ($type !== 'all' && !in_array($type, self::MODAL_TYPES, true)) {
            return;
        }

        $checkers = $this->resolveCheckers();
        $this->queue = [];
        $typesToShow = $type === 'all' ? self::MODAL_TYPES : [$type];

        foreach ($typesToShow as $key) {
            if (!isset($checkers[$key])) continue;
            $data = $checkers[$key]->getData($user);
            $data = $this->withFakeDevData($key, $data, $user);
            $this->queue[] = $key;
            $this->modalData[$key] = $data;
        }

        $this->isDevModeOpen = false;
        $this->showNext();
    }

    public function toggleDevMode(): void
    {
        $this->isDevModeOpen = !$this->isDevModeOpen;
    }

    public function dismiss(): void
    {
        $this->markCurrentAsSeen();
        $this->currentModal = null;
        $this->dispatch('modal-queue-next');
    }

    public function dismissAndRedirect(string $url): void
    {
        // Validate URL chỉ cho phép relative path (tránh open redirect)
        if (!str_starts_with($url, '/')) {
            $this->dismiss();
            return;
        }
        $this->markCurrentAsSeen();
        $this->currentModal = null;
        $this->redirect($url, navigate: true);
    }

    public function snoozeReminder(int $matchId): void
    {
        if (!auth()->check()) return;
        try {
            $now = now();
            DB::table('match_reminders')->upsert(
                [
                    'user_id'      => auth()->id(),
                    'match_id'     => $matchId,
                    'snoozed_until'=> $now->copy()->addMinutes(30),
                    'reminded_at'  => $now,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                ['user_id', 'match_id'],
                ['snoozed_until', 'updated_at']
            );
        } catch (\Exception $e) {
            // Ignore DB errors (e.g. FK constraint failure for fake dev data)
        }
        $this->removeMatchFromReminder($matchId);
    }

    public function dismissReminder(int $matchId): void
    {
        if (!auth()->check()) return;
        try {
            $now = now();
            DB::table('match_reminders')->upsert(
                [
                    'user_id'     => auth()->id(),
                    'match_id'    => $matchId,
                    'dismissed_at'=> $now,
                    'reminded_at' => $now,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                ['user_id', 'match_id'],
                ['dismissed_at', 'updated_at']
            );
        } catch (\Exception $e) {
            // Ignore DB errors (e.g. FK constraint failure for fake dev data)
        }
        $this->removeMatchFromReminder($matchId);
    }

    #[\Livewire\Attributes\On('modal-queue-next')]
    public function showNext(): void
    {
        if (empty($this->queue)) return;
        $this->currentModal = array_shift($this->queue);
    }

    // ─── Private Helpers ────────────────────────────────────────────────────

    private function buildQueue(): void
    {
        $user = auth()->user();
        if (!$user) return;

        foreach ($this->resolveCheckers() as $type => $checker) {
            if ($checker->shouldShow($user)) {
                $this->queue[]          = $type;
                $this->modalData[$type] = $checker->getData($user);
            }
        }
    }

    private function markCurrentAsSeen(): void
    {
        if (!$this->currentModal) return;
        $user = auth()->user();
        if (!$user) return;

        $checkers = $this->resolveCheckers();
        if (isset($checkers[$this->currentModal])) {
            $checkers[$this->currentModal]->markAsSeen($user);
        }
    }

    /**
     * Single source of truth cho checker map.
     * Tránh duplicate code trong buildQueue / markCurrentAsSeen / forceShowModal.
     *
     * @return array<string, \App\Domain\Modal\Contracts\ModalCheckerInterface>
     */
    private function resolveCheckers(): array
    {
        return [
            'celebration'  => app(CelebrationCheckerInterface::class),
            'settlement'   => app(SettlementSummaryCheckerInterface::class),
            'daily'        => app(DailyBriefingCheckerInterface::class),
            'ranking'      => app(DailyRankingCheckerInterface::class),
            'reminder'     => app(MatchReminderCheckerInterface::class),
            'reengagement' => app(ReengagementCheckerInterface::class),
        ];
    }

    /**
     * Xóa match khỏi reminder data. Nếu hết match → dismiss modal.
     */
    private function removeMatchFromReminder(int $matchId): void
    {
        if ($this->currentModal !== 'reminder') return;
        if (!isset($this->modalData['reminder']['matches'])) return;

        $matches = $this->modalData['reminder']['matches'];

        // Hỗ trợ cả Collection và array
        if ($matches instanceof \Illuminate\Support\Collection) {
            $filtered = $matches->reject(fn($match) => (int) ($match->id ?? $match['id'] ?? 0) === $matchId);
            $this->modalData['reminder']['matches'] = $filtered;
            if ($filtered->isEmpty()) {
                $this->dismiss();
            }
        } else {
            $filtered = array_filter($matches, fn($match) => (int) ($match['id'] ?? 0) !== $matchId);
            $this->modalData['reminder']['matches'] = array_values($filtered);
            if (empty($filtered)) {
                $this->dismiss();
            }
        }
    }

    private function withFakeDevData(string $type, array $data, $user): array
    {
        if ($type === 'celebration') {
            $achievementsCount = isset($data['achievements']) ? count($data['achievements']) : 0;
            $missionsCount     = isset($data['missions']) ? count($data['missions']) : 0;

            if ($achievementsCount === 0 && $missionsCount === 0) {
                $fakeAchievement = new \stdClass();
                $fakeAchievement->id          = 99;
                $fakeAchievement->name        = '[DEMO] Chiến Thần Mùa Hè';
                $fakeAchievement->description = 'Dự đoán đúng 5 trận liên tiếp.';
                $fakeAchievement->icon        = 'heroicon-s-fire';
                $fakeAchievement->awarded_at  = now()->timezone('Asia/Ho_Chi_Minh');

                $fakeUA              = new \stdClass();
                $fakeUA->achievement = $fakeAchievement;
                $fakeUA->awarded_at  = now()->timezone('Asia/Ho_Chi_Minh');

                $fakeMission              = new \stdClass();
                $fakeMission->id          = 98;
                $fakeMission->title       = '[DEMO] Tân Binh Năng Nổ';
                $fakeMission->description = 'Tham gia đặt dự đoán ít nhất 2 vé trong tuần.';
                $fakeMission->rewardAchievement = null;

                $fakeUM             = new \stdClass();
                $fakeUM->mission    = $fakeMission;
                $fakeUM->completed_at = now()->timezone('Asia/Ho_Chi_Minh');

                return [
                    'achievements'   => collect([$fakeUA]),
                    'missions'       => collect([$fakeUM]),
                    'hasAchievements'=> true,
                    'hasMissions'    => true,
                ];
            }
        }

        if ($type === 'settlement') {
            $betsCount = isset($data['bets']) ? count($data['bets']) : 0;
            if ($betsCount === 0) {
                $bet1 = new \App\Models\Bet();
                $bet1->status               = \App\Enums\BetStatus::WON;
                $bet1->net_result           = 50000;
                $bet1->stake                = 50000;
                $bet1->display_odds_snapshot = 'Kèo Asian Handicap: -0.5 ăn 1.0';

                $bet2 = new \App\Models\Bet();
                $bet2->status               = \App\Enums\BetStatus::LOST;
                $bet2->net_result           = -20000;
                $bet2->stake                = 20000;
                $bet2->display_odds_snapshot = 'Kèo Tài Xỉu: Tài 2.5 ăn 0.9';

                return [
                    'bets'    => collect([$bet1, $bet2]),
                    'byMatch' => [
                        [
                            'match_label' => '[DEMO] Brazil vs Argentina',
                            'bets'        => [$bet1, $bet2],
                            'subtotal'    => 30000,
                        ],
                    ],
                    'totalNetResult' => 30000,
                    'hasBets'        => true,
                ];
            }
        }

        if ($type === 'daily') {
            $missionsCount = isset($data['missions']) ? count($data['missions']) : 0;
            if ($missionsCount === 0) {
                $data['missions'] = [
                    [
                        'title'        => '[DEMO] Dự đoán mỗi ngày',
                        'description'  => 'Hoàn thành 1 vé dự đoán hợp lệ trong ngày hôm nay.',
                        'target_value' => 1,
                        'user_mission' => [
                            'current_value' => 1,
                            'is_completed'  => true,
                        ],
                    ],
                    [
                        'title'        => '[DEMO] Tiến vào Vòng trong',
                        'description'  => 'Tham gia đặt dự đoán ít nhất 1 vé ở trận đấu thuộc vòng Knockout.',
                        'target_value' => 1,
                        'user_mission' => [
                            'current_value' => 0,
                            'is_completed'  => false,
                        ],
                    ]
                ];
            }

            $matchesCount = isset($data['matches']) ? count($data['matches']) : 0;
            if ($matchesCount === 0) {
                $data['matches'] = [
                    [
                        'name'           => 'Asian Handicap',
                        'match_id'       => 999,
                        'close_at'       => now()->addMinutes(45)->toDateTimeString(),
                        'home_team_name' => '[DEMO] Việt Nam',
                        'away_team_name' => '[DEMO] Thái Lan',
                    ]
                ];
            }
        }

        if ($type === 'ranking') {
            if (empty($data['rankings'])) {
                $data['userRank']   = 1;
                $data['gapToAbove'] = 0;
                $data['messageKey'] = 'top1';
                $data['top3'] = [
                    ['user_id' => $user->id,  'name' => $user->name, 'net_profit' => 150000],
                    ['user_id' => 998, 'name' => '[DEMO] Đối thủ 1', 'net_profit' => 140000],
                    ['user_id' => 999, 'name' => '[DEMO] Đối thủ 2', 'net_profit' => 135000],
                ];
                $data['rankings'] = $data['top3'];
            }
        }

        if ($type === 'reminder') {
            $matchesCount = isset($data['matches']) ? count($data['matches']) : 0;
            if ($matchesCount === 0) {
                $data['matches'] = collect([
                    (object)[
                        'id'         => 999,
                        'home_team'  => '[DEMO] Việt Nam',
                        'away_team'  => '[DEMO] Thái Lan',
                        'kickoff_at' => now()->addMinutes(45),
                    ],
                ]);
            }
        }

        if ($type === 'reengagement') {
            if (empty($data['newMarkets'])) {
                $data['daysAbsent'] = 3;

                $fakeMarket = new \App\Models\Market();
                $fakeMarket->name           = 'Asian Handicap';
                $fakeMarket->match_id       = 999;
                $fakeMarket->home_team_name = '[DEMO] Brazil';
                $fakeMarket->away_team_name = '[DEMO] Pháp';

                $data['newMarkets'] = collect([$fakeMarket]);
                $data['settledBets'] = collect([
                    (object)['id' => 1, 'status' => 'WON', 'payout' => 5000, 'match_name' => '[DEMO] Anh vs Đức'],
                ]);
            }
        }

        return $data;
    }

    public function render()
    {
        return view('livewire.player.modal-orchestrator');
    }
}
