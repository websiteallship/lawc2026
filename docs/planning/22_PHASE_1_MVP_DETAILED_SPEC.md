---
title: 22 PHASE 1 MVP DETAILED SPEC
status: integrated
source: earlier-research-document
updated: 2026-06-12
---

# Giai đoạn 1 – MVP: Đặc tả nghiệp vụ, rules và workflow triển khai cho dự án “Lá Dự Đoán”


## Quy ước tỷ lệ ăn kiểu Việt Nam

Tài liệu này dùng cách ghi phổ biến ở Việt Nam: **line + ăn + hệ số lãi**. Ví dụ `Home -0.5 ăn 0.90` nghĩa là đặt 100 lá, thắng đủ nhận 190 lá, gồm 100 lá vốn + 90 lá lãi.

Quy ước kỹ thuật:

```text
profit_rate = tỷ lệ ăn / hệ số lãi
decimal_odds = 1 + profit_rate
gross_payout_full_win = stake * (1 + profit_rate)
net_profit_full_win = stake * profit_rate
```


> Phiên bản: 1.0  
> Ngày: 2026-06-11  
> Stack định hướng: Laravel + Filament + PostgreSQL + Redis  
> Phạm vi: Game dự đoán bóng đá nội bộ bằng điểm ảo “lá”, cố định theo lịch World Cup 2026, không nạp tiền, không rút tiền, không quy đổi lá thành tiền/hiện vật.

---

## 1. Mục tiêu của tài liệu

Tài liệu này mô tả chi tiết **Giai đoạn 1 – MVP** của hệ thống “Lá Dự Đoán”. Trọng tâm của giai đoạn này là xây dựng một phiên bản đủ chắc để chạy nội bộ công ty, ưu tiên đúng nghiệp vụ, đúng luật tính điểm, hạn chế lỗi settlement và có thể audit lại toàn bộ quá trình.

Tài liệu tập trung vào:

- Phạm vi MVP nên làm và chưa nên làm.
- Rules khả thi, phổ biến và dễ vận hành nhất.
- Workflow chi tiết cho admin, user và hệ thống.
- Logic nghiệp vụ cho:
  - Ví lá.
  - Đặt dự đoán.
  - Tỉ số chính xác.
  - Kèo châu Á.
  - Kèo tài/xỉu.
  - Các mốc thời gian: cả trận, hiệp 1, hiệp 2, hiệp phụ, penalty.
- Database tối thiểu cho giai đoạn 1.
- Service/domain logic cần tự code.
- Checklist test case bắt buộc.
- Các bước đầu để bắt đầu dự án Laravel + Filament.

---

## 2. Định nghĩa Giai đoạn 1

### 2.1. Giai đoạn 1 là gì?

Giai đoạn 1 là bản MVP có thể dùng thật trong nội bộ công ty với các chức năng cốt lõi:

```text
Admin tạo/cấu hình trận đấu theo lịch WC2026
Admin tạo market và nhập tỉ lệ
User đăng nhập và dùng lá để dự đoán
Hệ thống khóa market theo thời gian
Admin nhập kết quả
Hệ thống preview settlement
Admin xác nhận settlement
Hệ thống cộng/trừ lá và cập nhật leaderboard
Toàn bộ thay đổi có ledger/audit log
```

### 2.2. Mục tiêu kinh doanh của giai đoạn 1

| Mục tiêu | Diễn giải |
|---|---|
| Chạy được nội bộ | Nhân viên có tài khoản, có lá, có thể dự đoán các trận WC2026 |
| Ít lỗi nghiệp vụ | Luật tính lá rõ, có test case, không sửa dữ liệu âm thầm |
| Dễ vận hành | Admin nhập lịch, tỷ lệ ăn, kết quả qua Filament |
| An toàn pháp lý hơn | Dùng điểm ảo, không nạp/rút/đổi thưởng, tránh wording “cá cược” trên UI |
| Có bảng thành tích | User có động lực chơi qua leaderboard cá nhân/phòng ban |
| Có khả năng mở rộng | Giai đoạn sau thêm realtime, notification, badge, dashboard nâng cao |

---

## 3. Phạm vi Giai đoạn 1

### 3.1. Chức năng bắt buộc có

| Nhóm | Chức năng | Mức độ |
|---|---|---|
| Auth | Login/logout | Bắt buộc |
| User | Admin tạo user, gán phòng ban, reset password | Bắt buộc |
| Role | Super Admin, Operator, Settlement Manager, Auditor, User | Bắt buộc |
| Ví lá | Cấp lá, trừ lá, khóa lá khi đặt, hoàn lá, cộng lá khi thắng | Bắt buộc |
| Ledger | Ghi mọi biến động ví | Bắt buộc |
| Lịch trận | Seed lịch WC2026 cố định | Bắt buộc |
| Match | Quản lý trận, giờ đá, trạng thái | Bắt buộc |
| Market | Tạo market theo trận và mốc thời gian | Bắt buộc |
| Tỷ lệ ăn | Nhập tỉ lệ cho từng cửa dự đoán | Bắt buộc |
| Bet | User đặt lá trước giờ đóng | Bắt buộc |
| Lock | Tự khóa market khi quá giờ | Bắt buộc |
| Result | Admin nhập kết quả từng mốc | Bắt buộc |
| Settlement | Preview trước, xác nhận sau | Bắt buộc |
| Leaderboard | Xếp hạng theo tổng lá/lãi ròng | Bắt buộc |
| Audit log | Ghi thao tác admin/user quan trọng | Bắt buộc |
| Export | Xuất CSV cơ bản cho bets, ledger, leaderboard | Nên có |

### 3.2. Chức năng chưa làm trong Giai đoạn 1

Các phần sau không nên đưa vào MVP để tránh phức tạp:

| Chức năng | Lý do chưa làm |
|---|---|
| Live betting | Dễ lỗi, cần realtime tỷ lệ ăn và khóa theo event |
| Cash-out | Phức tạp và dễ tạo hiểu nhầm như cá cược thật |
| Chuyển lá giữa user | Tăng rủi ro gian lận và rủi ro pháp lý |
| Nạp/rút/đổi thưởng | Không phù hợp định vị điểm ảo nội bộ |
| Tỷ lệ ăn tự động từ API bookmaker | Không cần thiết, admin tự nhập là đủ |
| Multi-currency | Chỉ dùng “lá” |
| App mobile riêng | Web responsive là đủ |
| Chat/comment | Không liên quan logic cốt lõi |
| Referral, nhiệm vụ nhận lá | Để phase game hóa sau |
| Auto-generate tỷ lệ ăn | Không cần trong MVP |
| Realtime dashboard phức tạp | Có thể làm sau bằng Reverb/WebSocket |

---

## 4. Nguyên tắc sản phẩm và pháp lý nội bộ

### 4.1. Định vị bắt buộc

Sản phẩm phải được định vị là:

> Hệ thống game dự đoán bóng đá nội bộ bằng điểm ảo “lá”, phục vụ hoạt động gắn kết nhân viên.

Không định vị là:

> Website cá cược bóng đá.

### 4.2. Rules an toàn cần khóa trong hệ thống

| Rule | Cách triển khai |
|---|---|
| Không nạp tiền | Không có màn hình payment, không có giao dịch mua lá |
| Không rút tiền | Không có chức năng withdrawal |
| Không quy đổi lá | Trang thể lệ ghi rõ lá không có giá trị quy đổi |
| Không chuyển lá | Không có transfer giữa user |
| Không public | Chặn đăng ký tự do, chỉ admin tạo account |
| Không user ngoài công ty | User phải thuộc department/company domain |
| Không sửa dữ liệu âm thầm | Dùng audit log và correction transaction |
| Không xóa ledger | Ledger chỉ append-only |

### 4.3. Từ ngữ nên dùng trên UI

| Từ nên tránh | Từ nên dùng |
|---|---|
| Cá cược | Dự đoán |
| Cược | Lá tham gia |
| Kèo | Cửa dự đoán / dòng dự đoán |
| Nhà cái | Ban tổ chức |
| Ăn kèo | Dự đoán đúng |
| Thua kèo | Dự đoán sai |
| Tiền cược | Số lá |
| Rút thưởng | Xếp hạng / vinh danh |

### 4.4. Từ kỹ thuật trong code

Trong code/backend có thể dùng thuật ngữ chuẩn để dễ dev:

```text
bet
market
tỷ lệ ăn
settlement
payout
stake
ledger
```

Tuy nhiên trên giao diện user nên dùng ngôn ngữ nhẹ hơn:

```text
dự đoán
cửa dự đoán
lá tham gia
kết quả xử lý
lá nhận về
```

---

## 5. Rules mặc định đề xuất cho Giai đoạn 1

Đây là bộ rules khả thi, phổ biến và dễ vận hành nhất cho MVP.

