<x-filament-panels::page>
    <x-filament::card>
        <div class="space-y-4">
            <h2 class="text-lg font-bold">Danh sách bản sao lưu (Backups)</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 border dark:border-gray-700">Tên file</th>
                            <th class="px-4 py-2 border dark:border-gray-700">Kích thước</th>
                            <th class="px-4 py-2 border dark:border-gray-700">Ngày tạo</th>
                            <th class="px-4 py-2 border dark:border-gray-700 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->backups as $backup)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-4 py-2">{{ $backup['name'] }}</td>
                                <td class="px-4 py-2">{{ $backup['size'] }}</td>
                                <td class="px-4 py-2">{{ $backup['date'] }}</td>
                                <td class="px-4 py-2 text-right space-x-2">
                                    <x-filament::button 
                                        color="info" 
                                        size="sm"
                                        wire:click="downloadBackup('{{ $backup['path'] }}')"
                                        icon="heroicon-o-arrow-down-tray">
                                        Tải xuống
                                    </x-filament::button>
                                    
                                    <x-filament::button 
                                        color="danger" 
                                        size="sm"
                                        wire:click="deleteBackup('{{ $backup['path'] }}')"
                                        wire:confirm="Bạn có chắc chắn muốn xóa bản sao lưu này?"
                                        icon="heroicon-o-trash">
                                        Xóa
                                    </x-filament::button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-center text-gray-500">
                                    Chưa có bản sao lưu nào. Hãy bấm "Sao lưu Database" ở trên.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-filament::card>
</x-filament-panels::page>
