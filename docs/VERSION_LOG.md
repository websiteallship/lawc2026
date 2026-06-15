# Version Log & Implementation History

## v0.1.0 (Phase 0) - Hoàn tất
- Khởi tạo project Laravel 12.
- Cài đặt Filament 5.6.
- Cấu hình timezone `Asia/Ho_Chi_Minh` trong `.env`.
- Publish cấu hình mặc định.

## v0.2.0 (Phase 0) - Hoàn tất
- Cài đặt `spatie/laravel-permission` và `bezhansalleh/filament-shield`.
- Chạy migrate permission tables.
- Tạo `RoleSeeder` với các role: `super_admin`, `operator`, `settlement_manager`, `auditor`, `player`.
- Chạy seed database cho roles.
- Khởi tạo base Policy classes: `UserPolicy`, `MarketPolicy`, `BetPolicy`, `SettlementPolicy`.

## v0.3.0 (Phase 0) - Hoàn tất
- Khởi tạo Core Migrations cho các module.
- Cài đặt `spatie/laravel-activitylog` và `spatie/laravel-settings`.

## v0.4.0 (Phase 0) - Hoàn tất
- Khởi tạo Seeder: `SuperAdminSeeder`, `SeasonSeeder`, `SystemSettingSeeder`.
- Thêm `HasRoles` trait vào `User` model.
- Định nghĩa schema cho 11 bảng core (seasons, wallets, wallet_ledgers, matches, match_period_results, markets, market_outcomes, bets, settlements, settlement_items, leaderboard_snapshots).
- `migrate:fresh --seed` chạy thành công, toàn bộ roles và super admin đã seed.

## v0.5.0 (Phase 1 - MVP) - Hoàn tất
- Tạo Enums: `LedgerType`, `BetStatus`, `MarketStatus`, `MarketType`, `PeriodType`.
- Tạo Models: `Wallet`, `WalletLedger`, `Season`, `FootballMatch`, `Market`, `MarketOutcome`, `Bet`, `Settlement`, `SettlementItem`, `LeaderboardSnapshot`, `MatchPeriodResult`.
- Implement `WalletService` với: `createWallet`, `grant`, `deduct`, `lockStake`, `settleBet`, `voidBet`.
- Tất cả operations đều qua DB transaction + lockForUpdate + ledger row + kiểm tra âm.
- Domain exceptions: `InsufficientBalanceException`, `NegativeBalanceException`.
- **10/10 unit tests PASS** (`WalletServiceTest`): grant, deduct, lockStake, settleBet WON/LOST, voidBet, negative balance guard.

## v0.6.0 (Phase 1 - MVP) - Hoàn tất
- `MarketLockService` — transition guard (DRAFT→OPEN→LOCKED→SETTLING→SETTLED/VOIDED), `lockExpiredMarkets()` idempotent.
- Domain exception `InvalidMarketTransitionException`.
- Artisan command `markets:lock-expired` + Scheduler (everyMinute).
- Filament 5 Resources (Schema API): `FootballMatchResource`, `MarketResource`, `OutcomesRelationManager`.
- Filament actions: Publish (DRAFT→OPEN), Void (kèm lý do).
- **Tích hợp cờ quốc gia**: `stijnvanouplines/blade-country-flags` + `CountryFlagHelper` (không đổi DB).
- **8/8 MarketLockServiceTest PASS + 20/20 tổng PASS**.

## v0.7.0 (Phase 1 - MVP) - Hoàn tất
- `BetPlacementService::placeBet()` — 14 bước đầy đủ theo AGENTS.md rule 6:
  1. DB transaction
  2. Lock wallet FOR UPDATE
  3. User active? (re-fetch fresh)
  4. Market tồn tại + OPEN?
  5. now < close_at?
  6. Outcome active + thuộc market?
  7. stake >= min_stake (10)
  8. stake <= max_stake_per_bet (200)
  9. total_match_stake + stake <= max_stake_per_match (500)
  10. available_balance >= stake
  11. Snapshot: profit_rate, line, label, display_odds, close_at, market_type, period_type, selection_side
  12. Tạo Bet PENDING + public_code `DL-YYYYMMDD-XXXXX`
  13. WalletService::lockStake()
  14. Commit
