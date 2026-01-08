<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Immeuble;
use App\Models\Etage;
use Illuminate\Http\Request;

class ImmeubleController extends Controller
{
    /**
     * Display a listing of buildings with statistics.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Immeuble::query();

        if ($user->user_type === 'agence') {
            $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
            if ($agenceId) {
                $query->where('agence_id', $agenceId);
            }
        } elseif ($user->user_type === 'bailleur') {
            $query->where('bailleur_id', $user->bailleur->id);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('adresse', 'like', "%{$search}%");
            });
        }

        // Bailleur filter (for agencies)
        if ($request->has('bailleur_id') && $request->bailleur_id) {
            $query->where('bailleur_id', $request->bailleur_id);
        }

        // Include counts for statistics
        $query->with([
            'etages.biens.baux' => function ($q) {
                $q->where('statut', 'actif');
            }
        ]);

        // Pagination or fetch all
        if ($request->has('all') && $request->all === 'true') {
            // Fetch all records without pagination (useful for stats)
            $immeubles = $query->with('etages')->latest()->get();
        } else {
            // Default pagination
            $immeubles = $query->with('etages')->latest()->paginate(15);
        }

        // Calculate totals for dashboard stats
        $totalBiens = 0;
        $totalLocataires = 0;
        $totalEtages = 0;
        $totalChiffreAffaires = 0;

        foreach ($immeubles as $immeuble) {
            $immeubleChiffre = 0;
            $totalEtages += $immeuble->etages->count();

            foreach ($immeuble->etages as $etage) {
                if ($etage->biens) {
                    $totalBiens += $etage->biens->count();
                    foreach ($etage->biens as $bien) {
                        if ($bien->baux) {
                            $totalLocataires += $bien->baux->count();
                            // Add monthly rent from active leases
                            foreach ($bien->baux as $bail) {
                                $immeubleChiffre += $bail->loyer_mensuel ?? 0;
                            }
                        }
                    }
                }
            }

            // Attach chiffre to immeuble
            $immeuble->chiffre_mensuel = $immeubleChiffre;
            $totalChiffreAffaires += $immeubleChiffre;
        }

        return response()->json([
            'success' => true,
            'data' => $immeubles,
            'stats' => [
                'total_etages' => $totalEtages,
                'total_biens' => $totalBiens,
                'total_locataires' => $totalLocataires,
                'total_chiffre_affaires' => $totalChiffreAffaires,
            ]
        ]);
    }

    /**
     * Store a newly created building.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Resolve IDs similar to Bien logic
        $bailleurId = null;
        $agenceId = null;

        if ($user->user_type === 'agence') {
            $request->validate(['bailleur_id' => 'required|exists:bailleurs,id']);
            $bailleurId = $request->bailleur_id;
            $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
        } elseif ($user->user_type === 'bailleur') {
            $bailleurId = $user->bailleur->id;
        }

        $request->merge(['bailleur_id' => $bailleurId, 'agence_id' => $agenceId]);

        // Enforce Subscription Limit on Immeubles (Buildings)
        if ($user->user_type === 'agence') {
            $agence = $user->agence;
            $abonnement = $agence->abonnement()->actif()->first();

            if ($abonnement) {
                $plan = $abonnement->plan;
                // Interpret 'limite_biens' as 'Limit of Buildings'
                if ($plan && $plan->limite_biens > 0) {
                    $currentImmeublesCount = $agence->immeubles()->count();
                    if ($currentImmeublesCount >= $plan->limite_biens) {
                        return response()->json([
                            'message' => "La limite d'immeubles pour votre abonnement ({$plan->limite_biens}) a été atteinte. Veuillez passer au plan supérieur.",
                            'limit_reached' => true
                        ], 403);
                    }
                }
            }
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nombre_etages' => 'required|integer|min:0',
            'nombre_biens' => 'nullable|integer|min:0',
            'bailleur_id' => 'required|exists:bailleurs,id',
            'taux_commission' => 'nullable|numeric|min:0|max:100',
            'type_mandat' => 'nullable|in:gerance_totale,recouvrement_seulement,declaration_impots',
            'duree_mandat' => 'nullable|integer|min:1',
            'date_debut_mandat' => 'nullable|date',
            'date_fin_mandat' => 'nullable|date|after_or_equal:date_debut_mandat',
        ]);

        $data = $validated;
        $data['agence_id'] = $agenceId;

        $immeuble = Immeuble::create($data);

        // Auto-create floors?
        if ($immeuble->nombre_etages > 0) {
            for ($i = 0; $i <= $immeuble->nombre_etages; $i++) {
                $nom = $i == 0 ? "Rez-de-chaussée" : "{$i}e Étage";
                Etage::create([
                    'nom' => $nom,
                    'numero' => $i,
                    'immeuble_id' => $immeuble->id
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Immeuble créé avec succès',
            'data' => $immeuble->load('etages')
        ], 201);
    }

    /**
     * Display the specified building.
     */
    public function show(Request $request, string $id)
    {
        $immeuble = Immeuble::with(['etages.biens.baux.paiementsLoyer', 'etages.biens.baux.locataire.user', 'bailleur.user'])
            ->findOrFail($id);

        $this->checkOwnership($request->user(), $immeuble);

        // Calculate Statistics
        $totalLocataires = 0;
        $totalRevenus = 0;
        $totalImpayes = 0;
        $revenusParMois = [];

        foreach ($immeuble->etages as $etage) {
            $etageStats = [
                'revenu' => 0,
                'impaye' => 0,
                'locataires' => 0,
                'loyer_attendu' => 0,
                'biens_loues' => 0,
                'biens_vides' => 0,
            ];

            foreach ($etage->biens as $bien) {
                // Count Active Tenants & Expected Rent
                $actifBail = $bien->baux->where('statut', 'actif')->first();
                if ($actifBail) {
                    $totalLocataires++;
                    $etageStats['locataires']++;
                    $etageStats['biens_loues']++;
                    $etageStats['loyer_attendu'] += $actifBail->loyer_mensuel;
                } else {
                    $etageStats['biens_vides']++;
                }

                foreach ($bien->baux as $bail) {
                    foreach ($bail->paiementsLoyer as $paiement) {
                        // Revenue = collected payments
                        if ($paiement->statut === 'paye') {
                            $totalRevenus += $paiement->montant;
                            $etageStats['revenu'] += $paiement->montant;

                            // Group by Month for Chart (YYYY-MM)
                            $mois = substr($paiement->date_paiement, 0, 7); // e.g., 2024-01
                            if (!isset($revenusParMois[$mois])) {
                                $revenusParMois[$mois] = 0;
                            }
                            $revenusParMois[$mois] += $paiement->montant;
                        }

                        // Unpaid debt (only if record exists with status impaye/partiel)
                        // Note: This only counts recorded unpaid debts, not missing months.
                        if ($paiement->statut === 'impaye' || $paiement->statut === 'partiel') {
                            $montant_recu = $paiement->montant ?? 0;
                            $montant_du = $bail->loyer_mensuel;
                            $dette = $montant_du - $montant_recu;

                            $totalImpayes += $dette;
                            $etageStats['impaye'] += $dette;
                        }
                    }
                }
            }
            // Attach stats to the floor object (dynamically)
            $etage->setAttribute('stats', $etageStats);
        }

        // Format chart data for Recharts (array of objects sorted by date)
        $chartData = [];
        ksort($revenusParMois);
        foreach ($revenusParMois as $mois => $montant) {
            $chartData[] = [
                'mois' => $mois,
                'revenu' => $montant
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $immeuble,
            'statistics' => [
                'total_locataires' => $totalLocataires,
                'chiffre_affaires' => $totalRevenus,
                'loyers_impayes' => $totalImpayes,
                'revenus_chart' => $chartData
            ]
        ]);
    }

