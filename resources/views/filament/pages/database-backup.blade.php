<x-filament-panels::page>
    <div class="fi-ta-ctn border border-gray-200 shadow-sm rounded-xl bg-white dark:bg-gray-900 dark:border-white/10 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-white/10 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Danh sách bản sao lưu
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Các bản sao lưu cơ sở dữ liệu đã được tạo. Bạn có thể tải xuống hoặc xóa chúng.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5">
                <thead class="bg-gray-55 dark:bg-gray-800/50">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Tên file sao lưu
                        </th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Kích thước
                        </th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Ngày tạo (Giờ hệ thống)
                        </th>
                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Thao tác
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5 bg-white dark:bg-transparent">
                    @forelse($this->backups as $backup)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-x-2">
                                    <x-filament::icon
                                        icon="heroicon-m-document-arrow-down"
                                        class="h-5 w-5 text-gray-400 dark:text-gray-500"
                                    />
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $backup['name'] }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                                    {{ $backup['size'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                {{ $backup['date'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                <x-filament::button 
                                    color="info" 
                                    size="sm"
                                    outlined
                                    wire:click="downloadBackup('{{ $backup['path'] }}')"
                                    icon="heroicon-m-arrow-down-tray">
                                    Tải xuống
                                </x-filament::button>
                                
                                <x-filament::button 
                                    color="danger" 
                                    size="sm"
                                    outlined
                                    wire:click="deleteBackup('{{ $backup['path'] }}')"
                                    wire:confirm="Bạn có chắc chắn muốn xóa bản sao lưu này?"
                                    icon="heroicon-m-trash">
                                    Xóa
                                </x-filament::button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center">
                                <div class="flex flex-col items-center justify-center gap-y-2">
                                    <x-filament::icon
                                        icon="heroicon-o-server-stack"
                                        class="h-8 w-8 text-gray-400 dark:text-gray-500"
                                    />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Chưa có bản sao lưu nào. Hãy bấm "Sao lưu Database" ở trên để tạo.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
