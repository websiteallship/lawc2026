<?php

namespace App\Livewire;

use Livewire\Component;

class PlayerBalanceHeader extends Component
{
    public function render()
    {
        $user = auth()->user();
        $balance = 0;

        if ($user) {
            // Lấy ví đầu tiên (hoặc ví mặc định của mùa giải hiện tại)
            $wallet = $user->wallets()->first();
            $balance = $wallet ? $wallet->available_balance : 0;
        }

        return view('livewire.player-balance-header', [
            'balance' => $balance,
        ]);
    }
}
