<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Mission\Services\MissionService;

class EvaluateMissionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;
    public $action;
    public $value;
    public bool $absolute;

    public function __construct(int $userId, string $action, int $value = 1, bool $absolute = false)
    {
        $this->userId   = $userId;
        $this->action   = $action;
        $this->value    = $value;
        $this->absolute = $absolute;
    }

    public function handle(MissionService $missionService): void
    {
        if ($this->absolute) {
            $missionService->trackAbsolute($this->userId, $this->action, $this->value);
        } else {
            $missionService->trackProgress($this->userId, $this->action, $this->value);
        }
    }
}
