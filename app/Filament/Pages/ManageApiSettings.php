<?php

namespace App\Filament\Pages;

use App\Settings\ApiSettings;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ManageApiSettings extends SettingsPage
{
    use HasPageShield;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server';

    protected static string|\UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 3;

    protected static string $settings = ApiSettings::class;

    protected static ?string $navigationLabel = 'Cấu hình API';

    protected static ?string $title = 'Cấu hình API';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Cấu hình Đồng bộ Live Score & Tỉ số')
                    ->description('Cấu hình kết nối lấy dữ liệu trận đấu và tỉ số tự động.')
                    ->schema([
                        Forms\Components\Select::make('live_score_provider')
                            ->label('Nguồn cấp dữ liệu Live Score')
                            ->options([
                                'rapidapi' => 'RapidAPI (Khuyên dùng, yêu cầu gói PRO)',
                                'rapidapi_fallback_footballdata' => 'RapidAPI (Ưu tiên) + Fallback Football-Data',
                                'football_data' => 'Football-Data.org (Truyền thống)',
                            ])
                            ->default('rapidapi_fallback_footballdata')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('football_data_api_token')
                            ->label('Football-Data API Token')
                            ->password()
                            ->revealable()
                            ->required(),
                        Forms\Components\TextInput::make('competition_id')
                            ->label('Competition ID (Football-Data)')
                            ->default('2000')
                            ->required(),
                        Forms\Components\Toggle::make('is_auto_sync_enabled')
                            ->label('Bật tự động đồng bộ (Cronjob)')
                            ->helperText('Nếu tắt, hệ thống sẽ ngừng quét API tự động.'),
                        Forms\Components\Select::make('auto_sync_interval_minutes')
                            ->label('Tần suất quét Live Score')
                            ->options([
                                1 => 'Mỗi phút (Khuyên dùng khi có gói API trả phí)',
                                3 => 'Mỗi 3 phút',
                                5 => 'Mỗi 5 phút (Tiết kiệm quota)',
                            ])
                            ->required(),
                    ])->columns(2),
                Section::make('RapidAPI - API-Sports')
                    ->description('Cấu hình lấy tỉ lệ kèo bóng đá (Odds) & Live Score (Nhanh).')
                    ->schema([
                        Grid::make(3)->schema([
                            Forms\Components\TextInput::make('rapidapi_key')
                                ->label('RapidAPI Key')
                                ->password()
                                ->revealable()
                                ->required(),
                            Forms\Components\TextInput::make('rapidapi_host')
                                ->label('RapidAPI Host')
                                ->default('v3.football.api-sports.io')
                                ->required(),
                            Forms\Components\TextInput::make('bookmaker_id')
                                ->label('Bookmaker ID (Mặc định 8 - Bet365)')
                                ->numeric()
                                ->default(8)
                                ->required(),
                        ]),
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('is_rapidapi_auto_sync_enabled')
                                ->label('Bật tự động kéo tỷ lệ kèo (Cronjob)')
                                ->helperText('Nếu tắt, hệ thống sẽ ngừng kéo tỉ lệ kèo mới nhất.'),
                            Forms\Components\Select::make('rapidapi_auto_sync_interval_minutes')
                                ->label('Tần suất kéo Odds')
                                ->options([
                                    5 => 'Mỗi 5 phút',
                                    15 => 'Mỗi 15 phút',
                                    30 => 'Mỗi 30 phút',
                                    60 => 'Mỗi 1 giờ',
                                ])
                                ->required(),
                        ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('testConnection')
                    ->label('Test Football-Data')
                    ->icon('heroicon-o-check-circle')
                    ->action(function (ApiSettings $settings) {
                        $token = $settings->football_data_api_token;

                        if (empty($token)) {
                            Notification::make()
                                ->title('Thiếu API Token')
                                ->body('Vui lòng điền và lưu API Token trước khi kiểm tra.')
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            $response = Http::withHeaders([
                                'X-Auth-Token' => $token,
                            ])->timeout(5)->get('https://api.football-data.org/v4/competitions');

                            if ($response->successful()) {
                                Notification::make()
                                    ->title('Kết nối thành công!')
                                    ->body('Đã liên kết thành công tới Football-Data.org API.')
                                    ->success()
                                    ->send();
                            } else {
                                $error = $response->json('message') ?? 'Lỗi không xác định từ API';
                                Notification::make()
                                    ->title('Kết nối thất bại')
                                    ->body("Mã HTTP {$response->status()}: {$error}")
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Lỗi kết nối')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('testRapidApiConnection')
                    ->label('Test RapidAPI')
                    ->icon('heroicon-o-check-circle')
                    ->action(function (ApiSettings $settings) {
                        $key = $settings->rapidapi_key;
                        $host = $settings->rapidapi_host;

                        if (empty($key) || empty($host)) {
                            Notification::make()
                                ->title('Thiếu cấu hình RapidAPI')
                                ->body('Vui lòng điền và lưu Key, Host trước khi kiểm tra.')
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            $response = Http::withHeaders([
                                'X-RapidAPI-Key' => $key,
                                'X-RapidAPI-Host' => $host,
                            ])->timeout(5)->get('https://'.$host.'/status');

                            if ($response->successful()) {
                                Notification::make()
                                    ->title('Kết nối thành công!')
                                    ->body('Đã liên kết thành công tới RapidAPI.')
                                    ->success()
                                    ->send();
                            } else {
                                $error = $response->json('message') ?? 'Lỗi không xác định từ API';
                                Notification::make()
                                    ->title('Kết nối thất bại')
                                    ->body("Mã HTTP {$response->status()}: {$error}")
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Lỗi kết nối')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
                ->label('Kiểm tra kết nối')
                ->color('info')
                ->button(),

            ActionGroup::make([
                Action::make('syncApiManual')
                    ->label('Kéo Toàn bộ Lịch & Tỉ số (API)')
                    ->icon('heroicon-o-calendar-days')
                    ->requiresConfirmation()
                    ->modalHeading('Kéo Lịch & Cập nhật lại toàn bộ dữ liệu lịch sử')
                    ->modalDescription('Bạn có chắc chắn muốn kéo toàn bộ 104 trận đấu? Các trận đã kết thúc sẽ bị ghi đè kết quả hiệp đấu và sự kiện mới nhất. Quá trình này sẽ tốn chút thời gian và sử dụng 1 quota API.')
                    ->action(function () {
                        try {
                            $exitCode = Artisan::call('matches:sync-api', ['--historic' => true]);
                            
                            if ($exitCode !== 0) {
                                throw new \Exception(Artisan::output() ?: 'Lỗi không xác định khi chạy lệnh đồng bộ.');
                            }
                            
                            Notification::make()
                                ->title('Đồng bộ thành công')
                                ->body('Đã kéo lịch và kết quả toàn bộ giải đấu.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Lỗi đồng bộ')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('syncOddsManual')
                    ->label('Kéo Tỉ lệ kèo (RapidAPI)')
                    ->icon('heroicon-o-currency-dollar')
                    ->action(function () {
                        try {
                            $hasMatches = \App\Models\FootballMatch::where('status', 'SCHEDULED')
                                ->whereBetween('kickoff_at', [now(), now()->addHours(24)])
                                ->exists();

                            if (! $hasMatches) {
                                Notification::make()
                                    ->title('Không có dữ liệu mới')
                                    ->body('Không có trận đấu nào sắp diễn ra trong 24h tới. Đã bỏ qua để tiết kiệm Quota API.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            \Illuminate\Support\Facades\Bus::dispatchSync(new \App\Jobs\SyncPreMatchOddsJob());
                            
                            Notification::make()
                                ->title('Hoàn tất')
                                ->body('Đã kéo thành công tỷ lệ kèo Pre-match cho các trận sắp tới.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Lỗi đồng bộ')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('syncAllMatchDetails')
                    ->label('Kéo Chi tiết Trận đấu (Sự kiện)')
                    ->icon('heroicon-o-bars-3-bottom-left')
                    ->requiresConfirmation()
                    ->modalHeading('Kéo chi tiết sự kiện cho tất cả trận đấu')
                    ->modalDescription('Hành động này sẽ kéo các sự kiện (bàn thắng, thẻ phạt, thay người) cho toàn bộ trận đấu trong hệ thống. Hệ thống sẽ gom nhóm 20 trận/request để tiết kiệm tối đa API Quota. Quá trình có thể mất vài giây.')
                    ->action(function () {
                        try {
                            \Illuminate\Support\Facades\Bus::dispatchSync(new \App\Jobs\SyncAllMatchDetailsJob());
                            
                            Notification::make()
                                ->title('Hoàn tất')
                                ->body('Đã kéo thành công chi tiết các trận đấu.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Lỗi đồng bộ')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
                ->label('Kéo API thủ công')
                ->color('success')
                ->button()
                ->icon('heroicon-o-arrow-path'),
        ];
    }
}
