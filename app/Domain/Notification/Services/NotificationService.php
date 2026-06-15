<?php

namespace App\Domain\Notification\Services;

use App\Models\Achievement;
use App\Models\Bet;
use App\Models\Market;
use App\Models\User;
use App\Models\WalletLedger;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class NotificationService
{
    /**
     * Thông báo market sắp đóng (Code: MARKET_CLOSING_SOON)
     */
    public function notifyMarketClosingSoon(User $user, Market $market): void
    {
        Notification::make()
            ->title('Market sắp đóng')
            ->body("Trận đấu {$market->match->home_team} vs {$market->match->away_team} sẽ đóng dự đoán trong thời gian tới.")
            ->warning()
            ->icon('heroicon-o-clock')
            ->actions([
                Action::make('view')
                    ->label('Dự đoán ngay')
                    ->markAsRead()
                    ->url('/player/match/' . $market->match->id),
            ])
            ->sendToDatabase($user);
    }

    /**
     * Thông báo nhiều market sắp đóng cùng lúc (gom nhóm)
     * @param User $user
     * @param \App\Models\FootballMatch[]|\Illuminate\Support\Collection $matches
     */
    public function notifyMatchesClosingSoon(User $user, $matches): void
    {
        if (count($matches) === 1) {
            $match = $matches[0] ?? $matches->first();
            $title = 'Trận đấu sắp đóng dự đoán';
            $body = "Trận đấu {$match->home_team} vs {$match->away_team} sắp diễn ra. Đặt cược ngay!";
            $url = '/player/match/' . $match->id;
        } else {
            $count = count($matches);
            $title = "Có {$count} trận đấu sắp đóng!";
            $body = "Có {$count} trận đấu sắp diễn ra. Hãy đưa ra dự đoán của bạn trước khi quá muộn.";
            $url = '/player/matches'; // Go to match list
        }

        Notification::make()
            ->title($title)
            ->body($body)
            ->warning()
            ->icon('heroicon-o-clock')
            ->actions([
                Action::make('view')
                    ->label('Dự đoán ngay')
                    ->markAsRead()
                    ->url($url),
            ])
            ->sendToDatabase($user);
    }

    /**
     * Thông báo kết quả vé cược sau settlement (Code: BET_SETTLED)
     */
    public function notifyBetSettled(User $user, Bet $bet): void
    {
        $isWin = $bet->net_result > 0;
        
        $notification = Notification::make()
            ->title($isWin ? 'Vé cược thắng!' : 'Vé cược đã xử lý')
            ->body("Vé dự đoán trận {$bet->match->home_team} vs {$bet->match->away_team} đã có kết quả. " . ($isWin ? "Bạn nhận được {$bet->net_result} lá." : "Kết quả không như mong đợi."))
            ->icon($isWin ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle');

        if ($isWin) {
            $notification->success();
        } else {
            $notification->danger();
        }

        $notification->actions([
            Action::make('view')
                ->label('Xem lịch sử')
                ->markAsRead()
                ->url('/player/my-bets-page'),
        ])->sendToDatabase($user);
    }

    /**
     * Thông báo nhận huy hiệu (Code: ACHIEVEMENT_UNLOCKED)
     */
    public function notifyAchievementUnlocked(User $user, Achievement $achievement): void
    {
        Notification::make()
            ->title('Thành tựu mới!')
            ->body("Bạn đã mở khóa danh hiệu: {$achievement->name}")
            ->success()
            ->icon('heroicon-o-trophy')
            ->actions([
                Action::make('view')
                    ->label('Xem thành tựu')
                    ->markAsRead()
                    ->url('/player/achievements'),
            ])
            ->sendToDatabase($user);
    }

    /**
     * Thông báo khi Admin cấp thêm lá (Code: WALLET_GRANTED)
     */
    public function notifyWalletGranted(User $user, WalletLedger $ledger): void
    {
        Notification::make()
            ->title('Được cấp lá mới')
            ->body("Bạn đã nhận được {$ledger->amount_available} lá. Chúc bạn chơi vui vẻ!")
            ->success()
            ->icon('heroicon-o-banknotes')
            ->actions([
                Action::make('view')
                    ->label('Kiểm tra ví')
                    ->markAsRead()
                    ->url('/player/wallet-history-page'),
            ])
            ->sendToDatabase($user);
    }

    /**
     * Thông báo market bị hủy và hoàn vé (Code: MARKET_VOIDED)
     */
    public function notifyMarketVoided(User $user, Market $market): void
    {
        Notification::make()
            ->title('Market bị hủy')
            ->body("Dự đoán của bạn cho trận {$market->match->home_team} vs {$market->match->away_team} đã bị hủy. Lá đã được hoàn lại.")
            ->warning()
            ->icon('heroicon-o-exclamation-triangle')
            ->actions([
                Action::make('view')
                    ->label('Kiểm tra ví')
                    ->markAsRead()
                    ->url('/player/wallet-history-page'),
            ])
            ->sendToDatabase($user);
    }

    /**
     * Thông báo khi kết quả bị thay đổi do Correction (Code: SETTLEMENT_CORRECTED)
     */
    public function notifySettlementCorrected(User $user, Bet $bet, WalletLedger $ledger): void
    {
        Notification::make()
            ->title('Điều chỉnh kết quả')
            ->body("Vé cược trận {$bet->match->home_team} vs {$bet->match->away_team} đã được điều chỉnh lại kết quả.")
            ->info()
            ->icon('heroicon-o-arrow-path')
            ->actions([
                Action::make('view')
                    ->label('Xem phiếu cược')
                    ->markAsRead()
                    ->url('/player/my-bets-page'),
            ])
            ->sendToDatabase($user);
    }

    /**
     * Thông báo có bảng xếp hạng mới (Code: LEADERBOARD_UPDATED)
     */
    public function notifyLeaderboardUpdated(User $user, string $message): void
    {
        Notification::make()
            ->title('Cập nhật Bảng xếp hạng')
            ->body($message)
            ->info()
            ->icon('heroicon-o-chart-bar')
            ->actions([
                Action::make('view')
                    ->label('Xem Bảng vàng')
                    ->markAsRead()
                    ->url('/player/leaderboard-page'),
            ])
            ->sendToDatabase($user);
    }
}