### 5.1. Rules về lá

| Cấu hình | Giá trị đề xuất | Ghi chú |
|---|---:|---|
| Lá khởi tạo/user | 1.000 lá | Admin có thể cấu hình |
| Số lá tối thiểu/vé | 10 lá | Tránh spam vé quá nhỏ |
| Số lá tối đa/vé | 200 lá | Giảm rủi ro all-in |
| Số lá tối đa/trận/user | 500 lá | Tính tổng mọi market của trận |
| Số lá tối đa/ngày/user | 1.000 lá | Tính theo ngày đặt vé |
| Cho phép số dư âm | Không | Bắt buộc |
| Cho phép chuyển lá | Không | Bắt buộc |
| Cho phép hủy vé sau khi đặt | Không | Đơn giản, tránh tranh chấp |
| Cho phép sửa vé sau khi đặt | Không | Đặt sai thì tạo vé mới nếu còn thời gian |
| Cho phép đặt nhiều vé cùng market | Có | Nhưng chịu giới hạn theo trận/ngày |

### 5.2. Đơn vị làm tròn

Khuyến nghị:

```text
User nhập stake bằng số nguyên lá.
Backend lưu balance bằng đơn vị nhỏ: leaf_unit.
1 lá = 100 leaf_unit.
Payout được tính chính xác đến 0.01 lá.
UI hiển thị tối đa 2 chữ số thập phân.
```

Lý do:

- Tỷ lệ ăn kiểu Việt Nam như 0.85, 0.90, 1.05 có thể tạo payout lẻ.
- Kèo 0.25/0.75 cần chia stake làm 2 phần.
- Lưu integer unit giúp tránh sai số float.

Ví dụ:

```text
100 lá = 10.000 leaf_unit
Ăn 0.90
Payout = 100 × (1 + 0.90) = 190 lá = 19.000 leaf_unit
```

### 5.3. Rule stake cho kèo quarter line

Kèo quarter line gồm:

```text
-0.25, +0.25, -0.75, +0.75
2.25, 2.75, 3.25, 3.75...
```

Các kèo này sẽ chia stake thành 2 phần bằng nhau. Vì backend lưu leaf_unit nên không cần bắt stake phải chẵn, nhưng UI nên khuyến nghị nhập số nguyên lá.

Ví dụ:

```text
Stake 101 lá ở kèo tài 2.25
Backend xử lý:
50.50 lá ở tài 2.0
50.50 lá ở tài 2.5
```

---

## 6. Vai trò và quyền hạn Giai đoạn 1

### 6.1. Super Admin

Có toàn quyền:

- Tạo/sửa/xóa user trước khi phát sinh dữ liệu.
- Gán role.
- Cấp/trừ lá.
- Quản lý mùa giải.
- Quản lý trận.
- Quản lý market.
- Nhập tỷ lệ ăn.
- Nhập kết quả.
- Preview settlement.
- Execute settlement.
- Void market.
- Correction sau settlement.
- Xem audit log.
- Export báo cáo.
- Cập nhật system settings.

### 6.2. Operator

Phụ trách vận hành trước và trong trận:

- Tạo/sửa trận.
- Tạo/sửa market khi chưa có vé.
- Nhập tỷ lệ ăn.
- Publish market.
- Lock market.
- Nhập kết quả nháp.
- Không được execute settlement nếu chưa được phân quyền.
- Không được cấp/trừ lá user.

### 6.3. Settlement Manager

Phụ trách xác nhận kết quả và settlement:

- Xem settlement preview.
- Xác nhận execute settlement.
- Void market nếu có lỗi, cần ghi lý do.
- Correction settlement nếu nhập sai kết quả.
- Không được tự ý sửa tỷ lệ ăn đã có vé.

### 6.4. Auditor

Chỉ xem:

- Bets.
- Wallet ledger.
- Settlement history.
- Audit log.
- Leaderboard.
- Export báo cáo.

Không được thay đổi dữ liệu.

### 6.5. User

Có quyền:

- Xem lịch trận.
- Xem market đang mở.
- Đặt dự đoán bằng lá.
- Xem vé của mình.
- Xem ví lá.
- Xem lịch sử ví.
- Xem bảng xếp hạng.
- Xem thể lệ.

---

## 7. Các entity nghiệp vụ cốt lõi

### 7.1. Season

Đại diện cho một mùa/sự kiện lớn.

Ví dụ:

```text
World Cup 2026
```

Trường chính:

```text
id
name
code
starts_at
ends_at
status
starting_leaves
rules_json
created_at
updated_at
```

Status:

| Status | Ý nghĩa |
|---|---|
| draft | Đang chuẩn bị |
| active | Đang chạy |
| completed | Đã kết thúc |
| archived | Lưu trữ |

### 7.2. Match

Đại diện cho một trận đấu.

Trường chính:

```text
id
season_id
match_no
stage
group_code
home_team_name
away_team_name
home_team_code
away_team_code
venue
city
kickoff_at
status
home_score_ft
away_score_ft
home_score_ht
away_score_ht
home_score_et
away_score_et
home_score_pen
away_score_pen
created_at
updated_at
```

Status:

| Status | Ý nghĩa |
|---|---|
| scheduled | Đã lên lịch |
| live | Đang diễn ra |
| finished | Đã kết thúc |
| postponed | Hoãn |
| cancelled | Hủy |
| settled | Đã xử lý toàn bộ market |

### 7.3. Period

Mốc thời gian để tính kết quả.

Các period trong Giai đoạn 1:

| Code | Tên | Rule tính kết quả |
|---|---|---|
| FULL_TIME | Cả trận 90 phút | Tính 90 phút + bù giờ, không gồm hiệp phụ/penalty |
| FIRST_HALF | Hiệp 1 | Tính hiệp 1 + bù giờ hiệp 1 |
| SECOND_HALF | Hiệp 2 | Chỉ tính bàn trong hiệp 2 |
| EXTRA_TIME | Hiệp phụ | Chỉ tính bàn trong 30 phút hiệp phụ |
| PENALTY | Penalty | Chỉ tính loạt luân lưu |

### 7.4. Market

Market là một nhóm cửa dự đoán cho một trận và một period.

Ví dụ:

```text
Match 001: Argentina vs Spain
Period: FULL_TIME
Market Type: ASIAN_HANDICAP
```

Trường chính:

```text
id
match_id
period_code
market_type
name
open_at
close_at
status
is_manual_lock
created_by
published_at
locked_at
settled_at
voided_at
void_reason
```

Status:

| Status | Ý nghĩa |
|---|---|
| draft | Admin đang chuẩn bị |
| open | User được đặt |
| locked | Đã khóa, không nhận thêm dự đoán |
| result_entered | Đã nhập kết quả |
| settlement_previewed | Đã preview settlement |
| settled | Đã xử lý lá |
| voided | Hủy market, hoàn lá |

### 7.5. Market Outcome

Outcome là từng cửa cụ thể trong một market.

Ví dụ:

```text
Tỉ số 1-0 @ 6.50
Argentina -0.5 ăn 0.90
Spain +0.5 ăn 0.90
Tài 2.5 @ 1.95
Xỉu 2.5 ăn 0.85
```

Trường chính:

```text
id
market_id
outcome_type
label
team_side
score_home
score_away
handicap_line
total_line
over_under_side
tỷ lệ ăn
status
sort_order
created_at
updated_at
```

### 7.6. Bet

Bet là một vé dự đoán của user.

Trường chính:

```text
id
user_id
season_id
match_id
market_id
outcome_id
stake_units
profit_rate_snapshot
line_snapshot
outcome_label_snapshot
status
potential_return_units
actual_return_units
profit_units
placed_at
settled_at
voided_at
```

Status:

| Status | Ý nghĩa |
|---|---|
| pending | Đã đặt, đang chờ kết quả |
| won | Thắng đủ |
| lost | Thua đủ |
| push | Hòa kèo, hoàn stake |
| half_won | Nửa thắng, nửa hoàn |
| half_lost | Nửa thua, nửa hoàn |
| voided | Market hủy, hoàn stake |
| corrected | Đã bị điều chỉnh bởi correction |

### 7.7. Wallet

Ví hiện tại của user.

Trường chính:

```text
id
user_id
season_id
available_units
locked_units
total_deposit_units
total_staked_units
total_returned_units
total_profit_units
created_at
updated_at
```

### 7.8. Wallet Ledger

Ledger là lịch sử bất biến của ví. Không được update/delete dòng ledger sau khi tạo.

Trường chính:

```text
id
wallet_id
user_id
season_id
bet_id
match_id
market_id
type
direction
amount_units
balance_available_after
balance_locked_after
meta_json
created_by
created_at
```

Types:

