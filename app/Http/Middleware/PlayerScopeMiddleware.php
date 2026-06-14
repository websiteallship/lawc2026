<?php

namespace App\Http\Middleware;

use App\Models\Bet;
use App\Models\WalletLedger;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PlayerScopeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $userId = Auth::id();
            
            Bet::addGlobalScope('player_isolation', function (Builder $builder) use ($userId) {
                $builder->where('user_id', $userId);
            });
            
            WalletLedger::addGlobalScope('player_isolation', function (Builder $builder) use ($userId) {
                $builder->where('user_id', $userId);
            });
        }

        return $next($request);
    }
}
