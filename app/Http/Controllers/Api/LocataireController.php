<?php

namespace App\Http\Controllers\Api;

use App\Models\Locataire;
use App\Models\PaiementLoyer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LocataireController extends Controller
{
    /**
     * Display a listing of tenants - filtered by role
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Locataire::with([
            'user',
            'baux' => function ($q) {
                $q->where('statut', 'actif');
            }
        ]);

        // Filter by user role
        if ($user->user_type === 'agence' && $user->agence) {
            // Agency: only tenants with leases managed by this agency
            $query->whereHas('baux', function ($q) use ($user) {
                $q->where('agence_id', $user->agence->id);
            });
        } elseif ($user->user_type === 'bailleur' && $user->bailleur) {
            // Bailleur: only tenants renting their properties
            $query->whereHas('baux.bien', function ($q) use ($user) {
                $q->where('bailleur_id', $user->bailleur->id);
            });
        }
        // Admin sees all tenants

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $locataires = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $locataires
        ]);
    }

    /**
     * Store a newly created tenant (and user)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prenom' => 'required|string',
            'nom' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'telephone' => 'nullable|string',
            'password' => 'required|string|min:6',
        ]);

        $user = \App\Models\User::create([
            'prenom' => $validated['prenom'],
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'user_type' => 'locataire',
        ]);

        $locataire = Locataire::create(['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'Locataire créé avec succès',
            'data' => $locataire->load('user')
        ], 201);
    }

    /**
     * Display the specified tenant with full details, stats and history
     */
    public function show(string $id)
    {
        $locataire = Locataire::with([
            'user',
            'baux.bien',
            'baux.agence',
            'baux.paiementsLoyer',
            'incidents.bail.bien'
        ])->findOrFail($id);

        // Get all payments for this tenant's leases
        $bailIds = $locataire->baux->pluck('id');
        $paiements = PaiementLoyer::whereIn('bail_id', $bailIds)
            ->with('bail.bien')
            ->orderBy('date_paiement', 'desc')
            ->get();

        // Calculate statistics
        $totalPaye = $paiements->where('statut', 'paye')->sum('montant');
        $totalPartiel = $paiements->where('statut', 'partiel')->sum('montant');
        $totalPaiements = $totalPaye + $totalPartiel;

        // Calculate total expected (sum of all monthly rents for active leases)
        $bauxActifs = $locataire->baux->where('statut', 'actif');
        $loyerMensuelTotal = $bauxActifs->sum('loyer_mensuel');

        // Calculate months of active leases for expected payments
        $totalAttendu = 0;
        foreach ($locataire->baux as $bail) {
            $monthsActive = Carbon::parse($bail->date_debut)->diffInMonths(
                $bail->statut === 'actif' ? Carbon::now() : Carbon::parse($bail->date_fin)
            ) + 1;
            $totalAttendu += $bail->loyer_mensuel * $monthsActive;
        }

        $solde = $totalAttendu - $totalPaiements;

        // Payment rate
        $paiementsCount = $paiements->count();
        $paiementsATemps = $paiements->filter(function ($p) {
            return $p->statut === 'paye' && Carbon::parse($p->date_paiement)->day <= 5;
        })->count();
        $tauxPaiement = $paiementsCount > 0 ? round(($paiementsATemps / $paiementsCount) * 100) : 0;

        // Payments history for chart (last 12 months)
        $paymentsChart = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthPayments = $paiements->filter(function ($p) use ($month) {
                return Carbon::parse($p->date_paiement)->format('Y-m') === $month->format('Y-m');
            });
            $paymentsChart[] = [
                'month' => $month->translatedFormat('M Y'),
                'montant' => $monthPayments->sum('montant'),
                'count' => $monthPayments->count()
            ];
        }

        // Payment status distribution
        $statusDistribution = [
            'paye' => $paiements->where('statut', 'paye')->count(),
            'partiel' => $paiements->where('statut', 'partiel')->count(),
            'en_retard' => $paiements->where('statut', 'en_retard')->count(),
        ];

        // Separate active and historical leases
        $bauxActifsData = $locataire->baux->where('statut', 'actif')->values();
        $bauxHistorique = $locataire->baux->whereIn('statut', ['expire', 'resilie'])->values();

        return response()->json([
            'success' => true,
            'data' => $locataire,
            'stats' => [
                'total_paye' => $totalPaiements,
                'solde_du' => max(0, $solde),
                'nombre_baux' => $locataire->baux->count(),
                'baux_actifs' => $bauxActifs->count(),
                'taux_paiement' => $tauxPaiement,
                'loyer_mensuel_total' => $loyerMensuelTotal,
                'nombre_incidents' => $locataire->incidents->count(),
            ],
            'baux_actifs' => $bauxActifsData,
            'baux_historique' => $bauxHistorique,
            'paiements' => $paiements,
            'payments_chart' => $paymentsChart,
            'status_distribution' => $statusDistribution,
        ]);
    }
}
