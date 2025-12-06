<?php

namespace App\Http\Controllers\Api;

use App\Models\Commande;
use App\Models\LigneCommande;
use Illuminate\Http\Request;

class CommandeController extends Controller
{
    public function index(Request $request)
    {
        $query = Commande::with(['bailleur.user', 'projetConstruction', 'lignesCommande.produit']);

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $commandes = $query->latest()->paginate(15);

        return response()->json(['success' => true, 'data' => $commandes]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bailleur_id' => 'required|exists:bailleurs,id',
            'projet_construction_id' => 'nullable|exists:projets_construction,id',
            'lignes' => 'required|array',
            'lignes.*.produit_id' => 'required|exists:produits,id',
            'lignes.*.quantite' => 'required|integer|min:1',
        ]);

        $numero = 'CMD-' . date('Y') . '-' . str_pad(Commande::count() + 1, 6, '0', STR_PAD_LEFT);

        $commande = Commande::create([
            'bailleur_id' => $validated['bailleur_id'],
            'projet_construction_id' => $validated['projet_construction_id'] ?? null,
            'numero_commande' => $numero,
            'date_commande' => now(),
            'montant_total' => 0,
            'statut' => 'en_attente',
        ]);

        $montant_total = 0;
        foreach ($validated['lignes'] as $ligne) {
            $produit = \App\Models\Produit::find($ligne['produit_id']);
            $sous_total = $produit->prix_unitaire * $ligne['quantite'];

            LigneCommande::create([
                'commande_id' => $commande->id,
                'produit_id' => $ligne['produit_id'],
                'quantite' => $ligne['quantite'],
                'prix_unitaire' => $produit->prix_unitaire,
                'sous_total' => $sous_total,
            ]);

            $montant_total += $sous_total;
        }

        $commande->update(['montant_total' => $montant_total]);

        return response()->json(['success' => true, 'data' => $commande->load('lignesCommande.produit')], 201);
    }

    public function show(string $id)
    {
        $commande = Commande::with(['bailleur.user', 'lignesCommande.produit', 'livraison'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $commande]);
    }

    public function update(Request $request, string $id)
    {
        $commande = Commande::findOrFail($id);
        $commande->update($request->only(['statut']));
        return response()->json(['success' => true, 'data' => $commande]);
    }

    public function destroy(string $id)
    {
        Commande::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Commande supprimée']);
    }
}