| Type | Ý nghĩa |
|---|---|
| ADMIN_GRANT | Admin cấp lá |
| ADMIN_DEDUCT | Admin trừ lá |
| BET_PLACED | User đặt lá, chuyển available sang locked |
| BET_WON | Vé thắng, cộng return |
| BET_LOST | Vé thua, xóa locked stake |
| BET_PUSH | Hòa kèo, hoàn stake |
| BET_HALF_WON | Nửa thắng nửa hoàn |
| BET_HALF_LOST | Nửa thua nửa hoàn |
| MARKET_VOID | Hủy market, hoàn stake |
| CORRECTION_DEBIT | Điều chỉnh trừ do sửa kết quả |
| CORRECTION_CREDIT | Điều chỉnh cộng do sửa kết quả |

---

## 8. Workflow tổng thể Giai đoạn 1

```text
[Setup Season]
    ↓
[Seed lịch WC2026]
    ↓
[Admin tạo market cho trận]
    ↓
[Admin nhập tỷ lệ ăn]
    ↓
[Publish market]
    ↓
[User đặt dự đoán]
    ↓
[Hệ thống khóa market theo close_at]
    ↓
[Admin nhập kết quả]
    ↓
[Hệ thống preview settlement]
    ↓
[Settlement Manager xác nhận]
    ↓
[Hệ thống xử lý ví lá]
    ↓
[Cập nhật leaderboard]
    ↓
[Audit/export]
```

---

## 9. Workflow chi tiết theo stage

## Stage 1.1 – Khởi tạo hệ thống

### Mục tiêu

Tạo nền tảng Laravel + Filament, phân quyền, user, season và cấu hình rules.

### Công việc

1. Tạo Laravel project.
2. Cài Filament.
3. Cài plugin phân quyền.
4. Tạo role mặc định.
5. Tạo model user/department.
6. Tạo season `WC2026`.
7. Tạo system settings.
8. Tạo seed Super Admin.
9. Tạo trang thể lệ nội bộ.

### Output

```text
Admin có thể login Filament
Super Admin có thể tạo user
User có ví lá theo season
Rules mặc định đã lưu trong settings
```

### Tiêu chí hoàn thành

- Super Admin login được.
- Tạo được ít nhất 1 department.
- Tạo được ít nhất 1 user.
- User được cấp ví mặc định.
- Có audit log khi cấp lá.

---

## Stage 1.2 – Seed lịch WC2026

### Mục tiêu

Nhập cố định lịch World Cup 2026 vào hệ thống để admin không phải tạo thủ công từng trận.

### Phương án phổ biến nhất

Giai đoạn 1 nên dùng **CSV seed cố định**, không gọi API bên ngoài.

Lý do:

- Dễ kiểm soát dữ liệu.
- Không phụ thuộc API.
- Không cần xử lý quota/API key.
- Admin vẫn có thể sửa giờ/trạng thái nếu có thay đổi.

### File CSV đề xuất

```csv
match_no,stage,group_code,home_team_name,away_team_name,venue,city,kickoff_at
1,group,A,Team A1,Team A2,Estadio Azteca,Mexico City,2026-06-11 09:00:00
```

### Trường bắt buộc

| Field | Bắt buộc | Ghi chú |
|---|---|---|
| match_no | Có | Số thứ tự trận |
| stage | Có | group, round_32, round_16, quarter_final, semi_final, third_place, final |
| group_code | Không | Chỉ có ở vòng bảng |
| home_team_name | Có | Có thể là placeholder nếu chưa xác định |
| away_team_name | Có | Có thể là placeholder nếu chưa xác định |
| venue | Không | Sân |
| city | Không | Thành phố |
| kickoff_at | Có | Giờ Việt Nam hoặc UTC có timezone rõ |

### Rule thời gian

Khuyến nghị lưu DB bằng UTC, hiển thị theo timezone cấu hình:

```text
DB: UTC
UI: Asia/Ho_Chi_Minh
```

### Tiêu chí hoàn thành

- Import đủ danh sách trận.
- Trận có status `scheduled`.
- Admin xem/sửa được thông tin trận.
- Không có 2 trận trùng `match_no` trong cùng season.

---

## Stage 1.3 – Tạo market và tỷ lệ ăn

### Mục tiêu

Admin tạo các market cần thiết cho từng trận.

### Market type trong Giai đoạn 1

| Market Type | Bắt buộc | Ghi chú |
|---|---|---|
| EXACT_SCORE | Có | Dự đoán tỉ số chính xác |
| ASIAN_HANDICAP | Có | Kèo châu Á |
| OVER_UNDER | Có | Tài/xỉu |

### Period trong Giai đoạn 1

| Period | Bắt buộc | Cách vận hành |
|---|---|---|
| FULL_TIME | Có | Mở trước trận, đóng trước kickoff |
| FIRST_HALF | Có | Mở trước trận, đóng trước kickoff |
| SECOND_HALF | Có | Có thể mở trước trận hoặc mở ở nghỉ giữa hiệp |
| EXTRA_TIME | Có cấu trúc, optional vận hành | Chỉ mở nếu trận knock-out có khả năng đá hiệp phụ |
| PENALTY | Có cấu trúc, optional vận hành | Chỉ mở khi có khả năng penalty |

### Rule khả thi nhất cho MVP

Để đơn giản và tránh live-betting phức tạp:

```text
FULL_TIME và FIRST_HALF:
- Admin tạo trước trận.
- Market đóng mặc định trước kickoff 5 phút.

SECOND_HALF:
- Phương án MVP an toàn: admin tạo trước nhưng có thể để draft.
- Khi hết hiệp 1, admin mở thủ công nếu muốn.
- Admin set close_at trước thời điểm hiệp 2 bắt đầu.

EXTRA_TIME:
- Chỉ dùng cho knock-out.
- Admin mở thủ công khi trận có khả năng đá hiệp phụ.
- Đóng trước khi hiệp phụ bắt đầu.

PENALTY:
- Chỉ dùng cho knock-out.
- Admin mở thủ công trước loạt penalty nếu còn thời gian.
- Đóng trước cú sút đầu tiên.
```

Không nên làm auto-open hiệp 2/hiệp phụ/penalty ở MVP vì bù giờ và tình huống trận thực tế khó xác định chính xác.

### Tỷ lệ ăn rule

| Rule | Diễn giải |
|---|---|
| Dùng profit_rate kiểu Việt Nam | Ví dụ ăn 0.90, 1.05, 6.50 |
| Tỷ lệ ăn > 1 | Không cho tỷ lệ ăn <= 1 |
| Tỷ lệ ăn snapshot | Vé lưu tỷ lệ ăn tại thời điểm đặt |
| Sửa tỷ lệ ăn sau khi có vé | Không sửa trực tiếp outcome cũ; tạo version/outcome mới hoặc khóa sửa |
| Ẩn outcome | Được ẩn outcome nếu chưa có vé |
| Xóa outcome | Chỉ cho xóa nếu chưa có vé |

### Tiêu chí hoàn thành

- Admin tạo được market.
- Admin nhập được outcome/tỷ lệ ăn.
- Publish market thành `open`.
- Market tự động hoặc thủ công chuyển `locked` khi quá giờ.
- User chỉ thấy market `open`.

---

## Stage 1.4 – User đặt dự đoán

### Mục tiêu

User dùng lá khả dụng để tạo vé dự đoán.

### Workflow user

```text
1. User login
2. Chọn trận
3. Chọn market đang mở
4. Chọn cửa dự đoán
5. Nhập số lá
6. Xem preview:
   - Số lá tham gia
   - Tỉ lệ
   - Lá có thể nhận
   - Thời gian khóa
7. Xác nhận
8. Hệ thống trừ available và tăng locked
9. Tạo vé pending
10. Ghi wallet ledger
```

### Validation khi đặt

| Điều kiện | Rule |
|---|---|
| User active | Bắt buộc |
| Season active | Bắt buộc |
| Market open | Bắt buộc |
| Chưa quá close_at | Bắt buộc |
| Outcome active | Bắt buộc |
| Stake >= min_stake | Bắt buộc |
| Stake <= max_stake_per_bet | Bắt buộc |
| Tổng stake/trận <= max_stake_per_match | Bắt buộc |
| Tổng stake/ngày <= max_stake_per_day | Bắt buộc |
| available_balance đủ | Bắt buộc |
| Không cho đặt khi match cancelled/postponed | Bắt buộc |

### Transaction bắt buộc

Đặt vé phải chạy trong database transaction:

```text
BEGIN
  lock wallet row
  validate balance
  validate market still open
  create bet
  decrease available
  increase locked
  create ledger BET_PLACED
COMMIT
```

Nếu lỗi bất kỳ bước nào:

```text
ROLLBACK
```

### Race condition cần xử lý

Tình huống phổ biến:

```text
User A mở màn hình lúc 19:55
Market close_at = 20:00
User submit lúc 20:00:01
```

