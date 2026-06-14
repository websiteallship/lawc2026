<x-filament-panels::page>
    <div class="max-w-2xl mx-auto space-y-6">
        {{-- Header --}}
        <div class="text-center flex flex-col items-center">
            <x-filament::icon icon="heroicon-o-shield-check" class="h-16 w-16 mb-3 text-emerald-500" />
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Xác nhận thể lệ tham gia</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Vui lòng đọc và xác nhận trước khi chơi</p>
        </div>

        {{-- Rules box --}}
        <x-filament::card>
            <div class="space-y-4">
                <p class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-clipboard-document-list" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                    Quy định bắt buộc
                </p>
                <ul class="space-y-3">
                    <li class="flex gap-3">
                        <x-filament::icon icon="heroicon-m-check" class="h-5 w-5 text-emerald-500 shrink-0 mt-0.5" />
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            <strong>"Lá" là điểm ảo nội bộ</strong> — không phải tiền thật.
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <x-filament::icon icon="heroicon-m-check" class="h-5 w-5 text-emerald-500 shrink-0 mt-0.5" />
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            <strong>"Lá" không có giá trị quy đổi</strong> thành tiền, hiện vật hoặc dịch vụ.
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <x-filament::icon icon="heroicon-m-check" class="h-5 w-5 text-emerald-500 shrink-0 mt-0.5" />
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            <strong>Không mua, bán hoặc chuyển "lá"</strong> cho người khác dưới bất kỳ hình thức nào.
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <x-filament::icon icon="heroicon-m-check" class="h-5 w-5 text-emerald-500 shrink-0 mt-0.5" />
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            <strong>Hệ thống chỉ phục vụ giải trí nội bộ</strong> — không phải nền tảng cá cược.
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <x-filament::icon icon="heroicon-m-check" class="h-5 w-5 text-emerald-500 shrink-0 mt-0.5" />
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            <strong>Mọi kết quả</strong> do ban tổ chức xác nhận theo thể lệ đã công bố.
                        </span>
                    </li>
                </ul>

                <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ App\Filament\Player\Pages\RulesPage::getUrl() }}"
                       class="text-sm text-emerald-600 hover:underline flex items-center gap-1 inline-flex">
                        <x-filament::icon icon="heroicon-m-book-open" class="h-4 w-4" />
                        Xem đầy đủ thể lệ &rarr;
                    </a>
                </div>
            </div>
        </x-filament::card>

        {{-- Disclaimer box --}}
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4">
            <p class="text-sm text-amber-800 dark:text-amber-200 italic">
                "Dự đoán "Lá" là game điểm ảo nội bộ dành cho nhân sự công ty. "Lá" không phải tiền, không có giá trị quy đổi, và hệ thống chỉ dùng cho mục đích giải trí."
            </p>
        </div>

        {{-- Checkbox + button --}}
        <x-filament::card>
            <form wire:submit="accept" class="space-y-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox"
                           wire:model.live="agreed"
                           class="mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        Tôi xác nhận đã đọc và hiểu rằng <strong>Dự đoán "Lá" là game điểm ảo nội bộ</strong>.
                        "Lá" không phải tiền, không có giá trị quy đổi thành tiền, hiện vật hoặc dịch vụ.
                        Tôi không mua bán, chuyển nhượng hoặc dùng "lá" cho bất kỳ thỏa thuận lợi ích nào ngoài hệ thống.
                    </span>
                </label>

                <x-filament::button
                    type="submit"
                    size="xl"
                    class="w-full"
                    :disabled="!$agreed"
                >
                    <div class="flex items-center gap-2 justify-center">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5" />
                        <span>Tôi đã hiểu và đồng ý — Vào chơi!</span>
                    </div>
                </x-filament::button>
            </form>
        </x-filament::card>
    </div>
</x-filament-panels::page>
