<?php

namespace BetterFuturesStudio\FilamentLocalLogins\Filament\Pages\Auth;

use BetterFuturesStudio\FilamentLocalLogins\Concerns\HasLocalLogins;
use Filament\Auth\Pages\Login;

class LoginPage extends Login
{
    use HasLocalLogins;
}
