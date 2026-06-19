<?php
echo "Recalculating UserStatistics and Achievements...\n";
App\Models\User::chunk(50, function ($users) {
    foreach ($users as $user) {
        // Force recalculate user statistic to ensure longest_win_streak is correct
        app(App\Domain\Betting\Services\UserStatisticsService::class)->recalculateForUser($user->id);
        
        // Then re-evaluate achievements
        dispatch_sync(new App\Jobs\CheckAchievementJob($user->id));
        echo "Done user #{$user->id}\n";
    }
});
echo "All done!\n";
