---
title: "Security & Performance Upgrade Plan"
project: "Dự Đoán Lá"
version: "1.0"
status: "draft"
last_updated: "2026-06-14"
---

# Kế hoạch nâng cấp Bảo mật & Hiệu suất

**Ngữ cảnh:** Hệ thống vận hành nội bộ với quy mô nhỏ (~20 người chơi), không yêu cầu 2FA. Kiến trúc ưu tiên tính chính xác tuyệt đối của dữ liệu (Data Integrity) hơn là xử lý tải cao.

---

## Phase 1: Core Data Integrity & Wallet Security (Ưu tiên Cao nhất)

*Đảm bảo không bao giờ sai lệch số dư, không âm ví, không double spend.*

1. **Transaction & Row Locking:**
   - Bọc toàn bộ các action liên quan đến tiền (đặt lá, settlement, cấp/trừ lá) bằng `DB::transaction()`.
   - Bắt buộc dùng `$wallet->lockForUpdate()` khi đọc số dư khả dụng trước khi trừ lá để chống race condition.

2. **Append-Only Wallet Ledger:**
   - Mọi biến động ví phải tạo 1 record trong `wallet_ledgers`.
   - Vô hiệu hóa tính năng xóa cứng (Hard Delete) trên các bảng: `wallets`, `wallet_ledgers`, `bets`, `settlements`. Sử dụng Soft Delete nếu cần thiết.

3. **Bet Snapshot Strategy:**
   - Khi đặt vé, lưu cứng (snapshot) các giá trị: `profit_rate`, `line`, `outcome_label` vào bảng `bets`.
   - Settlement đọc từ snapshot, không đọc từ `outcome` hiện tại để chống lỗi thay đổi tỷ lệ ăn sau khi đã đặt.

---

## Phase 2: Authorization & Auditing (Bảo mật Vận hành)

*Phân quyền chặt chẽ các thao tác Admin, lưu vết mọi hành động.*

1. **Role-Based Access Control (RBAC):**
   - Tích hợp `bezhansalleh/filament-shield`.
   - Tạo các role cơ bản: `Super Admin`.
   - Kiểm tra quyền trực tiếp tại Service/Controller, không chỉ ẩn nút trên UI.

2. **Activity Logging:**
   - Tích hợp `spatie/laravel-activitylog` vào Filament.
   - Bắt buộc ghi log (IP, User ID, Before/After) cho các hành động: Thay đổi tỷ lệ ăn, Nhập kết quả trận đấu, Void/Correction kèo.

3. **Data Isolation (Player View):**
   - Áp dụng Global Scope cho Player panel: User chỉ được query `bets` và `wallet_ledgers` thuộc `user_id` của chính mình.

---

## Phase 3: Settlement Idempotency & Queue Optimization

*Xử lý kết quả trận đấu tự động, an toàn và không bị trùng lặp.*

1. **Idempotency (Chống Double Payout):**
   - Thêm trạng thái `SETTLING` cho `markets`. Workflow: `OPEN` -> `LOCKED` -> `SETTLING` -> `SETTLED`.
   - Bọc toàn bộ block xử lý Settlement vào `DB::transaction` với thao tác lock bảng `markets`. Nếu trạng thái đang là `SETTLING` hoặc `SETTLED`, ném Exception (ngăn chặn chạy job 2 lần).

2. **Queue Optimization (Quy mô 20 users):**
   - Chạy Settlement trên nền background job (`ShouldQueue`).
   - Do lượng user nhỏ, không cần chunking phức tạp (1 job có thể xử lý mượt mà toàn bộ bet của 1 market cho 20 người).
   - Redis Cache là không bắt buộc, có thể dùng database queue driver để tiết kiệm tài nguyên hệ thống.

---

## Phase 4: Legal Compliance & UI Hardening (Bảo vệ pháp lý)

*Đảm bảo an toàn theo quy định nội bộ.*

1. **Wording & Terminology Check:**
   - Thay thế toàn bộ text frontend/backend:
     - `Cá cược`, `Nhà cái`, `Đặt cược` -> `Dự đoán`, `Hệ thống`.
     - `Tiền` -> `Điểm ảo`, `Lá`.
     - `Nạp/Rút` -> Xóa hoàn toàn khỏi UI và logic.

2. **Legal Disclaimer:**
   - Gắn cố định dòng cảnh báo pháp lý ở Footer hệ thống: *"Game nội bộ sử dụng điểm ảo giải trí, không có giá trị quy đổi thành tiền hay hiện vật"*.

3. **Basic Rate Limiting:**
   - Thêm `throttle` cho các route đăng nhập (`login`) và đặt dự đoán (`place-bet`) (Ví dụ: 30 requests/phút) để ngăn spam click.
