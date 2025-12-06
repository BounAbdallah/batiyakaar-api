<?php

namespace App\Http\Controllers\Api;

use App\Models\Livraison;
use Illuminate\Http\Request;

class LivraisonController extends Controller
{
    public function index(Request $request)
    {
        $query = Livraison::with(['commande', 'fournisseur.user']);

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $livraisons = $query->latest()->paginate(15);
        return response()->json(['success' => true, 'data' => $livraisons]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'commande_id' => 'required|exists:commandes,id',
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'date_livraison_prevue' => 'required|date',
            'adresse_livraison' => 'required|string',
        ]);

        $validated['statut'] = 'en_preparation';
        $livraison = Livraison::create($validated);

        return response()->json(['success' => true, 'data' => $livraison], 201);
    }

    public function show(string $id)
    {
        $livraison = Livraison::with(['commande.lignesCommande.produit', 'fournisseur.user'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $livraison]);
    }

    public function update(Request $request, string $id)
    {
        $livraison = Livraison::findOrFail($id);
        $livraison->update($request->only(['statut', 'date_livraison_effective', 'url_preuve']));
        return response()->json(['success' => true, 'data' => $livraison]);
    }

    public function destroy(string $id)
    {
        Livraison::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Livraison supprimée']);
    }
}
