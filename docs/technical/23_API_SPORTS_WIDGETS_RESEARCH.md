# TÀI LIỆU NGHIÊN CỨU: API-SPORTS WIDGETS & LINEUPS

## 1. Khả năng lấy đội hình ra sân (Lineups)
- **Hỗ trợ:** API-Sports (RapidAPI) hoàn toàn hỗ trợ dữ liệu đội hình ra sân.
- **Thời điểm có data:** Thường có trước giờ bóng lăn (kick-off) khoảng 20-40 phút khi các đội chính thức công bố.
- **Hiện trạng hệ thống:** Đã kết nối API nhưng chưa parse data `lineups`. Cần code bổ sung phần này nếu muốn tự render.

## 2. Các Widget Frontend có sẵn (Plug-and-play)
API-Sports cung cấp sẵn các widget nhúng trực tiếp bằng HTML/JS, hỗ trợ custom CSS (Dark/Light mode):
1. **Games Widget:** Lịch thi đấu theo ngày/giải.
2. **Game Widget:** Chi tiết trận đấu (Lineups, Events, Stats).
3. **Standings Widget:** Bảng xếp hạng.
4. **Team Widget:** Thông tin đội bóng, danh sách đội hình.
5. **Player Widget:** Thống kê cá nhân cầu thủ.
6. **League(s) Widget:** Tổng quan và danh sách giải đấu.

## 3. Cách tính phí & Quản lý Request Quota
Các widget là **miễn phí**, nhưng mỗi lần load/refresh widget sẽ **bị trừ trực tiếp vào quota API** của tài khoản.

**Bài toán: Kéo data của 7 loại Widgets với tần suất 5 phút/lần**
- **Trường hợp 1: Gắn Widget trực tiếp ở Frontend (User Request)**
  - 1 User = 12 requests/giờ/widget = 288 requests/ngày/widget.
  - Tổng cộng 7 widgets = **2.016 requests / ngày / 1 người dùng**.
  - *Rủi ro:* Nếu có 1.000 user online, số lượng request là >2 triệu -> Cháy sạch quota API ngay lập tức.
  
- **Trường hợp 2: Kéo bằng Backend (Cronjob)**
  - Hệ thống tự gọi API lưu vào DB/Cache.
  - Tần suất 5 phút/lần = 288 lần gọi/ngày.
  - Kéo 7 endpoint tương ứng = **2.016 requests / ngày (CỐ ĐỊNH)**.
  - *Giải pháp:* Gói PRO của API-Sports ($19/tháng - 7.500 req/ngày) hoàn toàn đáp ứng được.

## 4. Kết luận thiết kế
**KHÔNG** dùng thẳng Widget của API-Sports trên Frontend cho môi trường có lượng truy cập lớn. Bắt buộc phải cấu hình **Backend Cronjob kéo data về (hoặc Webhook) -> Lưu Cache (Redis) -> Tự render UI** hoặc chế lại widget đọc từ API nội bộ để tiết kiệm chi phí.

## 5. Giám sát & Đếm Request API (Rate Limiting & Quota Management)
Để đảm bảo không bị vượt quá giới hạn (Quota) của các gói trả phí/miễn phí, hệ thống cần bổ sung tính năng theo dõi số lượng request đã sử dụng theo ngày/tuần/tháng.

**Phương án triển khai tối ưu:**
1. **Lưu trữ qua Redis:** Tăng biến đếm (increment) mỗi lần gọi HTTP. Thiết lập TTL theo ngày/tuần/tháng để tự động reset. Phương án này đảm bảo tốc độ cực nhanh (< 1ms) và không tạo ra tải cho Database.
2. **Đọc Header API:** Hầu hết các nhà cung cấp (RapidAPI, Football-Data) đều trả về Header sau mỗi request (ví dụ: `X-RateLimit-Remaining` hoặc `x-requests-available`). Hệ thống chỉ cần parse header này lưu vào Redis/DB để đồng bộ số dư quota thực tế thay vì tự đếm thủ công.
3. **Cảnh báo (Alert):** Tích hợp Job kiểm tra định kỳ (hoặc check sau mỗi request), nếu lượng quota còn lại dưới ngưỡng an toàn (vd: < 10%) sẽ gửi cảnh báo qua Telegram/Email cho Admin.

**Đánh giá ảnh hưởng:**
- **Hiệu năng:** Gần như không ảnh hưởng (bằng 0) nếu triển khai qua Redis hoặc đọc trực tiếp từ Response Header.
- **Lợi ích:** Đảm bảo hệ thống vận hành ổn định, không bị đứt gãy luồng cập nhật tỷ lệ kèo và tỷ số trực tiếp do cháy quota, đồng thời giúp Admin dễ dàng tối ưu ngân sách mua API.
