<?php

namespace App\Http\Controllers\Api;

use App\Models\PaiementLoyer;
use App\Models\Ventilation;
use App\Models\Quittance;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PaiementLoyerController extends Controller
{
    /**
     * Display a listing of rent payments - filtered by role with advanced filters
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = PaiementLoyer::query();

        // Admin sees all payments

        // Filter by user role - automatic restriction
        if ($user->user_type === 'agence') {
            $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
            if ($agenceId) {
                $query->whereHas('bail', function ($q) use ($agenceId) {
                    $q->where('agence_id', $agenceId);
                });
            }
        } elseif ($user->user_type === 'bailleur' && $user->bailleur) {
            $query->whereHas('bail.bien', function ($q) use ($user) {
                $q->where('bailleur_id', $user->bailleur->id);
            });
        } elseif ($user->user_type === 'locataire' && $user->locataire) {
            $query->whereHas('bail', function ($q) use ($user) {
                $q->where('locataire_id', $user->locataire->id);
            });
        }

        // Status filter
        if ($request->has('statut') && $request->statut) {
            $query->where('statut', $request->statut);
        }

        // Bail filter
        if ($request->has('bail_id') && $request->bail_id) {
            $query->where('bail_id', $request->bail_id);
        }

        // Bien filter
        if ($request->has('bien_id') && $request->bien_id) {
            $query->whereHas('bail', function ($q) use ($request) {
                $q->where('bien_id', $request->bien_id);
            });
        }

        // Locataire filter
        if ($request->has('locataire_id') && $request->locataire_id) {
            $query->whereHas('bail', function ($q) use ($request) {
                $q->where('locataire_id', $request->locataire_id);
            });
        }

        // Date range filter
        if ($request->has('date_debut') && $request->date_debut) {
            $query->whereDate('date_paiement', '>=', $request->date_debut);
        }
        if ($request->has('date_fin') && $request->date_fin) {
            $query->whereDate('date_paiement', '<=', $request->date_fin);
        }

        // Amount range filter
        if ($request->has('montant_min') && $request->montant_min) {
            $query->where('montant', '>=', $request->montant_min);
        }
        if ($request->has('montant_max') && $request->montant_max) {
            $query->where('montant', '<=', $request->montant_max);
        }

        // Include relationships
        $query->with([
            'bail.bien',
            'bail.locataire.user',
            'quittance',
            'ventilation'
        ]);

        // Pagination
        $paiements = $query->latest('date_paiement')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $paiements
        ]);
    }

    /**
     * Store a newly created rent payment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bail_id' => 'required|exists:baux,id',
            'montant' => 'required|numeric|min:0',
            'date_paiement' => 'required|date',
            'date_prevue' => 'nullable|date',
            'mode_paiement' => 'required|in:especes,virement,mobile_money,cheque',
            'reference_transaction' => 'nullable|string',
            'periode_debut' => 'nullable|date',
            'periode_fin' => 'nullable|date',
        ]);

        // Get the bail to determine expected amount
        $bail = \App\Models\Bail::findOrFail($validated['bail_id']);
        $montant_attendu = $bail->loyer_mensuel;

        // Determine payment status based on amount paid
        if ($validated['montant'] >= $montant_attendu) {
            $validated['statut'] = 'paye';
        } else {
            $validated['statut'] = 'partiel';
        }

        $validated['montant_attendu'] = $montant_attendu;

        // Default date_prevue to start of period or payment date if missing
        if (!isset($validated['date_prevue'])) {
            $validated['date_prevue'] = $validated['periode_debut'] ?? $validated['date_paiement'];
        }

        $paiement = PaiementLoyer::create($validated);

        // Check if there are other payments for the same period and same bail
        if ($validated['periode_debut'] && $validated['periode_fin']) {
            $paiementsMemeperiode = PaiementLoyer::where('bail_id', $validated['bail_id'])
                ->where('periode_debut', $validated['periode_debut'])
                ->where('periode_fin', $validated['periode_fin'])
                ->get();

            // Calculate total paid for this period
            $totalPaye = $paiementsMemeperiode->sum('montant');

            // If total reaches or exceeds expected amount, update all payments to 'paye'
            if ($totalPaye >= $montant_attendu) {
                $paiementsMemeperiode->each(function ($p) {
                    $p->update(['statut' => 'paye']);
                });
                // Reload current payment to reflect updated status
                $paiement->refresh();
            }
        }

        // Auto-ventilation (on actual amount paid, not expected)
        $this->ventilerPaiement($paiement);

        // Generate quittance
        $this->genererQuittance($paiement);

        return response()->json([
            'success' => true,
            'message' => 'Paiement enregistré avec succès',
            'data' => $paiement->load(['quittance', 'ventilation'])
        ], 201);
    }

    /**
     * Display the specified rent payment
     */
    public function show(string $id)
    {
        $paiement = PaiementLoyer::with([
            'bail.bien.bailleur.user',
            'bail.locataire.user',
            'bail.agence',
            'quittance',
            'ventilation'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $paiement
        ]);
    }

    /**
     * Update the specified rent payment
     */
    public function update(Request $request, string $id)
    {
        $paiement = PaiementLoyer::findOrFail($id);

        $validated = $request->validate([
            'montant' => 'sometimes|numeric|min:0',
            'date_paiement' => 'sometimes|date',
            'mode_paiement' => 'sometimes|in:especes,virement,mobile_money,cheque',
            'statut' => 'sometimes|in:en_attente,paye,en_retard,annule',
            'reference_transaction' => 'nullable|string',
        ]);

        $paiement->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Paiement mis à jour avec succès',
            'data' => $paiement
        ]);
    }

    /**
     * Remove the specified rent payment
     */
    public function destroy(string $id)
    {
        $paiement = PaiementLoyer::findOrFail($id);
        $paiement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paiement supprimé avec succès'
        ]);
    }

    /**
     * Ventiler le paiement (distribution)
     */
    private function ventilerPaiement(PaiementLoyer $paiement)
    {
        $bail = $paiement->bail;
        $montant = $paiement->montant;

        // Get commission rates from agence settings (or use defaults if no agence)
        $taux_plateforme = 5.00; // Default
        $taux_agence = 0.00;

        if ($bail->agence_id) {
            $agence = $bail->agence;
            $taux_plateforme = $agence->taux_commission_plateforme ?? 5.00;
            // Check if property has a specific commission, fallback to agence default
            $taux_agence = $bail->bien->taux_commission ?? ($agence->taux_commission_agence ?? 10.00);
        }

        // Calculate amounts based on percentages
        $montant_plateforme = $montant * ($taux_plateforme / 100);
        $montant_agence = $montant * ($taux_agence / 100);

        // Reste pour le bailleur
        $montant_bailleur = $montant - $montant_plateforme - $montant_agence;

        Ventilation::create([
            'paiement_loyer_id' => $paiement->id,
            'montant_agence' => $montant_agence,
            'montant_plateforme' => $montant_plateforme,
            'montant_bailleur' => $montant_bailleur,
            'date_ventilation' => now(),
        ]);
    }

    /**
     * Générer une quittance
     */
    private function genererQuittance(PaiementLoyer $paiement)
    {
        $numero = 'Q-' . date('Y') . '-' . str_pad($paiement->id, 6, '0', STR_PAD_LEFT);

        Quittance::create([
            'paiement_loyer_id' => $paiement->id,
            'numero' => $numero,
            'date_emission' => now(),
            'url_pdf' => null, // À générer plus tard
        ]);
    }

    /**
     * Get all unpaid and partial payments + Active Leases with NO payment for current month
     */
    public function getUnpaidRents(Request $request)
    {
        $user = $request->user();

        // 1. Identify context (Agency/Landlord)
        $agencyId = null;
        $landlordId = null;

        if ($user->user_type === 'agence') {
            $agencyId = $user->agence ? $user->agence->id : $user->agence_id;
        } elseif ($user->user_type === 'bailleur' && $user->bailleur) {
            $landlordId = $user->bailleur->id;
        }

        // 2. Fetch existing debts (Partial/Unpaid entries in DB)
        $queryDbDebts = PaiementLoyer::with([
            'bail.bien',
            'bail.locataire.user',
            'bail.agence'
        ])
            ->whereIn('statut', ['partiel', 'impaye', 'en_retard'])
            ->latest('date_paiement');

        if ($agencyId) {
            $queryDbDebts->whereHas('bail', function ($q) use ($agencyId) {
                $q->where('agence_id', $agencyId);
            });
        } elseif ($landlordId) {
            $queryDbDebts->whereHas('bail.bien', function ($q) use ($landlordId) {
                $q->where('bailleur_id', $landlordId);
            });
        }

        // Group DB debts by bail
        $dbDebts = $queryDbDebts->get()->groupBy('bail_id');


        // 3. Find Active Leases that missed current month payment
        $leasesQuery = \App\Models\Bail::with(['bien', 'locataire.user', 'agence'])
            ->where('statut', 'actif');

        if ($agencyId) {
            $leasesQuery->where('agence_id', $agencyId);
        } elseif ($landlordId) {
            $leasesQuery->whereHas('bien', function ($q) use ($landlordId) {
                $q->where('bailleur_id', $landlordId);
            });
        }

        $activeLeases = $leasesQuery->get();
        $missingPayments = collect();

        foreach ($activeLeases as $bail) {
            // Check if this bail already has a 'paye' or 'partiel' payment for current month
            // We also check if it's already in $dbDebts to avoid duplication (though dbDebts captures 'partiel'/'impaye')

            // If it's in dbDebts, it means there is a record (likely partial), so we don't treat it as "missing from scratch"
            if ($dbDebts->has($bail->id)) {
                continue;
            }

            // Check DB for any payment this month
            $hasPaymentThisMonth = PaiementLoyer::where('bail_id', $bail->id)
                ->whereYear('date_paiement', now()->year)
                ->whereMonth('date_paiement', now()->month)
                ->exists();

            if (!$hasPaymentThisMonth) {
                // Determine due date (usually 5th of month or start of lease day)
                // For simplicity: date_debut day or 1st of month
                $dueDate = now()->setDay(1);

                // Add to missing list
                $missingPayments->push([
                    'bail_id' => $bail->id,
                    'bien' => $bail->bien,
                    'locataire' => $bail->locataire,
                    'loyer_mensuel' => $bail->loyer_mensuel,
                    'montant_paye' => 0,
                    'montant_attendu' => $bail->loyer_mensuel,
                    'dette' => $bail->loyer_mensuel,
                    'paiements' => [], // Empty array as no record exists
                    'derniere_periode' => null, // No last payment
                    'periode_debut' => $dueDate->format('Y-m-d'),
                    'periode_fin' => $dueDate->copy()->endOfMonth()->format('Y-m-d'),
                    'is_virtual' => true // Flag to indicate generated debt
                ]);
            }
        }

        // 4. Transform DB debts to matching format
        $formattedDbDebts = $dbDebts->map(function ($payments) {
            $bail = $payments->first()->bail;
            $totalPaye = $payments->sum('montant');
            $montantAttendu = $payments->first()->montant_attendu ?? $bail->loyer_mensuel;
            $dette = $montantAttendu - $totalPaye;
            $firstPayment = $payments->first();

            return [
                'bail_id' => $bail->id,
                'bien' => $bail->bien,
                'locataire' => $bail->locataire,
                'loyer_mensuel' => $bail->loyer_mensuel,
                'montant_paye' => $totalPaye,
                'montant_attendu' => $montantAttendu,
                'dette' => $dette,
                'paiements' => $payments,
                'derniere_periode' => $firstPayment->periode_debut ?? $firstPayment->date_paiement,
                'periode_debut' => $firstPayment->periode_debut,
                'periode_fin' => $firstPayment->periode_fin,
                'is_virtual' => false
            ];
        })->values();

        // 5. Merge and Return
        $allDebts = $formattedDbDebts->merge($missingPayments);

        return response()->json([
            'success' => true,
            'data' => $allDebts
        ]);
    }

    /**
     * Télécharger la quittance PDF
     */
    public function downloadQuittance(string $id)
    {
        $paiement = PaiementLoyer::with(['bail.locataire.user', 'bail.bien.bailleur.user', 'bail.agence', 'bail.agence.user', 'quittance'])->findOrFail($id);

        if (!$paiement->quittance) {
            // Generate if missing logic could be here, but usually done at store
            $this->genererQuittance($paiement);
            $paiement->refresh();
        }

        $pdf = Pdf::loadView('documents.quittance', compact('paiement'));
        $filename = 'quittance-' . ($paiement->quittance->numero ?? $paiement->id) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Download debt acknowledgment document
     */
    public function downloadDebtAcknowledgment(string $id)
    {
        $paiement = PaiementLoyer::with([
            'bail.bien.bailleur.user',
            'bail.locataire.user',
            'bail.agence',
            'bail.agence.user'
        ])->findOrFail($id);

        // Calculate debt amount
        $montantDette = ($paiement->montant_attendu ?? $paiement->bail->loyer_mensuel) - $paiement->montant;
        $montantEnLettres = $this->numberToFrenchWords($montantDette);

        $pdf = Pdf::loadView('pdfs.reconnaissance_dette', compact('paiement', 'montantDette', 'montantEnLettres'));

        return $pdf->download('reconnaissance_dette_' . $paiement->id . '.pdf');
    }

    /**
     * View debt acknowledgment document
     */
    public function viewDebtAcknowledgment(string $id)
    {
        $paiement = PaiementLoyer::with([
            'bail.bien.bailleur.user',
            'bail.locataire.user',
            'bail.agence',
            'bail.agence.user'
        ])->findOrFail($id);

        // Calculate debt amount
        $montantDette = ($paiement->montant_attendu ?? $paiement->bail->loyer_mensuel) - $paiement->montant;
        $montantEnLettres = $this->numberToFrenchWords($montantDette);

        $pdf = Pdf::loadView('pdfs.reconnaissance_dette', compact('paiement', 'montantDette', 'montantEnLettres'));

        return $pdf->stream('reconnaissance_dette_' . $paiement->id . '.pdf');
    }

    /**
     * Convert number to French words
     */
    private function numberToFrenchWords($number)
    {
        $number = (int) $number;

        if ($number == 0)
            return 'zéro';

        $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];
        $teens = ['dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
        $tens = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'];

        $result = '';

        // Millions
        if ($number >= 1000000) {
            $millions = intval($number / 1000000);
            if ($millions == 1) {
                $result .= 'un million ';
            } else {
                $result .= $this->numberToFrenchWords($millions) . ' millions ';
            }
            $number %= 1000000;
        }

        // Thousands
        if ($number >= 1000) {
            $thousands = intval($number / 1000);
            if ($thousands == 1) {
                $result .= 'mille ';
            } else {
                $result .= $this->numberToFrenchWords($thousands) . ' mille ';
            }
            $number %= 1000;
        }

        // Hundreds
        if ($number >= 100) {
            $hundreds = intval($number / 100);
            if ($hundreds == 1) {
                $result .= 'cent ';
            } else {
                $result .= $units[$hundreds] . ' cent ';
            }
            $number %= 100;
        }

        // Tens and units
        if ($number >= 20) {
            $tensDigit = intval($number / 10);
            $unitsDigit = $number % 10;

            if ($tensDigit == 7 || $tensDigit == 9) {
                $result .= $tens[$tensDigit] . '-';
                if ($tensDigit == 7) {
                    $result .= $teens[$unitsDigit];
                } else {
                    $result .= $teens[$unitsDigit];
                }
            } else {
                $result .= $tens[$tensDigit];
                if ($unitsDigit > 0) {
                    if ($unitsDigit == 1 && $tensDigit != 8) {
                        $result .= ' et un';
                    } else {
                        $result .= '-' . $units[$unitsDigit];
                    }
                }
            }
        } elseif ($number >= 10) {
            $result .= $teens[$number - 10];
        } elseif ($number > 0) {
            $result .= $units[$number];
        }

        return trim($result);
    }
}
