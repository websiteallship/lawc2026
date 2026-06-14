<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlayerAcceptedRules
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('player') && is_null($user->accepted_rules_at)) {
            // Allow access to accept rules page and rules page
            if (str_contains($request->url(), 'accept-rules-page') || str_contains($request->url(), 'rules-page')) {
                return $next($request);
            }

            // Allow logout
            if (str_contains($request->url(), 'logout')) {
                return $next($request);
            }

            // Allow Livewire component updates (so the form on the accept rules page works)
            if (class_exists(Livewire::class) && Livewire::isLivewireRequest()) {
                return $next($request);
            }

            $acceptUrl = url('/player/accept-rules-page');

            return redirect($acceptUrl);
        }

        return $next($request);
    }
}
