<?php

namespace App\Http\Controllers\Api;

use App\Models\Bail;
use Illuminate\Http\Request;

class BailController extends Controller
{
    /**
     * Display a listing of leases
     */
    public function index(Request $request)
    {
        $query = Bail::query();

        // Filters
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('bien_id')) {
            $query->where('bien_id', $request->bien_id);
        }

        if ($request->has('locataire_id')) {
            $query->where('locataire_id', $request->locataire_id);
        }

        if ($request->has('agence_id')) {
            $query->where('agence_id', $request->agence_id);
        }

        // Include relationships
        $query->with([
            'bien.bailleur.user',
            'locataire.user',
            'agence.user'
        ]);

        // Pagination
        $baux = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $baux
        ]);
    }

    /**
     * Store a newly created lease
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bien_id' => 'required|exists:biens,id',
            'locataire_id' => 'required|exists:locataires,id',
            'agence_id' => 'nullable|exists:agences,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'loyer_mensuel' => 'required|numeric|min:0',
            'caution' => 'required|numeric|min:0',
        ]);

        $validated['statut'] = 'actif';

        $bail = Bail::create($validated);

        // Update bien status
        $bail->bien->update(['statut' => 'loue']);

        return response()->json([
            'success' => true,
            'message' => 'Bail créé avec succès',
            'data' => $bail->load(['bien', 'locataire.user', 'agence.user'])
        ], 201);
    }

    /**
     * Display the specified lease
     */
    public function show(string $id)
    {
        $bail = Bail::with([
            'bien.bailleur.user',
            'locataire.user',
            'agence.user',
            'paiementsLoyer.quittance',
            'incidents.technicien',
            'etatsDesLieux'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $bail
        ]);
    }

    /**
     * Update the specified lease
     */
    public function update(Request $request, string $id)
    {
        $bail = Bail::findOrFail($id);

        $validated = $request->validate([
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date',
            'loyer_mensuel' => 'sometimes|numeric|min:0',
            'caution' => 'sometimes|numeric|min:0',
            'statut' => 'sometimes|in:actif,expire,resilie',
        ]);

        $bail->update($validated);

        // Update bien status if lease expired
        if (isset($validated['statut']) && $validated['statut'] !== 'actif') {
            $bail->bien->update(['statut' => 'disponible']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bail mis à jour avec succès',
            'data' => $bail->load(['bien', 'locataire.user'])
        ]);
    }

    /**
     * Remove the specified lease
     */
    public function destroy(string $id)
    {
        $bail = Bail::findOrFail($id);

        // Update bien status
        $bail->bien->update(['statut' => 'disponible']);

        $bail->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bail supprimé avec succès'
        ]);
    }
}
