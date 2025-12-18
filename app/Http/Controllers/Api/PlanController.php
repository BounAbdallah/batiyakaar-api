<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Only show public plans (exclude private custom plans)
        $plans = Plan::actif()->where('est_public', true)->get();
        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $plan = Plan::find($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $plan
        ]);
    }

    /**
     * Validate access token for private plan
     */
    public function validateToken(Request $request)
    {
        $planId = $request->input('plan_id');
        $token = $request->input('token');

        if (!$planId || !$token) {
            return response()->json([
                'success' => false,
                'message' => 'Plan ID et token requis'
            ], 400);
        }

        $plan = Plan::where('id', $planId)
            ->where('access_token', $token)
            ->where('est_personnalise', true)
            ->first();

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Lien invalide ou expiré'
            ], 404);
        }

        // Check if token is expired
        if ($plan->token_expires_at && now()->isAfter($plan->token_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce lien a expiré. Veuillez contacter notre équipe.'
            ], 410);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token valide',
            'data' => $plan
        ]);
    }

    /**
     * Create a new plan (Admin only)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'prix_mensuel' => 'required|numeric|min:0',
            'prix_annuel' => 'nullable|numeric|min:0',
            'limite_utilisateurs' => 'required|integer|min:-1',
            'limite_biens' => 'required|integer|min:-1',
            'fonctionnalites' => 'nullable|array',
            'actif' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $plan = Plan::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Plan créé avec succès',
            'data' => $plan
        ], 201);
    }

    /**
     * Update an existing plan (Admin only)
     */
    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'prix_mensuel' => 'sometimes|required|numeric|min:0',
            'prix_annuel' => 'nullable|numeric|min:0',
            'limite_utilisateurs' => 'sometimes|required|integer|min:-1',
            'limite_biens' => 'sometimes|required|integer|min:-1',
            'fonctionnalites' => 'nullable|array',
            'actif' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $plan->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Plan mis à jour avec succès',
            'data' => $plan
        ]);
    }

    /**
     * Delete a plan (Admin only)
     */
    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);

        // Check if plan is being used by any agencies
        if ($plan->agences()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce plan car il est utilisé par des agences'
            ], 400);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan supprimé avec succès'
        ]);
    }

    /**
     * Get all agencies subscribed to a specific plan (Admin only)
     */
    public function getSubscribers($id)
    {
        $plan = Plan::findOrFail($id);

        // Get all agencies with active subscriptions to this plan
        $agencies = $plan->agences()
            ->with([
                'user',
                'abonnement' => function ($query) use ($id) {
                    $query->where('plan_id', $id);
                }
            ])
            ->get()
            ->map(function ($agence) {
                return [
                    'id' => $agence->id,
                    'raison_sociale' => $agence->raison_sociale,
                    'email' => $agence->user->email ?? null,
                    'telephone' => $agence->user->telephone ?? null,
                    'adresse' => $agence->adresse,
                    'abonnement' => $agence->abonnement ? [
                        'statut' => $agence->abonnement->statut,
                        'date_debut' => $agence->abonnement->date_debut,
                        'date_fin' => $agence->abonnement->date_fin,
                    ] : null
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'plan' => $plan,
                'subscribers' => $agencies,
                'total_subscribers' => $agencies->count()
            ]
        ]);
    }
}
