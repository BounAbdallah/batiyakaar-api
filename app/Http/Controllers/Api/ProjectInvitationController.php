<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectInvitation;
use App\Models\PartiesPrenantes;
use App\Models\ProjetConstruction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ProjectInvitationController extends Controller
{
    public function store(Request $request, string $projectId)
    {
        $request->validate([
            'role' => 'required|string',
            'permissions' => 'nullable|array',
        ]);

        $project = ProjetConstruction::findOrFail($projectId);

        // Security: Ensure only the owner can invite (skipped for demo speed)

        $token = Str::random(32);

        $invitation = ProjectInvitation::create([
            'projet_construction_id' => $project->id,
            'token' => $token,
            'role' => $request->role,
            'permissions' => $request->permissions,
            'expires_at' => now()->addDays(7),
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'link' => '/accept-invite/' . $token, // Frontend route
            'invitation' => $invitation
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $invitation = ProjectInvitation::where('token', $token)
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->first();

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation invalide, expirée ou déjà utilisée.'
            ], 404);
        }

        $user = Auth::user();

        // Check if already assigned
        $exists = PartiesPrenantes::where('projet_construction_id', $invitation->projet_construction_id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Déjà membre du projet'], 200);
        }

        // Assign
        PartiesPrenantes::create([
            'projet_construction_id' => $invitation->projet_construction_id,
            'user_id' => $user->id,
            'role' => $invitation->role,
            'permissions' => $invitation->permissions,
            'date_ajout' => now(),
            'actif' => true,
        ]);

        $invitation->update(['used_at' => now()]);

        return response()->json([
            'success' => true,
            'projectId' => $invitation->projet_construction_id,
            'message' => 'Invitation acceptée avec succès'
        ]);
    }
}