- DTO `PlaceBetInput`, Exception `BetPlacementException` với `errorCode`.
- **12/12 BetPlacementServiceTest PASS + 32/32 tổng PASS**.

## v0.9.0 (Phase 1 - MVP) - Hoàn tất
- **Data DTOs**: `MatchResult`, `SettlementResult`.
- **3 Calculators** (từ `AbstractSettlementCalculator`):
  - `ExactScoreSettlementCalculator` — match chính xác tỉ số.
  - `AsianHandicapSettlementCalculator` — full line (0, ±0.5, ±1...) + quarter-line split (±0.25, ±0.75).
  - `OverUnderSettlementCalculator` — full line + quarter-line split (2.25, 2.75...) theo ví dụ AGENTS.md.
- **Payout math** (AGENTS.md): Full Win `stake×(1+r)`, Lose `0`, Push `stake`, Half Win `(s/2)×(1+r)+(s/2)`, Half Lose `s/2`. Rounding `PHP_ROUND_HALF_UP`.
- **`SettlementEngine`**: `preview()` (không ghi DB) + `execute()` (transaction, lockForUpdate, idempotent, ledger, SettlementItem).
- **Idempotency**: throw `SettlementException::ALREADY_SETTLED` nếu execute lần 2.
- **Models**: `Settlement`, `SettlementItem` với fillable/casts đầy đủ. `Bet.outcome()` relation.
- **19/19 SettlementEngineTest PASS + 51/51 tổng PASS**.
  - ExactScore win/lose, AH 0/±0.5/±0.25/±0.75, O/U full/2.25/2.75, engine execute, idempotent, integration ledger.

## v0.10.0 (Phase 1 - MVP) - Hoàn tất
- **`LeaderboardService`** — `computeSeason()`, `snapshot()`, `getLatestSnapshot()`.
- **Sort order** (theo spec): `net_profit DESC → ROI DESC → exact_score_wins DESC → win_rate DESC`.
- **Không dùng balance để rank** — tránh méo khi admin cấp thêm lá.
- **DTO `LeaderboardEntry`** — roi, win_rate, exact_score_wins, rank.
- **`RebuildLeaderboardJob`** — `ShouldQueue`, dispatch NGOÀI transaction sau `SettlementEngine::execute()`.
- **`LeaderboardSnapshot` model** — `timestamps = false` (table thiếu updated_at đúng schema).
- **9/9 LeaderboardServiceTest PASS + 60/60 tổng PASS**.
  - computeSeason empty, rank by net_profit, ROI calc, win_rate=0 khi chưa settle, exact_score_wins, tiebreak ROI, snapshot DB, getLatestSnapshot, Queue::assertPushed job.

## v0.11.0 (Phase 1 - MVP) - Hoàn tất
- **`spatie/laravel-activitylog`** — publish migrations, config. Table `activity_log` sẵn sàng.
- **`AuditLogService`** — wrapper domain-layer: `log()`, `logWalletChange()`, `logMarketTransition()`, `logSettlement()`.
- **Audit events** được log:
  - `WALLET_GRANTED` / `WALLET_DEDUCTED` (WalletService::grant/deduct)
  - `MARKET_PUBLISHED` / `MARKET_LOCKED` / `MARKET_VOIDED` (MarketLockService::transition)
  - `SETTLEMENT_EXECUTED` (SettlementEngine::execute, ngoài transaction)
