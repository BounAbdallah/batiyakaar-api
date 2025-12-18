<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\TeamInvitation;

class TeamController extends Controller
{
    /**
     * List team members
     */
    public function index(Request $request)
    {
        $agence = $request->user()->agence;

        // If user is a member, get the agency they belong to
        if (!$agence && $request->user()->agence_id) {
            $agence = \App\Models\Agence::find($request->user()->agence_id);
        }

        if (!$agence) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $members = $agence->equipe()->get();

        return response()->json([
            'success' => true,
            'data' => $members
        ]);
    }

    /**
     * Invite a new member
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'permissions' => 'nullable|array',
            'permissions.*.view' => 'boolean',
            'permissions.*.create' => 'boolean',
            'permissions.*.edit' => 'boolean',
            'permissions.*.delete' => 'boolean',
        ]);

        $manager = $request->user();
        $agence = $manager->agence;

        if (!$agence) {
            return response()->json(['message' => 'Seul le gérant de l\'agence peut inviter des membres.'], 403);
        }

        // Check subscription limits
        // Count manager (1) + existing team members
        // Actually limits are usually "Users", so Manager + Team
        $currentUsersCount = 1 + $agence->equipe()->count(); // 1 for the manager themselves

        $abonnement = $agence->abonnement()->actif()->first();

        $limit = 1; // Default fallback (Starter)
        if ($abonnement && $abonnement->plan) {
            $limit = $abonnement->plan->limite_utilisateurs;
        } else {
            // Handle no active sub or fetch default starter plan limit if applicable
            // For strict enforcement:
            // return response()->json(['message' => 'Abonnement requis.'], 403);
        }

        if ($currentUsersCount >= $limit) {
            return response()->json([
                'success' => false,
                'message' => "La limite d'utilisateurs pour votre plan ({$limit}) est atteinte. Veuillez passer au niveau supérieur.",
                'limit_reached' => true
            ], 403);
        }

        // Create the user
        $password = Str::random(10); // Temporary password

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone ?? null,
            'password' => Hash::make($password),
            'user_type' => 'agence', // They are agency users
            'agence_id' => $agence->id,
            'actif' => true, // Directly active? or require email verification?
            'permissions' => $request->permissions ?? null,
        ]);

        // Send invitation email
        Mail::to($user)->send(new TeamInvitation($user, $password, $manager));

        return response()->json([
            'success' => true,
            'message' => 'Membre invité avec succès',
            'data' => $user,
            'password_temp' => $password // For dev/demo only, strictly should be email only
        ], 201);
    }

    /**
     * Update a member's permissions
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*.view' => 'boolean',
            'permissions.*.create' => 'boolean',
            'permissions.*.edit' => 'boolean',
            'permissions.*.delete' => 'boolean',
        ]);

        $manager = $request->user();
        $agence = $manager->agence;

        if (!$agence) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $member = User::where('id', $id)->where('agence_id', $agence->id)->firstOrFail();

        if ($member->id === $manager->id) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier vos propres permissions.'], 403);
        }

        $member->update([
            'permissions' => $request->permissions
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permissions mises à jour avec succès',
            'data' => $member
        ]);
    }

    /**
     * Remove a member
     */
    public function destroy(Request $request, $id)
    {
        $manager = $request->user();
        $agence = $manager->agence;

        if (!$agence) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $member = User::where('id', $id)->where('agence_id', $agence->id)->firstOrFail();

        if ($member->id === $manager->id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous supprimer vous-même.'], 403);
        }

        $member->delete(); // Soft delete

        return response()->json([
            'success' => true,
            'message' => 'Membre retiré de l\'équipe'
        ]);
    }
}
