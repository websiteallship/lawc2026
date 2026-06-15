<?php

namespace App\Livewire\Player;

use Livewire\Component;
use App\Models\Mission;
use App\Models\UserMission;

class MissionsMobileWidget extends Component
{
    public function render()
    {
        $user = auth()->user();
        if (!$user) {
            return <<<'HTML'
            <div></div>
            HTML;
        }

        return view('livewire.player.missions-mobile-widget');
    }
}