Rule:

```text
Server time là nguồn chính.
Nếu now >= close_at thì từ chối.
Không tin vào thời gian trên trình duyệt.
```

### Tiêu chí hoàn thành

- User đặt được vé hợp lệ.
- Số dư available giảm ngay.
- Locked balance tăng đúng.
- Vé hiển thị trong “Vé của tôi”.
- Không thể đặt sau close_at.
- Không thể đặt vượt số dư hoặc vượt limit.

---

## Stage 1.5 – Khóa market

### Mục tiêu

Đảm bảo hết giờ thì không ai đặt thêm được.

### Cơ chế khóa

Có 2 lớp bảo vệ:

#### Lớp 1 – Scheduler

Laravel Scheduler chạy mỗi phút:

```text
Tìm market status = open và close_at <= now
Chuyển status sang locked
Ghi audit log
```

#### Lớp 2 – Validation khi submit

Dù scheduler chưa chạy kịp, API đặt vé vẫn kiểm tra:

```text
if now >= market.close_at:
    reject
```

### Rule khóa thủ công

Admin có thể lock thủ công nếu cần:

```text
market.status = locked
market.is_manual_lock = true
market.locked_at = now
```

Sau khi locked:

- Không cho đặt vé mới.
- Không cho sửa outcome.
- Không cho sửa tỷ lệ ăn.
- Chỉ cho nhập kết quả/void.

### Tiêu chí hoàn thành

- Market quá giờ tự lock.
- User không thể đặt sau giờ đóng dù UI chưa refresh.
- Audit log ghi rõ auto lock/manual lock.

---

## Stage 1.6 – Nhập kết quả

### Mục tiêu

Admin nhập kết quả thật của trận/mốc để settlement.

### Kết quả cần nhập

| Period | Kết quả cần nhập |
|---|---|
| FIRST_HALF | home_score_ht, away_score_ht |
| FULL_TIME | home_score_ft, away_score_ft |
| SECOND_HALF | Có thể tự tính từ FT - HT |
| EXTRA_TIME | home_score_et, away_score_et |
| PENALTY | home_score_pen, away_score_pen |

### Rule tính SECOND_HALF

Phương án phổ biến và ít lỗi nhất:

```text
SECOND_HALF_HOME = FULL_TIME_HOME - FIRST_HALF_HOME
SECOND_HALF_AWAY = FULL_TIME_AWAY - FIRST_HALF_AWAY
```

Ví dụ:

```text
Hiệp 1: 1-1
Cả trận: 3-2
Hiệp 2 độc lập: 2-1
```

### Validation kết quả

| Điều kiện | Rule |
|---|---|
| Điểm số không âm | Bắt buộc |
| FT >= HT từng đội | Bắt buộc |
| ET chỉ nhập nếu trận knock-out | Nên kiểm tra |
| Penalty chỉ nhập nếu có loạt luân lưu | Nên kiểm tra |
| Market phải locked trước settlement | Bắt buộc |

### Tiêu chí hoàn thành

- Admin nhập được kết quả.
- Hệ thống tự tính được score của period.
- Không cho nhập kết quả vô lý, ví dụ FT thấp hơn HT.
- Ghi audit log khi nhập/sửa kết quả.

---

## Stage 1.7 – Settlement preview

### Mục tiêu

Trước khi cộng/trừ lá thật, hệ thống phải cho admin xem kết quả xử lý.

### Preview cần hiển thị

| Thông tin | Ý nghĩa |
|---|---|
| Tổng số vé | Số vé pending trong market |
| Số vé thắng | Dự kiến won |
| Số vé thua | Dự kiến lost |
| Số vé push | Dự kiến hoàn stake |
| Số vé half won | Dự kiến nửa thắng |
| Số vé half lost | Dự kiến nửa thua |
| Tổng stake | Tổng lá đang locked |
| Tổng payout | Tổng lá dự kiến trả về |
| Net system | Chênh lệch để đối chiếu game |
| Top payout | Các vé payout cao nhất |

### Workflow preview

```text
1. Admin chọn market đã locked
2. Admin nhập/chọn kết quả period
3. Click “Preview settlement”
4. SettlementEngine tính thử toàn bộ bet pending
5. Lưu settlement batch ở trạng thái previewed
6. Hiển thị bảng chi tiết
7. Settlement Manager kiểm tra
```

### Rule quan trọng

Preview không được thay đổi ví.

Chỉ được tạo dữ liệu nháp:

```text
settlement_batches
settlement_items_preview
```

Hoặc tính runtime không lưu, tùy cách thiết kế. Khuyến nghị lưu batch preview để audit.

### Tiêu chí hoàn thành

- Preview tính đúng từng loại market.
- Ví user chưa thay đổi ở bước preview.
- Admin xem được chi tiết từng vé.
- Có thể hủy preview và tính lại nếu nhập sai kết quả.

---

## Stage 1.8 – Execute settlement

### Mục tiêu

Xác nhận settlement và cập nhật ví thật.

### Workflow execute

```text
1. Settlement Manager xem preview
2. Click “Confirm settlement”
3. Hệ thống mở database transaction
4. Lock từng wallet liên quan
5. Cập nhật bet status
6. Giảm locked stake
7. Cộng actual return vào available nếu có
8. Ghi wallet ledger
9. Mark market = settled
10. Cập nhật leaderboard snapshot
11. Ghi audit log
12. Commit transaction
```

### Rule idempotency

Settlement phải chống chạy 2 lần.

Bắt buộc kiểm tra:

```text
if market.status == settled:
    reject

if bet.status != pending:
    skip/reject
```

Khuyến nghị thêm:

```text
settlement_batch_id unique per market
```

### Tiêu chí hoàn thành

- Không thể settle 2 lần cùng market.
- Ví user thay đổi đúng.
- Locked balance giảm đúng.
- Bet status đúng.
- Ledger đầy đủ.
- Leaderboard cập nhật.

---

## 10. Rules chi tiết cho từng loại market

# 10.1. Dự đoán tỉ số chính xác – EXACT_SCORE

### Mục đích

User chọn đúng tỉ số của một period.

Ví dụ:

```text
FULL_TIME: 2-1 @ 7.00
FIRST_HALF: 0-0 @ 2.40
SECOND_HALF: 1-0 @ 4.20
```

### Cách nhập outcome

Admin nhập danh sách tỉ số và tỷ lệ ăn.

Trường cần có:

```text
score_home
score_away
tỷ lệ ăn
label
```

Ví dụ outcome:

| Label | score_home | score_away | Tỷ lệ ăn |
|---|---:|---:|---:|
| 0-0 | 0 | 0 | 7.50 |
| 1-0 | 1 | 0 | 6.00 |
| 1-1 | 1 | 1 | 5.50 |
| 2-1 | 2 | 1 | 8.00 |

### Settlement rule

```text
Nếu predicted_home == actual_home và predicted_away == actual_away:
    status = won
    return = stake × tỷ lệ ăn
    profit = return - stake
Ngược lại:
    status = lost
    return = 0
    profit = -stake
```

### Ví dụ

User đặt:

```text
Stake: 100 lá
Outcome: 2-1 @ 8.00
Actual: 2-1
```

Kết quả:

```text
Return = 100 × 8.00 = 800 lá
Profit = 800 - 100 = 700 lá
Status = won
```

Nếu actual là 1-1:

```text
Return = 0
Profit = -100
Status = lost
```

### Rules bổ sung

| Rule | Diễn giải |
|---|---|
| Không có push | Tỉ số chính xác chỉ thắng hoặc thua |
| Không split stake | Không có half win/half loss |
| Không cho nhập score âm | Validation |
| Có thể thêm “Other score” không? | Không nên ở MVP |

### Vì sao không nên có “Other score” ở MVP?

“Other score” làm settlement phức tạp hơn vì phải đảm bảo không trùng với danh sách tỉ số đã nhập. Giai đoạn 1 nên chỉ dùng danh sách tỉ số cụ thể.

---

# 10.2. Kèo châu Á – ASIAN_HANDICAP

### Mục đích

User chọn một đội với handicap line.

Ví dụ:

```text
Argentina -0.5 ăn 0.90
Spain +0.5 ăn 0.90
```

### Các line nên hỗ trợ trong MVP

| Loại line | Ví dụ | Kết quả có thể có |
|---|---|---|
| Whole line | 0, -1, +1 | Win/Lose/Push |
| Half line | -0.5, +0.5, -1.5, +1.5 | Win/Lose |
| Quarter line | -0.25, +0.25, -0.75, +0.75 | Win/Half Win/Half Lose/Lose |

### Line nên cho phép

```text
-3.0 đến +3.0
Bước nhảy 0.25
```

Giai đoạn sau có thể mở rộng.

### Cách tính