    /**
     * Update the specified building.
     */
    public function update(Request $request, string $id)
    {
        $immeuble = Immeuble::findOrFail($id);
        $this->checkOwnership($request->user(), $immeuble);

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'adresse' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'nombre_biens' => 'nullable|integer|min:0',
            'taux_commission' => 'sometimes|numeric|min:0|max:100',
            'type_mandat' => 'sometimes|in:gerance_totale,recouvrement_seulement,declaration_impots',
            'duree_mandat' => 'sometimes|integer|min:1',
            'date_debut_mandat' => 'nullable|date',
            'date_fin_mandat' => 'nullable|date|after_or_equal:date_debut_mandat',
        ]);

        $immeuble->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Immeuble mis à jour',
            'data' => $immeuble
        ]);
    }

    /**
     * Remove the specified building.
     */
    public function destroy(Request $request, string $id)
    {
        $immeuble = Immeuble::findOrFail($id);
        $this->checkOwnership($request->user(), $immeuble);
        $immeuble->delete();

        return response()->json([
            'success' => true,
            'message' => 'Immeuble supprimé'
        ]);
    }

    /**
     * Check if user is authorized to access the building
     */
    private function checkOwnership($user, $immeuble)
    {
        if ($user->user_type === 'agence') {
            $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
            if ($immeuble->agence_id !== $agenceId) {
                abort(403, 'Accès non autorisé à cet immeuble.');
            }
        } elseif ($user->user_type === 'bailleur') {
            if ($immeuble->bailleur_id !== $user->bailleur->id) {
                abort(403, 'Accès non autorisé à cet immeuble.');
            }
        }
        // Admin gets pass
    }

    public function downloadMandat($id)
    {
        $immeuble = Immeuble::with(['bailleur.user', 'agence', 'agence.user', 'etages.biens'])->findOrFail($id);
        $this->checkOwnership(request()->user(), $immeuble);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.mandat_gerance_immeuble', compact('immeuble'));

        return $pdf->download('mandat_gerance_immeuble_' . $immeuble->nom . '.pdf');
    }

    public function viewMandat($id)
    {
        $immeuble = Immeuble::with(['bailleur.user', 'agence', 'agence.user', 'etages.biens'])->findOrFail($id);
        $this->checkOwnership(request()->user(), $immeuble);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.mandat_gerance_immeuble', compact('immeuble'));

        return $pdf->stream('mandat_gerance_immeuble_' . $immeuble->nom . '.pdf');
    }
}
