<x-filament-panels::page>
    <div class="max-w-4xl mx-auto space-y-6">
        
        {{-- TIÊU ĐỀ CHÍNH & GIẢI THÍCH NGHĨA CỦA TỪ "LÁ" --}}
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-6 text-white shadow-sm">
            <h1 class="text-3xl font-bold mb-3 flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-book-open" class="h-8 w-8" />
                Giải thích về hệ thống điểm ảo & Thể lệ
            </h1>
            <p class="text-emerald-50 text-base leading-relaxed">
                Hệ thống sử dụng một đơn vị điểm ảo thống nhất gọi là <strong>"Lá"</strong>. 
                <strong>"Lá"</strong> là điểm số nội bộ, đóng vai trò làm công cụ để nhân viên tham gia dự đoán kết quả các trận đấu bóng đá. 
                <span class="underline decoration-wavy"><strong>"Lá"</strong> hoàn toàn KHÔNG PHẢI là tiền tệ</span>, không có giá trị pháp lý, không được mua bán, chuyển nhượng và tuyệt đối không có giá trị quy đổi thành tiền mặt, hiện vật, voucher hay bất kỳ dịch vụ thực tế nào bên ngoài hệ thống.
            </p>
        </div>

        {{-- TUYÊN BỐ PHÁP LÝ --}}
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4">
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-1 flex items-center gap-2">
                <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-5 w-5 text-amber-500" />
                Tuyên bố pháp lý quan trọng
            </p>
            <p class="text-sm text-amber-700 dark:text-amber-300 leading-relaxed">
                Dự đoán <strong>"Lá"</strong> là game dự đoán điểm ảo lưu hành nội bộ dành riêng cho nhân sự công ty. 
                Hệ thống chỉ phục vụ mục đích giải trí lành mạnh, tạo không khí hào hứng trong các giải đấu bóng đá lớn và thắt chặt sự gắn kết nội bộ. 
                Mọi hành vi lợi dụng hệ thống để cá cược bằng tiền thật dưới mọi hình thức đều bị nghiêm cấm hoàn toàn.
            </p>
        </div>

        {{-- 1. GIỚI THIỆU & 2. ĐỊNH NGHĨA "LÁ" CHI TIẾT --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-o-shield-check" class="h-6 w-6 text-emerald-500" />
                1. Giới thiệu & Vai trò của "Lá"
            </h2>
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <p>
                    Dự án <strong>"Dự đoán Lá"</strong> cho phép người chơi sử dụng điểm ảo <strong>"Lá"</strong> cấp sẵn để đặt vào các mốc dự đoán của trận đấu.
                </p>
                <p class="font-medium text-gray-700 dark:text-gray-200">
                    Đơn vị <strong>"Lá"</strong> được dùng để:
                </p>
                <ul class="list-disc list-inside space-y-1 pl-2">
                    <li>Đặt phiếu dự đoán các trận đấu.</li>
                    <li>Ghi nhận kết quả dự đoán đúng hoặc sai để cộng/trừ số dư.</li>
                    <li>Làm căn cứ xếp hạng cá nhân trên Bảng xếp hạng.</li>
                    <li>Tạo hoạt động tương tác vui vẻ trong nội bộ công ty.</li>
                </ul>
            </div>
        </x-filament::card>

        {{-- 3. QUY ĐỊNH BẮT BUỘC --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-lock-closed" class="h-6 w-6 text-red-500" />
                2. Quy định bắt buộc về "Lá"
            </h2>
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <div class="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900 rounded p-3 text-red-800 dark:text-red-200 font-mono text-xs space-y-1">
                    <p>• <strong>"Lá"</strong> là điểm ảo nội bộ.</p>
                    <p>• <strong>"Lá"</strong> không có giá trị quy đổi thành tiền mặt.</p>
                    <p>• <strong>"Lá"</strong> không có giá trị quy đổi thành hiện vật.</p>
                    <p>• <strong>"Lá"</strong> không có giá trị quy đổi thành dịch vụ.</p>
                    <p>• Nghiêm cấm mua bán <strong>"Lá"</strong> bằng tiền thật.</p>
                    <p>• Nghiêm cấm chuyển nhượng hoặc tặng <strong>"Lá"</strong> giữa các tài khoản người chơi.</p>
                    <p>• Nghiêm cấm mọi hành vi lợi dụng <strong>"Lá"</strong> để thỏa thuận lợi ích cá nhân bên ngoài.</p>
                </div>
                <p class="text-xs text-red-500 font-semibold italic">
                    * Mọi tài khoản vi phạm các quy định trên sẽ bị khóa vĩnh viễn và hủy bỏ toàn bộ phiếu dự đoán liên quan mà không cần báo trước.
                </p>
            </div>
        </x-filament::card>

        {{-- 4. TÀI KHOẢN NGƯỜI CHƠI --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-user-circle" class="h-6 w-6 text-blue-500" />
                3. Quy định về tài khoản người chơi
            </h2>
            <ul class="list-disc list-inside space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <li>Tài khoản tham gia do Ban tổ chức (Admin) cấp phát trực tiếp. Hệ thống không mở tính năng tự đăng ký tự do.</li>
                <li>Mỗi người chơi chỉ được sở hữu và sử dụng một tài khoản duy nhất.</li>
                <li>Không được chia sẻ thông tin đăng nhập hoặc sử dụng chung tài khoản với người khác.</li>
                <li>Tuyệt đối không đăng nhập hoặc đặt dự đoán thay cho đồng nghiệp.</li>
                <li>Trường hợp quên thông tin tài khoản hoặc mật khẩu, người chơi cần liên hệ trực tiếp Admin để được cấp lại.</li>
            </ul>
        </x-filament::card>

        {{-- 5. CÁCH NHẬN LÁ --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-gift" class="h-6 w-6 text-purple-500" />
                4. Cơ chế cấp phát "Lá"
            </h2>
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <p>
                    Người chơi được Admin cấp <strong>"Lá"</strong> định kỳ khi bắt đầu mùa giải hoặc thông qua các sự kiện nội bộ của công ty.
                </p>
                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded border font-medium text-gray-700 dark:text-gray-200">
                    Ví dụ: Mỗi tài khoản khi kích hoạt thành công sẽ được cấp mặc định 1.000 <strong>"Lá"</strong> để làm vốn dự đoán ban đầu.
                </div>
                <p>
                    Ban tổ chức có quyền thu hồi, điều chỉnh số dư <strong>"Lá"</strong> trong tài khoản nếu phát hiện lỗi hệ thống, gian lận, hoặc để phục vụ việc thiết lập lại dữ liệu khi chuyển giao giữa các mùa giải.
                </p>
            </div>
        </x-filament::card>

        {{-- 6. CÁC LOẠI SỐ DƯ --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-wallet" class="h-6 w-6 text-teal-500" />
                5. Phân loại số dư trong ví "Lá"
            </h2>
            <div class="space-y-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Loại số dư</th>
                                <th class="px-4 py-3">Ý nghĩa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">"Lá" khả dụng</td>
                                <td class="px-4 py-3">Số lượng <strong>"Lá"</strong> tự do hiện tại bạn có thể dùng để tạo phiếu dự đoán mới.</td>
                            </tr>
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">"Lá" đang khóa</td>
                                <td class="px-4 py-3">Số lượng <strong>"Lá"</strong> đang bị giữ lại do nằm trong các phiếu dự đoán đang chờ trận đấu kết thúc.</td>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-gray-800/50">
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">Tổng số "Lá"</td>
                                <td class="px-4 py-3 font-bold text-emerald-600 dark:text-emerald-400">Tổng = "Lá" khả dụng + "Lá" đang khóa.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="text-xs text-gray-500 bg-gray-50 dark:bg-gray-850 p-3 rounded font-mono">
                    <strong>Ví dụ minh họa:</strong><br>
                    - Bạn đang có tổng cộng 1.000 <strong>"Lá"</strong>.<br>
                    - Bạn tạo một phiếu dự đoán trị giá 100 <strong>"Lá"</strong>.<br>
                    - Ngay lập tức: Số <strong>"Lá"</strong> khả dụng giảm còn 900; số <strong>"Lá"</strong> đang khóa tăng lên 100; Tổng số <strong>"Lá"</strong> vẫn được giữ nguyên là 1.000.
                </div>
            </div>
        </x-filament::card>

        {{-- 7. CÁCH ĐẶT DỰ ĐOÁN & 8. THỜI GIAN ĐÓNG --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-clipboard-document-list" class="h-6 w-6 text-gray-600" />
                6. Hướng dẫn đặt dự đoán & Thời gian khóa
            </h2>
            <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                <h3 class="font-bold text-gray-800 dark:text-white">Quy trình đặt phiếu:</h3>
                <ol class="list-decimal list-inside space-y-1.5 pl-2">
                    <li>Đăng nhập vào hệ thống.</li>
                    <li>Truy cập danh sách trận đấu đang mở.</li>
                    <li>Chọn mốc thời gian muốn dự đoán: <strong>Cả trận, Hiệp 1, Hiệp 2, Hiệp phụ, hoặc Penalty</strong>.</li>
                    <li>Chọn loại kèo: <strong>Tỉ số chính xác, Handicap (Châu Á), hoặc Tài/Xỉu</strong>.</li>
                    <li>Bấm vào tỷ lệ ăn của lựa chọn bạn tin tưởng.</li>
                    <li>Nhập số lượng <strong>"Lá"</strong> muốn đặt (Tối thiểu 10 <strong>"Lá"</strong>, tối đa 200 <strong>"Lá"</strong> cho mỗi phiếu).</li>
                    <li>Kiểm tra kỹ lưỡng thông tin hiển thị và bấm xác nhận đặt phiếu.</li>
                </ol>

                <div class="border-t pt-3 mt-3">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-1 flex items-center gap-1.5 text-xs uppercase tracking-wide">
                        <x-filament::icon icon="heroicon-m-clock" class="h-4 w-4 text-amber-500" />
                        Quy định đóng kèo:
                    </h3>
                    <p class="leading-relaxed">
                        Mỗi kèo dự đoán có một mốc thời gian đóng tự động được cấu hình trước. Khi đã quá thời gian đóng, hệ thống sẽ chặn toàn bộ thao tác đặt mới, sửa đổi hoặc hủy bỏ phiếu dự đoán. Thời gian ghi nhận trên máy chủ hệ thống là căn cứ pháp lý cuối cùng để xác định tính hợp lệ của phiếu.
                    </p>
                </div>
            </div>
        </x-filament::card>

        {{-- 9. DỰ ĐOÁN TỈ SỐ CHÍNH XÁC --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-numbered-list" class="h-6 w-6 text-emerald-600" />
                7. Dự đoán Tỉ số chính xác
            </h2>
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <p>
                    Người chơi dự đoán kết quả tỉ số bàn thắng cụ thể khi kết thúc thời gian thi đấu của mốc đã chọn.
                </p>
                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded font-mono text-xs">
                    <strong>Công thức tính kết quả:</strong><br>
                    - Nếu đoán đúng tỉ số: Số <strong>"Lá"</strong> nhận về = Số <strong>"Lá"</strong> đặt × (1 + Tỷ lệ ăn)<br>
                    - Nếu đoán sai tỉ số: Số <strong>"Lá"</strong> nhận về = 0
                </div>
                <div class="text-xs text-gray-500 bg-gray-50 dark:bg-gray-850 p-3 rounded">
                    <strong>Ví dụ:</strong> Bạn đặt 100 <strong>"Lá"</strong> vào tỉ số chính xác 2-1 với tỷ lệ ăn là 6.00. 
                    Nếu tỉ số chung cuộc là 2-1, bạn nhận về 700 <strong>"Lá"</strong> (bao gồm 100 <strong>"Lá"</strong> vốn gốc và 600 <strong>"Lá"</strong> lãi). 
                    Nếu tỉ số là bất kỳ kết quả nào khác, bạn mất hoàn toàn 100 <strong>"Lá"</strong> đã đặt.
                </div>
            </div>
        </x-filament::card>

        {{-- 10. DỰ ĐOÁN HANDICAP CHÂU Á --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-arrows-right-left" class="h-6 w-6 text-indigo-500" />
                8. Dự đoán Handicap (Kèo Chấp Châu Á)
            </h2>
            <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                <p>
                    Kèo chấp là hình thức cộng hoặc trừ một số lượng bàn thắng ảo vào tỉ số thực tế của đội bóng được chọn để tính kết quả phiếu.
                </p>

                <h3 class="font-bold text-gray-800 dark:text-white">Các loại tỷ lệ chấp thường gặp:</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-gray-700 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="px-3 py-2">Tỷ lệ chấp</th>
                                <th class="px-3 py-2">Cách tính kết quả</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-3 py-2 font-bold">Chấp 0 (Đồng banh)</td>
                                <td class="px-3 py-2">Hòa trận thì hoàn lại <strong>"Lá"</strong>. Đội nào thắng thì người chọn đội đó thắng.</td>
                            </tr>
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-3 py-2 font-bold">Chấp -0.5</td>
                                <td class="px-3 py-2">Đội chấp phải thắng trận thì mới được tính là thắng. Kết quả hòa thì đội chấp bị tính thua đủ.</td>
                            </tr>
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-3 py-2 font-bold">Chấp -1.0</td>
                                <td class="px-3 py-2">Đội chấp thắng cách biệt đúng 1 bàn thì hòa kèo (hoàn <strong>"Lá"</strong>); thắng cách biệt 2 bàn trở lên thì thắng đủ; hòa hoặc thua thì thua đủ.</td>
                            </tr>
                            <tr class="border-b dark:border-gray-700 bg-emerald-50/50 dark:bg-emerald-950/10">
                                <td class="px-3 py-2 font-bold">Chấp -0.25 (kèo 1/4)</td>
                                <td class="px-3 py-2">Số <strong>"Lá"</strong> đặt được chia đôi: 50% tính theo kèo Chấp 0 và 50% tính theo kèo Chấp -0.5.</td>
                            </tr>
                            <tr class="border-b dark:border-gray-700 bg-emerald-50/50 dark:bg-emerald-950/10">
                                <td class="px-3 py-2 font-bold">Chấp -0.75 (kèo 3/4)</td>
                                <td class="px-3 py-2">Số <strong>"Lá"</strong> đặt được chia đôi: 50% tính theo kèo Chấp -0.5 và 50% tính theo kèo Chấp -1.0.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded font-mono text-xs">
                    <strong>Ví dụ Chấp -0.75 (Đặt 100 "Lá", tỷ lệ ăn 0.90):</strong><br>
                    - 50 <strong>"Lá"</strong> đặt vào mốc chấp -0.5<br>
                    - 50 <strong>"Lá"</strong> đặt vào mốc chấp -1.0<br>
                    <strong>Kết quả thực tế:</strong><br>
                    - Đội chấp thắng cách biệt từ 2 bàn trở lên: Thắng đủ cả hai nửa kèo &rarr; Nhận về 190 <strong>"Lá"</strong>.<br>
                    - Đội chấp thắng cách biệt đúng 1 bàn: Nửa kèo -0.5 thắng đủ (+95 <strong>"Lá"</strong>), nửa kèo -1.0 hòa kèo (hoàn +50 <strong>"Lá"</strong>) &rarr; Nhận về tổng 145 <strong>"Lá"</strong>.<br>
                    - Kết quả hòa hoặc thua: Thua cả hai nửa kèo &rarr; Nhận về 0 <strong>"Lá"</strong>.
                </div>
            </div>
        </x-filament::card>

        {{-- 11. DỰ ĐOÁN TÀI/XỈU --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-presentation-chart-line" class="h-6 w-6 text-amber-500" />
                9. Dự đoán Tài / Xỉu (Over / Under)
            </h2>
            <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                <p>
                    Dự đoán dựa trên tổng số bàn thắng ghi được của cả hai đội trong mốc thời gian thi đấu được chọn.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded">
                        <p class="font-semibold text-gray-800 dark:text-white mb-2">Ví dụ với mốc Tài/Xỉu 2.5:</p>
                        <ul class="space-y-1 text-xs list-disc list-inside">
                            <li>Tổng bàn từ 3 trở lên: Chọn <strong>Tài</strong> thắng đủ, chọn <strong>Xỉu</strong> thua đủ.</li>
                            <li>Tổng bàn từ 2 trở xuống: Chọn <strong>Tài</strong> thua đủ, chọn <strong>Xỉu</strong> thắng đủ.</li>
                        </ul>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded">
                        <p class="font-semibold text-gray-800 dark:text-white mb-2">Ví dụ với mốc Tài/Xỉu 2.0:</p>
                        <ul class="space-y-1 text-xs list-disc list-inside">
                            <li>Tổng bàn từ 3 trở lên: Chọn <strong>Tài</strong> thắng, chọn <strong>Xỉu</strong> thua.</li>
                            <li>Tổng bàn đúng bằng 2: Hòa kèo, cả hai lựa chọn đều được hoàn 100% <strong>"Lá"</strong>.</li>
                            <li>Tổng bàn từ 1 trở xuống: Chọn <strong>Tài</strong> thua, chọn <strong>Xỉu</strong> thắng.</li>
                        </ul>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-850 p-3 rounded font-mono text-xs border border-gray-200 dark:border-gray-700">
                    <strong>Ví dụ với mốc Tài 2.25 (Đặt 100 "Lá", tỷ lệ ăn 0.90):</strong><br>
                    Mốc 2.25 được chia đôi thành: 50 <strong>"Lá"</strong> đặt vào Tài 2.0 và 50 <strong>"Lá"</strong> đặt vào Tài 2.5.<br>
                    - Trận đấu có 3 bàn trở lên: Thắng đủ cả hai nửa kèo &rarr; Nhận về 190 <strong>"Lá"</strong>.<br>
                    - Trận đấu có đúng 2 bàn: Nửa kèo Tài 2.0 hòa kèo (hoàn 50 <strong>"Lá"</strong>), nửa kèo Tài 2.5 thua (mất 50 <strong>"Lá"</strong>) &rarr; Nhận về tổng 50 <strong>"Lá"</strong> (mất một nửa số <strong>"Lá"</strong> đặt).<br>
                    - Trận đấu có 1 bàn hoặc không bàn thắng: Thua đủ cả hai nửa &rarr; Nhận về 0 <strong>"Lá"</strong>.
                </div>
            </div>
        </x-filament::card>

        {{-- 12. CÁC MỐC TRẬN --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-calendar" class="h-6 w-6 text-purple-600" />
                10. Các mốc thời gian áp dụng dự đoán
            </h2>
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <p>Mỗi trận đấu có thể chia làm nhiều mốc dự đoán độc lập:</p>
                <ul class="space-y-2 list-disc list-inside pl-2">
                    <li><strong>Cả trận (Full Time):</strong> Tính kết quả trong 90 phút thi đấu chính thức cộng bù giờ. Không tính hiệp phụ và đá luân lưu.</li>
                    <li><strong>Hiệp 1 (1st Half):</strong> Tính từ lúc bắt đầu trận đến khi trọng tài thổi còi kết thúc hiệp 1 (bao gồm bù giờ hiệp 1).</li>
                    <li><strong>Hiệp 2 (2nd Half):</strong> Chỉ tính số lượng bàn thắng ghi được trong phạm vi hiệp 2 (từ phút 46 đến phút 90 và bù giờ). Không cộng dồn tỉ số hiệp 1.</li>
                    <li><strong>Hiệp phụ (Extra Time):</strong> Chỉ tính kết quả trong 30 phút hiệp phụ (nếu có). Không tính kết quả 90 phút chính thức và luân lưu.</li>
                    <li><strong>Luân lưu (Penalty Shootout):</strong> Chỉ tính kết quả loạt sút luân lưu phân định thắng thua (nếu có).</li>
                </ul>
            </div>
        </x-filament::card>

        {{-- 13. KHI NÀO ĐƯỢC HOÀN LÁ --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-arrow-path" class="h-6 w-6 text-teal-600" />
                11. Quy định hoàn trả "Lá"
            </h2>
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <p>Người chơi sẽ được hệ thống hoàn lại 100% số <strong>"Lá"</strong> đã đặt vào phiếu dự đoán trong các trường hợp sau:</p>
                <ol class="list-decimal list-inside space-y-1.5 pl-2">
                    <li>Trận đấu kết thúc với kết quả rơi đúng vào điểm Hòa kèo (Push).</li>
                    <li>Kèo dự đoán (Market) bị Ban tổ chức tuyên hủy (Voided) do sai sót cấu hình hệ thống hoặc thay đổi tỷ lệ ăn nghiêm trọng.</li>
                    <li>Trận đấu bị hoãn thi đấu quá thời gian quy định hoặc bị hủy bỏ hoàn toàn mà không có lịch đá lại trong vòng 24 giờ.</li>
                    <li>Nửa kèo chấp hoặc nửa kèo tài/xỉu có kết quả là hòa (đối với các dòng kèo chia đôi như .25 hoặc .75) sẽ được hoàn lại tương ứng 50% số <strong>"Lá"</strong> đặt.</li>
                </ol>
            </div>
        </x-filament::card>

        {{-- 14. BẢNG XẾP HẠNG --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-trophy" class="h-6 w-6 text-yellow-500" />
                12. Cơ chế xếp hạng cá nhân
            </h2>
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <p>
                    Bảng xếp hạng cá nhân được hệ thống tính toán tự động dựa trên thành tích dự đoán thực tế của từng tài khoản. Các chỉ số xếp hạng chính bao gồm:
                </p>
                <ul class="list-disc list-inside space-y-1 pl-2">
                    <li>Tổng số dư <strong>"Lá"</strong> hiện có trong ví.</li>
                    <li>Lợi nhuận ròng mùa giải (Tổng <strong>"Lá"</strong> thắng trừ đi Tổng <strong>"Lá"</strong> thua).</li>
                    <li>Tỷ lệ sinh lời ROI (%).</li>
                    <li>Tỉ lệ đoán trúng (Win rate %).</li>
                </ul>
                <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold italic">
                    * Lưu ý: Để lọt vào bảng xếp hạng ROI hoặc Win rate chính thức, người chơi cần đạt số lượng phiếu dự đoán tối thiểu theo quy định của từng mùa giải để đảm bảo tính cạnh tranh công bằng.
                </p>
            </div>
        </x-filament::card>

        {{-- 15. TRẠNG THÁI PHIẾU DỰ ĐOÁN --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-chart-bar" class="h-6 w-6 text-blue-500" />
                13. Danh sách trạng thái phiếu dự đoán
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                @foreach([
                    ['Pending', 'gray', 'Phiếu đang chờ trận đấu diễn ra hoặc kết thúc.'],
                    ['Won', 'success', 'Dự đoán chính xác hoàn toàn. Nhận đủ số "Lá" thắng.'],
                    ['Lost', 'danger', 'Dự đoán sai hoàn toàn. Mất toàn bộ số "Lá" đặt.'],
                    ['Push', 'info', 'Hòa kèo. Hoàn lại 100% số "Lá" gốc đã đặt.'],
                    ['Half Won', 'success', 'Thắng một nửa kèo. Nhận 50% số "Lá" lãi + 100% số "Lá" gốc.'],
                    ['Half Lost', 'warning', 'Thua một nửa kèo. Hoàn lại 50% số "Lá" gốc đặt.'],
                    ['Voided', 'danger', 'Phiếu bị hủy do lỗi hệ thống hoặc trận đấu bị hủy. Hoàn 100% "Lá".'],
                ] as [$label, $color, $desc])
                    <div class="flex items-start gap-2.5 p-2 rounded bg-gray-50 dark:bg-gray-800/50">
                        <x-filament::badge :color="$color" class="shrink-0 mt-0.5">{{ $label }}</x-filament::badge>
                        <span class="text-gray-500 text-xs leading-normal">{{ $desc }}</span>
                    </div>
                @endforeach
            </div>
        </x-filament::card>

        {{-- 16. LỊCH SỬ VÍ --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-clock" class="h-6 w-6 text-gray-500" />
                14. Minh bạch biến động số dư ví
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                Mỗi khi tài khoản có biến động tăng hoặc giảm <strong>"Lá"</strong>, hệ thống đều tự động ghi nhận một dòng nhật ký chi tiết trong <strong>Lịch sử ví</strong>. Nhật ký bao gồm lý do biến động (Admin cấp, đặt phiếu, thắng/thua kèo, hoàn tiền do hủy trận, điều chỉnh sai sót). Người chơi có thể tự kiểm tra nhật ký để đối chiếu số dư bất kỳ lúc nào trước khi gửi khiếu nại lên Admin.
            </p>
        </x-filament::card>

        {{-- 17. CÁC HÀNH VI KHÔNG ĐƯỢC PHÉP --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-x-circle" class="h-6 w-6 text-red-500" />
                15. Các hành vi bị nghiêm cấm
            </h2>
            <ul class="list-disc list-inside space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <li>Mua bán, giao dịch <strong>"Lá"</strong> trực tiếp hoặc gián tiếp bằng tiền thật hoặc tài sản ngoài đời thực.</li>
                <li>Chuyển nhượng số dư <strong>"Lá"</strong> giữa các tài khoản người chơi dưới mọi danh nghĩa.</li>
                <li>Thỏa thuận quy đổi <strong>"Lá"</strong> lấy quà tặng, quyền lợi cá nhân hoặc dịch vụ thực tế.</li>
                <li>Sử dụng thông tin đăng nhập hoặc chơi hộ trên tài khoản của người khác.</li>
                <li>Tự ý tạo nhiều tài khoản ảo (clone) để trục lợi lượng <strong>"Lá"</strong> cấp phát ban đầu.</li>
                <li>Cố tình khai thác, lợi dụng lỗ hổng bảo mật hoặc lỗi hiển thị của hệ thống để làm sai lệch kết quả ví.</li>
                <li>Lôi kéo, tổ chức hoặc kích động đồng nghiệp sử dụng điểm ảo của hệ thống vào mục đích cá cược tiền thật bên ngoài.</li>
            </ul>
        </x-filament::card>

        {{-- 18. KHI CÓ LỖI HOẶC TRANH CHẤP --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-chat-bubble-left-right" class="h-6 w-6 text-amber-600" />
                16. Quy trình xử lý lỗi và giải quyết tranh chấp
            </h2>
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <p>
                    Khi phát hiện sự cố hiển thị số dư <strong>"Lá"</strong> hoặc sai lệch kết quả phiếu dự đoán, người chơi cần liên hệ ngay với Ban tổ chức và cung cấp các thông tin sau để đối chiếu nhanh:
                </p>
                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded font-mono text-xs space-y-1">
                    <p>• Mã phiếu dự đoán bị lỗi.</p>
                    <p>• Tên trận đấu và lựa chọn mốc kèo.</p>
                    <p>• Thời điểm thực hiện đặt phiếu.</p>
                    <p>• Ảnh chụp màn hình giao diện báo lỗi (nếu có).</p>
                </div>
                <p class="leading-relaxed">
                    Ban tổ chức sẽ đối chiếu dữ liệu thô ghi nhận trên hệ thống cơ sở dữ liệu (Database) bao gồm nhật ký biến động ví, mốc thời gian khóa kèo thực tế và thông tin tỷ lệ ăn được chụp lại tại thời điểm đặt phiếu. Quyết định xử lý của Ban tổ chức dựa trên cơ sở kỹ thuật và thể lệ công bố là quyết định cuối cùng.
                </p>
            </div>
        </x-filament::card>

        {{-- 19. CÂU XÁC NHẬN THAM GIA --}}
        <x-filament::card>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-check-circle" class="h-6 w-6 text-emerald-500" />
                17. Cam kết từ người chơi
            </h2>
            <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900 rounded p-4 text-emerald-800 dark:text-emerald-200 text-sm leading-relaxed italic">
                "Tôi hiểu rằng Dự đoán <strong>"Lá"</strong> là game điểm ảo nội bộ. <strong>"Lá"</strong> không phải tiền, không có giá trị quy đổi thành tiền, hiện vật hoặc dịch vụ. Tôi cam kết không thực hiện mua bán, chuyển nhượng hoặc sử dụng <strong>"Lá"</strong> cho bất kỳ thỏa thuận lợi ích nào ngoài hệ thống."
            </div>
        </x-filament::card>

        {{-- 20. TÓM TẮT NHANH --}}
        <x-filament::card class="bg-gray-50 dark:bg-gray-800">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2 border-b pb-2">
                <x-filament::icon icon="heroicon-m-bolt" class="h-6 w-6 text-amber-500" />
                Tóm tắt nhanh quy định
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs font-mono text-gray-600 dark:text-gray-300">
                <div>• Chơi vui vẻ, lành mạnh, gắn kết nội bộ.</div>
                <div>• <strong>"Lá"</strong> hoàn toàn là điểm ảo.</div>
                <div>• Tuyệt đối không giao dịch tiền thật.</div>
                <div>• Không hỗ trợ quy đổi quà thưởng thực tế.</div>
                <div>• Nghiêm cấm mọi hành vi chuyển nhượng <strong>"Lá"</strong>.</div>
                <div>• Đặt cược hợp lệ trước giờ đóng mốc dự đoán.</div>
                <div>• Kết quả được tính toán tự động dựa trên số liệu thực tế.</div>
                <div>• Mọi thắc mắc liên hệ Admin dựa trên mã phiếu và lịch sử ví.</div>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