Với outcome chọn đội nào, tính hiệu số theo đội đó:

```text
selected_diff = selected_team_score - opponent_score
adjusted_diff = selected_diff + handicap_line
```

Kết quả:

```text
adjusted_diff > 0  => thắng
adjusted_diff = 0  => push
adjusted_diff < 0  => thua
```

### Ví dụ line whole: Team A -1.0

User đặt:

```text
Team A -1.0 ăn 0.90
Stake 100 lá
```

| Tỉ số thực | selected_diff | adjusted_diff | Kết quả |
|---|---:|---:|---|
| A thắng 2-0 | +2 | +1 | Win |
| A thắng 1-0 | +1 | 0 | Push |
| A hòa 1-1 | 0 | -1 | Lose |
| A thua 0-1 | -1 | -2 | Lose |

### Ví dụ line half: Team A -0.5

| Tỉ số thực | adjusted_diff | Kết quả |
|---|---:|---|
| A thắng | > 0 | Win |
| Hòa | < 0 | Lose |
| A thua | < 0 | Lose |

### Quarter line split rule

Kèo quarter line được split thành 2 kèo gần nhất.

| Line gốc | Split thành |
|---:|---|
| -0.25 | 0 và -0.5 |
| +0.25 | 0 và +0.5 |
| -0.75 | -0.5 và -1.0 |
| +0.75 | +0.5 và +1.0 |
| -1.25 | -1.0 và -1.5 |
| +1.25 | +1.0 và +1.5 |
| -1.75 | -1.5 và -2.0 |
| +1.75 | +1.5 và +2.0 |

### Cách tổng hợp kết quả split

| Leg 1 | Leg 2 | Kết quả tổng |
|---|---|---|
| Win | Win | won |
| Win | Push | half_won |
| Push | Win | half_won |
| Push | Lose | half_lost |
| Lose | Push | half_lost |
| Lose | Lose | lost |

### Công thức payout

| Status | Return | Profit |
|---|---:|---:|
| won | stake × tỷ lệ ăn | return - stake |
| lost | 0 | -stake |
| push | stake | 0 |
| half_won | stake/2 × tỷ lệ ăn + stake/2 | return - stake |
| half_lost | stake/2 | -stake/2 |

### Ví dụ -0.75

User đặt:

```text
Team A -0.75 ăn 0.90
Stake: 100 lá
```

Split:

```text
50 lá ở Team A -0.5
50 lá ở Team A -1.0
```

Nếu Team A thắng đúng 1 bàn:

```text
-0.5: Win -> 50 × (1 + 0.90) = 95
-1.0: Push -> 50
Return = 145
Profit = 45
Status = half_won
```

Nếu Team A hòa:

```text
-0.5: Lose
-1.0: Lose
Return = 0
Profit = -100
Status = lost
```

### Ví dụ +0.25

User đặt:

```text
Team B +0.25 ăn 0.90
Stake: 100 lá
```

Split:

```text
50 lá ở Team B 0
50 lá ở Team B +0.5
```

Nếu trận hòa:

```text
Team B 0: Push
Team B +0.5: Win
Return = 50 + 95 = 145
Profit = 45
Status = half_won
```

Nếu Team B thua 1 bàn:

```text
Team B 0: Lose
Team B +0.5: Lose
Return = 0
Profit = -100
Status = lost
```

---

# 10.3. Tài/xỉu – OVER_UNDER

### Mục đích

User dự đoán tổng số bàn thắng của period cao hơn hoặc thấp hơn một line.

Ví dụ:

```text
Tài 2.5 @ 1.95
Xỉu 2.5 ăn 0.85
```

### Tổng bàn theo period

| Period | Tổng bàn |
|---|---|
| FULL_TIME | home_score_ft + away_score_ft |
| FIRST_HALF | home_score_ht + away_score_ht |
| SECOND_HALF | second_half_home + second_half_away |
| EXTRA_TIME | home_score_et + away_score_et |
| PENALTY | Không khuyến nghị dùng tài/xỉu ở MVP |

### Line nên hỗ trợ trong MVP

```text
0.5 đến 5.5
Bước nhảy 0.25
```

Ví dụ:

```text
1.5, 1.75, 2.0, 2.25, 2.5, 2.75, 3.0, 3.25
```

### Settlement rule cơ bản

Với `OVER`:

```text
Nếu total_goals > line: Win
Nếu total_goals = line: Push
Nếu total_goals < line: Lose
```

Với `UNDER`:

```text
Nếu total_goals < line: Win
Nếu total_goals = line: Push
Nếu total_goals > line: Lose
```

Line dạng .5 không có push vì tổng bàn là số nguyên.

### Quarter total split rule

Line quarter như 2.25 hoặc 2.75 sẽ split thành 2 line.

| Line gốc | Split thành |
|---:|---|
| 1.25 | 1.0 và 1.5 |
| 1.75 | 1.5 và 2.0 |
| 2.25 | 2.0 và 2.5 |
| 2.75 | 2.5 và 3.0 |
| 3.25 | 3.0 và 3.5 |
| 3.75 | 3.5 và 4.0 |

### Ví dụ tài 2.25

User đặt:

```text
Tài 2.25 ăn 0.90
Stake 100 lá
```

Split:

```text
50 lá ở Tài 2.0
50 lá ở Tài 2.5
```

| Tổng bàn | Tài 2.0 | Tài 2.5 | Kết quả | Return |
|---:|---|---|---|---:|
| 3+ | Win | Win | won | 190 |
| 2 | Push | Lose | half_lost | 50 |
| 0-1 | Lose | Lose | lost | 0 |

### Ví dụ xỉu 2.25

User đặt:

```text
Xỉu 2.25 ăn 0.90
Stake 100 lá
```

Split:

```text
50 lá ở Xỉu 2.0
50 lá ở Xỉu 2.5
```

| Tổng bàn | Xỉu 2.0 | Xỉu 2.5 | Kết quả | Return |
|---:|---|---|---|---:|
| 0-1 | Win | Win | won | 190 |
| 2 | Push | Win | half_won | 145 |
| 3+ | Lose | Lose | lost | 0 |

### Ví dụ tài 2.75

User đặt:

```text
Tài 2.75 ăn 0.90
Stake 100 lá
```

Split:

```text
50 lá ở Tài 2.5
50 lá ở Tài 3.0
```

| Tổng bàn | Tài 2.5 | Tài 3.0 | Kết quả | Return |
|---:|---|---|---|---:|
| 4+ | Win | Win | won | 190 |
| 3 | Win | Push | half_won | 145 |
| 0-2 | Lose | Lose | lost | 0 |

### Ví dụ xỉu 2.75

User đặt:

```text
Xỉu 2.75 ăn 0.90
Stake 100 lá
```

Split:

```text
50 lá ở Xỉu 2.5
50 lá ở Xỉu 3.0
```

| Tổng bàn | Xỉu 2.5 | Xỉu 3.0 | Kết quả | Return |
|---:|---|---|---|---:|
| 0-2 | Win | Win | won | 190 |
| 3 | Lose | Push | half_lost | 50 |
| 4+ | Lose | Lose | lost | 0 |

---

## 11. Rules theo từng period

## 11.1. FULL_TIME

### Định nghĩa

```text
Cả trận 90 phút chính thức + bù giờ.
Không bao gồm hiệp phụ.
Không bao gồm penalty.
```

### Market nên có

| Market | Giai đoạn 1 |
|---|---|
| Exact score | Có |
| Asian handicap | Có |
| Over/Under | Có |

### Close time đề xuất

```text
close_at = kickoff_at - 5 phút
```

## 11.2. FIRST_HALF

### Định nghĩa

```text
Từ lúc bắt đầu trận đến khi kết thúc hiệp 1, bao gồm bù giờ hiệp 1.
```

### Market nên có

| Market | Giai đoạn 1 |
|---|---|
| Exact score | Có |
| Asian handicap | Có |
| Over/Under | Có |

### Close time đề xuất

```text
close_at = kickoff_at - 5 phút
```

Không nên cho user đặt hiệp 1 sau khi trận đã bắt đầu.

## 11.3. SECOND_HALF

### Định nghĩa

```text
Chỉ tính bàn thắng trong hiệp 2.
Không tính bàn của hiệp 1.
```

### Công thức

```text
second_half_home = full_time_home - first_half_home
second_half_away = full_time_away - first_half_away
```

### Market nên có

| Market | Giai đoạn 1 |
|---|---|
| Exact score | Có |
| Asian handicap | Có |
| Over/Under | Có |

### Close time đề xuất

Phương án an toàn:

```text
Admin mở thủ công khi hết hiệp 1.
Admin đóng thủ công trước khi hiệp 2 bắt đầu.
```

Hoặc nếu muốn auto đơn giản:

```text
open_at = kickoff_at + 45 phút
close_at = kickoff_at + 60 phút
```