- **`CsvExportService`** — `betsRows()`, `ledgerRows()`, `leaderboardRows()` + `writeCsv()` với BOM UTF-8, chunk(500) tránh OOM.
- **Artisan commands**:
  - `export:season {season_code} --type=bets|ledger|leaderboard|all` — export CSV ra `storage/exports/`
  - `import:wc2026 --file=path --season=WC2026 --dry-run` — import lịch WC2026 idempotent (upsert by match_code)
- **60/60 tổng PASS** (không thêm test vì commands là I/O layer, audit là side-effect trong existing tests).

## v1.0.0 (MVP Production-Ready) 🚀
- **`spatie/laravel-backup` v9**: cài, publish config, cấu hình cho project.
  - Backup DB daily `02:00 AM` + cleanup weekly.
  - Exclude `storage/exports`, `storage/logs` khỏi file backup.
  - `BACKUP_NOTIFICATION_EMAIL` qua `.env`.
- **Scheduler mới** (`routes/console.php`):
  - `backup:run --only-db` → `dailyAt('02:00')`
  - `backup:clean` → `weekly()`
  - `snapshot:leaderboard WC2026` → `hourly()`
- **`SnapshotLeaderboardCommand`** — Artisan command `snapshot:leaderboard {season_code}`.
- **`AppServiceProvider` Security Hardening**:
  - Force HTTPS trong production (`URL::forceScheme('https')`)
  - Disable DB query log trong production
  - Model strict mode trong dev/test
- **`.env.production.example`** — template cấu hình production đầy đủ.
- **`docs/SECURITY_CHECKLIST.md`** — checklist 7 mục: env, auth, DB, audit, integrity, legal, pre-launch.
- **`docs/UAT_GOLIVE_CHECKLIST.md`** — 6 UAT test cases (happy path, void, concurrent, stake limit, settlement matrix, correction) + deploy sequence + rollback plan.
- **60/60 tổng PASS** — tất cả domain tests không bị ảnh hưởng.

---
### Phase 1 MVP: COMPLETE ✅
**Deliverables đã đạt:**
- ✅ User đặt được dự đoán (BetPlacementService, 14 bước)
- ✅ Market tự khóa đúng giờ (MarketLockService, everyMinute)
- ✅ Admin settle được, ví cộng/trừ đúng (SettlementEngine, 3 calculators)
- ✅ Leaderboard cập nhật sau settlement (LeaderboardService + Queue job)
- ✅ Toàn bộ có ledger và audit log (WalletLedger + AuditLogService)
- ✅ `php artisan test` 60/60 PASS (settlement matrix đầy đủ)

## v1.1.0 & v1.2.0 (WP-1 & WP-2 - Player Panel UI/UX) - Hoàn tất
- **`MyBetsPage`**: Trang danh sách phiếu cược của người chơi với 4 tabs (Tất cả, Chưa mở thưởng, Đã mở thưởng, Đã hủy). Tích hợp UI Bet Card với trạng thái màu sắc chuẩn.
- **`PlaceBetModal`**: Cập nhật luồng trải nghiệm sau khi đặt cược thành công có Notification điều hướng nhanh đến trang Phiếu của tôi.

## v1.3.0 (WP-3 - Admin Panel Hoàn Thiện) - Hoàn tất
- **`UserResource`**: Bổ sung Custom Actions để cấp lá (`WalletService::grant()`), trừ lá (`WalletService::deduct()`) kèm Audit log, Reset mật khẩu, và khoá tài khoản.
- **`SeasonResource`**: Bổ sung Bulk Action "Seed Wallets" để cấp ví và lá khởi đầu tự động cho tất cả người chơi trong mùa giải mới. Đã fix lỗi type hint PHP 8.2 đối với properties của Filament Resource.
- **`BetResource`**: Màn hình xem toàn bộ phiếu dự đoán của hệ thống (Read-only) với tính năng lọc chi tiết và Bulk Action "Export CSV" tải trực tiếp.
- **`MarketResource`**: Bổ sung trọn vẹn workflow Settlement:
  - Action **Nhập kết quả**: form cập nhật `MatchPeriodResult` cho từng trận đấu.
  - Action **Preview Settlement**: Notification bảng tóm tắt preview payout / win / lose trước khi execute.
  - Action **Execute Settlement**: Form gõ "EXECUTE" xác nhận để gọi `SettlementEngine::execute()`.
