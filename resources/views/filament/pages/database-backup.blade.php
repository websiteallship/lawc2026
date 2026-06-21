<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Danh sách bản sao lưu
        </x-slot>

        <x-slot name="description">
            Các bản sao lưu cơ sở dữ liệu đã được tạo. Bạn có thể tải xuống hoặc xóa chúng.
        </x-slot>

        <div class="backup-table-wrap">
            <style>
                .backup-table-wrap { border-radius: 0.75rem; overflow: hidden; border: 1px solid rgba(0,0,0,0.07); }
                .dark .backup-table-wrap { border-color: rgba(255,255,255,0.08); }
                .backup-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
                .backup-table thead tr { background: rgba(249,250,251,1); }
                .dark .backup-table thead tr { background: rgba(255,255,255,0.04); }
                .backup-table th {
                    padding: 0.75rem 1rem;
                    font-size: 0.7rem;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.07em;
                    color: #6b7280;
                    white-space: nowrap;
                    text-align: left;
                }
                .dark .backup-table th { color: #9ca3af; }
                .backup-table td {
                    padding: 0.875rem 1rem;
                    border-top: 1px solid rgba(0,0,0,0.06);
                    vertical-align: middle;
                }
                .dark .backup-table td { border-color: rgba(255,255,255,0.06); }
                .backup-table tbody tr:hover { background: rgba(249,250,251,0.8); }
                .dark .backup-table tbody tr:hover { background: rgba(255,255,255,0.03); }
                .backup-icon {
                    display: flex; align-items: center; justify-content: center;
                    height: 2.25rem; width: 2.25rem; border-radius: 0.5rem; flex-shrink: 0;
                }
                .backup-icon-db-auto   { background: #f3f4f6; color: #6b7280; }
                .backup-icon-db-manual { background: #fffbeb; color: #d97706; }
                .backup-icon-csv-auto  { background: #f0fdf4; color: #16a34a; }
                .backup-icon-csv-manual{ background: #eff6ff; color: #2563eb; }
                .dark .backup-icon-db-auto   { background: rgba(255,255,255,0.08); color: #9ca3af; }
                .dark .backup-icon-db-manual { background: rgba(251,191,36,0.15); color: #fbbf24; }
                .dark .backup-icon-csv-auto  { background: rgba(34,197,94,0.15);  color: #4ade80; }
                .dark .backup-icon-csv-manual{ background: rgba(59,130,246,0.15); color: #60a5fa; }
                .backup-name { font-weight: 600; color: #111827; font-size: 0.875rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 24rem; }
                .dark .backup-name { color: #f9fafb; }
                .backup-sub  { font-size: 0.75rem; color: #9ca3af; margin-top: 0.125rem; }
                .backup-size { display: inline-block; padding: 0.2rem 0.6rem; font-size: 0.75rem; font-weight: 500; border-radius: 9999px; background: #f3f4f6; color: #374151; white-space: nowrap; }
                .dark .backup-size { background: rgba(255,255,255,0.08); color: #d1d5db; }
                .backup-date-main { font-weight: 500; color: #111827; font-size: 0.875rem; white-space: nowrap; }
                .dark .backup-date-main { color: #f9fafb; }
                .backup-date-time { font-size: 0.75rem; color: #9ca3af; font-variant-numeric: tabular-nums; margin-top: 0.125rem; }
                .backup-actions { display: flex; gap: 0.25rem; justify-content: flex-end; align-items: center; }
                .backup-empty { padding: 3rem 1rem; text-align: center; color: #9ca3af; }
                .backup-pagination {
                    padding: 0.75rem 1rem;
                    border-top: 1px solid rgba(0,0,0,0.06);
                    background: rgba(249,250,251,1);
                    display: flex; align-items: center; justify-content: space-between;
                }
                .dark .backup-pagination { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.06); }
                .backup-pagination-label { font-size: 0.8125rem; color: #6b7280; }
                .dark .backup-pagination-label { color: #9ca3af; }
            </style>

            <div style="overflow-x: auto;">
                <table class="backup-table">
                    <thead>
                        <tr>
                            <th style="width: 2rem;"></th>
                            <th>Tên bản sao lưu</th>
                            <th style="text-align: right;">Kích thước</th>
                            <th>Ngày tạo</th>
                            <th style="text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->backups as $backup)
                            <tr>
                                <td style="width: 2rem;">
                                    @if(($backup['is_csv'] ?? false) && ($backup['is_manual'] ?? false))
                                        <div class="backup-icon backup-icon-csv-manual">
                                            <x-filament::icon icon="heroicon-m-document-arrow-down" style="width:1.1rem;height:1.1rem;" />
                                        </div>
                                    @elseif($backup['is_csv'] ?? false)
                                        <div class="backup-icon backup-icon-csv-auto">
                                            <x-filament::icon icon="heroicon-m-document-chart-bar" style="width:1.1rem;height:1.1rem;" />
                                        </div>
                                    @elseif($backup['is_manual'] ?? false)
                                        <div class="backup-icon backup-icon-db-manual">
                                            <x-filament::icon icon="heroicon-m-user-circle" style="width:1.1rem;height:1.1rem;" />
                                        </div>
                                    @else
                                        <div class="backup-icon backup-icon-db-auto">
                                            <x-filament::icon icon="heroicon-m-clock" style="width:1.1rem;height:1.1rem;" />
                                        </div>
                                    @endif
                                </td>
                                <td style="min-width: 18rem;">
                                    <div style="display:flex;align-items:center;gap:0.75rem;">
                                        <div>
                                            <div class="backup-name">{{ $backup['name'] }}</div>
                                            <div class="backup-sub">{{ $backup['type'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <span class="backup-size">{{ $backup['size'] }}</span>
                                </td>
                                <td style="white-space: nowrap;">
                                    <div class="backup-date-main">{{ $backup['date_formatted'] }}</div>
                                    <div class="backup-date-time">{{ $backup['time_formatted'] }}</div>
                                </td>
                                <td style="white-space: nowrap;">
                                    <div class="backup-actions">
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
                                <td colspan="5" class="backup-empty">
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
                <div class="backup-pagination">
                    <p class="backup-pagination-label">
                        Hiển thị {{ $this->backupsPaginator->firstItem() }} đến {{ $this->backupsPaginator->lastItem() }} của {{ $this->backupsPaginator->total() }} bản sao lưu
                    </p>
                    <x-filament::pagination :paginator="$this->backupsPaginator" />
                </div>
            @else
                <div class="backup-pagination">
                    <p class="backup-pagination-label">
                        Tổng: {{ $this->backupsPaginator->total() }} bản sao lưu
                    </p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
