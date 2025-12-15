<?php

namespace App\Http\Controllers\Api;

use App\Models\Etape;
use App\Models\Chantier;
use Illuminate\Http\Request;

class EtapeController extends Controller
{
    /**
     * Store a newly created milestone (etape) for a chantier
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'chantier_id' => 'required|exists:chantiers,id',
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'budget_prevu' => 'required|numeric|min:0',
            'date_debut_prevue' => 'required|date',
            'date_fin_prevue' => 'required|date|after_or_equal:date_debut_prevue',
            'ordre' => 'required|integer|min:1',
        ]);

        $validated['statut'] = 'non_commence'; // Default status matching DB enum

        $etape = Etape::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Étape créée avec succès',
            'data' => $etape
        ], 201);
    }

    /**
     * Update the specified milestone
     */
    public function update(Request $request, string $id)
    {
        $etape = Etape::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'budget_prevu' => 'sometimes|numeric|min:0',
            'statut' => 'sometimes|in:non_commence,en_cours,termine',
            'date_debut_effective' => 'nullable|date',
            'date_fin_effective' => 'nullable|date',
        ]);

        $etape->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Étape mise à jour avec succès',
            'data' => $etape
        ]);
    }

    /**
     * Remove the specified milestone
     */
    public function destroy(string $id)
    {
        $etape = Etape::findOrFail($id);
        $etape->delete();

        return response()->json([
            'success' => true,
            'message' => 'Étape supprimée avec succès'
        ]);
    }
}