- **`SettlementResource`**: Màn hình xem lại lịch sử các đợt mở thưởng. Infostlist (view detail) chia 2 Section: thông tin tổng quan và RepeatableEntry breakdown từng phiếu.
- **`AdminDashboardWidgets`**:
  - `AdminStatsOverviewWidget`: Thống kê người chơi, tổng lá, kèo đang mở, kèo chờ mở thưởng.
  - `AdminLeaderboardWidget`: Bảng xếp hạng Top 10 mùa hiện tại.
  - `AdminRecentAuditLogsWidget`: 10 nhật ký Audit log gần nhất.
- **`AuditLogResource`**: Xem toàn bộ lịch sử `activity_log` (Spatie Activitylog) với bộ lọc mạnh mẽ.
- **`AppSettingsPage`**: Trang cấu hình ứng dụng (`spatie/laravel-settings`) cho phép chỉnh tên app, số tiền cược min/max, lá mặc định ban đầu...

## v1.3.5 (WP-3.5 - Football-Data API Integration) - Hoàn tất
- **`FootballDataApiService`**: Tích hợp API Client kết nối với `football-data.org`, lấy thông tin lịch thi đấu và tỉ số trực tiếp qua `ApiSettings`.
- **`MatchSyncService`**:
  - `syncSchedules()`: Tự động mapping 104 trận đấu có sẵn sang ID của API (api_id) dựa trên tên đội (Morocco FC -> Morocco) và thời gian diễn ra chênh lệch dưới 24h.
  - `syncLiveScores()`: Cập nhật tỉ số trực tiếp và tự động tạo `MatchPeriodResult` trạng thái `CONFIRMED` cho hiệp 1, cả trận khi trận đấu hoàn thành, tự động khóa kèo (Market LOCKED).
  - Xử lý trận đấu bị hoãn/hủy (`POSTPONED`/`CANCELLED`), tự động Void các market liên quan và hoàn trả lá cho người chơi (`WalletService::voidBet`).
- **`SyncScheduledMatchesJob` & `SyncLiveMatchScoresJob`**: Đưa các tác vụ đồng bộ lịch và tỉ số live vào background queue. SyncLiveMatchScoresJob có guard tối ưu chỉ truy vấn khi có trận đấu đang hoặc sắp đá.
- **Scheduler**: Đăng ký đồng bộ lịch hằng giờ và đồng bộ live score hằng phút. Chuyển đổi command `matches:update-status` (cũ) thành fallback (chỉ chạy khi tắt đồng bộ API).
- **64/64 tests PASS**: Bổ sung `MatchSyncServiceTest` bao phủ toàn bộ các case mapping, đồng bộ tỉ số live, auto-populate, khóa kèo, void/refund cược khi bị hủy. Sửa lỗi `MissingAttributeException` trong `ExactScoreSettlementCalculator`.

## v1.4.0 (WP-4 - RapidAPI Odds Integration) - Hoàn tất (Phase 1 tới 3.3)
- **Phase 1: Foundation & Cấu hình:**
  - Bổ sung cấu hình `ApiSettings` (`rapidapi_key`, `rapidapi_host`, `bookmaker_id`).
  - Khởi tạo các DTOs chuẩn hóa dữ liệu: `OddsResponseDto`, `MarketDataDto`, `OutcomeDataDto`.
- **Phase 2: Core Domain Services:**
  - Xây dựng `OddsIntegrationService` gọi HTTP Client tới RapidAPI (`/v3/odds`).
  - Tích hợp `MarketSyncService` tuân thủ Snapshot Rule và Whitelist.
  - Implement logic tính tỷ lệ Việt Nam: `round($odd - 1, 3)` và kiểm soát nghiêm ngặt line hợp lệ.
