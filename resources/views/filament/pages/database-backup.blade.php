<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Danh sách bản sao lưu
        </x-slot>

        <x-slot name="description">
            Các bản sao lưu cơ sở dữ liệu đã được tạo. Bạn có thể tải xuống hoặc xóa chúng.
        </x-slot>

        <div class="mt-4 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5" style="min-width: 100%;">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3.5" style="width: 2.5rem;">
                                <x-filament::input.checkbox disabled />
                            </th>
                            <th class="px-4 py-3.5 font-semibold text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400" style="min-width: 16rem; white-space: nowrap;">
                                Tên bản sao lưu
                            </th>
                            <th class="px-4 py-3.5 font-semibold text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400" style="width: 1px; white-space: nowrap;">
                                Kích thước
                            </th>
                            <th class="px-4 py-3.5 font-semibold text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400" style="width: 1px; white-space: nowrap;">
                                Ngày tạo
                            </th>
                            <th class="px-4 py-3.5 font-semibold text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 text-right" style="width: 1px; white-space: nowrap;">
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5 bg-white dark:bg-gray-900">
                        @forelse($this->backups as $backup)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                                <td class="px-4 py-4" style="width: 2.5rem;">
                                    <x-filament::input.checkbox disabled />
                                </td>
                                <td class="px-4 py-4" style="min-width: 16rem;">
                                    <div class="flex items-center gap-x-3">
                                        @if(($backup['is_csv'] ?? false) && ($backup['is_manual'] ?? false))
                                            {{-- CSV thủ công --}}
                                            <div class="flex items-center justify-center h-9 w-9 rounded-lg bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 shrink-0">
                                                <x-filament::icon icon="heroicon-m-document-arrow-down" class="h-5 w-5" />
                                            </div>
                                        @elseif($backup['is_csv'] ?? false)
                                            {{-- CSV tự động --}}
                                            <div class="flex items-center justify-center h-9 w-9 rounded-lg bg-success-50 dark:bg-success-900/30 text-success-600 dark:text-success-400 shrink-0">
                                                <x-filament::icon icon="heroicon-m-document-chart-bar" class="h-5 w-5" />
                                            </div>
                                        @elseif($backup['is_manual'] ?? false)
                                            {{-- DB thủ công --}}
                                            <div class="flex items-center justify-center h-9 w-9 rounded-lg bg-warning-50 dark:bg-warning-900/30 text-warning-600 dark:text-warning-400 shrink-0">
                                                <x-filament::icon icon="heroicon-m-user-circle" class="h-5 w-5" />
                                            </div>
                                        @else
                                            {{-- DB tự động --}}
                                            <div class="flex items-center justify-center h-9 w-9 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 shrink-0">
                                                <x-filament::icon icon="heroicon-m-clock" class="h-5 w-5" />
                                            </div>
                                        @endif
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-semibold text-sm text-gray-950 dark:text-white truncate">
                                                {{ $backup['name'] }}
                                            </span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                                {{ $backup['type'] }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4" style="width: 1px; white-space: nowrap;">
                                    <x-filament::badge color="gray">
                                        {{ $backup['size'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-4" style="width: 1px; white-space: nowrap;">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-sm text-gray-950 dark:text-white">
                                            {{ $backup['date_formatted'] }}
                                        </span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 tabular-nums">
                                            {{ $backup['time_formatted'] }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right" style="width: 1px; white-space: nowrap;">
                                    <div class="flex items-center justify-end gap-x-1">
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
                                <td colspan="5" class="px-4 py-12 text-center">
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

            @if($this->backupsPaginator->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-white/5 flex items-center justify-between bg-gray-50 dark:bg-white/5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Hiển thị {{ $this->backupsPaginator->firstItem() }} đến {{ $this->backupsPaginator->lastItem() }} của {{ $this->backupsPaginator->total() }} bản sao lưu
                    </p>
                    <x-filament::pagination :paginator="$this->backupsPaginator" />
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
