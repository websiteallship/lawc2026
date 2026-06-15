<?php

namespace App\Jobs;

use App\Domain\Achievement\Services\AchievementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckAchievementJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $userId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AchievementService $service): void
    {
        $service->checkAndAward($this->userId);
    }
}