- **Phase 3: Tự động hóa (Cronjobs & Event Triggers):**
  - Khởi tạo migration thêm `odds_fetch_status` JSON vào bảng `matches` nhằm khóa vĩnh viễn request lặp, bảo vệ Quota.
  - Xây dựng `SyncPreMatchOddsJob` tự động kéo tỷ lệ trước 12 tiếng. Đã cấu hình scheduler hourly.
  - Phát hành sự kiện `MatchStatusChanged` và tạo `FetchLiveOddsListener` kích hoạt kéo odds Live khi có trạng thái `HALFTIME`, `REGULAR_TIME_FINISHED` hoặc `EXTRA_TIME_FINISHED`.
  - Bổ sung `MarketStatusTransitionListener` tự động ĐÓNG KÈO (Lock) chính xác theo period (Hiệp 1, Hiệp 2, Hiệp phụ, Pen) khi trận đấu chuyển trạng thái.
- **Phase 4: UI/UX & Fallback (Giao diện Admin & Xử lý lỗi):**
  - Thêm Custom Action "Đồng bộ Kèo API" trực tiếp trên `FootballMatchResource` để Admin có thể kéo cược thủ công khi cần.
  - Tích hợp Spatie Activitylog ghi nhận sự kiện `API_RATE_LIMIT` (cảnh báo 429) vào Activity Log nhằm xử lý rủi ro vượt Quota.
- **Phase 5.1: Unit Tests:**
  - Bổ sung `MarketSyncServiceTest` với reflection để kiểm thử logic private.
  - Test kiểm tra thuật toán tính tỷ lệ Việt Nam: `profit_rate = odd - 1`.
  - Test kiểm tra thuật toán chuẩn hoá giá trị line dị (vd: `3.1` thành `3.0`, `2.2` thành `2.25`) với quy tắc `round($line * 4) / 4`.
- **Phase 5.2: Integration Tests:**
  - Cấu hình HTTP Mock Fake để chặn call thực tế tới RapidAPI trong test.
  - Xây dựng `SyncPreMatchOddsJobTest`, tự động sinh giả lập trận đấu (kickoff_at trước 12 tiếng) và mock payload `API-Football`.
  - Verify toàn vẹn luồng kéo Odds: tạo được `Market` (từ `OPEN`), tạo `MarketOutcome` với đầy đủ params chính xác (home/away, line_value, profit_rate).
  - Test xác nhận cờ `pre_match` được ghi vào `odds_fetch_status` trên `FootballMatch`.

## v1.5.0 (WP-5 - Achievement Engine) - Hoàn tất (Phase 2)
- **Database**: Tạo migration bảng `achievements` (`code`, `name`, `description`, `icon`, `color`, `is_repeatable`, `cooldown_period`, vv) và `user_achievements`.
- **Domain Service**: Xây dựng `AchievementService::checkAndAward()` xử lý toàn bộ các huy hiệu chính và phụ. Đã tích hợp logic tính toán Streak, ROI, High Roller, v.v.
- **Workflow**: Bắt event `BetPlaced`, `SettlementCompleted` -> Gọi service tính toán điều kiện -> Insert DB và trigger Notification (kèm popup Confetti).

## v1.6.0 (WP-6 - Mission Engine) - Hoàn tất (Phase 2)
- **Hệ thống nhiệm vụ ngắn hạn**: Giúp người chơi tương tác đều đặn, văn minh (không thưởng `lá`, chỉ thưởng tiến độ hoặc badge để tránh lạm phát).
- **Database**:
  - Bảng `missions`: `code`, `title`, `description`, `type` (enum: daily, weekly, round, season), `target_value` (int), `start_at`, `end_at`, `reward_achievement_id` (nullable, FK tới achievements).
  - Bảng `user_missions`: `user_id`, `mission_id`, `current_value`, `is_completed`, `completed_at`.
