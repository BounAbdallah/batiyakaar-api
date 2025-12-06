<?php

namespace App\Http\Controllers\Api;

use App\Models\Incident;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    /**
     * Display a listing of incidents
     */
    public function index(Request $request)
    {
        $query = Incident::query();

        // Filters
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('priorite')) {
            $query->where('priorite', $request->priorite);
        }

        if ($request->has('bail_id')) {
            $query->where('bail_id', $request->bail_id);
        }

        if ($request->has('locataire_id')) {
            $query->where('locataire_id', $request->locataire_id);
        }

        // Include relationships
        $query->with([
            'bail.bien',
            'locataire.user',
            'technicien'
        ]);

        // Pagination
        $incidents = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $incidents
        ]);
    }

    /**
     * Store a newly created incident
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bail_id' => 'required|exists:baux,id',
            'locataire_id' => 'required|exists:locataires,id',
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'categorie' => 'required|in:plomberie,electricite,serrurerie,climatisation,autre',
            'priorite' => 'required|in:faible,moyenne,haute,urgente',
        ]);

        $validated['statut'] = 'ouvert';
        $validated['date_declaration'] = now();

        $incident = Incident::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Incident créé avec succès',
            'data' => $incident->load(['bail.bien', 'locataire.user'])
        ], 201);
    }

    /**
     * Display the specified incident
     */
    public function show(string $id)
    {
        $incident = Incident::with([
            'bail.bien.bailleur.user',
            'bail.agence.user',
            'locataire.user',
            'technicien'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $incident
        ]);
    }

    /**
     * Update the specified incident
     */
    public function update(Request $request, string $id)
    {
        $incident = Incident::findOrFail($id);

        $validated = $request->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'categorie' => 'sometimes|in:plomberie,electricite,serrurerie,climatisation,autre',
            'priorite' => 'sometimes|in:faible,moyenne,haute,urgente',
            'statut' => 'sometimes|in:ouvert,en_cours,resolu,ferme',
            'technicien_id' => 'nullable|exists:techniciens,id',
        ]);

        // If resolved, set resolution date
        if (isset($validated['statut']) && $validated['statut'] === 'resolu' && !$incident->date_resolution) {
            $validated['date_resolution'] = now();
        }

        $incident->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Incident mis à jour avec succès',
            'data' => $incident->load(['technicien'])
        ]);
    }

    /**
     * Remove the specified incident
     */
    public function destroy(string $id)
    {
        $incident = Incident::findOrFail($id);
        $incident->delete();

        return response()->json([
            'success' => true,
            'message' => 'Incident supprimé avec succès'
        ]);
    }

    /**
     * Assign incident to technician
     */
    public function assign(Request $request, string $id)
    {
        $incident = Incident::findOrFail($id);

        $validated = $request->validate([
            'technicien_id' => 'required|exists:techniciens,id',
        ]);

        $incident->update([
            'technicien_id' => $validated['technicien_id'],
            'statut' => 'en_cours'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Incident assigné avec succès',
            'data' => $incident->load('technicien')
        ]);
    }

    /**
     * Mark incident as resolved
     */
    public function resolve(string $id)
    {
        $incident = Incident::findOrFail($id);

        $incident->update([
            'statut' => 'resolu',
            'date_resolution' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Incident marqué comme résolu',
            'data' => $incident
        ]);
    }
}
