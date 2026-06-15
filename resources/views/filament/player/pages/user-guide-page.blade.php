<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Banner/Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary-600 to-indigo-600 p-8 text-white shadow-lg dark:from-primary-700 dark:to-indigo-700">
            <div class="relative z-10 max-w-2xl">
                <h1 class="text-3xl font-extrabold tracking-tight">Hướng Dẫn & Thể Lệ Chơi</h1>
                <p class="mt-2 text-primary-100 text-lg">
                    Chào mừng bạn đến với <strong>Dự đoán Lá</strong>. Hệ thống dự đoán bóng đá nội bộ giải trí. Hãy nắm rõ quy tắc để có trải nghiệm tốt nhất!
                </p>
            </div>
            <div class="absolute -right-10 -bottom-10 opacity-10">
                <x-heroicon-o-information-circle class="w-64 h-64 text-white" />
            </div>
        </div>

        <!-- 3 Cột Tổng Quan -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <x-filament::card class="flex flex-col items-center text-center p-6">
                <div class="rounded-full bg-primary-100 p-3 dark:bg-primary-950/50">
                    <x-heroicon-o-currency-dollar class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                </div>
                <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Lá Là Điểm Ảo</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Mỗi người chơi bắt đầu mùa giải với số Lá mặc định (ví dụ 1,000 Lá). Dùng để tích lũy và đua TOP.
                </p>
            </x-filament::card>

            <x-filament::card class="flex flex-col items-center text-center p-6">
                <div class="rounded-full bg-danger-100 p-3 dark:bg-danger-950/50">
                    <x-heroicon-o-shield-check class="w-8 h-8 text-danger-600 dark:text-danger-400" />
                </div>
                <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Nghiêm Cấm Quy Đổi</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Không có giá trị quy đổi tiền thật, không mua bán/chuyển nhượng Lá dưới mọi hình thức.
                </p>
            </x-filament::card>

            <x-filament::card class="flex flex-col items-center text-center p-6">
                <div class="rounded-full bg-success-100 p-3 dark:bg-success-950/50">
                    <x-heroicon-o-trophy class="w-8 h-8 text-success-600 dark:text-success-400" />
                </div>
                <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Đua Top Nhận Thưởng</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Vinh danh bảng xếp hạng cá nhân dựa trên tổng số Lá, tỷ lệ thắng và ROI đạt được.
                </p>
            </x-filament::card>
        </div>

        <!-- Section chính dùng x-filament::tabs -->
        <x-filament::card>
            <div x-data="{ activeTab: 'general' }" class="space-y-6">
                <!-- Navigation Tabs -->
                <div class="flex flex-wrap border-b border-gray-200 dark:border-gray-800 gap-2 pb-px">
                    <button 
                        @click="activeTab = 'general'"
                        :class="activeTab === 'general' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="border-b-2 px-4 py-2 text-sm font-semibold transition-all">
                        Quy Định Chung
                    </button>
                    <button 
                        @click="activeTab = 'odds'"
                        :class="activeTab === 'odds' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="border-b-2 px-4 py-2 text-sm font-semibold transition-all">
                        Cách Chơi & Tính Kèo
                    </button>
                    <button 
                        @click="activeTab = 'terms'"
                        :class="activeTab === 'terms' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="border-b-2 px-4 py-2 text-sm font-semibold transition-all">
                        Mốc Trận & Xử Lý Lỗi
                    </button>
                </div>

                <!-- Tab: Quy định chung -->
                <div x-show="activeTab === 'general'" class="space-y-6 prose dark:prose-invert max-w-none">
                    <h3>1. Giới thiệu tổng quan</h3>
                    <p>Hệ thống <strong>Dự đoán Lá</strong> hoạt động như một sân chơi giải trí nội bộ. Bạn được cấp tài khoản và mật khẩu trực tiếp từ Ban Tổ Chức (BTC) và tự quản lý số Lá khả dụng của mình.</p>

                    <h3>2. Cam kết pháp lý (Bắt buộc)</h3>
                    <blockquote class="border-l-4 border-danger-500 bg-danger-50 p-4 dark:bg-danger-950/20 rounded-r-lg">
                        <span class="font-bold text-danger-800 dark:text-danger-300">Tuyên bố miễn trừ trách nhiệm:</span><br>
                        "Tôi hiểu rằng Dự đoán Lá là game điểm ảo nội bộ. Lá không phải tiền, không có giá trị quy đổi thành tiền, hiện vật hoặc dịch vụ. Tôi không mua bán, chuyển nhượng hoặc dùng lá cho bất kỳ thỏa thuận lợi ích nào ngoài hệ thống."
                    </blockquote>

                    <h3>3. Các loại số dư trong Ví</h3>
                    <ul>
                        <li><strong>Lá khả dụng (Available):</strong> Số lượng Lá bạn đang sở hữu và có thể dùng để đặt phiếu dự đoán mới.</li>
                        <li><strong>Lá đang khóa (Locked):</strong> Số lượng Lá đã đặt vào các trận đấu chưa kết thúc/chưa được tính thưởng. Số Lá này tạm thời bị khóa cho đến khi có kết quả chính thức.</li>
                        <li><strong>Tổng Lá:</strong> Tổng của Lá khả dụng và Lá đang khóa.</li>
                    </ul>
                </div>

                <!-- Tab: Cách chơi & Tính kèo -->
                <div x-show="activeTab === 'odds'" class="space-y-6 prose dark:prose-invert max-w-none">
                    <h3>1. Dự đoán Tỉ số chính xác (Exact Score)</h3>
                    <p>Người chơi chọn tỉ số cụ thể sau khi kết thúc thời gian thi đấu chính thức (90 phút + bù giờ).</p>
                    <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-100 dark:border-gray-800">
                        <span class="font-bold text-primary-600">Cách tính:</span> Nếu đúng, bạn nhận được: <br>
                        <code class="text-sm bg-gray-200 dark:bg-gray-800 px-2 py-1 rounded">Lá nhận = Số Lá đặt × (1 + Tỷ lệ ăn)</code><br>
                        <em>Ví dụ: Đặt 100 Lá vào tỉ số 2-1 (ăn 6.00). Thắng nhận 700 Lá (gồm 100 Lá vốn + 600 Lá thắng). Thua mất 100 Lá.</em>
                    </div>

                    <h3>2. Dự đoán Handicap châu Á (Kèo chấp)</h3>
                    <p>Handicap cộng hoặc trừ một số lượng bàn thắng ảo vào tỉ số chung cuộc của đội bạn chọn.</p>
                    
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full text-sm border-collapse text-left">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-900">
                                    <th class="border px-4 py-2 font-bold">Tỷ lệ Chấp</th>
                                    <th class="border px-4 py-2 font-bold">Kịch bản kết quả</th>
                                    <th class="border px-4 py-2 font-bold">Cách xử lý Lá đặt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="border px-4 py-2 font-bold">Chấp Đồng banh (0)</td>
                                    <td class="border px-4 py-2">Hòa trận đấu</td>
                                    <td class="border px-4 py-2">Hoàn trả lại 100% số Lá đã đặt (Push)</td>
                                </tr>
                                <tr>
                                    <td class="border px-4 py-2 font-bold">Chấp Nửa trái (-0.5)</td>
                                    <td class="border px-4 py-2">Đội chấp thắng (bất kể tỉ số)</td>
                                    <td class="border px-4 py-2">Thắng đủ (Nhận vốn + Lãi)</td>
                                </tr>
                                <tr>
                                    <td class="border px-4 py-2 font-bold">Chấp Đồng banh nửa trái (-0.25)</td>
                                    <td class="border px-4 py-2">Hòa trận đấu</td>
                                    <td class="border px-4 py-2">Thua nửa tiền (Mất 50% số Lá, hoàn 50% số Lá)</td>
                                </tr>
                                <tr>
                                    <td class="border px-4 py-2 font-bold">Chấp Nửa một (-0.75)</td>
                                    <td class="border px-4 py-2">Đội chấp chỉ thắng cách biệt đúng 1 bàn</td>
                                    <td class="border px-4 py-2">Thắng nửa tiền (Nhận vốn + 50% tiền lãi)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3>3. Dự đoán Tài/Xỉu (Over/Under)</h3>
                    <p>Dự đoán tổng số bàn thắng ghi được của cả hai đội nằm trên (Tài) hoặc dưới (Xỉu) mốc nhà cái đưa ra.</p>
                    <ul>
                        <li><strong>Tài (Over):</strong> Tổng bàn thắng chung cuộc lớn hơn mốc chấp.</li>
                        <li><strong>Xỉu (Under):</strong> Tổng bàn thắng chung cuộc nhỏ hơn mốc chấp.</li>
                    </ul>
                </div>

                <!-- Tab: Mốc trận & Xử lý lỗi -->
                <div x-show="activeTab === 'terms'" class="space-y-6 prose dark:prose-invert max-w-none">
                    <h3>1. Các mốc thời gian thi đấu</h3>
                    <ul>
                        <li><strong>Cả trận (Full Time):</strong> Tính kết quả trong 90 phút thi đấu chính thức kèm thời gian bù giờ. Không tính hiệp phụ và luân lưu.</li>
                        <li><strong>Hiệp 1 (First Half):</strong> Tính kết quả từ phút thứ 1 đến hết bù giờ hiệp 1.</li>
                        <li><strong>Hiệp 2 (Second Half):</strong> Chỉ tính riêng số bàn thắng được ghi trong hiệp thi đấu thứ 2 (kèm bù giờ hiệp 2).</li>
                        <li><strong>Hiệp phụ / Penalty:</strong> Chỉ áp dụng cho các trận knock-out và tính riêng biệt nếu có xuất hiện kèo trên hệ thống.</li>
                    </ul>

                    <h3>2. Quy định đóng kèo</h3>
                    <p>Mỗi thị trường (market) có thời gian khóa kèo tự động dựa trên thời gian thực tế của trận đấu. Khi đã quá thời gian đóng, hệ thống từ chối mọi yêu cầu đặt cược, thay đổi hoặc hoàn trả phiếu.</p>

                    <h3>3. Hỗ trợ sự cố & Tranh chấp</h3>
                    <p>Trong trường hợp phát sinh lỗi hiển thị hoặc nghi ngờ kết quả tính toán sai lệch, vui lòng liên hệ admin kèm các thông tin:</p>
                    <ul>
                        <li>Mã phiếu dự đoán (Public Code).</li>
                        <li>Ảnh chụp màn hình (nếu có).</li>
                        <li>Nội dung mô tả lỗi cụ thể.</li>
                    </ul>
                </div>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
