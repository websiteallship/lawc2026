---
title: "Dự Đoán Lá Documentation Index"
project: "Dự Đoán Lá"
version: "1.1"
status: "draft"
last_updated: "2026-06-11"
---

# Dự Đoán Lá — Documentation Index

Bộ tài liệu này dùng để import trực tiếp vào repo dưới thư mục `docs/`.

Dự án là hệ thống **dự đoán bóng đá nội bộ bằng điểm ảo “lá”**, không nạp tiền, không rút tiền, không quy đổi lá thành tiền/hiện vật/dịch vụ.

## Quy ước tỷ lệ ăn kiểu Việt Nam

Tài liệu dùng cách ghi phổ biến:

```text
Brazil -0.5 ăn 0.90
Tài 2.5 ăn 0.90
Tỉ số 2-1 ăn 7.00
```

Trong hệ thống:

```text
profit_rate = tỷ lệ ăn
full_win_gross_payout = stake * (1 + profit_rate)
```

Ví dụ:

```text
Đặt 100 lá, ăn 0.90
Thắng đủ: nhận 190 lá = 100 vốn + 90 lãi
Thua: nhận 0 lá
Push: nhận 100 lá
Half win: nhận 145 lá
Half lose: nhận 50 lá
```

---

## Cấu trúc tài liệu

```text
docs/
  00_README.md
  MANIFEST.md
  26_DOCUMENTATION_GAP_ANALYSIS.md

  product/
    01_PRD.md
    02_LEGAL_COMPLIANCE_POLICY.md
    10_USER_GUIDE_THE_LE.md
    11_MVP_USER_ADMIN_FLOWS.md

  requirements/
    03_SRS.md
    06_PERMISSION_MATRIX.md
    07_UI_UX_WIREFRAME.md

  technical/
    04_ERD_DATABASE_DESIGN.md
    05_SETTLEMENT_ENGINE_SPECIFICATION.md
    11_DEV_SETUP_GUIDE.md
    12_DEPLOYMENT_INFRASTRUCTURE.md
    13_SECURITY_CHECKLIST.md
    16_API_SPECIFICATION.md
    17_DOMAIN_SERVICE_DESIGN.md
    18_ERROR_CODES_AND_MESSAGES.md
    19_OBSERVABILITY_LOGGING_MONITORING.md
    21_TECH_DECISIONS.md
    22_ARCHITECTURE_DECISION_RECORDS.md
    23_THREAT_MODEL.md

  operation/
    08_TEST_PLAN.md
    09_ADMIN_OPERATION_MANUAL.md
    14_DATA_IMPORT_TEMPLATES.md
    15_SEED_DATA_SPECIFICATION.md
    20_CHANGELOG_RELEASE_PLAN.md
    21_CORRECTION_OPERATION_POLICY.md
    22_VPS_DEPLOYMENT_RUNBOOK.md
    23_WC2026_FILE_IMPORT_POLICY.md
```

---

## Thứ tự đọc đề xuất

### Cho product/owner

1. `product/01_PRD.md`
2. `product/02_LEGAL_COMPLIANCE_POLICY.md`
3. `product/10_USER_GUIDE_THE_LE.md`
4. `requirements/07_UI_UX_WIREFRAME.md`

### Cho developer

1. `technical/21_TECH_DECISIONS.md`
2. `technical/22_ARCHITECTURE_DECISION_RECORDS.md`
3. `requirements/03_SRS.md`
4. `technical/04_ERD_DATABASE_DESIGN.md`
5. `technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md`
6. `technical/17_DOMAIN_SERVICE_DESIGN.md`
7. `technical/11_DEV_SETUP_GUIDE.md`
8. `operation/08_TEST_PLAN.md`

### Cho admin vận hành

1. `operation/09_ADMIN_OPERATION_MANUAL.md`
2. `operation/14_DATA_IMPORT_TEMPLATES.md`
3. `product/10_USER_GUIDE_THE_LE.md`
4. `technical/18_ERROR_CODES_AND_MESSAGES.md`

### Cho DevOps/Security

1. `technical/21_TECH_DECISIONS.md`
2. `operation/22_VPS_DEPLOYMENT_RUNBOOK.md`
3. `technical/12_DEPLOYMENT_INFRASTRUCTURE.md`
4. `technical/13_SECURITY_CHECKLIST.md`
5. `technical/23_THREAT_MODEL.md`
6. `technical/19_OBSERVABILITY_LOGGING_MONITORING.md`
7. `operation/20_CHANGELOG_RELEASE_PLAN.md`

---

## Danh sách tài liệu

