# Review Bảo mật & Hiệu suất - Phase 2

Dựa trên OWASP, Laravel Security, Postgres Best Practices.

## 1. Achievement & Mission Engine (v1.5.0 - v1.6.0)
- **Hiệu suất (DB Load):** `CheckAchievementJob` & `EvaluateMissionsJob` chạy liên tục sau Bet/Settlement. Việc COUNT/SUM trực tiếp `bets` table liên tục sẽ gây nghẽn.
  - *Giải pháp:* Tạo composite index `bets(user_id, status, market_type)`. Dùng Redis counter cho Mission progress. Cache kết quả tổng hợp (ROI, Win rate) thay vì query realtime.
- **Bảo mật (Race Condition):** Tránh trao nhầm 2 lần cùng 1 huy hiệu khi user click nhanh hoặc job chạy song song.
  - *Giải pháp:* Bắt buộc dùng DB Transaction với `lockForUpdate()` khi insert `user_achievements` hoặc thêm Unique Constraint `(user_id, achievement_id)`.

## 2. Notification System (v1.7.0)
- **Hiệu suất (N+1 Query & Polling):** Cronjob `notify:closing-soon` chạy mỗi phút để quét `markets`. Send database notification bằng vòng lặp gây N+1 insert.
  - *Giải pháp:* Thêm index `markets(status, close_at)`. Dùng `insert()` batch (mass insert) cho bảng `notifications` thay vì `Notification::send()` qua vòng lặp.
- **Bảo mật (IDOR):**
  - *Giải pháp:* Bắt buộc kiểm tra quyền sở hữu kênh Broadcast. Payload notification không chứa dữ liệu nhạy cảm (ID nội bộ, API keys).

## 3. Settlement Correction Workflow (v1.8.0)
- **Bảo mật (Toàn vẹn Dữ liệu):** Sửa đổi số dư ví cực kỳ nhạy cảm.
  - *Giải pháp:* Bắt buộc verify quyền Admin/Manager qua Policy trước khi run `CorrectionService`. Sử dụng DB Transaction với Row-level lock (`lockForUpdate()`) trên record `wallets`. Có CHECK constraint trên DB đảm bảo `available_balance >= 0`.
- **Hiệu suất (Long Transaction):** Điều chỉnh kết quả của 10,000 vé sẽ gây lock ví quá lâu.
  - *Giải pháp:* Chunking update (VD: 500 vé/lần) hoặc xử lý bất đồng bộ qua queue theo từng nhóm user, tránh DB deadlock.

## 4. Dashboard Analytics & Pro-Max UI (v1.9.0)
- **Hiệu suất (Slow Queries):** Tính toán `PlayerProfitChartWidget` và `PlayerCombinedStatsWidget` (Win rate, ROI, Streak) lúc load page sẽ gây lag.
  - *Giải pháp:* Phải sử dụng Projection (`user_statistics` table) được cập nhật bằng async job. Bắt buộc gắn Redis cache (TTL 5-15 phút) cho các query Widget trên Dashboard.
- **Bảo mật (Data Exposure):**
  - *Giải pháp:* Ràng buộc chặt `UserStatisticsService` chỉ query đúng `auth()->id()`. Chống IDOR triệt để ở API trả data cho chart.