Không khuyến nghị auto trong MVP vì bù giờ hiệp 1 thay đổi.

## 11.4. EXTRA_TIME

### Định nghĩa

```text
Chỉ tính bàn trong 30 phút hiệp phụ.
Không cộng bàn của 90 phút.
Không tính penalty.
```

### Market nên có

Giai đoạn 1 chỉ nên hỗ trợ:

| Market | Khuyến nghị |
|---|---|
| Exact score | Có |
| Asian handicap | Có nếu admin cần |
| Over/Under | Có nếu admin cần |

### Rule vận hành

```text
Chỉ mở với trận knock-out.
Admin mở thủ công khi trận có khả năng đá hiệp phụ.
Đóng trước khi hiệp phụ bắt đầu.
```

## 11.5. PENALTY

### Định nghĩa

```text
Chỉ tính loạt sút luân lưu.
Không tính bàn trong 90 phút hoặc hiệp phụ.
```

### Market nên có trong MVP

Giai đoạn 1 nên giữ đơn giản:

| Market | Khuyến nghị |
|---|---|
| Penalty winner | Có thể thêm đơn giản |
| Exact penalty score | Có thể thêm nếu cần |
| Asian handicap penalty | Không nên |
| Over/Under penalty | Không nên |

Nếu chưa muốn mở thêm market type `PENALTY_WINNER`, có thể chưa vận hành penalty trong MVP, nhưng database nên có period `PENALTY` để mở rộng sau.

---

## 12. Ví lá và ledger logic

## 12.1. Nguyên tắc ví

Không được tính số dư bằng cách sửa trực tiếp `wallet.available_units` mà không ghi ledger.

Mỗi thay đổi phải có:

```text
1 dòng ledger
1 lý do nghiệp vụ
1 actor nếu là admin
1 reference nếu liên quan bet/market
```

## 12.2. Khi admin cấp lá

Ví dụ cấp 1.000 lá:

```text
available_units += 100000
ledger type = ADMIN_GRANT
amount_units = 100000
```

## 12.3. Khi user đặt vé

User đặt 100 lá:

```text
available_units -= 10000
locked_units += 10000
ledger type = BET_PLACED
amount_units = 10000
```

Không ghi lãi/lỗ lúc đặt. Lãi/lỗ chỉ ghi khi settlement.

## 12.4. Khi vé thắng đủ

Stake 100, ăn 0.90:

```text
locked_units -= 10000
available_units += 19000
ledger type = BET_WON
amount_units = 19000
profit_units = 9000
```

## 12.5. Khi vé thua đủ

Stake 100:

```text
locked_units -= 10000
available_units += 0
ledger type = BET_LOST
amount_units = 0
profit_units = -10000
```

## 12.6. Khi push

Stake 100:

```text
locked_units -= 10000
available_units += 10000
ledger type = BET_PUSH
profit_units = 0
```

## 12.7. Khi half won

Stake 100, ăn 0.90:

```text
50 lá thắng: 50 × (1 + 0.90) = 95
50 lá hoàn: 50
Return = 145
Profit = 45
```

Ledger:

```text
locked_units -= 10000
available_units += 14500
ledger type = BET_HALF_WON
profit_units = 4500
```

## 12.8. Khi half lost

Stake 100:

```text
50 lá thua
50 lá hoàn
Return = 50
Profit = -50
```

Ledger:

```text
locked_units -= 10000
available_units += 5000
ledger type = BET_HALF_LOST
profit_units = -5000
```

---

## 13. Tỷ lệ ăn snapshot và versioning

### 13.1. Vấn đề

Admin có thể nhập tỷ lệ ăn ban đầu rồi đổi tỷ lệ ăn trước trận. Nếu user đã đặt, vé cũ phải giữ tỷ lệ ăn cũ.

### 13.2. Rule MVP

Khi user đặt vé, lưu snapshot:

```text
profit_rate_snapshot
line_snapshot
outcome_label_snapshot
market_type_snapshot
period_code_snapshot
```

Settlement dùng snapshot trong bet, không đọc tỷ lệ ăn hiện tại từ outcome.

### 13.3. Sửa tỷ lệ ăn sau khi có vé

Rule phổ biến và an toàn:

| Tình huống | Cho phép? | Cách xử lý |
|---|---|---|
| Outcome chưa có vé | Cho sửa trực tiếp |
| Outcome đã có vé | Không sửa trực tiếp |
| Muốn đổi tỷ lệ ăn | Tạo outcome mới/version mới |
| Muốn đóng outcome cũ | Set inactive/hidden |

### 13.4. Vì sao không sửa trực tiếp?

Nếu sửa trực tiếp tỷ lệ ăn đã có vé:

- User có thể khiếu nại.
- Settlement có thể sai nếu không snapshot.
- Audit khó đối chiếu.

---

## 14. Void và correction

## 14.1. Void market

Void dùng khi:

| Tình huống | Ví dụ |
|---|---|
| Trận hủy | Match cancelled |
| Nhập sai market nghiêm trọng | Sai đội, sai period |
| Tỷ lệ ăn bị nhập lỗi rõ ràng | Ví dụ 19.0 thay vì 0.90, tùy rule nội bộ |
| Market mở nhầm thời gian | Đóng sau khi trận bắt đầu |

### Rule void

```text
Chỉ void market chưa settled hoặc market cần reverse toàn bộ.
Tất cả bet pending được hoàn stake.
Bet status = voided.
Ledger type = MARKET_VOID.
Bắt buộc nhập void_reason.
```

### Không nên lạm dụng void

Void quá nhiều làm mất niềm tin. Nên có quyền riêng:

```text
settlements.void_market
```

## 14.2. Correction sau settlement

Dùng khi đã settle nhưng phát hiện sai kết quả.

### Rule MVP khả thi

Không sửa ledger cũ. Tạo correction mới.

Workflow:

```text
1. Admin tạo correction request
2. Nhập lý do
3. Nhập kết quả đúng
4. Hệ thống tính lại settlement đúng
5. So sánh với settlement cũ
6. Tạo ledger bù trừ:
   - CORRECTION_CREDIT nếu user thiếu lá
   - CORRECTION_DEBIT nếu user thừa lá
7. Bet status chuyển corrected hoặc lưu correction reference
8. Ghi audit log
```

### Ví dụ correction

Settlement cũ trả user 190 lá. Settlement đúng là thua 0 lá.

```text
Correction debit = -190 lá
```

Nếu user không đủ available để debit?

Phương án MVP:

```text
Cho phép available âm nội bộ do correction? Không khuyến nghị.
```

Phương án nên dùng:

```text
Không trừ vượt available.
Nếu thiếu, ghi correction_debt_units để admin xử lý thủ công.
```

Giai đoạn 1 có thể đơn giản hơn:

```text
Chỉ cho correction khi tất cả user liên quan còn đủ available để debit.
Nếu không đủ, yêu cầu Super Admin xử lý riêng.
```

---

## 15. Leaderboard Giai đoạn 1

### 15.1. Bảng chính

Sắp xếp theo:

```text
available_units + locked_units DESC
```

Tức là tổng tài sản lá hiện tại.

### 15.2. Chỉ số hiển thị

| Chỉ số | Cách tính |
|---|---|
| Tổng lá | available + locked |
| Lá khả dụng | available |
| Lá đang chờ | locked |
| Lãi/lỗ | total_returned - total_staked |
| Tổng lá đã tham gia | total_staked |
| Số vé | count(bets) |
| Số vé thắng | count(won, half_won) |
| Tỉ lệ thắng | vé có lợi nhuận dương / tổng vé đã settled |
| ROI | profit / total_staked × 100 |

### 15.3. Leaderboard phụ nên có

| Bảng | Giai đoạn 1 |
|---|---|
| Cá nhân toàn mùa | Có |
| Cá nhân theo tuần | Nên có |
| Phòng ban | Có nếu có department |
| Đúng tỉ số nhiều nhất | Nên có |
| ROI cao nhất | Nên có nếu đủ dữ liệu |

### 15.4. Rule chống méo leaderboard

ROI cao nhưng đặt quá ít sẽ không công bằng. Nên có điều kiện tối thiểu:

```text
Chỉ hiển thị ROI ranking nếu user có ít nhất 5 vé settled hoặc tổng stake >= 300 lá.
```

---

## 16. Database tối thiểu Giai đoạn 1

## 16.1. Danh sách bảng

```text
users
departments
roles
permissions
seasons
matches
markets
market_outcomes
bets
wallets
wallet_ledgers
settlement_batches
settlement_items
leaderboard_snapshots
audit_logs
system_settings
```

## 16.2. Index quan trọng

