<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Banner/Header -->
        <div style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); padding: 2.5rem; border-radius: 1rem; color: white; position: relative; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <div style="position: relative; z-index: 10; max-width: 42rem;">
                <h1 style="font-size: 1.875rem; font-weight: 800; line-height: 1.25; color: white; margin: 0;">Hướng Dẫn & Thể Lệ Chơi</h1>
                <p style="margin-top: 0.75rem; color: #e0e7ff; font-size: 1.125rem; line-height: 1.625;">
                    Chào mừng bạn đến với <strong>Dự đoán Lá</strong>. Hệ thống dự đoán bóng đá nội bộ giải trí. Hãy nắm rõ quy tắc để có trải nghiệm tốt nhất!
                </p>
            </div>
            <div style="position: absolute; right: -2.5rem; bottom: -2.5rem; opacity: 0.1; pointer-events: none;">
                <x-heroicon-o-information-circle style="width: 16rem; height: 16rem; color: white;" />
            </div>
        </div>

        <!-- 3 Cột Tổng Quan -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            <div style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 2rem; background-color: var(--card-bg, #ffffff); border: 1px solid #e5e7eb; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" class="dark:border-gray-800 dark:bg-gray-900">
                <div style="border-radius: 9999px; padding: 1rem; background-color: rgba(79, 70, 229, 0.1);">
                    <x-heroicon-o-currency-dollar style="width: 2rem; height: 2rem; color: #4f46e5;" />
                </div>
                <h3 style="margin-top: 1rem; font-size: 1.125rem; font-weight: 700;" class="text-gray-900 dark:text-white">Lá Là Điểm Ảo</h3>
                <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280; line-height: 1.5;" class="dark:text-gray-400">
                    Mỗi người chơi bắt đầu mùa giải với số Lá mặc định (ví dụ 1,000 Lá). Dùng để tích lũy và đua TOP.
                </p>
            </div>

            <div style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 2rem; background-color: var(--card-bg, #ffffff); border: 1px solid #e5e7eb; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" class="dark:border-gray-800 dark:bg-gray-900">
                <div style="border-radius: 9999px; padding: 1rem; background-color: rgba(239, 68, 68, 0.1);">
                    <x-heroicon-o-shield-check style="width: 2rem; height: 2rem; color: #ef4444;" />
                </div>
                <h3 style="margin-top: 1rem; font-size: 1.125rem; font-weight: 700;" class="text-gray-900 dark:text-white">Nghiêm Cấm Quy Đổi</h3>
                <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280; line-height: 1.5;" class="dark:text-gray-400">
                    Không có giá trị quy đổi tiền thật, không mua bán/chuyển nhượng Lá dưới mọi hình thức.
                </p>
            </div>

            <div style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 2rem; background-color: var(--card-bg, #ffffff); border: 1px solid #e5e7eb; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" class="dark:border-gray-800 dark:bg-gray-900">
                <div style="border-radius: 9999px; padding: 1rem; background-color: rgba(34, 197, 94, 0.1);">
                    <x-heroicon-o-trophy style="width: 2rem; height: 2rem; color: #22c55e;" />
                </div>
                <h3 style="margin-top: 1rem; font-size: 1.125rem; font-weight: 700;" class="text-gray-900 dark:text-white">Đua Top Nhận Thưởng</h3>
                <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280; line-height: 1.5;" class="dark:text-gray-400">
                    Vinh danh bảng xếp hạng cá nhân dựa trên tổng số Lá, tỷ lệ thắng và ROI đạt được.
                </p>
            </div>
        </div>

        <!-- Section chính -->
        <x-filament::card>
            <div x-data="{ activeTab: 'general' }" style="display: flex; flex-direction: column; gap: 1.5rem;">
                <!-- Navigation Tabs -->
                <div style="display: flex; flex-wrap: wrap; border-bottom: 1px solid #e5e7eb; gap: 0.5rem;" class="dark:border-gray-800">
                    <button 
                        @click="activeTab = 'general'"
                        :style="activeTab === 'general' ? 'border-color: #4f46e5; color: #4f46e5;' : 'border-color: transparent; color: #6b7280;'"
                        style="border-bottom-width: 2px; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s;"
                        class="dark:text-gray-400">
                        Quy Định Chung
                    </button>
                    <button 
                        @click="activeTab = 'odds'"
                        :style="activeTab === 'odds' ? 'border-color: #4f46e5; color: #4f46e5;' : 'border-color: transparent; color: #6b7280;'"
                        style="border-bottom-width: 2px; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s;"
                        class="dark:text-gray-400">
                        Cách Chơi & Tính Kèo
                    </button>
                    <button 
                        @click="activeTab = 'terms'"
                        :style="activeTab === 'terms' ? 'border-color: #4f46e5; color: #4f46e5;' : 'border-color: transparent; color: #6b7280;'"
                        style="border-bottom-width: 2px; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s;"
                        class="dark:text-gray-400">
                        Mốc Trận & Xử Lý Lỗi
                    </button>
                </div>

                <!-- Tab: Quy định chung -->
                <div x-show="activeTab === 'general'" class="prose dark:prose-invert max-w-none" style="display: flex; flex-direction: column; gap: 1rem;">
                    <h3 style="margin-top: 0.5rem;">1. Giới thiệu tổng quan</h3>
                    <p>Hệ thống <strong>Dự đoán Lá</strong> hoạt động như một sân chơi giải trí nội bộ. Bạn được cấp tài khoản và mật khẩu trực tiếp từ Ban Tổ Chức (BTC) và tự quản lý số Lá khả dụng của mình.</p>

                    <h3>2. Cam kết pháp lý (Bắt buộc)</h3>
                    <blockquote style="border-left: 4px solid #ef4444; padding: 1rem; border-radius: 0 0.5rem 0.5rem 0; background-color: rgba(239, 68, 68, 0.05); margin: 0.5rem 0;">
                        <span style="font-weight: 700; color: #b91c1c;">Tuyên bố miễn trừ trách nhiệm:</span><br>
                        "Tôi hiểu rằng Dự đoán Lá là game điểm ảo nội bộ. Lá không phải tiền, không có giá trị quy đổi thành tiền, hiện vật hoặc dịch vụ. Tôi không mua bán, chuyển nhượng hoặc dùng lá cho bất kỳ thỏa thuận lợi ích nào ngoài hệ thống."
                    </blockquote>

                    <h3>3. Các loại số dư trong Ví</h3>
                    <ul style="list-style-type: disc; padding-left: 1.25rem;">
                        <li><strong>Lá khả dụng (Available):</strong> Số lượng Lá bạn đang sở hữu và có thể dùng để đặt phiếu dự đoán mới.</li>
                        <li><strong>Lá đang khóa (Locked):</strong> Số lượng Lá đã đặt vào các trận đấu chưa kết thúc/chưa được tính thưởng. Số Lá này tạm thời bị khóa cho đến khi có kết quả chính thức.</li>
                        <li><strong>Tổng Lá:</strong> Tổng của Lá khả dụng và Lá đang khóa.</li>
                    </ul>
                </div>

                <!-- Tab: Cách chơi & Tính kèo -->
                <div x-show="activeTab === 'odds'" class="prose dark:prose-invert max-w-none" style="display: flex; flex-direction: column; gap: 1rem;">
                    <h3 style="margin-top: 0.5rem;">1. Dự đoán Tỉ số chính xác (Exact Score)</h3>
                    <p>Người chơi chọn tỉ số cụ thể sau khi kết thúc thời gian thi đấu chính thức (90 phút + bù giờ).</p>
                    <div style="padding: 1rem; border-radius: 0.5rem; border: 1px solid rgba(79, 70, 229, 0.1); background-color: rgba(79, 70, 229, 0.05); margin: 0.5rem 0;">
                        <span style="font-weight: 700; color: #4f46e5;">Cách tính:</span> Nếu đúng, bạn nhận được: <br>
                        <code style="background-color: rgba(0,0,0,0.05); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">Lá nhận = Số Lá đặt × (1 + Tỷ lệ ăn)</code><br>
                        <em style="display: block; margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280;" class="dark:text-gray-400">Ví dụ: Đặt 100 Lá vào tỉ số 2-1 (ăn 6.00). Thắng nhận 700 Lá (gồm 100 Lá vốn + 600 Lá thắng). Thua mất 100 Lá.</em>
                    </div>

                    <h3>2. Dự đoán Handicap châu Á (Kèo chấp)</h3>
                    <p>Handicap cộng hoặc trừ một số lượng bàn thắng ảo vào tỉ số chung cuộc của đội bạn chọn.</p>
                    
                    <div style="overflow-x: auto; margin: 0.5rem 0;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                            <thead>
                                <tr style="background-color: #f3f4f6;" class="dark:bg-gray-800">
                                    <th style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem; font-weight: 700;" class="dark:border-gray-700">Tỷ lệ Chấp</th>
                                    <th style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem; font-weight: 700;" class="dark:border-gray-700">Kịch bản kết quả</th>
                                    <th style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem; font-weight: 700;" class="dark:border-gray-700">Cách xử lý Lá đặt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem; font-weight: 700;" class="dark:border-gray-700">Chấp Đồng banh (0)</td>
                                    <td style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem;" class="dark:border-gray-700">Hòa trận đấu</td>
                                    <td style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem;" class="dark:border-gray-700">Hoàn trả lại 100% số Lá đã đặt (Push)</td>
                                </tr>
                                <tr>
                                    <td style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem; font-weight: 700;" class="dark:border-gray-700">Chấp Nửa trái (-0.5)</td>
                                    <td style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem;" class="dark:border-gray-700">Đội chấp thắng (bất kể tỉ số)</td>
                                    <td style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem;" class="dark:border-gray-700">Thắng đủ (Nhận vốn + Lãi)</td>
                                </tr>
                                <tr>
                                    <td style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem; font-weight: 700;" class="dark:border-gray-700">Chấp Đồng banh nửa trái (-0.25)</td>
                                    <td style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem;" class="dark:border-gray-700">Hòa trận đấu</td>
                                    <td style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem;" class="dark:border-gray-700">Thua nửa tiền (Mất 50% số Lá, hoàn 50% số Lá)</td>
                                </tr>
                                <tr>
                                    <td style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem; font-weight: 700;" class="dark:border-gray-700">Chấp Nửa một (-0.75)</td>
                                    <td style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem;" class="dark:border-gray-700">Đội chấp chỉ thắng cách biệt đúng 1 bàn</td>
                                    <td style="border: 1px solid #e5e7eb; padding: 0.75rem 1rem;" class="dark:border-gray-700">Thắng nửa tiền (Nhận vốn + 50% tiền lãi)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3>3. Dự đoán Tài/Xỉu (Over/Under)</h3>
                    <p>Dự đoán tổng số bàn thắng ghi được của cả hai đội nằm trên (Tài) hoặc dưới (Xỉu) mốc nhà cái đưa ra.</p>
                    <ul style="list-style-type: disc; padding-left: 1.25rem;">
                        <li><strong>Tài (Over):</strong> Tổng bàn thắng chung cuộc lớn hơn mốc chấp.</li>
                        <li><strong>Xỉu (Under):</strong> Tổng bàn thắng chung cuộc nhỏ hơn mốc chấp.</li>
                    </ul>
                </div>

                <!-- Tab: Mốc trận & Xử lý lỗi -->
                <div x-show="activeTab === 'terms'" class="prose dark:prose-invert max-w-none" style="display: flex; flex-direction: column; gap: 1rem;">
                    <h3 style="margin-top: 0.5rem;">1. Các mốc thời gian thi đấu</h3>
                    <ul style="list-style-type: disc; padding-left: 1.25rem;">
                        <li><strong>Cả trận (Full Time):</strong> Tính kết quả trong 90 phút thi đấu chính thức kèm thời gian bù giờ. Không tính hiệp phụ và luân lưu.</li>
                        <li><strong>Hiệp 1 (First Half):</strong> Tính kết quả từ phút thứ 1 đến hết bù giờ hiệp 1.</li>
                        <li><strong>Hiệp 2 (Second Half):</strong> Chỉ tính riêng số bàn thắng được ghi trong hiệp thi đấu thứ 2 (kèm bù giờ hiệp 2).</li>
                        <li><strong>Hiệp phụ / Penalty:</strong> Chỉ áp dụng cho các trận knock-out và tính riêng biệt nếu có xuất hiện kèo trên hệ thống.</li>
                    </ul>

                    <h3>2. Quy định đóng kèo</h3>
                    <p>Mỗi thị trường (market) có thời gian khóa kèo tự động dựa trên thời gian thực tế của trận đấu. Khi đã quá thời gian đóng, hệ thống từ chối mọi yêu cầu đặt cược, thay đổi hoặc hoàn trả phiếu.</p>

                    <h3>3. Hỗ trợ sự cố & Tranh chấp</h3>
                    <p>Trong trường hợp phát sinh lỗi hiển thị hoặc nghi ngờ kết quả tính toán sai lệch, vui lòng liên hệ admin kèm các thông tin:</p>
                    <ul style="list-style-type: disc; padding-left: 1.25rem;">
                        <li>Mã phiếu dự đoán (Public Code).</li>
                        <li>Ảnh chụp màn hình (nếu có).</li>
                        <li>Nội dung mô tả lỗi cụ thể.</li>
                    </ul>
                </div>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