- **MissionSeeder**: Khởi tạo danh sách nhiệm vụ từ dễ đến khó (DAILY_ONE_BET, WEEKLY_ACTIVE_PLAYER, WEEKLY_MARKET_EXPLORER, SMART_STAKE, KNOCKOUT_PARTICIPANT...).
- **MissionService**: 
  - `trackProgress(int $userId, string $action, int $value = 1, array $context = [])`: Xử lý tăng tiến độ nhiệm vụ dựa vào hành động.
  - `evaluateDailyMissions()`, `evaluateWeeklyMissions()`: Đánh giá và reset tiến độ định kỳ.
  - `completeMission(int $userId, int $missionId)`: Đóng gói tiến độ, nếu có `reward_achievement_id` thì trao trực tiếp Huy hiệu tương ứng.
- **Jobs & Commands**: 
  - `EvaluateMissionsJob` chạy bất đồng bộ sau khi cược hoặc mở thưởng để cập nhật tiến độ.
  - Console command `app:evaluate-missions` và `missions:rotate-weekly` (xoay vòng ngẫu nhiên 5 nhiệm vụ tuần từ danh sách 10 nhiệm vụ cố định cho toàn bộ player).

## v1.7.0 (WP-7 - Notification System) - Hoàn tất (Phase 2)
- **System**: Cấu hình Laravel Notifications (driver `database`). Fix lỗi định tuyến (từ đường dẫn tuyệt đối sang tương đối).
- **Domain Service**: Xây dựng `NotificationService` tích hợp đầy đủ các hàm: `notifyMarketClosingSoon()`, `notifyBetSettled()`, `notifyAchievementUnlocked()`, `notifyWalletGranted()`, `notifyMarketVoided()`, `notifySettlementCorrected()`, `notifyLeaderboardUpdated()`.
- **Workflow**: 
  - Đã tích hợp `->markAsRead()` vào toàn bộ Action button.
  - Tự động khóa & thông báo kèo sắp đóng bằng cronjob `notify:closing-soon` chạy mỗi phút (báo trước 30p).
  - Tự động gửi thông báo Leaderboard bằng cronjob `notify:leaderboard` chạy hằng ngày và hằng tuần.
  - Tích hợp Filament Database Notifications hiển thị realtime trên Header kèm hiệu ứng popup `canvas-confetti` cực mượt.

## v1.8.0 (WP-8 - Settlement Correction Workflow) - Hoàn tất (Phase 2)
- **Database**: Migration và Model `SettlementCorrection` để lưu lại kết quả cũ, kết quả mới và lý do sửa kết quả nhằm mục đích audit.
- **Domain Service**: Cập nhật `CorrectionService` thực thi logic tái tính toán kết quả (Settle), gọi ngược lại `WalletService` để cập nhật chênh lệch cho Ví (Wallet) mà không làm mất trạng thái lịch sử. Ghi Wallet Ledger `SETTLEMENT_CORRECTION` và Audit Log rõ ràng.
- **UI/UX (Admin)**: Bổ sung Action "Sửa kết quả" trên trang View Settlement, hiển thị trực quan thông tin chênh lệch trước khi Confirm.
- **UI/UX (Player)**: Cập nhật giao diện trang Phiếu dự đoán (My Bets):
  - Bổ sung bộ lọc tab "Đã điều chỉnh" (Corrected).
  - Việt hóa tự động (Home/Away/Over/Under -> Đội nhà, Đội khách, Tài, Xỉu) và gắn thêm tên đội tuyển chính xác vào phiếu cược.
- **Testing**: Bổ sung `CorrectionServiceTest` cover hoàn toàn logic Settle lỗi -> Điều chỉnh kết quả -> Update Ví & Bet.