```text
users.email unique
wallets(user_id, season_id) unique
matches(season_id, match_no) unique
markets(match_id, period_code, market_type)
markets(status, close_at)
market_outcomes(market_id, status)
bets(user_id, season_id)
bets(match_id, market_id)
bets(status)
wallet_ledgers(user_id, season_id)
wallet_ledgers(bet_id)
settlement_batches(market_id) unique where status = executed
```

## 16.3. Constraint quan trọng

| Constraint | Mục đích |
|---|---|
| wallet available >= 0 | Không cho âm lá |
| wallet locked >= 0 | Không âm locked |
| stake > 0 | Vé phải có stake |
| tỷ lệ ăn > 1 | Tỷ lệ ăn hợp lệ |
| close_at > open_at | Thời gian market hợp lệ |
| score >= 0 | Tỉ số không âm |

---

## 17. Service/domain class nên tạo

```text
app/Domain/Game/Services/WalletService.php
app/Domain/Game/Services/BetPlacementService.php
app/Domain/Game/Services/MarketLockService.php
app/Domain/Game/Services/ResultService.php
app/Domain/Game/Services/SettlementPreviewService.php
app/Domain/Game/Services/SettlementExecutionService.php
app/Domain/Game/Services/LeaderboardService.php

app/Domain/Game/Settlement/ExactScoreSettlement.php
app/Domain/Game/Settlement/AsianHandicapSettlement.php
app/Domain/Game/Settlement/OverUnderSettlement.php
app/Domain/Game/Settlement/SettlementResult.php
app/Domain/Game/Settlement/LineSplitter.php
```

### 17.1. Không nên viết settlement trong Controller/Filament Resource

Filament Resource chỉ nên gọi service.

Không nên:

```php
// Sai hướng
public function settle()
{
    // tính kèo trực tiếp trong action Filament
}
```

Nên:

```php
SettlementExecutionService::execute($market, $result, $actor);
```

### 17.2. Lợi ích

- Dễ test unit.
- Không phụ thuộc UI.
- Sau này có API/mobile vẫn dùng lại logic.
- Giảm lỗi khi sửa giao diện admin.

---

## 18. Test case nghiệp vụ bắt buộc

## 18.1. Test ví lá

| Case | Kỳ vọng |
|---|---|
| Admin cấp 1.000 lá | available = 1.000 |
| User đặt 100 lá | available giảm 100, locked tăng 100 |
| Không đủ lá | Reject |
| Vượt max bet | Reject |
| Vượt max match | Reject |
| Vượt max day | Reject |

## 18.2. Test exact score

| Bet | Actual | Kết quả |
|---|---|---|
| 1-0 | 1-0 | won |
| 1-0 | 1-1 | lost |
| 0-0 HT | 0-0 HT | won |
| 0-0 HT | 1-0 HT | lost |

## 18.3. Test Asian handicap

| Line | Tỉ số theo đội chọn | Kết quả |
|---:|---:|---|
| 0 | Hòa | push |
| -0.5 | Thắng 1 | won |
| -0.5 | Hòa | lost |
| +0.5 | Hòa | won |
| -1.0 | Thắng 1 | push |
| -1.0 | Thắng 2 | won |
| -1.0 | Hòa | lost |
| -0.25 | Hòa | half_lost |
| -0.25 | Thắng 1 | won |
| +0.25 | Hòa | half_won |
| +0.25 | Thua 1 | lost |
| -0.75 | Thắng 1 | half_won |
| -0.75 | Thắng 2 | won |
| +0.75 | Thua 1 | half_lost |
| +0.75 | Hòa | won |

## 18.4. Test tài/xỉu

| Side | Line | Tổng bàn | Kết quả |
|---|---:|---:|---|
| Over | 2.5 | 3 | won |
| Over | 2.5 | 2 | lost |
| Under | 2.5 | 2 | won |
| Under | 2.5 | 3 | lost |
| Over | 2.0 | 2 | push |
| Under | 2.0 | 2 | push |
| Over | 2.25 | 2 | half_lost |
| Under | 2.25 | 2 | half_won |
| Over | 2.75 | 3 | half_won |
| Under | 2.75 | 3 | half_lost |

## 18.5. Test lock market

| Case | Kỳ vọng |
|---|---|
| now < close_at | Cho đặt |
| now = close_at | Từ chối |
| now > close_at | Từ chối |
| Scheduler chưa lock nhưng quá giờ | API vẫn từ chối |
| Admin manual lock | User không đặt được |

## 18.6. Test settlement idempotency

| Case | Kỳ vọng |
|---|---|
| Execute lần 1 | Thành công |
| Execute lần 2 | Reject |
| Bet đã settled | Không xử lý lại |
| Market voided | Không settle |
| Market chưa locked | Không settle |

---

## 19. Filament Resource cần làm trong Giai đoạn 1

## 19.1. Admin panel

| Resource/Page | Mục đích |
|---|---|
| UserResource | Quản lý user |
| DepartmentResource | Quản lý phòng ban |
| SeasonResource | Quản lý mùa giải |
| MatchResource | Quản lý trận đấu |
| MarketResource | Quản lý market |
| MarketOutcomeRelationManager | Quản lý outcome/tỷ lệ ăn |
| WalletResource | Xem ví, cấp/trừ lá |
| BetResource | Xem vé |
| SettlementPage | Preview/execute settlement |
| LeaderboardPage | Bảng xếp hạng |
| WalletLedgerResource | Lịch sử ví |
| AuditLogResource | Nhật ký hệ thống |
| SettingsPage | Cấu hình rules |

## 19.2. User-facing pages

Có thể dùng Filament panel riêng cho user hoặc Blade/Livewire frontend.

Giai đoạn 1 khuyến nghị:

```text
Admin: Filament panel /admin
User: Blade + Livewire hoặc Filament user panel /app
```

Trang user cần có:

| Page | Nội dung |
|---|---|
| Dashboard | Số lá, trận sắp đá, vé đang chờ |
| Fixtures | Danh sách trận WC2026 |
| Match Detail | Market/outcome đang mở |
| Place Prediction | Form đặt lá |
| My Bets | Vé của tôi |
| Wallet History | Lịch sử ví |
| Leaderboard | Bảng xếp hạng |
| Rules | Thể lệ |

---

## 20. Các bước đầu để bắt đầu dự án

## 20.1. Bước 1 – Chốt scope MVP

Trước khi code, cần chốt bằng văn bản:

```text
1. Có dùng quà hiện vật không? Nếu có, cần pháp chế/HR duyệt.
2. Lá có reset theo mùa không?
3. Mỗi user khởi tạo bao nhiêu lá?
4. Giới hạn stake bao nhiêu?
5. Có cho user đặt nhiều vé cùng market không?
6. Có mở hiệp 2/hiệp phụ/penalty ngay MVP không?
7. Ai có quyền settlement?
8. Có cần 2 người duyệt settlement không?
```

Khuyến nghị chốt mặc định:

```text
Không quà quy đổi theo lá.
1.000 lá/user.
Min 10 lá/vé.
Max 200 lá/vé.
Max 500 lá/trận/user.
Max 1.000 lá/ngày/user.
User được đặt nhiều vé nhưng không được sửa/hủy.
Settlement cần Operator nhập kết quả, Settlement Manager xác nhận.
```

## 20.2. Bước 2 – Tạo repository và coding standard

```bash
composer create-project laravel/laravel la-du-doan
cd la-du-doan
```

Cấu hình:

```text
PHP 8.3+
Laravel 12/13 tùy thời điểm triển khai
PostgreSQL
Redis
Pint
PHPStan/Larastan
Pest hoặc PHPUnit
```

## 20.3. Bước 3 – Cài Filament và plugin nền

Package đề xuất:

```bash
composer require filament/filament
composer require spatie/laravel-permission
composer require bezhansalleh/filament-shield
composer require spatie/laravel-activitylog
composer require pxlrbt/filament-activity-log
composer require spatie/laravel-settings
composer require filament/spatie-laravel-settings-plugin
composer require jeffgreco13/filament-breezy
```

Có thể thêm backup:

```bash
composer require spatie/laravel-backup
composer require shuvroroy/filament-spatie-laravel-backup
```

## 20.4. Bước 4 – Tạo migrations cốt lõi

Thứ tự nên làm:

```text
1. departments
2. seasons
3. wallets
4. matches
5. markets
6. market_outcomes
7. bets
8. wallet_ledgers
9. settlement_batches
10. settlement_items
11. leaderboard_snapshots
```

## 20.5. Bước 5 – Seed dữ liệu ban đầu

Seed:

```text
Roles
Permissions
Super Admin
Departments mẫu
Season WC2026
System Settings
WC2026 Fixtures CSV
```

## 20.6. Bước 6 – Viết service ví trước

Ưu tiên viết `WalletService` đầu tiên vì mọi logic khác phụ thuộc ví.

Method cần có:

