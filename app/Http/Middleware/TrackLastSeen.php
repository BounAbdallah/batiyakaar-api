<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class TrackLastSeen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for user in request (which handles multiple guards if resolved)
        $user = $request->user();

        if ($user) {
            // Update only if last update was more than 1 minute ago to reduce DB writes
            if (!$user->last_seen_at || $user->last_seen_at->diffInMinutes(now()) > 1) {
                $user->last_seen_at = now();
                $user->saveQuietly(); // Don't trigger updated_at
            }
        }

        return $next($request);
    }
}
