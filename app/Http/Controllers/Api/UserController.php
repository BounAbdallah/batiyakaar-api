<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load([
            'bailleur',
            'agence.abonnement.plan',
            'entrepreneur',
            'fournisseur.catalogue',
            'locataire',
            'portefeuilleVirtuel',
            'notifications' => function ($query) {
                $query->where('lue', false)->latest()->limit(10);
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'telephone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'data' => $user
        ]);
    }

    /**
     * Get wallet balance
     */
    public function portefeuille(Request $request)
    {
        $portefeuille = $request->user()->portefeuilleVirtuel;

        return response()->json([
            'success' => true,
            'data' => $portefeuille
        ]);
    }

    /**
     * Get notifications
     */
    public function notifications(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->update(['lue' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }
}