```php
WalletService::grant(User $user, Season $season, int $units, User $actor, string $reason)
WalletService::deduct(User $user, Season $season, int $units, User $actor, string $reason)
WalletService::lockStake(Wallet $wallet, Bet $bet, int $units)
WalletService::settleReturn(Wallet $wallet, Bet $bet, int $returnUnits, int $profitUnits)
WalletService::voidReturn(Wallet $wallet, Bet $bet)
```

## 20.7. Bước 7 – Viết settlement engine và test

Thứ tự test:

```text
1. ExactScoreSettlement
2. AsianHandicapSettlement
3. OverUnderSettlement
4. LineSplitter
5. SettlementExecutionService
```

Không làm UI settlement trước khi test logic xong.

## 20.8. Bước 8 – Làm UI admin

Sau khi domain logic ổn:

```text
UserResource
WalletResource
MatchResource
MarketResource
BetResource
SettlementPage
LeaderboardPage
```

## 20.9. Bước 9 – Làm UI user

Ưu tiên đơn giản:

```text
Dashboard
Fixtures
Match detail
Place bet modal
My Bets
Leaderboard
```

## 20.10. Bước 10 – Test vận hành giả lập

Chạy dry-run với dữ liệu giả:

```text
20 user
5 department
10 trận
3 market/trận
100 vé giả
Settlement đủ loại win/lost/push/half win/half loss
```

Chỉ go-live khi ledger, leaderboard và settlement khớp.

---

## 21. Checklist nghiệm thu Giai đoạn 1

## 21.1. Checklist nghiệp vụ

- [ ] User không thể tự đăng ký.
- [ ] User không thể nạp/rút/chuyển lá.
- [ ] Admin cấp lá có ledger.
- [ ] User đặt lá bị trừ available và tăng locked.
- [ ] Market quá giờ không đặt được.
- [ ] Tỷ lệ ăn snapshot lưu đúng.
- [ ] Sửa tỷ lệ ăn sau khi có vé bị chặn.
- [ ] Exact score settlement đúng.
- [ ] Asian handicap settlement đúng.
- [ ] Over/Under settlement đúng.
- [ ] Push hoàn đúng stake.
- [ ] Half win/half loss tính đúng.
- [ ] Settlement không chạy được 2 lần.
- [ ] Void hoàn đúng stake.
- [ ] Correction không xóa ledger cũ.
- [ ] Leaderboard cập nhật đúng.
- [ ] Audit log đủ thao tác nhạy cảm.

## 21.2. Checklist kỹ thuật

- [ ] Tất cả thay đổi ví dùng transaction.
- [ ] Wallet row được lock khi đặt/settle.
- [ ] Không dùng float để tính lá.
- [ ] Dùng integer units.
- [ ] Có unit test settlement.
- [ ] Có feature test đặt vé.
- [ ] Có feature test settlement.
- [ ] Có scheduler lock market.
- [ ] Có queue nếu settlement nhiều vé.
- [ ] Có backup DB.
- [ ] Có role/permission rõ.

## 21.3. Checklist vận hành

- [ ] Admin biết cách import lịch.
- [ ] Admin biết cách tạo market.
- [ ] Admin biết cách nhập tỷ lệ ăn.
- [ ] Admin biết cách khóa market.
- [ ] Admin biết cách nhập kết quả.
- [ ] Settlement Manager biết cách preview/confirm.
- [ ] Có hướng dẫn xử lý nhập sai.
- [ ] Có trang thể lệ cho user.
- [ ] Có file export backup trước go-live.

---

## 22. Phương án rules chốt cho MVP

Đây là bộ rules nên chốt để bắt đầu code:

```text
1. Sản phẩm là game dự đoán nội bộ bằng điểm ảo “lá”.
2. Không nạp tiền, không rút tiền, không chuyển lá, không quy đổi lá.
3. Mỗi user được cấp 1.000 lá đầu mùa.
4. Min stake: 10 lá.
5. Max stake/vé: 200 lá.
6. Max stake/trận/user: 500 lá.
7. Max stake/ngày/user: 1.000 lá.
8. User được đặt nhiều vé, nhưng không được sửa/hủy sau xác nhận.
9. Market FULL_TIME và FIRST_HALF đóng trước kickoff 5 phút.
10. SECOND_HALF, EXTRA_TIME, PENALTY mở/đóng thủ công bởi admin trong MVP.
11. Tỷ lệ ăn dùng tỷ lệ ăn kiểu Việt Nam.
12. Vé lưu tỷ lệ ăn snapshot và line snapshot.
13. Outcome đã có vé thì không sửa tỷ lệ ăn trực tiếp.
14. Kèo châu Á hỗ trợ line bước 0.25.
15. Tài/xỉu hỗ trợ line bước 0.25.
16. Quarter line split stake 50/50.
17. Settlement có preview trước, execute sau.
18. Settlement chỉ chạy một lần cho mỗi market.
19. Void market phải nhập lý do và hoàn stake.
20. Correction sau settlement dùng ledger bù trừ, không xóa dữ liệu cũ.
```

---

## 23. Rủi ro chính của Giai đoạn 1 và cách giảm

| Rủi ro | Cách giảm |
|---|---|
| Sai settlement | Unit test đầy đủ cho từng line |
| User đặt sau giờ đóng | Server-side validation + scheduler |
| Sai ví do concurrent request | DB transaction + lockForUpdate |
| Admin sửa tỷ lệ ăn sau khi có vé | Tỷ lệ ăn snapshot + chặn sửa outcome có vé |
| Settlement chạy 2 lần | Idempotency + unique settlement batch |
| Nhập sai kết quả | Preview + quyền Settlement Manager |
| Tranh chấp rule | Trang thể lệ rõ ràng |
| Rủi ro pháp lý | Không nạp/rút/quy đổi, wording an toàn |
| Mất dữ liệu | Backup tự động |

---

## 24. Tóm tắt kiến trúc MVP

```text
Laravel App
├── Filament Admin Panel
│   ├── User Management
│   ├── Wallet Management
│   ├── Match Management
│   ├── Market/Tỷ lệ ăn Management
│   ├── Settlement
│   └── Reports/Audit
│
├── User Web App
│   ├── Dashboard
│   ├── Fixtures
│   ├── Match Detail
│   ├── Place Prediction
│   ├── My Bets
│   └── Leaderboard
│
├── Domain Services
│   ├── WalletService
│   ├── BetPlacementService
│   ├── MarketLockService
│   ├── SettlementEngine
│   └── LeaderboardService
│
├── Database
│   ├── PostgreSQL
│   ├── Append-only Ledger
│   └── Settlement Batch
│
└── Worker/Scheduler
    ├── Auto lock market
    ├── Recalculate leaderboard
    └── Backup
```

---

## 25. Kết luận

Giai đoạn 1 nên tập trung vào một MVP chắc nghiệp vụ, không mở quá nhiều tính năng gây rủi ro. Bộ rules phổ biến và khả thi nhất là:

- Tỷ lệ ăn kiểu Việt Nam.
- Stake bằng điểm ảo “lá”.
- Quarter handicap/total split 50/50.
- Push hoàn stake.
- Half win/half loss xử lý theo nửa stake.
- Ví vận hành bằng ledger.
- Tỷ lệ ăn snapshot tại thời điểm đặt.
- Settlement preview trước khi execute.
- Không sửa/xóa dữ liệu đã phát sinh.

Khi hoàn thành Giai đoạn 1, hệ thống đã đủ để chạy nội bộ cho lịch World Cup 2026 với các market chính: **tỉ số chính xác, kèo châu Á và tài/xỉu** theo các mốc **cả trận, hiệp 1, hiệp 2, hiệp phụ và penalty**.

---

## 26. Nguồn tham khảo rule phổ biến

Các nguồn dưới đây được dùng để đối chiếu logic phổ biến của kèo châu Á và tài/xỉu châu Á:

1. Asian handicap – Wikipedia: https://en.wikipedia.org/wiki/Asian_handicap
2. Asian handicap betting guide – Footy Accumulators: https://footyaccumulators.com/how-to/asian-handicap-betting
3. Asian goal line / Asian total explanation – Rules of Sport: https://www.rulesofsport.com/betting/football/what-is-asian-goal-line-betting/
4. Asian total goals examples – WWin: https://wwin.com/en/blog/asian-total-goals/
5. Nghị định 06/2017/NĐ-CP về kinh doanh đặt cược: https://luatvietnam.vn/thuong-mai/nghi-dinh-06-2017-nd-cp-chinh-phu-112061-d1.html
6. Điều 321 Bộ luật Hình sự – tham khảo diễn giải từ Bộ Công an: https://bocongan.gov.vn/chinh-sach-phap-luat/chi-tiet-cau-hoi/3ac90792-4a1a-4900-ae5f-c97af8bd5f39
