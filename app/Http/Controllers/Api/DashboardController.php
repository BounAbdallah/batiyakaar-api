<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Bien;
use App\Models\Bail;
use App\Models\Incident;
use App\Models\ProjetConstruction;
use App\Models\Locataire;
use App\Models\PaiementLoyer;
use App\Models\Ventilation;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = Auth::user();
        $stats = [];

        if ($user->user_type === 'agence') {
            $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
            $this->getRichStats($stats, 'agence_id', $agenceId);
        } elseif ($user->user_type === 'bailleur') {
            $bailleurId = $user->bailleur->id;
            $this->getRichStats($stats, 'bailleur_id', $bailleurId);
        } elseif ($user->user_type === 'locataire') {
            $locataireId = $user->locataire->id;
            $this->getTenantStats($stats, $locataireId);
        } elseif ($user->user_type === 'admin' || $user->hasRole('admin')) {
            $this->getAdminStats($stats);
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    private function getRichStats(&$stats, $field, $id)
    {
        // 1. Basic Counts
        $stats['properties_count'] = Bien::where($field, $id)->count();
        $stats['active_leases_count'] = Bail::where($field, $id)->where('statut', 'actif')->count();
        $stats['tenants_count'] = Locataire::whereHas('baux', function ($q) use ($field, $id) {
            $q->where($field, $id)->where('statut', 'actif');
        })->distinct()->count();

        // 1b. Landlords Count (Only for Agency)
        if ($field === 'agence_id') {
            $stats['landlords_count'] = Bien::where($field, $id)
                ->distinct('bailleur_id')
                ->count('bailleur_id');
        }

        $stats['incidents_pending_count'] = Incident::whereHas('bail.bien', function ($q) use ($field, $id) {
            $q->where($field, $id);
        })->whereIn('statut', ['ouvert', 'en_cours'])->count();

        // Count late rents (active leases without a "paye" payment for current month)
        $activeLeaseIds = Bail::where($field, $id)->where('statut', 'actif')->pluck('id');
        $paidLeaseIds = PaiementLoyer::whereIn('bail_id', $activeLeaseIds)
            ->whereYear('date_paiement', now()->year)
            ->whereMonth('date_paiement', now()->month)
            ->where('statut', 'paye')
            ->pluck('bail_id')
            ->unique();
        $stats['loyers_en_retard'] = $activeLeaseIds->diff($paidLeaseIds)->count();

        // 2. Financials        // Revenue this month
        $stats['revenue_month'] = PaiementLoyer::whereHas('bail', function ($q) use ($field, $id) { // Changed $agenceId to $id to match function signature
            $q->where($field, $id);
        })->whereMonth('date_paiement', now()->month)->sum('montant');

        // Commissions (Agency Earnings from Ventilation)
        // Ensure to include Ventilation model usage at top of file if not present, or use full path.
        // Better to add `use App\Models\Ventilation;` at top, but for replace_block context:

        $stats['commissions_month'] = Ventilation::whereHas('paiementLoyer.bail', function ($q) use ($field, $id) { // Changed $agenceId to $id
            $q->where($field, $id);
        })->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
            ->sum('montant_agence');

        $stats['commissions_total'] = Ventilation::whereHas('paiementLoyer.bail', function ($q) use ($field, $id) { // Changed $agenceId to $id
            $q->where($field, $id);
        })->sum('montant_agence');

        // Charts Data
        $currentMonthPayments = PaiementLoyer::whereHas('bail.bien', function ($q) use ($field, $id) {
            $q->where($field, $id);
        })->whereYear('date_paiement', now()->year)
            ->whereMonth('date_paiement', now()->month)
            ->get();

        $stats['revenue_collected_month'] = $currentMonthPayments->where('statut', 'paye')->sum('montant');

        // Expected Revenue (Sum of monthly rent of ALL properties, per user request)
        $stats['revenue_expected_month'] = Bien::where($field, $id)->sum('loyer_mensuel');

        // 3. Charts Data

        // A. Revenue History (Last 12 months) - Collected vs Expected
        $revenueHistory = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M Y'); // e.g. "Dec 2024"

            // Collected
            $collected = PaiementLoyer::whereHas('bail.bien', function ($q) use ($field, $id) {
                $q->where($field, $id);
            })
                ->whereYear('date_paiement', $date->year)
                ->whereMonth('date_paiement', $date->month)
                ->where('statut', 'paye')
                ->sum('montant');

            // Expected (Approximation: Active leases at that time - simpler to just take current active for trend or snapshot)
            // For accuracy, we'd need history of leases. For now, let's use current active leases as baseline or query leases active at $date
            $expected = Bail::where($field, $id)
                ->where('date_debut', '<=', $date->endOfMonth())
                ->where(function ($q) use ($date) {
                    $q->whereNull('date_fin')->orWhere('date_fin', '>=', $date->startOfMonth());
                })
                ->sum('loyer_mensuel');

            $revenueHistory[] = [
                'month' => $monthName,
                'collected' => $collected,
                'expected' => $expected
            ];
        }
        $stats['revenue_history'] = $revenueHistory;

        // B. Occupancy Distribution
        $stats['property_distribution'] = [
            'loue' => Bien::where($field, $id)->where('statut', 'loue')->count(),
            'disponible' => Bien::where($field, $id)->where('statut', 'disponible')->count(),
            'maintenance' => Bien::where($field, $id)->where('statut', 'maintenance')->count(),
        ];

        // C. Payment Status (Current Month)
        // Ensure we count leases, not just payments made.
        // Paid = payments made. Unpaid = Active Leases - Paid.
        // Or simpler: Iterate active leases and check payment status.

        $activeLeases = Bail::where($field, $id)->where('statut', 'actif')->with([
            'paiementsLoyer' => function ($q) {
                $q->whereYear('date_paiement', now()->year)->whereMonth('date_paiement', now()->month);
            }
        ])->get();

        $paymentStatus = ['paye' => 0, 'retard' => 0, 'impaye' => 0];

        foreach ($activeLeases as $bail) {
            $payment = $bail->paiementsLoyer->first();
            if ($payment && $payment->statut === 'paye') {
                $paymentStatus['paye']++;
            } elseif ($payment && $payment->statut === 'partiel') {
                $paymentStatus['retard']++; // Partial count as late/pending
            } else {
                // No payment or unpaid
                // Check if due date passed? Assuming yes for current month report
                $paymentStatus['impaye']++;
            }
        }
        $stats['payment_status_distribution'] = $paymentStatus;

        // D. Recent Activities (Merged timeline)
        $activities = collect();

        // 1. Recent Payments
        $payments = PaiementLoyer::whereHas('bail.bien', function ($q) use ($field, $id) {
            $q->where($field, $id);
        })->latest('created_at')->take(5)->get()->map(function ($item) {
            return [
                'type' => 'payment',
                'title' => 'Paiement reçu',
                'description' => "{$item->montant} FCFA - " . ($item->bail->locataire->user->nom ?? 'Locataire'),
                'date' => $item->created_at,
                'status' => $item->statut
            ];
        });
        $activities = $activities->merge($payments);

        // 2. Recent Incidents
        $incidents = Incident::whereHas('bail.bien', function ($q) use ($field, $id) {
            $q->where($field, $id);
        })->latest('created_at')->take(5)->get()->map(function ($item) {
            return [
                'type' => 'incident',
                'title' => 'Nouvel incident',
                'description' => "{$item->titre} (" . ($item->bail->bien->reference ?? 'Bien') . ")",
                'date' => $item->created_at,
                'status' => $item->statut
            ];
        });
        $activities = $activities->merge($incidents);

        // 3. New Leases
        $leases = Bail::where($field, $id)->latest('created_at')->take(5)->get()->map(function ($item) {
            return [
                'type' => 'lease',
                'title' => 'Nouveau bail',
                'description' => ($item->bien->reference ?? 'Bien') . " - " . ($item->locataire->user->nom ?? 'Locataire'),
                'date' => $item->created_at,
                'status' => $item->statut
            ];
        });
        $activities = $activities->merge($leases);

        // Sort by date desc and take top 10
        $stats['recent_activities'] = $activities->sortByDesc('date')->take(10)->values();
        // E. Advanced Financials
        // 1. Total Security Deposits Held
        $stats['total_deposits'] = Bail::where($field, $id)->where('statut', 'actif')->sum('caution');

        // 3. Leases Expiring Soon (Next 60 days)
        $stats['leases_expiring_soon'] = Bail::where($field, $id)
            ->where('statut', 'actif')
            ->whereBetween('date_fin', [now(), now()->addDays(60)])
            ->count();
    }

    private function getTenantStats(&$stats, $id)
    {
        $activeLease = Bail::where('locataire_id', $id)->where('statut', 'actif')->first();
        $stats['has_active_lease'] = $activeLease ? true : false;

        // Incident counts by status
        $stats['incident_stats'] = [
            'ouvert' => Incident::whereHas('bail', function ($q) use ($id) {
                $q->where('locataire_id', $id);
            })->where('statut', 'ouvert')->count(),
            'en_cours' => Incident::whereHas('bail', function ($q) use ($id) {
                $q->where('locataire_id', $id);
            })->where('statut', 'en_cours')->count(),
            'resolu' => Incident::whereHas('bail', function ($q) use ($id) {
                $q->where('locataire_id', $id);
            })->where('statut', 'resolu')->count(),
        ];

        $stats['incidents_reported_count'] = array_sum($stats['incident_stats']);

        if ($activeLease) {
            $stats['rent_due'] = $activeLease->loyer_mensuel;
            $stats['lease_end_date'] = $activeLease->date_fin;

            // Payments history for charts (Last 12 months)
            $paymentHistory = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthName = $date->format('M Y');

                $amount = $activeLease->paiementsLoyer()
                    ->whereYear('date_paiement', $date->year)
                    ->whereMonth('date_paiement', $date->month)
                    ->sum('montant');

                $paymentHistory[] = [
                    'month' => $monthName,
                    'amount' => $amount
                ];
            }
            $stats['payment_history'] = $paymentHistory;

            // Recent payments (for the list)
            $stats['payments_history_list'] = $activeLease->paiementsLoyer()
                ->latest()
                ->take(6)
                ->get()
                ->map(function ($p) {
                    return [
                        'date' => $p->date_paiement,
                        'amount' => $p->montant,
                        'status' => $p->statut
                    ];
                });
        }
    }

    private function getAdminStats(&$stats)
    {
        // Global Counts
        $stats['properties_count'] = Bien::count();
        $stats['active_leases_count'] = Bail::where('statut', 'actif')->count();
        $stats['users_count'] = \App\Models\User::count();
        $stats['incidents_pending_count'] = Incident::whereIn('statut', ['ouvert', 'en_cours'])->count();

        // Financials (Platform Wallet)
        // Total commissions earned by platform (Net Profit)
        $stats['platform_wallet_balance'] = Ventilation::sum('montant_plateforme');
        $stats['platform_revenue_month'] = Ventilation::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('montant_plateforme');

        // Recent Earnings (Commissions)
        $stats['recent_earnings'] = Ventilation::where('montant_plateforme', '>', 0)
            ->with(['paiementLoyer.bail.agence', 'paiementLoyer.bail.bien', 'paiementLoyer.bail.locataire.user'])
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(function ($v) {
                $paiement = $v->paiementLoyer;
                $bail = $paiement->bail;
                $client = $bail->locataire->user;

                return [
                    'id' => $v->id, // Add ID for key
                    'amount' => $v->montant_plateforme,
                    'source' => 'Commission sur Loyer',
                    'date' => $v->created_at,
                    'property' => $bail->bien->reference ?? 'Bien inconnu', // Renamed from details to property for clarity
                    'agence' => $bail->agence->raison_sociale ?? 'N/A',
                    'transaction_ref' => $paiement->reference_transaction ?? 'N/A',
                    'client' => $client ? ($client->prenom . ' ' . $client->nom) : 'Inconnu',
                    'mode' => $paiement->mode_paiement ?? 'N/A'
                ];
            });

        // Other Global Financials
        $stats['total_rent_collected_month'] = PaiementLoyer::whereMonth('date_paiement', now()->month)
            ->whereYear('date_paiement', now()->year)
            ->where('statut', 'paye')
            ->sum('montant');
    }

    public function sidebarCounts(Request $request)
    {
        $user = Auth::user();
        $counts = [
            'incidents' => 0,
            'payments' => 0,
            'notifications' => $user->notifications()->where('lue', false)->count()
        ];

        if ($user->user_type === 'agence') {
            $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
            // Incidents: Open or In Progress
            $counts['incidents'] = Incident::whereHas('bail', function ($q) use ($agenceId) {
                $q->where('agence_id', $agenceId);
            })->whereIn('statut', ['ouvert', 'en_cours'])->count();

            // Payments: Late rents for current month (active leases without paid status)
            // Simplified: Count unpaid/partial payments
            $counts['payments'] = PaiementLoyer::whereHas('bail', function ($q) use ($agenceId) {
                $q->where('agence_id', $agenceId);
            })->whereIn('statut', ['impaye', 'partiel', 'en_retard'])->count();

            // Or maybe simpler: Count active leases with debt? For now, stick to payment items status.

        } elseif ($user->user_type === 'bailleur' && $user->bailleur) {
            $bailleurId = $user->bailleur->id;
            $counts['incidents'] = Incident::whereHas('bail.bien', function ($q) use ($bailleurId) {
                $q->where('bailleur_id', $bailleurId);
            })->whereIn('statut', ['ouvert', 'en_cours'])->count();

            $counts['payments'] = PaiementLoyer::whereHas('bail.bien', function ($q) use ($bailleurId) {
                $q->where('bailleur_id', $bailleurId);
            })->whereIn('statut', ['impaye', 'partiel', 'en_retard'])->count();

        } elseif ($user->user_type === 'locataire' && $user->locataire) {
            $locataireId = $user->locataire->id;
            // Incidents: All incidents reported by this tenant (or maybe just open ones?)
            // Let's show open ones
            $counts['incidents'] = Incident::whereHas('bail', function ($q) use ($locataireId) {
                $q->where('locataire_id', $locataireId);
            })->whereIn('statut', ['ouvert', 'en_cours'])->count();

            // Payments: Unpaid/Late
            $counts['payments'] = PaiementLoyer::whereHas('bail', function ($q) use ($locataireId) {
                $q->where('locataire_id', $locataireId);
            })->whereIn('statut', ['impaye', 'partiel', 'en_retard'])->count();
        }

        return response()->json([
            'success' => true,
            'data' => $counts
        ]);
    }
}
