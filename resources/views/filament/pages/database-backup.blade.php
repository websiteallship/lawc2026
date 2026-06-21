<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Danh sách bản sao lưu
        </x-slot>

        <x-slot name="description">
            Các bản sao lưu cơ sở dữ liệu và dữ liệu CSV đã được tạo. Bạn có thể tải xuống hoặc xóa chúng.
        </x-slot>

        <div class="mt-4 ring-1 ring-gray-200 dark:ring-white/10 rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5 whitespace-nowrap">
                    <thead class="bg-gray-50 dark:bg-white/5 text-sm">
                        <tr>
                            <th scope="col" class="px-4 py-3.5 w-12 font-medium text-gray-500 dark:text-gray-400"></th>
                            <th scope="col" class="px-4 py-3.5 font-medium text-gray-500 dark:text-gray-400">
                                Tên bản sao lưu
                            </th>
                            <th scope="col" class="px-4 py-3.5 text-right font-medium text-gray-500 dark:text-gray-400">
                                Kích thước
                            </th>
                            <th scope="col" class="px-4 py-3.5 font-medium text-gray-500 dark:text-gray-400">
                                Ngày tạo
                            </th>
                            <th scope="col" class="px-4 py-3.5 text-right font-medium text-gray-500 dark:text-gray-400">
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @forelse($this->backups as $backup)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out">
                                <td class="px-4 py-4 w-12">
                                    @if(($backup['is_csv'] ?? false) && ($backup['is_manual'] ?? false))
                                        <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                                            <x-filament::icon icon="heroicon-m-document-arrow-down" class="h-5 w-5" />
                                        </div>
                                    @elseif($backup['is_csv'] ?? false)
                                        <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                                            <x-filament::icon icon="heroicon-m-document-chart-bar" class="h-5 w-5" />
                                        </div>
                                    @elseif($backup['is_manual'] ?? false)
                                        <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                                            <x-filament::icon icon="heroicon-m-user-circle" class="h-5 w-5" />
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                            <x-filament::icon icon="heroicon-m-clock" class="h-5 w-5" />
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 min-w-[16rem]">
                                    <div class="flex flex-col gap-y-1">
                                        <span class="font-semibold text-sm text-gray-950 dark:text-white truncate max-w-sm">
                                            {{ $backup['name'] }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $backup['type'] }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <x-filament::badge color="gray" class="inline-flex">
                                        {{ $backup['size'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-col gap-y-1">
                                        <span class="font-medium text-sm text-gray-950 dark:text-white">
                                            {{ $backup['date_formatted'] }}
                                        </span>
                                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                                            {{ $backup['time_formatted'] }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex items-center justify-end gap-x-2">
                                        <x-filament::icon-button
                                            color="gray"
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
                                <td colspan="5" class="px-4 py-16 text-center">
                                    <x-filament::empty-state
                                        heading="Chưa có bản sao lưu nào"
                                        description="Bấm 'Sao lưu Database' hoặc 'Backup CSV' ở trên để tạo bản sao lưu mới."
                                        icon="heroicon-o-circle-stack"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($this->backupsPaginator->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 flex flex-col md:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 shrink-0">
                        Hiển thị <span class="font-medium text-gray-950 dark:text-white">{{ $this->backupsPaginator->firstItem() }}</span> 
                        đến <span class="font-medium text-gray-950 dark:text-white">{{ $this->backupsPaginator->lastItem() }}</span> 
                        của <span class="font-medium text-gray-950 dark:text-white">{{ $this->backupsPaginator->total() }}</span> bản sao lưu
                    </p>
                    <div class="flex-1 w-full md:w-auto flex justify-end">
                        <x-filament::pagination :paginator="$this->backupsPaginator" />
                    </div>
                </div>
            @else
                <div class="px-4 py-3 border-t border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Tổng cộng: <span class="font-medium text-gray-950 dark:text-white">{{ $this->backupsPaginator->total() }}</span> bản sao lưu
                    </p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
