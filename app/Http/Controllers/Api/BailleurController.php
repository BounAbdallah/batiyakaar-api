<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bailleur;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Barryvdh\DomPDF\Facade\Pdf;

class BailleurController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $query = Bailleur::with('user');

        // If Agency, show Bailleurs who have properties managed by this agency 
        // OR who were created by/belong to this agency (especially important for new landlords without properties)
        if ($user->user_type === 'agence') {
            $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
            $query->where(function ($q) use ($agenceId) {
                $q->whereHas('biens', function ($sub) use ($agenceId) {
                    $sub->where('agence_id', $agenceId);
                })->orWhereHas('user', function ($sub) use ($agenceId) {
                    $sub->where('agence_id', $agenceId);
                });
            });
        }

        // Support filtering by agence_id from request params (for dropdowns)
        if ($request->has('agence_id')) {
            $reqAgenceId = $request->agence_id;
            $query->where(function ($q) use ($reqAgenceId) {
                $q->whereHas('biens', function ($sub) use ($reqAgenceId) {
                    $sub->where('agence_id', $reqAgenceId);
                })->orWhereHas('user', function ($sub) use ($reqAgenceId) {
                    $sub->where('agence_id', $reqAgenceId);
                });
            });
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
            $stats['total_bailleurs'] = Bailleur::where(function ($q) use ($agenceId) {
                $q->whereHas('biens', function ($sub) use ($agenceId) {
                    $sub->where('agence_id', $agenceId);
                })->orWhereHas('user', function ($sub) use ($agenceId) {
                    $sub->where('agence_id', $agenceId);
                });
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
        try {
            \Log::info('Starting Bailleur creation process', ['email' => $request->email]);
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'prenom' => 'required|string|max:255',
                'nom' => 'required|string|max:255',
                'email' => 'nullable|string|email|max:255|unique:users',
                'telephone' => 'required_without:email|nullable|string|max:20',
                'pays' => 'required|string|max:100',
                'adresse_diaspora' => 'nullable|string',
                'numero_cni' => 'nullable|string|max:50',
                'date_naissance' => 'nullable|date|before:today',
                'lieu_naissance' => 'nullable|string|max:255',
                'cni_recto' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
                'cni_verso' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                \Log::error('Validation failed for Bailleur creation:', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->except(['cni_recto', 'cni_verso'])
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Auto-generate secure password
            $password = \Illuminate\Support\Str::random(10);

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

            \Log::info('Files stored', ['recto' => $cniRectoPath, 'verso' => $cniVersoPath]);

            $bailleur = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $password, $request, $cniRectoPath, $cniVersoPath) {
                \Log::info('Creating user for bailleur');
                // Create User
                $user = User::create([
                    'prenom' => $validated['prenom'],
                    'nom' => $validated['nom'],
                    'email' => $validated['email'],
                    'telephone' => $validated['telephone'] ?? null,
                    'password' => Hash::make($password),
                    'user_type' => 'bailleur',
                    'agence_id' => $request->user()->agence_id ?? ($request->user()->agence ? $request->user()->agence->id : null),
                ]);

                \Log::info('User created', ['user_id' => $user->id]);

                return Bailleur::create([
                    'user_id' => $user->id,
                    'pays' => $validated['pays'],
                    'adresse_diaspora' => $validated['adresse_diaspora'] ?? null,
                    'numero_cni' => $validated['numero_cni'] ?? null,
                    'date_naissance' => $validated['date_naissance'] ?? null,
                    'lieu_naissance' => $validated['lieu_naissance'] ?? null,
                    'cni_recto' => $cniRectoPath,
                    'cni_verso' => $cniVersoPath,
                ]);
            });

            \Log::info('Bailleur profile created', ['bailleur_id' => $bailleur->id]);

            $user = $bailleur->user;

            // Always send welcome email to landlord with credentials
            $authUser = $request->user();
            $agence = $authUser->agence ?? \App\Models\Agence::find($authUser->agence_id);
            $emailSent = false;

            if ($agence && $user->email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($user)->send(
                        new \App\Mail\LandlordAccountCreated($user, $password, $agence)
                    );
                    $emailSent = true;
                } catch (\Exception $e) {
                    \Log::error('Failed to send landlord welcome email: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bailleur créé avec succès',
                'data' => $bailleur->load('user'),
                'email_sent' => $emailSent,
                'password_temp' => $password // For dev/demo only
            ], 201);

        } catch (\Exception $mainError) {
            \Log::error('CRITICAL ERROR in Bailleur creation: ' . $mainError->getMessage(), [
                'exception' => $mainError
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur interne est survenue: ' . $mainError->getMessage()
            ], 500);
        }
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
            // OR created this landlord
            $isCreator = $bailleur->user && $bailleur->user->agence_id == $agenceId;

            if ($bailleur->biens->isEmpty() && !$isCreator) {
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
    /**
     * Generate General Monthly Report for Landlord
     */
    public function generateMonthlyReport(Request $request)
    {
        $validated = $request->validate([
            'bailleur_id' => 'required|exists:bailleurs,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
        ]);

        $bailleur = Bailleur::with('user')->findOrFail($validated['bailleur_id']);
        $agence = auth()->user()->agence ?? \App\Models\Agence::where('user_id', auth()->id())->first();

        $month = $validated['month'];
        $year = $validated['year'];
        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // 1. Get all properties for this landlord managed by this agency
        $biens = \App\Models\Bien::where('bailleur_id', $bailleur->id)
            ->where('agence_id', $agence->id)
            ->get();
        $bienIds = $biens->pluck('id');

        // 2. Get all payments received this month for these properties
        $payments = \App\Models\PaiementLoyer::with(['bail.locataire.user', 'bail.bien', 'ventilation'])
            ->whereHas('bail', function ($q) use ($bienIds) {
                $q->whereIn('bien_id', $bienIds);
            })
            ->where(function ($q) use ($month, $year) {
                $q->whereMonth('periode_debut', $month)->whereYear('periode_debut', $year)
                    ->orWhere(function ($sub) use ($month, $year) {
                        $sub->whereMonth('date_paiement', $month)->whereYear('date_paiement', $year);
                    });
            })
            ->get();

        // 3. Get active leases to identify missing payments
        $activeLeases = \App\Models\Bail::with(['locataire.user', 'bien'])
            ->whereIn('bien_id', $bienIds)
            ->where('statut', 'actif')
            ->where('date_debut', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', $startDate);
            })
            ->get();

        // 4. Calculate stats
        $totalCollected = $payments->where('statut', 'paye')->sum('montant');
        $totalPartial = $payments->where('statut', 'partiel')->sum('montant');
        $totalReceived = $totalCollected + $totalPartial;

        $totalCommission = 0;
        foreach ($payments as $p) {
            $taux_agence = $p->bail->bien->taux_commission ?? ($agence->taux_commission_agence ?? 10.00);
            $p->commission_percentage = $taux_agence;

            if ($p->ventilation) {
                $totalCommission += $p->ventilation->montant_agence;
            } else {
                // Fallback: calculate on-the-fly if ventilation record is missing (e.g. seeded data)
                $p->calculated_commission = $p->montant * ($taux_agence / 100);
                $totalCommission += $p->calculated_commission;
            }
        }

        // 5. Get expenses
        $expenses = \App\Models\NoteDepense::with('depenses')
            ->where('bailleur_id', $bailleur->id)
            ->where('agence_id', $agence->id)
            ->where('mois', $month)
            ->where('annee', $year)
            ->get();
        $totalExpenses = $expenses->sum('total_montant');

        // 6. Identify missing payments
        $missingPayments = [];
        $totalDue = $activeLeases->sum('loyer_mensuel');

        foreach ($activeLeases as $lease) {
            $hasPayment = $payments->contains(function ($p) use ($lease) {
                return $p->bail_id === $lease->id;
            });

            if (!$hasPayment) {
                $missingPayments[] = [
                    'locataire' => $lease->locataire->user->prenom . ' ' . $lease->locataire->user->nom,
                    'bien' => $lease->bien->reference,
                    'montant' => $lease->loyer_mensuel
                ];
            }
        }

        $data = [
            'bailleur' => $bailleur,
            'agence' => $agence,
            'month' => $startDate->translatedFormat('F'),
            'year' => $year,
            'payments' => $payments,
            'missing_payments' => $missingPayments,
            'expenses' => $expenses,
            'stats' => [
                'total_received' => $totalReceived,
                'total_due' => $totalDue,
                'total_commission' => $totalCommission,
                'total_expenses' => $totalExpenses,
                'balance' => $totalReceived - $totalCommission - $totalExpenses,
                'commission_rate' => $payments->count() > 0 ? ($payments->first()->commission_percentage ?? ($agence->taux_commission_agence ?? 10.00)) : ($agence->taux_commission_agence ?? 10.00)
            ]
        ];

        $pdf = Pdf::loadView('pdfs.landlord_monthly_report', $data);
        return $pdf->download('rapport_mensuel_' . $bailleur->id . '_' . $month . '_' . $year . '.pdf');
    }
}
