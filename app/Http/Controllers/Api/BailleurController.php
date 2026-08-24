<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bailleur;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class BailleurController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $query = Bailleur::with('user')->whereHas('user');

        // If Agency, only show Bailleurs who have properties managed by this agency
        if ($user->user_type === 'agence') {
            $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
            $query->whereHas('biens', function ($q) use ($agenceId) {
                $q->where('agence_id', $agenceId);
            });
        }

        // Global Stats for the Dashboard
        $stats = [];
        if ($user->user_type === 'agence') {
            $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
            $stats['total_bailleurs'] = Bailleur::whereHas('biens', function ($q) use ($agenceId) {
                $q->where('agence_id', $agenceId);
            })->count();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Add counts for cards
        $query->withCount([
            'biens',
            'baux as locataires_count' => function ($q) {
                // Determine table name or just use 'baux' as confirmed in model
                $q->where('baux.statut', 'actif');
            }
        ]);

        $bailleurs = $query->latest()->paginate(15);

        // Global Stats for the Dashboard
        $stats = [
            'total_bailleurs' => 0, // Will be calculated below based on user type
            'total_biens' => 0,
            'total_locataires' => 0,
            'total_revenus' => 0,
        ];

        if ($user->user_type === 'agence') {
            $agenceId = $user->agence ? $user->agence->id : $user->agence_id;

            // Use the agenceId already determined above
            $stats['total_bailleurs'] = Bailleur::whereHas('biens', function ($q) use ($agenceId) {
                $q->where('agence_id', $agenceId);
            })->count();

            // Total Biens for this agency
            $stats['total_biens'] = \App\Models\Bien::where('agence_id', $agenceId)->count();

            // Active Tenants (via active leases on agency properties)
            $stats['total_locataires'] = \App\Models\Bail::whereHas('bien', function ($q) use ($agenceId) {
                $q->where('agence_id', $agenceId);
            })->where('statut', 'actif')->count();

            // Total Revenue (Paid rents)
            $stats['total_revenus'] = \App\Models\PaiementLoyer::whereHas('bail.bien', function ($q) use ($agenceId) {
                $q->where('agence_id', $agenceId);
            })->where('statut', 'paye')->sum('montant');
        } else {
            // For other user types (e.g., admin), calculate global stats or specific to their context
            $stats['total_bailleurs'] = Bailleur::count();
            // Add other global stats if needed for non-agency users
        }


        return response()->json([
            'success' => true,
            'data' => $bailleurs,
            'stats' => $stats
        ]);


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'telephone' => 'nullable|string|max:20',
            'pays' => 'required|string|max:100',
            'adresse_diaspora' => 'nullable|string',
            'numero_cni' => 'nullable|string|max:50',
            'date_naissance' => 'nullable|date|before:today',
            'lieu_naissance' => 'nullable|string|max:255',
            'cni_recto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'cni_verso' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Auto-generate secure password
        $password = \Illuminate\Support\Str::random(10);

        // Create User
        $user = User::create([
            'prenom' => $validated['prenom'],
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'password' => Hash::make($password),
            'user_type' => 'bailleur',
        ]);

        // Create Bailleur Profile
        // Handle file uploads
        $cniRectoPath = null;
        $cniVersoPath = null;

        if ($request->hasFile('cni_recto')) {
            $cniRectoPath = $request->file('cni_recto')->store('cni/bailleurs', 'public');
        }

        if ($request->hasFile('cni_verso')) {
            $cniVersoPath = $request->file('cni_verso')->store('cni/bailleurs', 'public');
        }

        $bailleur = Bailleur::create([
            'user_id' => $user->id,
            'pays' => $validated['pays'],
            'adresse_diaspora' => $validated['adresse_diaspora'] ?? null,
            'numero_cni' => $validated['numero_cni'] ?? null,
            'date_naissance' => $validated['date_naissance'] ?? null,
            'lieu_naissance' => $validated['lieu_naissance'] ?? null,
            'cni_recto' => $cniRectoPath,
            'cni_verso' => $cniVersoPath,
        ]);

        // Check if agency plan allows landlord access and send email
        $authUser = $request->user();
        $agence = $authUser->agence ?? \App\Models\Agence::find($authUser->agence_id);
        $emailSent = false;

        if ($agence) {
            $abonnement = $agence->abonnement()->where('statut', 'actif')->first();

            if ($abonnement && $abonnement->plan) {
                $fonctionnalites = $abonnement->plan->fonctionnalites ?? [];

                // Check if plan allows landlord access
                if (isset($fonctionnalites['accies_bailleurs']) && $fonctionnalites['accies_bailleurs']) {
                    \Illuminate\Support\Facades\Mail::to($user)->send(
                        new \App\Mail\LandlordAccountCreated($user, $password, $agence)
                    );
                    $emailSent = true;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bailleur créé avec succès',
            'data' => $bailleur->load('user'),
            'email_sent' => $emailSent,
            'password_temp' => $password // For dev/demo only
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $authUser = \Illuminate\Support\Facades\Auth::user();

        // Load bailleur with relationships
        $bailleur = Bailleur::with(['user'])->findOrFail($id);

        // Filter biens by agency if user is agency type
        if ($authUser->user_type === 'agence') {
            $agenceId = $authUser->agence ? $authUser->agence->id : $authUser->agence_id;

            // Only load biens that belong to this agency
            $bailleur->load([
                'biens' => function ($query) use ($agenceId) {
                    $query->where('agence_id', $agenceId)->with('baux.paiementsLoyer');
                }
            ]);

            // Verify this agency actually manages properties for this landlord
            if ($bailleur->biens->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas accès aux informations de ce bailleur'
                ], 403);
            }
        } else {
            // Admin or other types can see all properties
            $bailleur->load(['biens.baux.paiementsLoyer']);
        }

        // Calculate Stats (only for properties managed by this agency)
        $stats = [];
        $bienIds = $bailleur->biens->pluck('id');

        // Property counts
        $stats['total_properties'] = $bailleur->biens->count();
        $stats['rented_properties'] = $bailleur->biens->where('statut', 'loue')->count();
        $stats['available_properties'] = $bailleur->biens->where('statut', 'disponible')->count();

        // Active leases (only for this agency's properties)
        $activeLeases = \App\Models\Bail::whereIn('bien_id', $bienIds)
            ->where('statut', 'actif')
            ->get();
        $stats['active_leases'] = $activeLeases->count();
        $stats['expected_monthly_revenue'] = $activeLeases->sum('loyer_mensuel');

        // Revenue calculations (only for this agency's properties)
        $allPayments = \App\Models\PaiementLoyer::whereHas('bail', function ($q) use ($bienIds) {
            $q->whereIn('bien_id', $bienIds);
        })->where('statut', 'paye')->get();

        $stats['total_revenue'] = $allPayments->sum('montant');
        $stats['current_month_revenue'] = $allPayments
            ->where('date_paiement', '>=', now()->startOfMonth())
            ->where('date_paiement', '<=', now()->endOfMonth())
            ->sum('montant');

        // Revenue by month (last 12 months)
        $revenueByMonth = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthRevenue = $allPayments
                ->where('date_paiement', '>=', $date->copy()->startOfMonth())
                ->where('date_paiement', '<=', $date->copy()->endOfMonth())
                ->sum('montant');

            $revenueByMonth[] = [
                'month' => $date->format('M Y'),
                'revenue' => $monthRevenue
            ];
        }
        $stats['revenue_by_month'] = $revenueByMonth;

        // Property distribution
        $stats['property_distribution'] = [
            'loue' => $stats['rented_properties'],
            'disponible' => $stats['available_properties'],
            'maintenance' => $bailleur->biens->where('statut', 'maintenance')->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $bailleur,
            'stats' => $stats
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $bailleur = Bailleur::findOrFail($id);
        $user = $bailleur->user;

        $validated = $request->validate([
            'prenom' => 'sometimes|string|max:255',
            'nom' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'telephone' => 'nullable|string|max:20',
            'pays' => 'sometimes|string|max:100',
            'adresse_diaspora' => 'nullable|string',
        ]);

        if (isset($validated['prenom']) || isset($validated['nom']) || isset($validated['email']) || isset($validated['telephone'])) {
            $user->update($request->only(['prenom', 'nom', 'email', 'telephone']));
        }

        if (isset($validated['pays']) || isset($validated['adresse_diaspora'])) {
            $bailleur->update($request->only(['pays', 'adresse_diaspora']));
        }

        return response()->json([
            'success' => true,
            'message' => 'Bailleur mis à jour avec succès',
            'data' => $bailleur->load('user')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bailleur = Bailleur::findOrFail($id);
        $user = $bailleur->user;

        $bailleur->delete();
        $user->delete(); // Optional: delete the user account too? Usually yes for a CRM.

        return response()->json([
            'success' => true,
            'message' => 'Bailleur supprimé avec succès'
        ]);
    }
}
