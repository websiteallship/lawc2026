<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class DatabaseBackup extends Page
{
    use HasPageShield;
    use WithPagination;

    protected string $view = 'filament.pages.database-backup';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-circle-stack';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Hệ thống';
    }

    public static function getNavigationLabel(): string
    {
        return 'Sao lưu dữ liệu';
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Sao lưu dữ liệu (Backup)';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backup_db')
                ->label('Sao lưu Database')
                ->icon('heroicon-o-server-stack')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        if (config('database.default') === 'sqlite') {
                            $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
                            $disk = Storage::disk($diskName);
                            $backupName = config('backup.backup.name');
                            $fileName = date('Y-m-d-H-i-s') . '-db.zip';
                            
                            $disk->makeDirectory($backupName);
                            
                            $dbPath = database_path('database.sqlite');
                            
                            // Use temp file for zip to support any disk (like S3)
                            $tempDir = storage_path('app/backup-temp');
                            if (!file_exists($tempDir)) {
                                mkdir($tempDir, 0755, true);
                            }
                            $zipPath = $tempDir . '/' . $fileName;
                            
                            $zip = new \ZipArchive();
                            if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
                                $zip->addFile($dbPath, 'database.sqlite');
                                $zip->close();
                                
                                // Put to destination disk
                                $disk->putFileAs($backupName, new \Illuminate\Http\File($zipPath), $fileName);
                                unlink($zipPath); // Clean up temp file
                                
                                $this->logManualBackup($fileName);
                                
                                Notification::make()->title('Sao lưu Database thành công')->success()->send();
                            } else {
                                throw new \Exception('Không thể tạo file zip');
                            }
                        } else {
                            $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
                            $disk = Storage::disk($diskName);
                            $backupName = config('backup.backup.name');
                            $filesBefore = $disk->exists($backupName) ? $disk->files($backupName) : [];

                            $exitCode = Artisan::call('backup:run', ['--only-db' => true]);
                            $output = Artisan::output();
                            if ($exitCode === 0) {
                                $filesAfter = $disk->exists($backupName) ? $disk->files($backupName) : [];
                                $newFiles = array_diff($filesAfter, $filesBefore);
                                foreach ($newFiles as $newFile) {
                                    $this->logManualBackup(basename($newFile));
                                }
                                Notification::make()->title('Sao lưu Database thành công')->success()->send();
                            } else {
                                \Illuminate\Support\Facades\Log::error('Backup DB failed', ['exit' => $exitCode, 'output' => $output]);
                                throw new \Exception('Exit ' . $exitCode . ': ' . trim(substr(strip_tags($output), -300)));
                            }
                        }
                    } catch (\Exception $e) {
                        Notification::make()->title('Lỗi sao lưu: ' . $e->getMessage())->danger()->send();
                    }
                }),
            Action::make('backup_full')
                ->label('Sao lưu Toàn bộ')
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
                        $disk = Storage::disk($diskName);
                        $backupName = config('backup.backup.name');
                        $filesBefore = $disk->exists($backupName) ? $disk->files($backupName) : [];

                        // Chỉ backup DB để tránh timeout khi zip toàn bộ project trên VPS
                        $exitCode = Artisan::call('backup:run', ['--only-db' => true]);
                        $output = Artisan::output();

                        if ($exitCode === 0) {
                            $filesAfter = $disk->exists($backupName) ? $disk->files($backupName) : [];
                            $newFiles = array_diff($filesAfter, $filesBefore);
                            foreach ($newFiles as $newFile) {
                                $this->logManualBackup(basename($newFile));
                            }
                            Notification::make()->title('Sao lưu thành công (DB)')->success()->send();
                        } else {
                            \Illuminate\Support\Facades\Log::error('Backup full failed', [
                                'exit'   => $exitCode,
                                'output' => $output,
                            ]);
                            $detail = trim(substr(strip_tags($output), -400));
                            throw new \Exception('Exit ' . $exitCode . ': ' . ($detail ?: 'xem log laravel để biết chi tiết'));
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Lỗi sao lưu')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
            \Filament\Actions\ExportAction::make('export_bets')
                ->label('Xuất CSV Vé (Bets)')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->model(\App\Models\Bet::class)
                ->exporter(\App\Filament\Exports\BetExporter::class),
            \Filament\Actions\ExportAction::make('export_markets')
                ->label('Xuất CSV Kèo (Markets)')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->model(\App\Models\Market::class)
                ->exporter(\App\Filament\Exports\MarketExporter::class),
            \Filament\Actions\ExportAction::make('export_settlements')
                ->label('Xuất CSV Kết quả')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->model(\App\Models\Settlement::class)
                ->exporter(\App\Filament\Exports\SettlementExporter::class),
        ];
    }

    protected function logManualBackup($filename)
    {
        $disk = Storage::disk('local');
        $manualBackups = [];
        if ($disk->exists('manual_backups.json')) {
            $manualBackups = json_decode($disk->get('manual_backups.json'), true) ?? [];
        }
        $manualBackups[] = $filename;
        $disk->put('manual_backups.json', json_encode($manualBackups));
    }

    public function getAllBackupsProperty()
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $disk = Storage::disk($diskName);
        $name = config('backup.backup.name');

        if (!$disk->exists($name)) {
            return collect([]);
        }

        $files = $disk->files($name);
        
        $manualBackups = [];
        if (Storage::disk('local')->exists('manual_backups.json')) {
            $manualBackups = json_decode(Storage::disk('local')->get('manual_backups.json'), true) ?? [];
        }

        return collect($files)
            ->filter(fn ($file) => str_ends_with($file, '.zip'))
            ->map(function ($file) use ($disk, $manualBackups) {
                $filename = basename($file);
                $isManual = in_array($filename, $manualBackups);
                $carbon = \Carbon\Carbon::createFromTimestamp($disk->lastModified($file));
                
                $formattedDate = $carbon->format('d') . ' Thg ' . (int)$carbon->format('m') . ', ' . $carbon->format('Y');
                $formattedTime = $carbon->format('H:i:s');

                return [
                    'name' => $filename,
                    'path' => $file,
                    'size' => round($disk->size($file) / 1024 / 1024, 2) . ' MB',
                    'date_formatted' => $formattedDate,
                    'time_formatted' => $formattedTime,
                    'type' => $isManual ? 'Tạo thủ công bởi Admin' : 'Tạo tự động (Cronjob)',
                    'raw_date' => $carbon->toDateTimeString(),
                ];
            })
            ->sortByDesc('raw_date')
            ->values();
    }

    public function getBackupsProperty()
    {
        $currentPage = method_exists($this, 'getPage') ? $this->getPage() : ($this->page ?? 1);
        $perPage = 10;
        
        return $this->all_backups->slice(($currentPage - 1) * $perPage, $perPage)->values();
    }

    public function getBackupsPaginatorProperty()
    {
        $currentPage = method_exists($this, 'getPage') ? $this->getPage() : ($this->page ?? 1);
        $perPage = 10;
        
        return new LengthAwarePaginator(
            $this->backups,
            $this->all_backups->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    public function downloadBackup($path)
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $disk = Storage::disk($diskName);

        if ($disk->exists($path)) {
            return $disk->download($path);
        }

        Notification::make()->title('File không tồn tại')->danger()->send();
    }

    public function deleteBackup($path)
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $disk = Storage::disk($diskName);

        if ($disk->exists($path)) {
            $disk->delete($path);
            Notification::make()->title('Đã xóa bản sao lưu')->success()->send();
        } else {
            Notification::make()->title('File không tồn tại')->danger()->send();
        }
    }
}
