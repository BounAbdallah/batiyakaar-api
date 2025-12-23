<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Agence;
use App\Models\Abonnement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import DB facade
use App\Models\Locataire;
use App\Models\Bailleur;
use App\Models\PaiementLoyer;
use App\Models\Incident;
use App\Models\Plan;

class AdminController extends Controller
{
    public function stats()
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'agences' => User::where('user_type', 'agence')->count(),
                'bailleurs' => User::where('user_type', 'bailleur')->count(),
                'locataires' => User::where('user_type', 'locataire')->count(),
            ],
            'agencies' => [
                'total' => Agence::count(),
                'active_subscriptions' => Abonnement::where('statut', 'actif')->count(),
            ],
            'revenue' => [
                // Simplified calculation based on active subscriptions * price
                // Ideally this should use a real Transaction model for subscriptions
                'current_mrr' => Abonnement::where('statut', 'actif')
                    ->join('plans', 'abonnements.plan_id', '=', 'plans.id')
                    ->sum('plans.prix_mensuel')
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function agencies(Request $request)
    {
        $query = Agence::with(['user', 'abonnement.plan'])
            ->withCount(['biens', 'baux']); // Assuming relationships exist

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('raison_sociale', 'like', "%{$search}%")
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('nom', 'like', "%{$search}%");
                });
        }

        $agencies = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $agencies
        ]);
    }

    public function showAgency($id)
    {
        $agence = Agence::with(['user', 'abonnement.plan'])->findOrFail($id);

        // Calculate stats using existing relationships and queries

        // Properties (Biens + Immeubles)
        $propertiesCount = $agence->biens()->count() + $agence->immeubles()->count();

        // Leases
        $leasesCount = $agence->baux()->count();
        $activeLeasesCount = $agence->baux()->where('statut', 'actif')->count();

        // Tenants (Distinct locataires linked to agency's leases)
        // Assuming Bail has locataire_id
        $tenantsCount = Locataire::whereHas('baux', function ($q) use ($id) {
            $q->where('agence_id', $id);
        })->count();

        // Landlords (Distinct bailleurs linked to agency's properties)
        // Assuming Bien has bailleur_id and agence_id
        $landlordsCount = Bailleur::whereHas('biens', function ($q) use ($id) {
            $q->where('agence_id', $id);
        })->count();

        // Revenue (Sum of payments for agency's leases)
        $totalRevenue = PaiementLoyer::whereHas('bail', function ($q) use ($id) {
            $q->where('agence_id', $id);
        })->where('statut', 'paye')->sum('montant');

        // Incidents (Linked to agency's leases)
        $incidentsCount = Incident::whereHas('bail', function ($q) use ($id) {
            $q->where('agence_id', $id);
        })->count();

        // Security / Sessions
        // Assuming Sanctum is used and relationship is 'tokens'
        $lastSeen = $agence->user->tokens()->orderBy('last_used_at', 'desc')->first()->last_used_at ?? $agence->user->updated_at;
        $deviceCount = $agence->user->tokens()->count();

        $stats = [
            'properties_count' => $propertiesCount,
            'tenants_count' => $tenantsCount,
            'landlords_count' => $landlordsCount,
            'leases_count' => $leasesCount,
            'active_leases_count' => $activeLeasesCount,
            'total_revenue' => $totalRevenue,
            'last_seen' => $lastSeen,
            'device_count' => $deviceCount,
            'db_usage' => [
                'records_count' => $propertiesCount + $leasesCount + $incidentsCount, // Simplified
                'approx_size_mb' => round(($propertiesCount + $leasesCount + $incidentsCount) * 0.05, 2) // Fake estimation
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'agency' => $agence,
                'stats' => $stats
            ]
        ]);
    }

    // --- Plan Management ---

    public function showPlan($id)
    {
        $plan = \App\Models\Plan::withCount([
            'abonnements' => function ($query) {
                $query->where('statut', 'actif');
            }
        ])->findOrFail($id);

        $subscribers = Abonnement::with(['agence.user'])
            ->where('plan_id', $id)
            ->whereHas('agence') // Ensure agence exists
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'plan' => $plan,
                'subscribers' => $subscribers
            ]
        ]);
    }

    public function plans()
    {
        $plans = \App\Models\Plan::withCount([
            'abonnements' => function ($query) {
                $query->where('statut', 'actif');
            }
        ])->get();
        return response()->json(['success' => true, 'data' => $plans]);
    }

    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string',
            'prix_mensuel' => 'required|numeric',
            'limite_biens' => 'required|integer',
            'limite_utilisateurs' => 'required|integer',
            'description' => 'nullable|string',
        ]);

        $plan = \App\Models\Plan::create($validated);

        return response()->json(['success' => true, 'message' => 'Plan créé', 'data' => $plan]);
    }

    public function updatePlan(Request $request, $id)
    {
        $plan = \App\Models\Plan::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'string',
            'prix_mensuel' => 'numeric',
            'limite_biens' => 'integer',
            'limite_utilisateurs' => 'integer',
            'description' => 'nullable|string',
            'actif' => 'boolean'
        ]);

        $plan->update($validated);

        return response()->json(['success' => true, 'message' => 'Plan mis à jour', 'data' => $plan]);
    }

    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);

        // Prevent banning self if admin
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier votre propre statut.'
            ], 403);
        }

        $wasInactive = !$user->actif;
        $user->actif = !$user->actif;
        $user->save();

        // Send activation email if user was just activated and is an agency
        if ($wasInactive && $user->actif && $user->user_type === 'agence') {
            try {
                $agence = $user->agence;
                $plan = $agence->abonnement?->plan;

                if ($agence && $plan) {
                    // Generate a temporary password (you might want to store this or use a password reset link instead)
                    $tempPassword = \Str::random(12);

                    \Illuminate\Support\Facades\Mail::send('emails.account-activated', [
                        'agence' => $agence,
                        'user' => $user,
                        'plan' => $plan,
                        'password' => $tempPassword
                    ], function ($message) use ($user, $agence) {
                        $message->to($user->email, $agence->raison_sociale)
                            ->subject('Votre compte Noor Immo est activé !');
                    });
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send activation email: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Statut utilisateur mis à jour.',
            'data' => [
                'actif' => $user->actif
            ]
        ]);
    }

    public function updateAgencySubscription(Request $request, $id)
    {
        $agence = Agence::findOrFail($id);

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'statut' => 'required|in:actif,en_attente,suspendu,expire',
            'duree_mois' => 'nullable|integer|min:1'
        ]);

        // Find existing or create new
        $abonnement = Abonnement::where('agence_id', $agence->id)->first();

        if (!$abonnement) {
            $abonnement = new Abonnement();
            $abonnement->agence_id = $agence->id;
            $abonnement->date_debut = now();
        }

        $abonnement->plan_id = $validated['plan_id'];
        $abonnement->statut = $validated['statut'];

        // If duration provided, update end date relative to now
        if (!empty($validated['duree_mois'])) {
            $abonnement->date_fin = now()->addMonths((int) $validated['duree_mois']);
        } elseif (!$abonnement->date_fin) {
            // Default 12 months if new
            $abonnement->date_fin = now()->addMonths(12);
        }

        $abonnement->save();

        // If activating, ensure user is active too
        if ($validated['statut'] === 'actif') {
            $agence->user->update(['actif' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Abonnement mis à jour avec succès.',
            'data' => $abonnement->load('plan')
        ]);
    }

    // --- Plan Features Management ---

    /**
     * Get features for a specific plan
     */
    public function getPlanFeatures($id)
    {
        $plan = Plan::with('fonctionnalitesActives')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $plan->fonctionnalitesActives
        ]);
    }

    /**
     * Update features for a specific plan
     */
    public function updatePlanFeatures(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'fonctionnalite_ids' => 'required|array',
            'fonctionnalite_ids.*' => 'exists:fonctionnalites,id',
        ]);

        // Sync features with plan
        $plan->fonctionnalites()->sync($validated['fonctionnalite_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Fonctionnalités du plan mises à jour avec succès',
            'data' => $plan->load('fonctionnalitesActives')
        ]);
    }

    /**
     * Remove a feature from a plan
     */
    public function removePlanFeature($planId, $fonctionnaliteId)
    {
        $plan = Plan::findOrFail($planId);
        $plan->fonctionnalites()->detach($fonctionnaliteId);

        return response()->json([
            'success' => true,
            'message' => 'Fonctionnalité dissociée du plan avec succès'
        ]);
    }
}
