<?php

namespace App\Http\Controllers\Api;

use App\Models\PaiementLoyer;
use App\Models\Ventilation;
use App\Models\Quittance;
use Illuminate\Http\Request;

class PaiementLoyerController extends Controller
{
    /**
     * Display a listing of rent payments
     */
    public function index(Request $request)
    {
        $query = PaiementLoyer::query();

        // Filters
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('bail_id')) {
            $query->where('bail_id', $request->bail_id);
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
            'date_prevue' => 'required|date',
            'mode_paiement' => 'required|in:especes,virement,mobile_money,cheque',
            'reference_transaction' => 'nullable|string',
        ]);

        $validated['statut'] = 'paye';

        $paiement = PaiementLoyer::create($validated);

        // Auto-ventilation
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
            'bail.agence.user',
            'quittance',
            'ventilation',
            'transaction'
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

        // Commission plateforme (5%)
        $montant_plateforme = $montant * 0.05;

        // Commission agence (10% si agence)
        $montant_agence = $bail->agence_id ? $montant * 0.10 : 0;

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
}
