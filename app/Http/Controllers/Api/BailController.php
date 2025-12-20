<?php

namespace App\Http\Controllers\Api;

use App\Models\Bail;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class BailController extends Controller
{
    /**
     * Display a listing of leases - filtered by role
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Bail::query();

        // Admin sees all leases

        // Filter by user role - automatic restriction
        if ($user->user_type === 'agence') {
            $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
            if ($agenceId) {
                $query->where('agence_id', $agenceId);
            }
        } elseif ($user->user_type === 'bailleur' && $user->bailleur) {
            $query->whereHas('bien', function ($q) use ($user) {
                $q->where('bailleur_id', $user->bailleur->id);
            });
        } elseif ($user->user_type === 'locataire' && $user->locataire) {
            $query->where('locataire_id', $user->locataire->id);
        }

        // Filters
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('bien_id')) {
            $query->where('bien_id', $request->bien_id);
        }

        if ($request->has('locataire_id')) {
            $query->where('locataire_id', $request->locataire_id);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('bien', function ($bq) use ($search) {
                    $bq->where('reference', 'like', "%{$search}%")
                        ->orWhere('adresse', 'like', "%{$search}%");
                })->orWhereHas('locataire.user', function ($lq) use ($search) {
                    $lq->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%");
                });
            });
        }

        // Include relationships
        $query->with([
            'bien.bailleur.user',
            'locataire.user',
            'agence.user'
        ]);

        // Pagination
        $baux = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $baux
        ]);
    }

    /**
     * Store a newly created lease
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bien_id' => 'required|exists:biens,id',
            'locataire_id' => 'required|exists:locataires,id',
            'agence_id' => 'nullable|exists:agences,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'type_duree' => 'required|in:determinee,indeterminee',
            'loyer_mensuel' => 'required|numeric|min:0',
            'caution' => 'required|numeric|min:0',
        ]);

        $validated['statut'] = 'actif';

        $bail = Bail::create($validated);

        // Update bien status
        $bail->bien->update(['statut' => 'loue']);

        return response()->json([
            'success' => true,
            'message' => 'Bail créé avec succès',
            'data' => $bail->load(['bien', 'locataire.user', 'agence.user'])
        ], 201);
    }

    /**
     * Display the specified lease
     */
    public function show(string $id)
    {
        $bail = Bail::with([
            'bien.bailleur.user',
            'locataire.user',
            'agence.user',
            'paiementsLoyer.quittance',
            'incidents.technicien',
            'etatsDesLieux'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $bail
        ]);
    }

    /**
     * Update the specified lease
     */
    public function update(Request $request, string $id)
    {
        $bail = Bail::findOrFail($id);

        $validated = $request->validate([
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date',
            'type_duree' => 'sometimes|in:determinee,indeterminee',
            'loyer_mensuel' => 'sometimes|numeric|min:0',
            'caution' => 'sometimes|numeric|min:0',
            'statut' => 'sometimes|in:actif,expire,resilie',
        ]);

        $bail->update($validated);

        // Update bien status if lease expired
        if (isset($validated['statut']) && $validated['statut'] !== 'actif') {
            $bail->bien->update(['statut' => 'disponible']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bail mis à jour avec succès',
            'data' => $bail->load(['bien', 'locataire.user'])
        ]);
    }

    /**
     * Remove the specified lease
     */
    public function destroy(string $id)
    {
        $bail = Bail::findOrFail($id);

        // Update bien status
        $bail->bien->update(['statut' => 'disponible']);

        $bail->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bail supprimé avec succès'
        ]);
    }
    /**
     * Download the lease contract as PDF
     */
    public function downloadContract($id)
    {
        $bail = Bail::with(['bien.bailleur.user', 'locataire.user', 'agence', 'agence.user'])->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.contrat_location', compact('bail'));

        return $pdf->download('contrat_bail_' . $bail->reference . '.pdf');
    }

    /**
     * View the lease contract as PDF (stream)
     */
    public function viewContract(Request $request, $id)
    {
        // Authenticate via token from query if present
        if ($token = $request->query('token')) {
            $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
            if ($user) {
                \Illuminate\Support\Facades\Auth::setUser($user);
            }
        }

        // Increase memory limit for PDF generation with images
        ini_set('memory_limit', '512M');

        $bail = Bail::with(['bien.bailleur.user', 'locataire.user', 'agence', 'agence.user'])->findOrFail($id);

        $pdf = Pdf::loadView('pdfs.contrat_location', compact('bail'));

        return $pdf->stream('contrat-bail-' . $bail->id . '.pdf');
    }

    /**
     * Download debt acknowledgment for unpaid rent based on bail
     */
    public function downloadDebtForBail(Request $request, string $id)
    {
        $bail = Bail::with([
            'bien.bailleur.user',
            'locataire.user',
            'agence',
            'agence.user'
        ])->findOrFail($id);

        // Get debt amount from query params or use monthly rent
        $montantDette = $request->query('montant', $bail->loyer_mensuel);
        $periodeDebut = $request->query('periode_debut');
        $periodeFin = $request->query('periode_fin');

        // Convert amount to French words
        $montantEnLettres = $this->numberToFrenchWords($montantDette);

        // Create a pseudo-payment object for the template
        $paiement = (object) [
            'bail' => $bail,
            'montant' => 0,
            'montant_attendu' => $montantDette,
            'periode_debut' => $periodeDebut,
            'periode_fin' => $periodeFin,
            'id' => 'bail-' . $id
        ];

        $pdf = Pdf::loadView('pdfs.reconnaissance_dette', compact('paiement', 'montantDette', 'montantEnLettres'));

        return $pdf->download('reconnaissance_dette_bail_' . $id . '.pdf');
    }

    /**
     * View debt acknowledgment for unpaid rent based on bail
     */
    public function viewDebtForBail(Request $request, string $id)
    {
        // Authenticate via token from query if present
        if ($token = $request->query('token')) {
            $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
            if ($user) {
                \Illuminate\Support\Facades\Auth::setUser($user);
            }
        }

        // Increase memory limit for PDF generation with images
        ini_set('memory_limit', '512M');

        $bail = Bail::with([
            'bien.bailleur.user',
            'locataire.user',
            'agence'
        ])->findOrFail($id);

        // Get debt amount from query params or use monthly rent
        $montantDette = $request->query('montant', $bail->loyer_mensuel);
        $periodeDebut = $request->query('periode_debut');
        $periodeFin = $request->query('periode_fin');

        // Convert amount to French words
        $montantEnLettres = $this->numberToFrenchWords($montantDette);

        // Create a pseudo-payment object for the template
        $paiement = (object) [
            'bail' => $bail,
            'montant' => 0,
            'montant_attendu' => $montantDette,
            'periode_debut' => $periodeDebut,
            'periode_fin' => $periodeFin,
            'id' => 'bail-' . $id
        ];

        $pdf = Pdf::loadView('pdfs.reconnaissance_dette', compact('paiement', 'montantDette', 'montantEnLettres'));

        return $pdf->stream('reconnaissance_dette_bail_' . $id . '.pdf');
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
     * Download demand letter for unpaid rent
     */
    public function downloadDemandLetter(Request $request, string $id)
    {
        $bail = Bail::with([
            'bien.bailleur.user',
            'locataire.user',
            'agence.user'
        ])->findOrFail($id);

        $montantTotal = $request->query('montant', $bail->loyer_mensuel);
        $moisImpayés = $request->query('mois', []);

        // Convert to array if string
        if (is_string($moisImpayés)) {
            $moisImpayés = json_decode($moisImpayés, true) ?: explode(',', $moisImpayés);
        }

        $montantEnLettres = $this->numberToFrenchWords($montantTotal);

        $pdf = Pdf::loadView('pdfs.mise_en_demeure', compact('bail', 'montantTotal', 'montantEnLettres', 'moisImpayés'));

        return $pdf->download('mise_en_demeure_' . $bail->id . '.pdf');
    }

    /**
     * View demand letter for unpaid rent
     */
    public function viewDemandLetter(Request $request, string $id)
    {
        // Authenticate via token from query if present
        if ($token = $request->query('token')) {
            $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
            if ($user) {
                \Illuminate\Support\Facades\Auth::setUser($user);
            }
        }

        // Increase memory limit for PDF generation with images
        ini_set('memory_limit', '512M');

        $bail = Bail::with([
            'bien.bailleur.user',
            'locataire.user',
            'agence',
            'agence.user'
        ])->findOrFail($id);

        $montantTotal = $request->query('montant', $bail->loyer_mensuel);
        $moisImpayés = $request->query('mois', []);

        // Convert to array if string
        if (is_string($moisImpayés)) {
            $moisImpayés = json_decode($moisImpayés, true) ?: explode(',', $moisImpayés);
        }

        $montantEnLettres = $this->numberToFrenchWords($montantTotal);

        $pdf = Pdf::loadView('pdfs.mise_en_demeure', compact('bail', 'montantTotal', 'montantEnLettres', 'moisImpayés'));

        return $pdf->stream('mise_en_demeure_' . $bail->id . '.pdf');
    }
}
