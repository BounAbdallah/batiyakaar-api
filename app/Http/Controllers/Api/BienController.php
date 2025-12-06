<?php

namespace App\Http\Controllers\Api;

use App\Models\Bien;
use Illuminate\Http\Request;

class BienController extends Controller
{
    /**
     * Display a listing of properties
     */
    public function index(Request $request)
    {
        $query = Bien::query();

        // Filters
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('agence_id')) {
            $query->where('agence_id', $request->agence_id);
        }

        if ($request->has('bailleur_id')) {
            $query->where('bailleur_id', $request->bailleur_id);
        }

        // Price range
        if ($request->has('loyer_min')) {
            $query->where('loyer_mensuel', '>=', $request->loyer_min);
        }

        if ($request->has('loyer_max')) {
            $query->where('loyer_mensuel', '<=', $request->loyer_max);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('reference', 'like', "%{$request->search}%")
                    ->orWhere('adresse', 'like', "%{$request->search}%");
            });
        }

        // Include relationships
        $query->with(['bailleur.user', 'agence.user', 'projetConstruction']);

        // Pagination
        $biens = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $biens
        ]);
    }

    /**
     * Store a newly created property
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bailleur_id' => 'required|exists:bailleurs,id',
            'agence_id' => 'nullable|exists:agences,id',
            'projet_construction_id' => 'nullable|exists:projets_construction,id',
            'reference' => 'required|string|unique:biens,reference',
            'adresse' => 'required|string',
            'type' => 'required|in:appartement,maison,studio,villa,commerce,terrain',
            'nombre_pieces' => 'nullable|integer|min:0',
            'surface' => 'nullable|numeric|min:0',
            'loyer_mensuel' => 'required|numeric|min:0',
        ]);

        $validated['statut'] = 'disponible';

        $bien = Bien::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bien créé avec succès',
            'data' => $bien->load(['bailleur.user', 'agence.user'])
        ], 201);
    }

    /**
     * Display the specified property
     */
    public function show(string $id)
    {
        $bien = Bien::with([
            'bailleur.user',
            'agence.user',
            'projetConstruction',
            'baux.locataire.user'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $bien
        ]);
    }

    /**
     * Update the specified property
     */
    public function update(Request $request, string $id)
    {
        $bien = Bien::findOrFail($id);

        $validated = $request->validate([
            'reference' => 'sometimes|string|unique:biens,reference,' . $id,
            'adresse' => 'sometimes|string',
            'type' => 'sometimes|in:appartement,maison,studio,villa,commerce,terrain',
            'nombre_pieces' => 'nullable|integer|min:0',
            'surface' => 'nullable|numeric|min:0',
            'loyer_mensuel' => 'sometimes|numeric|min:0',
            'statut' => 'sometimes|in:disponible,loue,en_travaux,indisponible',
        ]);

        $bien->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bien mis à jour avec succès',
            'data' => $bien->load(['bailleur.user', 'agence.user'])
        ]);
    }

    /**
     * Remove the specified property (soft delete)
     */
    public function destroy(string $id)
    {
        $bien = Bien::findOrFail($id);
        $bien->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bien supprimé avec succès'
        ]);
    }
}
