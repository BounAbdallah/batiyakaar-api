<?php

namespace App\Http\Controllers\Api;

use App\Models\PaiementEscrow;
use App\Models\ProjetConstruction;
use Illuminate\Http\Request;

class PaiementEscrowController extends Controller
{
    /**
     * Display a listing of escrow payments
     */
    public function index(Request $request)
    {
        $query = PaiementEscrow::query();

        if ($request->has('projet_id')) {
            $query->where('projet_construction_id', $request->projet_id);
        }

        $paiements = $query->with(['projetConstruction', 'etape', 'entrepreneur.user'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $paiements
        ]);
    }

    /**
     * Store (Deposit) funds into escrow
     */
    /**
     * Store (Deposit) funds into escrow
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'projet_construction_id' => 'required|exists:projets_construction,id',
            'etape_id' => 'nullable|exists:etapes,id',
            'entrepreneur_id' => 'required|exists:entrepreneurs,id',
            'montant' => 'required|numeric|min:0',
            'description' => 'required|string',
        ]);

        $validated['statut'] = 'bloque'; // Funds are blocked initially
        $validated['date_creation'] = now();

        $paiement = PaiementEscrow::create($validated);

        // Handle Wallet: Debit Bailleur (Logged in user or linked to project)
        // Assuming current user is the Bailleur or Authorized
        $user = $request->user();
        // For MVP, if user is not the bailleur, we might need to find the project's bailleur user.
        // But let's assume the Connected User IS the one paying (Bailleur).
        $this->processTransaction($user->id, $paiement->montant, 'debit', 'Dépôt séquestre: ' . $validated['description']);

        return response()->json([
            'success' => true,
            'message' => 'Fonds déposés et séquestrés avec succès',
            'data' => $paiement
        ], 201);
    }

    /**
     * Release funds (Validation by Bailleur)
     */
    public function release(Request $request, string $id)
    {
        $paiement = PaiementEscrow::findOrFail($id);

        if ($paiement->statut !== 'bloque') {
            return response()->json([
                'success' => false,
                'message' => 'Ce paiement ne peut pas être libéré (statut actuel: ' . $paiement->statut . ')'
            ], 400);
        }

        $paiement->update([
            'statut' => 'libere',
            'date_validation' => now(), // Assuming validation happens now
            'date_deblocage' => now(),
        ]);

        // Handle Wallet: Credit Entrepreneur
        // Need to find Entrepreneur's User ID.
        // Entrepreneur Model -> User Relationship
        $entrepreneur = \App\Models\Entrepreneur::find($paiement->entrepreneur_id);
        if ($entrepreneur && $entrepreneur->user_id) {
            $this->processTransaction($entrepreneur->user_id, $paiement->montant, 'credit', 'Paiement libéré: ' . $paiement->description);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fonds libérés avec succès',
            'data' => $paiement
        ]);
    }

    /**
     * Cancel/Refund funds
     */
    public function cancel(Request $request, string $id)
    {
        $paiement = PaiementEscrow::findOrFail($id);

        if ($paiement->statut !== 'bloque') {
            return response()->json([
                'success' => false,
                'message' => 'Ce paiement ne peut pas être annulé'
            ], 400);
        }

        $paiement->update([
            'statut' => 'annule',
        ]);

        // Refund Bailleur (Current User or Project Owner)
        // For MVP assuming acting user gets refund or we check project owner
        $projet = ProjetConstruction::find($paiement->projet_construction_id);
        if ($projet && $projet->bailleur && $projet->bailleur->user_id) {
            $this->processTransaction($projet->bailleur->user_id, $paiement->montant, 'credit', 'Remboursement: ' . $paiement->description);
        }

        return response()->json([
            'success' => true,
            'message' => 'Paiement annulé, fonds remboursés',
            'data' => $paiement
        ]);
    }

    /**
     * Process a wallet transaction
     */
    private function processTransaction($userId, $amount, $type, $description)
    {
        $wallet = \App\Models\PortefeuilleVirtuel::firstOrCreate(
            ['user_id' => $userId],
            ['solde' => 0, 'devise' => 'CFA']
        );

        if ($type === 'debit') {
            $wallet->solde -= $amount;
        } else {
            $wallet->solde += $amount;
        }
        $wallet->save();

        // Create Transaction Record
        \App\Models\Transaction::create([
            'reference' => 'TX-' . strtoupper(uniqid()),
            'montant' => $amount,
            'date_transaction' => now(),
            'type_transaction' => $type === 'debit' ? 'paiement_loyer' : 'paiement_loyer', // Reuse existing enum or generic
            'statut' => 'valide',
            'description' => $description,
            'emetteur_id' => $type === 'debit' ? $userId : null, // If debit, user pays.
            'beneficiaire_id' => $type === 'credit' ? $userId : null, // If credit, user receives.
        ]);
    }
}
