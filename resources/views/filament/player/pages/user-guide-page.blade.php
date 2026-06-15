<x-filament-panels::page>
    <x-filament::card class="prose max-w-none dark:prose-invert">
        <h1>Thể lệ người chơi - Dự đoán Lá</h1>

        <h2>1. Giới thiệu</h2>
        <p><strong>Dự đoán Lá</strong> là game dự đoán bóng đá nội bộ dành cho nhân sự công ty. Người chơi sử dụng điểm ảo gọi là <strong>lá</strong> để tham gia dự đoán kết quả trận đấu.</p>
        <p>Hệ thống chỉ phục vụ mục đích giải trí và gắn kết nội bộ.</p>

        <h2>2. Lá là gì?</h2>
        <p><strong>Lá</strong> là điểm ảo trong hệ thống. Lá dùng để:</p>
        <ul>
            <li>Đặt phiếu dự đoán.</li>
            <li>Ghi nhận kết quả đúng/sai.</li>
            <li>Xếp hạng cá nhân.</li>
            <li>Tạo sự vui vẻ trong mùa giải.</li>
        </ul>
        <p class="text-danger-600 font-bold dark:text-danger-400">Lá không phải tiền. Lá không có giá trị quy đổi.</p>

        <h2>3. Quy định bắt buộc về lá</h2>
        <p>Người chơi cần hiểu rõ:</p>
        <ul>
            <li>Lá là điểm ảo nội bộ.</li>
            <li>Lá không có giá trị quy đổi thành tiền, hiện vật hoặc dịch vụ.</li>
            <li>Không được mua bán, chuyển nhượng lá cho người khác.</li>
            <li>Không được dùng lá để thỏa thuận lợi ích bên ngoài hệ thống.</li>
        </ul>
        <p>Nếu vi phạm, tài khoản có thể bị khóa và phiếu dự đoán có thể bị hủy theo quyết định của ban tổ chức.</p>

        <h2>4. Tài khoản người chơi</h2>
        <ul>
            <li>Mỗi người chơi sử dụng tài khoản do admin cấp.</li>
            <li>Không tự đăng ký tài khoản, không chia sẻ hoặc dùng tài khoản của người khác.</li>
            <li>Nếu quên mật khẩu, liên hệ admin.</li>
        </ul>

        <h2>5. Cách nhận lá và Số dư</h2>
        <p>Người chơi được admin cấp lá theo mùa giải hoặc theo rule nội bộ. Ví dụ: Bắt đầu mùa giải với 1.000 lá.</p>
        <table class="table-auto w-full text-left mt-4">
            <thead>
                <tr>
                    <th class="border px-4 py-2">Loại số dư</th>
                    <th class="border px-4 py-2">Ý nghĩa</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border px-4 py-2">Lá khả dụng</td>
                    <td class="border px-4 py-2">Số lá có thể dùng để đặt dự đoán</td>
                </tr>
                <tr>
                    <td class="border px-4 py-2">Lá đang khóa</td>
                    <td class="border px-4 py-2">Số lá đã dùng cho phiếu đang chờ kết quả</td>
                </tr>
            </tbody>
        </table>

        <h2>6. Dự đoán và Thời gian đóng</h2>
        <p>Để đặt dự đoán: Chọn trận đấu &rarr; Chọn mốc dự đoán (Cả trận, H1, H2...) &rarr; Chọn loại kèo (Tỉ số, Handicap, Tài/Xỉu) &rarr; Nhập số lá và xác nhận.</p>
        <p>Mỗi trận đấu có thời gian đóng riêng. Quá thời gian đóng sẽ <strong>không thể đặt, sửa hoặc hủy phiếu.</strong> Thời gian trên hệ thống là thời gian chuẩn.</p>

        <h2>7. Handicap Châu Á</h2>
        <p>Handicap là kèo cộng/trừ bàn ảo cho đội nhà hoặc đội khách. Quý khách vui lòng tham khảo các tỷ lệ phổ biến:</p>
        <table class="table-auto w-full text-left mt-4">
            <thead>
                <tr>
                    <th class="border px-4 py-2">Line</th>
                    <th class="border px-4 py-2">Ý nghĩa cơ bản</th>
                </tr>
            </thead>
            <tbody>
                <tr><td class="border px-4 py-2">0</td><td class="border px-4 py-2">Hòa thì hoàn lá</td></tr>
                <tr><td class="border px-4 py-2">-0.5</td><td class="border px-4 py-2">Đội được chọn phải thắng</td></tr>
                <tr><td class="border px-4 py-2">+0.5</td><td class="border px-4 py-2">Đội được chọn thắng hoặc hòa là thắng</td></tr>
                <tr><td class="border px-4 py-2">-1.0</td><td class="border px-4 py-2">Thắng 1 thì hoàn lá, thắng 2+ thì thắng</td></tr>
                <tr><td class="border px-4 py-2">-0.25</td><td class="border px-4 py-2">Nửa line 0, nửa line -0.5</td></tr>
                <tr><td class="border px-4 py-2">-0.75</td><td class="border px-4 py-2">Nửa line -0.5, nửa line -1.0</td></tr>
            </tbody>
        </table>

        <h2>8. Tài/Xỉu</h2>
        <p>Tài/Xỉu là dự đoán tổng số bàn thắng của trận đấu (hoặc hiệp).</p>
        <ul>
            <li><strong>Tài:</strong> Tổng số bàn thắng lớn hơn line đặt.</li>
            <li><strong>Xỉu:</strong> Tổng số bàn thắng nhỏ hơn line đặt.</li>
        </ul>

        <h2>9. Trạng thái phiếu dự đoán</h2>
        <ul>
            <li><strong>Pending:</strong> Đang chờ kết quả</li>
            <li><strong>Won / Half Won:</strong> Thắng đủ / Thắng nửa</li>
            <li><strong>Lost / Half Lost:</strong> Thua đủ / Thua nửa</li>
            <li><strong>Push / Voided:</strong> Hòa kèo / Phiếu bị hủy (Hoàn lại lá)</li>
        </ul>

        <h2>10. Các hành vi không được phép</h2>
        <p>Người chơi không được: Mua bán lá, chuyển lá, dùng nhiều tài khoản, lợi dụng lỗi hệ thống, rủ rê cá cược tiền thật.</p>

        <p class="mt-8 font-bold text-center">Chúc các bạn chơi game vui vẻ, văn minh!</p>
    </x-filament::card>
</x-filament-panels::page>
