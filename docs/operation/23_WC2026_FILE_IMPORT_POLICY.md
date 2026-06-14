---
title: "WC2026 File Import Policy"
project: "Du Doan La"
version: "1.0"
status: "active"
last_updated: "2026-06-12"
owner: "Operations / Engineering"
---

# WC2026 File Import Policy

## 1. Decision

Lich WC2026 trong MVP duoc nhap tu file CSV/XLSX. Khong scrape live, khong goi external API, khong dong bo tu website cong khai trong runtime.

## 2. Source file

File nguon do operation/admin chuan bi va review thu cong truoc khi import.

Required format:

```text
UTF-8 CSV hoac XLSX
Timezone: Asia/Ho_Chi_Minh
Datetime: YYYY-MM-DD HH:mm:ss
```

Required columns:

```text
season_code
match_code
round
home_team
away_team
kickoff_at
venue
status
```

## 3. Timezone

```text
kickoff_at trong file la gio Asia/Ho_Chi_Minh.
He thong luu kickoff_at theo Asia/Ho_Chi_Minh.
Export cung dung Asia/Ho_Chi_Minh.
```

Neu file nguon la UTC hoac timezone khac, operation phai convert truoc hoac ghi them cot `source_timezone` va import service phai convert ro rang. Khong auto guess timezone.

## 4. Match code convention

```text
WC2026-M001
WC2026-M002
...
```

Neu can giu ngan trong DB/UI, co the dung `M001` nhung unique theo `season_code + match_code`.

## 5. TBD teams

Cho phep:

```text
home_team=TBD
away_team=TBD
```

Khi doi TBD thanh doi that:

- Chi duoc update khi match chua co bet, hoac
- Neu da co bet, update phai co audit log va khong lam doi market/outcome snapshot cua bet cu.

## 6. Import workflow

```text
Upload file
-> Parse
-> Validate all rows
-> Preview
-> Download error file neu co loi
-> Confirm
-> Execute in batch
-> Import log
-> Audit log
```

## 7. Validation

Bat buoc:

- `season_code` ton tai.
- `match_code` unique theo season.
- `kickoff_at` parse duoc theo Asia/Ho_Chi_Minh.
- `status` chi duoc `DRAFT` hoac `SCHEDULED` khi import fixtures.
- Khong overwrite match da co bet neu khong co Super Admin override.
- File khong duoc vuot size limit cau hinh.

## 8. Re-import policy

Cho phep re-import khi:

- Chua co bet lien quan.
- Chi update metadata an toan: venue, TBD team, kickoff_at neu market chua open.
- Co import batch id va audit log.

Khong cho re-import am tham khi:

- Market da OPEN.
- Da co bet.
- Da co settlement.

## 9. Tests

Can co test:

- Import valid fixtures.
- Reject duplicate `season_code + match_code`.
- Reject invalid datetime.
- Reject overwrite match co bet.
- Parse Asia/Ho_Chi_Minh dung.
- Generate import error file.
