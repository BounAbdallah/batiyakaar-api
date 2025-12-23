<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFonctionnalite
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $fonctionnaliteCode): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        if (!$user->hasFonctionnalite($fonctionnaliteCode)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Cette fonctionnalité n\'est pas incluse dans votre plan d\'abonnement.'
            ], 403);
        }

        return $next($request);
    }
}
