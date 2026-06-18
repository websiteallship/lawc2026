<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActiveMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            $lastActive = $user->last_active_at;

            // Only update DB once every 60 minutes to reduce load
            if (!$lastActive || $lastActive->diffInMinutes(now()) > 60) {
                // Store the previous active timestamp in session for ReengagementChecker
                if ($lastActive) {
                    session()->put('previous_last_active_at', $lastActive);
                }
                
                $user->updateQuietly(['last_active_at' => now()]);
            }
        }

        return $next($request);
    }
}
