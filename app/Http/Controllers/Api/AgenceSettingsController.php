<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use Illuminate\Http\Request;

class AgenceSettingsController extends Controller
{
    /**
     * Get agency settings
     */
    public function show()
    {
        $user = auth()->user();

        if ($user->user_type !== 'agence' || !$user->agence) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non autorisé'
            ], 403);
        }

        $agence = $user->agence;

        return response()->json([
            'success' => true,
            'data' => [
                'taux_commission_agence' => $agence->taux_commission_agence ?? 10.00,
                'taux_commission_plateforme' => $agence->taux_commission_plateforme ?? 5.00,
                'raison_sociale' => $agence->raison_sociale,
                'ninea' => $agence->ninea,
                'rccm' => $agence->rccm,
                'adresse' => $agence->adresse,
            ]
        ]);
    }

    /**
     * Update agency settings
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        if ($user->user_type !== 'agence' || !$user->agence) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non autorisé'
            ], 403);
        }

        $validated = $request->validate([
            'taux_commission_agence' => 'required|numeric|min:0|max:100',
            'taux_commission_plateforme' => 'required|numeric|min:0|max:100',
            'raison_sociale' => 'required|string|max:255',
            'ninea' => 'nullable|string|max:255',
            'rccm' => 'nullable|string|max:255',
            'adresse' => 'required|string|max:255',
        ]);

        // Ensure total doesn't exceed 100%
        $total = $validated['taux_commission_agence'] + $validated['taux_commission_plateforme'];
        if ($total > 100) {
            return response()->json([
                'success' => false,
                'message' => 'La somme des commissions ne peut pas dépasser 100%'
            ], 422);
        }

        $agence = $user->agence;
        $agence->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Paramètres mis à jour avec succès',
            'data' => $agence
        ]);
    }
}
