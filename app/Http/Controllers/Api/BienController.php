<?php

namespace App\Http\Controllers\Api;

use App\Models\Bien;
use Illuminate\Http\Request;

class BienController extends Controller
{
    /**
     * Display a listing of properties - filtered by role
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Bien::query();

        // Filter by user role - automatic restriction
        if ($user->user_type === 'agence' && $user->agence) {
            // Agency sees only properties they manage
            $query->where('agence_id', $user->agence->id);
        } elseif ($user->user_type === 'bailleur' && $user->bailleur) {
            // Bailleur sees only their own properties
            $query->where('bailleur_id', $user->bailleur->id);
        }
        // Admin sees all properties

        // Additional filters (optional)
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // These optional filters can still be used for additional narrowing
        if ($request->has('agence_id') && $user->user_type === 'admin') {
            $query->where('agence_id', $request->agence_id);
        }

        if ($request->has('bailleur_id') && in_array($user->user_type, ['admin', 'agence'])) {
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
        // 1. Resolve Bailleur logic
        $user = $request->user();
        $bailleurId = null;
        $agenceId = null;

        if ($user->user_type === 'agence') {
            // Agency creating property
            // Must provide bailleur_id
            if (!$request->has('bailleur_id')) {
                return response()->json(['message' => 'Veuillez sélectionner un bailleur.'], 422);
            }
            $bailleurId = $request->bailleur_id;
            $agenceId = $user->agence->id;
        } elseif ($user->user_type === 'bailleur') {
            // Landlord creating property
            // Bailleur must exist, or user is bailleur
            $bailleur = \App\Models\Bailleur::where('user_id', $user->id)->first();

            // Fallback if trying to create but profile incomplete? 
            // Better to fail if not found, or redirect to profile completion.
            if (!$bailleur) {
                return response()->json(['message' => 'Profil bailleur incomplet (Pays manquant). Veuillez compléter votre profil.'], 422);
            }
            $bailleurId = $bailleur->id;
        } elseif ($user->user_type === 'admin') {
            // Admin can do anything
            $bailleurId = $request->input('bailleur_id');
        }

        if (!$bailleurId) {
            return response()->json(['message' => 'Bailleur non identifié.'], 422);
        }

        $request->merge([
            'bailleur_id' => $bailleurId,
            'agence_id' => $agenceId
        ]);

        // 2. Client-side field mapping fallback
        if (!$request->has('loyer_mensuel') && $request->has('prix_location')) {
            $request->merge(['loyer_mensuel' => $request->prix_location]);
        }

        // 3. Reference generation logic (if 'nom' is provided instead of reference)
        if (!$request->has('reference') && $request->has('nom')) {
            // Generate reference from Name + Random to ensure uniqueness
            $ref = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $request->nom), 0, 10)) . '-' . mt_rand(1000, 9999);
            $request->merge(['reference' => $ref]);
        }

        $validated = $request->validate([
            'bailleur_id' => 'required|exists:bailleurs,id',
            'agence_id' => 'nullable|exists:agences,id',
            'immeuble_id' => 'nullable|exists:immeubles,id',
            'etage_id' => 'nullable|exists:etages,id',
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
            'data' => $bien->load(['bailleur.user', 'agence.user', 'immeuble', 'etage'])
        ], 201);
    }

    /**
     * Display the specified property
     */
    /**
     * Display the specified property
     */
    public function show(Request $request, string $id)
    {
        $bien = Bien::with([
            'bailleur.user',
            'agence.user',
            'immeuble',
            'etage',
            'baux.locataire.user'
        ])->findOrFail($id);

        $this->checkOwnership($request->user(), $bien);

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
        $this->checkOwnership($request->user(), $bien);

        $validated = $request->validate([
            'reference' => 'sometimes|string|unique:biens,reference,' . $id,
            'adresse' => 'sometimes|string',
            'type' => 'sometimes|in:appartement,maison,studio,villa,commerce,terrain',
            'nombre_pieces' => 'nullable|integer|min:0',
            'surface' => 'nullable|numeric|min:0',
            'loyer_mensuel' => 'sometimes|numeric|min:0',
            'statut' => 'sometimes|in:disponible,loue,en_travaux,maintenance,indisponible,vendu',
            'immeuble_id' => 'nullable|exists:immeubles,id',
            'etage_id' => 'nullable|exists:etages,id',
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
    public function destroy(Request $request, string $id)
    {
        $bien = Bien::findOrFail($id);
        $this->checkOwnership($request->user(), $bien);
        $bien->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bien supprimé avec succès'
        ]);
    }

    /**
     * Check if user is authorized to access the property
     */
    private function checkOwnership($user, $bien)
    {
        if ($user->user_type === 'agence') {
            if ($bien->agence_id !== $user->agence->id) {
                abort(403, 'Accès non autorisé à ce bien.');
            }
        } elseif ($user->user_type === 'bailleur') {
            if ($bien->bailleur_id !== $user->bailleur->id) {
                abort(403, 'Accès non autorisé à ce bien.');
            }
        }
        // Admin gets pass
    }
    /**
     * Download the management mandate (Mandat de Gérance)
     */
    public function downloadMandat($id)
    {
        $bien = Bien::with(['bailleur.user', 'agence.user'])->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.mandat_gerance', compact('bien'));

        return $pdf->download('mandat_gerance_' . $bien->reference . '.pdf');
    }

    /**
     * View the management mandate (Mandat de Gérance)
     */
    public function viewMandat($id)
    {
        $bien = Bien::with(['bailleur.user', 'agence.user'])->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.mandat_gerance', compact('bien'));

        return $pdf->stream('mandat_gerance_' . $bien->reference . '.pdf');
    }
}
