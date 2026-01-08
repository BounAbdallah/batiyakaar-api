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
     * Display a listing of rent payments - filtered by payment mode
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
        $taux_agence = 0.00;

        if ($bail->agence_id) {
            $agence = $bail->agence;
            // Check if property has a specific commission, fallback to agence default
            $taux_agence = $bail->bien->taux_commission ?? ($agence->taux_commission_agence ?? 10.00);
        }

        // Calculate amounts based on percentages
        $montant_agence = $montant * ($taux_agence / 100);

        // Reste pour le bailleur
        $montant_bailleur = $montant - $montant_agence;

        // Calcul montant plateforme (Net Profit)
        // 1.5% frais client - 1.0% frais Wave Payout = 0.5% Marge
        $montant_plateforme = $montant * 0.005;

        $ventilation = Ventilation::create([
            'paiement_loyer_id' => $paiement->id,
            'montant_agence' => $montant_agence,
            'montant_plateforme' => $montant_plateforme,
            'montant_bailleur' => $montant_bailleur,
            'date_ventilation' => now(),
        ]);

        // Notify Admins of Commission
        if ($montant_plateforme > 0) {
            $admins = \App\Models\User::where('user_type', 'admin')->get();
            // Also notify any user with 'admin' role if using Spatie permissions, but standard user_type check is safer for now based on prev code
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\CommissionEarned($ventilation));
            }
        }
    }

    /**
     * Générer une quittance
     */
    private function genererQuittance(PaiementLoyer $paiement)
    {
        $numero = 'Q-' . date('Y') . '-' . str_pad($paiement->id, 6, '0', STR_PAD_LEFT);

        // Create quittance record
        $quittance = Quittance::create([
            'paiement_loyer_id' => $paiement->id,
            'numero_quittance' => $numero,
            'montant' => $paiement->montant,
            'periode_debut' => $paiement->periode_debut,
            'periode_fin' => $paiement->periode_fin,
            'date_emission' => now(),
            'url_pdf' => null,
        ]);

        // Load relationships for email
        $paiement->load(['bail.locataire.user', 'bail.bien']);
        $quittance->load(['paiementLoyer.bail.bien', 'paiementLoyer.bail.locataire.user']);

        // Generate PDF and send email to tenant
        try {
            // Ensure temp directory exists
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Generate PDF
            $pdf = Pdf::loadView('pdfs.quittance', ['quittance' => $quittance]);
            $pdfPath = $tempDir . '/quittance_' . $quittance->id . '_' . time() . '.pdf';
            $pdf->save($pdfPath);

            // Send email to tenant if email exists, otherwise fallback to Agency
            if ($paiement->bail->locataire && $paiement->bail->locataire->user && $paiement->bail->locataire->user->email) {
                \Illuminate\Support\Facades\Mail::to($paiement->bail->locataire->user)->send(
                    new \App\Mail\ReceiptCreated($quittance, $paiement->bail->locataire->user, $pdfPath)
                );
            } elseif ($paiement->bail->agence) {
                // Fallback: Send to Agency if tenant has no email
                // We send it to the Agency User (owner or manager)
                $agenceUser = $paiement->bail->agence->user; // Assuming Agence belongsTo User
                if ($agenceUser && $agenceUser->email) {
                    \Illuminate\Support\Facades\Mail::to($agenceUser)->send(
                        new \App\Mail\ReceiptCreated($quittance, $paiement->bail->locataire->user, $pdfPath)
                    );
                }
            }

            // Delete temporary PDF
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send receipt email: ' . $e->getMessage());
        }
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
        $filename = 'quittance-' . ($paiement->quittance->numero_quittance ?? $paiement->id) . '.pdf';

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


    /**
     * Initiate Wave Payment
     */
    public function initiateWavePayment(Request $request, \App\Services\WaveService $waveService)
    {
        $validated = $request->validate([
            'bail_id' => 'required|exists:baux,id',
            'montant' => 'required|numeric|min:1',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020|max:2030',
        ]);

        $bail = \App\Models\Bail::findOrFail($validated['bail_id']);

        // Ensure user is authorized
        $user = $request->user();
        if ($user->user_type === 'locataire' && $user->locataire) {
            if ($bail->locataire_id !== $user->locataire->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        // Month/Year handling
        $month = $validated['month'] ?? now()->month;
        $year = $validated['year'] ?? now()->year;

        // Generate a simplified client reference with Month/Year
        // Format: RENT-{bail_id}-{timestamp}-{month}-{year}
        $clientReference = 'RENT-' . $bail->id . '-' . time() . '-' . $month . '-' . $year;

        // Define URLs (Frontend routes)
        // Use production URL if available or fallback to env.
        // User explicitly asked for: https://noor-immo.noorwebservices.com/
        $frontendUrl = 'https://noor-immo.noorwebservices.com';

        // Initial Logic for local dev fallback if needed, but for now specific request:
        if (app()->environment('local')) {
            $waveReturnUrl = str_replace('localhost', '127.0.0.1.nip.io', env('FRONTEND_URL', 'http://localhost:5173'));
            $waveReturnUrl = str_replace('http://', 'https://', $waveReturnUrl);
        } else {
            $waveReturnUrl = $frontendUrl;
        }

        // Calculate Fees (1.5%)
        $feesRate = 0.015;
        $originalAmount = $validated['montant'];
        $waveAmount = ceil($originalAmount * (1 + $feesRate));

        $errorUrl = $waveReturnUrl . '/dashboard/paiements/error';
        $successUrl = $waveReturnUrl . "/dashboard/paiements/success?ref={$clientReference}&bail_id={$validated['bail_id']}&amount={$originalAmount}";

        // Generate Motif
        // Use french locale for month name if possible, or simple array mapping
        $months = [
            1 => 'Janvier',
            2 => 'Février',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Août',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre'
        ];
        $monthName = $months[$month] ?? $month;
        $motif = "Paiement Loyer - $monthName $year";

        $monthName = $months[$month] ?? $month;
        $motif = "Paiement Loyer - $monthName $year";

        // Construct Customer Object
        // Only sending name to avoid validation errors (e.g. phone format E.164)
        $locataireUser = optional($bail->locataire)->user;
        $customer = [];

        if ($locataireUser) {
            $customer['name'] = trim($locataireUser->prenom . ' ' . $locataireUser->nom);

            // Optional: generic email if missing
            // $customer['email'] = $locataireUser->email ?? 'no-email@example.com'; 
        } else {
            // Fallback if no user attached (shouldn't happen for valid lease)
            $customer['name'] = 'Locataire - Bail ' . $bail->id;
        }

        try {
            $session = $waveService->createCheckoutSession(
                (int) $waveAmount, // Ensure integer
                'XOF',
                $errorUrl,
                $successUrl,
                $clientReference,
                $motif,
                $customer
            );

            \Illuminate\Support\Facades\Log::info('Wave Session Created', ['session' => $session]);

            // 1. Try generic web URL
            $checkoutUrl = $session['url'] ?? null;

            // 2. If missing, try to extract web URL from deep link (wave://capture/<web_url>)
            if (empty($checkoutUrl) && !empty($session['wave_launch_url'])) {
                $deepLink = $session['wave_launch_url'];
                if (strpos($deepLink, 'wave://capture/') === 0) {
                    $checkoutUrl = str_replace('wave://capture/', '', $deepLink);
                } else {
                    $checkoutUrl = $deepLink;
                }
            }

            // 3. Last resort fallback
            if (empty($checkoutUrl)) {
                $checkoutUrl = $session['wave_launch_url'] ?? '';
            }

            \Illuminate\Support\Facades\Log::info('Selected Checkout URL', ['url' => $checkoutUrl]);

            if (empty($checkoutUrl)) {
                throw new \Exception('URL de paiement non reçue de Wave.');
            }

            return response()->json([
                'success' => true,
                'checkout_url' => $checkoutUrl
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Wave Payment Init Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initialisation du paiement Wave: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Wave Webhook
     */
    public function handleWebhook(Request $request)
    {
        // 1. Log the incoming webhook
        \Illuminate\Support\Facades\Log::info('Wave Webhook Received', $request->all());

        // 2. Verify Signature (Optional but recommended)
        $waveSecret = env('WAVE_WEBHOOK_SECRET');
        $signature = $request->header('wave-signature');
        // Note: Wave documentation specifies how to verify.
        // For now preventing 500 if secret missing.

        // 3. Extract Data
        $data = $request->all();
        $type = $data['type'] ?? null; // e.g., 'checkout.session.completed'

        // Adjust based on actual Wave Event structure.
        // Commonly: type='checkout.session.completed', data object contains status.

        if ($type === 'checkout.session.completed') {
            $session = $data['data'];
            $clientReference = $session['client_reference'] ?? null;
            $paymentStatus = $session['payment_status'] ?? 'succeeded';

            if ($clientReference && $paymentStatus === 'succeeded') {
                // Find payment/lease by client reference
                // Match format: RENT-{bail_id}-{timestamp}-{month}-{year}
                // Fallback to old format: RENT-{bail_id}-{timestamp}
                $parts = explode('-', $clientReference);
                if (count($parts) >= 3 && $parts[0] === 'RENT') {
                    $bailId = $parts[1];
                    $month = isset($parts[3]) ? (int) $parts[3] : now()->month;
                    $year = isset($parts[4]) ? (int) $parts[4] : now()->year;

                    // Logic to confirm payment
                    // Since we don't store a pending payment record beforehand in 'initiate',
                    // we might need to create it now OR update an existing 'pending' one if we had one.
                    // But in 'initiate' we didn't create a 'pending' PaiementLoyer.
                    // We must rely on the info to CREATE the payment record now.

                    // However, we need the amount. Wave confirms the gross amount (w/ fees).
                    // We need the original rent amount.
                    // Ideally we should have stored a pending payment or encoded it in reference.

                    // Re-fetching bail to perform logic
                    $bail = \App\Models\Bail::find($bailId);
                    if ($bail) {
                        // Implement Idempotency: Check if payment with this reference exists
                        $existingPayment = PaiementLoyer::where('reference_transaction', $clientReference)->first();

                        if ($existingPayment) {
                            \Illuminate\Support\Facades\Log::info("Payment already processed for ref: {$clientReference}");
                            return response()->json(['status' => 'already_processed']);
                        }

                        // Determine Period based on Reference or Current Date
                        $dueDate = \Carbon\Carbon::createFromDate($year, $month, 1);

                        // Create Payment Record
                        $paiement = PaiementLoyer::create([
                            'bail_id' => $bail->id,
                            'montant' => $bail->loyer_mensuel, // Use rent amount, not Wave amount (which includes fees)
                            'date_paiement' => now(),
                            'date_prevue' => $dueDate,
                            'mode_paiement' => 'mobile_money',
                            'reference_transaction' => $clientReference,
                            'statut' => 'paye', // Direct success
                            'periode_debut' => $dueDate->format('Y-m-d'),
                            'periode_fin' => $dueDate->copy()->endOfMonth()->format('Y-m-d'),
                            'montant_attendu' => $bail->loyer_mensuel
                        ]);

                        // Auto-ventilation & Receipt
                        $this->ventilerPaiement($paiement);
                        $this->genererQuittance($paiement);

                        // Notify Agency
                        $this->notifyAgencyOfPayment($bail, $session['amount']);

                        // --- WAVE CASHOUT IMPLEMENTATION ---
                        try {
                            // Reload to get Agency User
                            $bail->load('agence.user');

                            if ($bail->agence && $bail->agence->user && $bail->agence->user->telephone) {
                                // Determine Amount: Full Rent (as requested by User)
                                // The agency receives the full rent amount.
                                $payoutAmount = $paiement->montant;

                                if ($payoutAmount > 0) {
                                    $recipientName = $bail->agence->raison_sociale ?? ($bail->agence->user->prenom . ' ' . $bail->agence->user->nom);

                                    // Instantiate service manually or use dependency injection if cleaner
                                    // Here we use the injected $waveService if passed to handleWebhook, or resolve it
                                    $waveService = app(\App\Services\WaveService::class);

                                    $payoutResult = $waveService->payout(
                                        (int) $payoutAmount,
                                        'XOF',
                                        $recipientName,
                                        $bail->agence->user->telephone
                                    );

                                    if ($payoutResult) {
                                        \Illuminate\Support\Facades\Log::info("Wave Payout Success for Bail {$bail->id}", $payoutResult);
                                    } else {
                                        \Illuminate\Support\Facades\Log::error("Wave Payout Failed for Bail {$bail->id}");
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Wave Payout Exception: ' . $e->getMessage());
                        }
                        // -----------------------------------

                        return response()->json(['status' => 'processed']);
                    }
                }
            }
        }

        return response()->json(['status' => 'ignored']);
    }

    private function notifyAgencyOfPayment($bail, $amount)
    {
        // Simple log for now, can be expanded to Email
        \Illuminate\Support\Facades\Log::info("Payment validated via Webhook for Bail #{$bail->id}. Amount: {$amount}");

        // Notify Agency User(s)
        // Notify Agency User(s)
        $userToNotify = null;
        if ($bail->agence && $bail->agence->user) {
            $userToNotify = $bail->agence->user;
        } elseif ($bail->bien->bailleur && $bail->bien->bailleur->user) {
            $userToNotify = $bail->bien->bailleur->user;
        }

        if ($userToNotify) {
            // Send Email (Standard Laravel)
            try {
                $userToNotify->notify(new \App\Notifications\AgencyPaymentNotification($bail, $amount));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Email notification failed: " . $e->getMessage());
            }

            // Insert into Database (Custom Table)
            try {
                \Illuminate\Support\Facades\DB::table('notifications')->insert([
                    'user_id' => $userToNotify->id,
                    'titre' => 'Paiement Wave Reçu',
                    'message' => "Paiement de " . number_format($amount, 0, ',', ' ') . " FCFA pour le bien " . $bail->bien->reference,
                    'type' => 'paiement', // Matches enum
                    'date_envoi' => now(),
                    'lue' => false,
                    'metadata' => json_encode([
                        'bail_id' => $bail->id,
                        'amount' => $amount,
                        'reference' => $this->getReferenceTransaction($bail, $amount) ?? 'N/A'
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Database notification failed: " . $e->getMessage());
            }
        }
    }

    private function getReferenceTransaction($bail, $amount)
    {
        return 'WAVE-' . time(); // Simple fallback if not passed directly
    }

    /**
     * Handle Wave Callback / Confirmation
     */
    public function confirmWavePayment(Request $request)
    {
        $validated = $request->validate([
            'bail_id' => 'required|exists:baux,id',
            'reference_transaction' => 'required|string',
            'montant' => 'required|numeric',
        ]);

        // Force mode to mobile_money and set date
        $request->merge([
            'mode_paiement' => 'mobile_money',
            'date_paiement' => now(),
        ]);

        // Check for existing payment
        $existing = PaiementLoyer::where('reference_transaction', $validated['reference_transaction'])->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Paiement déjà enregistré.',
                'data' => $existing
            ]);
        }

        return $this->store($request);
    }
}