| # | Tài liệu | Mục đích |
|---:|---|---|
| 01 | PRD | Mô tả sản phẩm, mục tiêu, phạm vi |
| 02 | Legal & Compliance Policy | Quy định điểm ảo, không quy đổi, wording an toàn |
| 03 | SRS | Đặc tả yêu cầu phần mềm |
| 04 | ERD / Database Design | Thiết kế database |
| 05 | Settlement Engine Specification | Logic tính tỉ số, handicap, tài/xỉu |
| 06 | Permission Matrix | Vai trò và quyền |
| 07 | UI/UX Wireframe | Màn hình và luồng thao tác |
| 08 | Test Plan | Kế hoạch kiểm thử |
| 09 | Admin Operation Manual | Hướng dẫn admin vận hành |
| 10 | User Guide / Thể lệ | Hướng dẫn người chơi |
| Product 11 | MVP User/Admin Flows | Luồng thao tác MVP cho player/admin |
| 11 | Dev Setup Guide | Hướng dẫn dev chạy local |
| 12 | Deployment & Infrastructure | Deploy, queue, scheduler, backup |
| 13 | Security Checklist | Bảo mật hệ thống |
| 14 | Data Import Templates | Mẫu CSV/XLSX import |
| 15 | Seed Data Specification | Dữ liệu mẫu cho dev/staging |
| 16 | API Specification | Chuẩn API nếu tách frontend |
| 17 | Domain Service Design | Thiết kế service nghiệp vụ |
| 18 | Error Codes and Messages | Chuẩn mã lỗi/thông báo |
| 19 | Observability/Logging/Monitoring | Log, metric, health check |
| 20 | Changelog & Release Plan | Versioning và release |
| 21 | Technical Decisions | Chốt version, stack, timezone, deployment target |
| 22 | Architecture Decision Records | ADR cho quyết định nhạy cảm |
| 23 | Threat Model | Threat model MVP và mitigation |
| Ops 21 | Correction Operation Policy | Chính sách correction sau settlement |
| Ops 22 | VPS Deployment Runbook | Runbook triển khai VPS |
| Ops 23 | WC2026 File Import Policy | Chính sách nhập lịch WC2026 từ file |

---

## Nguyên tắc tài liệu

```text
- Khi xung đột tài liệu, ưu tiên product/requirements/technical/operation.
- planning/ dùng để hiểu roadmap, không phải source of truth cuối cùng.
- reference/ dùng để tra cứu nghiên cứu, không override docs chuẩn.
- Nếu thay đổi nghiệp vụ settlement, cập nhật 05 và 08.
- Nếu thay đổi database, cập nhật 04.
- Nếu thay đổi quyền, cập nhật 06.
- Nếu thay đổi wording người dùng, cập nhật 10 và 07.
- Nếu thay đổi deploy/queue/scheduler, cập nhật 12 và 19.
- Nếu thay đổi quyết định kỹ thuật, cập nhật 21 và ADR 22.
```

---

## Trạng thái

Bộ tài liệu này là bản chuẩn hóa trước khi bắt đầu triển khai code. Các thông số cụ thể như version Laravel/Filament, domain nội bộ, email admin và thông tin server cần được khóa thêm trong repo hoặc file môi trường triển khai.

## Tài liệu planning/reference đã tích hợp thêm

Các tài liệu nghiên cứu tạo ở giai đoạn đầu đã được đưa vào repo để tránh mất thông tin chi tiết:

```text
planning/
  21_ROADMAP_WORKFLOW_STAGE.md
  22_PHASE_1_MVP_DETAILED_SPEC.md
  23_PHASE_2_ENHANCEMENT_SPEC.md

reference/
  24_PROJECT_RESEARCH_OVERVIEW_WC2026.md
  25_LARAVEL_FILAMENT_TECH_STACK_RESEARCH.md

26_DOCUMENTATION_GAP_ANALYSIS.md
```

Cách dùng:

- Đọc `planning/21_ROADMAP_WORKFLOW_STAGE.md` để hiểu roadmap và workflow stage tổng thể.
- Đọc `planning/22_PHASE_1_MVP_DETAILED_SPEC.md` trước khi code MVP.
- Đọc `planning/23_PHASE_2_ENHANCEMENT_SPEC.md` trước khi mở rộng game hóa/leaderboard/notification.
- Đọc `reference/24_PROJECT_RESEARCH_OVERVIEW_WC2026.md` để xem nghiên cứu tổng quan ban đầu.
- Đọc `reference/25_LARAVEL_FILAMENT_TECH_STACK_RESEARCH.md` để xem nghiên cứu stack Laravel + Filament ban đầu.
- Đọc `26_DOCUMENTATION_GAP_ANALYSIS.md` để biết vì sao các file này được tích hợp.
