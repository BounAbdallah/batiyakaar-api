<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgenceController extends Controller
{
    public function dashboard(Request $request)
    {
        $agence = $request->user()->agence;

        $stats = [
            'biens_total' => $agence->biens()->count(),
            'biens_loues' => $agence->biens()->where('statut', 'loue')->count(),
            'biens_disponibles' => $agence->biens()->where('statut', 'disponible')->count(),
            'baux_actifs' => $agence->baux()->where('statut', 'actif')->count(),
            'incidents_ouverts' => DB::table('incidents')
                ->join('baux', 'incidents.bail_id', '=', 'baux.id')
                ->where('baux.agence_id', $agence->id)
                ->where('incidents.statut', 'ouvert')
                ->count(),
            'revenus_mois' => DB::table('ventilations')
                ->join('paiements_loyer', 'ventilations.paiement_loyer_id', '=', 'paiements_loyer.id')
                ->join('baux', 'paiements_loyer.bail_id', '=', 'baux.id')
                ->where('baux.agence_id', $agence->id)
                ->whereMonth('ventilations.date_ventilation', now()->month)
                ->sum('ventilations.montant_agence'),
            'loyers_en_retard' => DB::table('paiements_loyer')
                ->join('baux', 'paiements_loyer.bail_id', '=', 'baux.id')
                ->where('baux.agence_id', $agence->id)
                ->whereIn('paiements_loyer.statut', ['partiel', 'impaye', 'en_retard'])
                ->count(),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function show(Request $request)
    {
        $agence = $request->user()->agence->load(['user', 'abonnement.plan', 'techniciens', 'biens', 'baux']);
        return response()->json(['success' => true, 'data' => $agence]);
    }

    public function updateSettings(Request $request)
    {
        $agence = $request->user()->agence;

        $validated = $request->validate([
            'raison_sociale' => 'required|string|max:255',
            'ninea' => 'nullable|string|max:255',
            'rccm' => 'nullable|string|max:255',
            'adresse' => 'nullable|string|max:500',
            'telephone' => 'nullable|string|max:20',
        ]);

        // Update agence
        $agence->update([
            'raison_sociale' => $validated['raison_sociale'],
            'ninea' => $validated['ninea'] ?? $agence->ninea,
            'rccm' => $validated['rccm'] ?? $agence->rccm,
            'adresse' => $validated['adresse'] ?? $agence->adresse,
        ]);

        // Update user telephone if provided
        if (isset($validated['telephone'])) {
            $agence->user->update(['telephone' => $validated['telephone']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Paramètres mis à jour avec succès',
            'data' => $agence->fresh('user')
        ]);
    }

    public function uploadLogo(Request $request)
    {
        $user = $request->user();

        if (!$user->agence) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non associé à une agence'
            ], 403);
        }

        $agence = $user->agence;

        try {
            $validated = $request->validate([
                'logo' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048', // 2MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier invalide',
                'errors' => $e->errors()
            ], 422);
        }

        // Delete old logo if exists
        if ($agence->logo) {
            $oldLogoPath = storage_path('app/public/logos/' . $agence->logo);
            if (file_exists($oldLogoPath)) {
                @unlink($oldLogoPath);
            }
        }

        // Store new logo
        $file = $request->file('logo');
        $filename = 'agence_' . $agence->id . '_' . time() . '.' . $file->getClientOriginalExtension();

        // Save to storage/app/public/logos/
        $file->move(storage_path('app/public/logos'), $filename);

        // Update agence
        $agence->update(['logo' => $filename]);

        return response()->json([
            'success' => true,
            'message' => 'Logo téléchargé avec succès',
            'data' => [
                'logo' => $filename,
                'logo_url' => $agence->fresh()->logo_url
            ]
        ]);
    }

    public function deleteLogo(Request $request)
    {
        $agence = $request->user()->agence;

        if ($agence->logo) {
            // Delete file
            $logoPath = storage_path('app/public/logos/' . $agence->logo);
            if (file_exists($logoPath)) {
                unlink($logoPath);
            }

            // Update database
            $agence->update(['logo' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logo supprimé avec succès'
        ]);
    }
}
