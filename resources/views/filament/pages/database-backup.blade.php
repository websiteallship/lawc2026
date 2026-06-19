<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Danh sách bản sao lưu
        </x-slot>

        <x-slot name="description">
            Các bản sao lưu cơ sở dữ liệu đã được tạo. Bạn có thể tải xuống hoặc xóa chúng.
        </x-slot>

        <div class="mt-4 overflow-x-auto ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl">
            <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3.5 sm:px-6 font-semibold whitespace-nowrap text-sm text-gray-950 dark:text-white">
                            Tên file sao lưu
                        </th>
                        <th class="px-4 py-3.5 sm:px-6 font-semibold whitespace-nowrap text-sm text-gray-950 dark:text-white">
                            Kích thước
                        </th>
                        <th class="px-4 py-3.5 sm:px-6 font-semibold whitespace-nowrap text-sm text-gray-950 dark:text-white">
                            Ngày tạo
                        </th>
                        <th class="px-4 py-3.5 sm:px-6 font-semibold whitespace-nowrap text-sm text-gray-950 dark:text-white text-right">
                            Thao tác
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5 bg-white dark:bg-gray-900">
                    @forelse($this->backups as $backup)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                            <td class="px-4 py-4 sm:px-6 whitespace-nowrap">
                                <div class="flex items-center gap-x-3">
                                    <x-filament::icon
                                        icon="heroicon-m-archive-box"
                                        class="h-5 w-5 text-gray-400 dark:text-gray-500 shrink-0"
                                    />
                                    <span class="font-medium text-sm text-gray-950 dark:text-white">
                                        {{ $backup['name'] }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4 sm:px-6 whitespace-nowrap">
                                <x-filament::badge color="gray">
                                    {{ $backup['size'] }}
                                </x-filament::badge>
                            </td>
                            <td class="px-4 py-4 sm:px-6 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $backup['date'] }}
                            </td>
                            <td class="px-4 py-4 sm:px-6 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-x-3">
                                    <x-filament::icon-button 
                                        color="info" 
                                        wire:click="downloadBackup('{{ $backup['path'] }}')"
                                        icon="heroicon-m-arrow-down-tray"
                                        tooltip="Tải xuống"
                                    />
                                    
                                    <x-filament::icon-button 
                                        color="danger" 
                                        wire:click="deleteBackup('{{ $backup['path'] }}')"
                                        wire:confirm="Bạn có chắc chắn muốn xóa bản sao lưu này?"
                                        icon="heroicon-m-trash"
                                        tooltip="Xóa"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 sm:px-6 text-center">
                                <x-filament::empty-state
                                    heading="Chưa có bản sao lưu nào"
                                    description="Bấm 'Sao lưu Database' ở trên để tạo bản sao lưu mới."
                                    icon="heroicon-o-circle-stack"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
