<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  Format: "module.action" (e.g., "biens.create")
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        // Parse permission string
        [$module, $action] = explode('.', $permission);

        // Agency owners have all permissions
        if ($user->agence && !$user->agence_id) {
            return $next($request);
        }

        // Super admins have all permissions
        if ($user->user_type === 'admin') {
            return $next($request);
        }

        // Check team member permissions
        $permissions = $user->permissions ?? [];

        // If no permissions set, deny access
        if (empty($permissions)) {
            return response()->json([
                'message' => 'Accès refusé. Vous n\'avez pas les permissions nécessaires.',
                'required_permission' => $permission
            ], 403);
        }

        // Check if user has permission for this module and action
        if (!isset($permissions[$module][$action]) || !$permissions[$module][$action]) {
            return response()->json([
                'message' => 'Accès refusé. Vous n\'avez pas la permission pour cette action.',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }
}
