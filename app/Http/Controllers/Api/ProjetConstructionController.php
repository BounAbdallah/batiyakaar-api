<?php

namespace App\Http\Controllers\Api;

use App\Models\ProjetConstruction;
use Illuminate\Http\Request;

class ProjetConstructionController extends Controller
{
    /**
     * Display a listing of projects
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = ProjetConstruction::query();

        // Scope: Own projects or Assigned projects
        $query->where(function ($q) use ($user) {
            $q->where('bailleur_id', $user->id)
                ->orWhereHas('partiesPrenantes', function ($sq) use ($user) {
                    $sq->where('user_id', $user->id);
                });
        });

        // Filters
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('bailleur_id')) {
            $query->where('bailleur_id', $request->bailleur_id);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('titre', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        // Include relationships
        $query->with(['bailleur.user', 'chantier', 'paiementsEscrow']);

        // Pagination
        $projets = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $projets
        ]);
    }

    /**
     * Store a newly created project
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bailleur_id' => 'required|exists:bailleurs,id',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'adresse' => 'required|string',
            'budget_total' => 'required|numeric|min:0',
            'date_debut' => 'required|date',
            'date_fin_prevue' => 'nullable|date|after:date_debut',
        ]);

        $validated['budget_consomme'] = 0;
        $validated['statut'] = 'en_cours';
        $validated['pourcentage_avancement'] = 0;

        $projet = ProjetConstruction::create($validated);

        // Auto-create associated Chantier
        $projet->chantier()->create([
            'localisation' => $validated['adresse'],
            // Geocoding can be implemented here later
            'latitude' => 14.433, // Default fallback
            'longitude' => -17.016,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Projet créé avec succès',
            'data' => $projet->load(['bailleur.user', 'chantier'])
        ], 201);
    }

    /**
     * Display the specified project
     */
    public function show(string $id)
    {
        try {
            $projet = ProjetConstruction::with([
                'bailleur.user',
                'chantier.etapes.preuvesVisuelles',
                'paiementsEscrow.entrepreneur.user',
                'partiesPrenantes.user',
                'rapports',
                'commandes',
                'bien'
            ])->findOrFail($id);

            // Self-healing: Ensure Chantier exists for legacy projects
            if (!$projet->chantier) {
                $projet->chantier()->create([
                    'localisation' => $projet->adresse,
                    'latitude' => 14.433,
                    'longitude' => -17.016,
                ]);
                $projet->load('chantier');
            }

            return response()->json([
                'success' => true,
                'data' => $projet
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Update the specified project
     */
    public function update(Request $request, string $id)
    {
        $projet = ProjetConstruction::findOrFail($id);

        $validated = $request->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'adresse' => 'sometimes|string',
            'budget_total' => 'sometimes|numeric|min:0',
            'budget_consomme' => 'sometimes|numeric|min:0',
            'date_debut' => 'sometimes|date',
            'date_fin_prevue' => 'nullable|date',
            'statut' => 'sometimes|in:en_cours,termine,suspendu,annule',
            'pourcentage_avancement' => 'sometimes|numeric|min:0|max:100',
        ]);

        $projet->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Projet mis à jour avec succès',
            'data' => $projet->load('bailleur.user')
        ]);
    }

    /**
     * Remove the specified project (soft delete)
     */
    public function destroy(string $id)
    {
        $projet = ProjetConstruction::findOrFail($id);
        $projet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Projet supprimé avec succès'
        ]);
    }
}
