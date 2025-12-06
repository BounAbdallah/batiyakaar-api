<?php

namespace App\Http\Controllers\Api;

use App\Models\Produit;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Produit::query();

        // Filters
        if ($request->has('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        if ($request->has('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }

        // Price range
        if ($request->has('prix_min')) {
            $query->where('prix_unitaire', '>=', $request->prix_min);
        }

        if ($request->has('prix_max')) {
            $query->where('prix_unitaire', '<=', $request->prix_max);
        }

        // In stock only
        if ($request->has('en_stock') && $request->en_stock) {
            $query->where('stock_disponible', '>', 0);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nom', 'like', "%{$request->search}%")
                    ->orWhere('reference', 'like', "%{$request->search}%");
            });
        }

        // Include relationships
        $query->with(['catalogue', 'fournisseur.user']);

        // Pagination
        $produits = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $produits
        ]);
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'catalogue_id' => 'required|exists:catalogues,id',
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'reference' => 'required|string|unique:produits,reference',
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'categorie' => 'required|string',
            'prix_unitaire' => 'required|numeric|min:0',
            'unite' => 'required|string',
            'stock_disponible' => 'required|integer|min:0',
            'url_image' => 'nullable|url',
        ]);

        $produit = Produit::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produit créé avec succès',
            'data' => $produit->load(['catalogue', 'fournisseur.user'])
        ], 201);
    }

    /**
     * Display the specified product
     */
    public function show(string $id)
    {
        $produit = Produit::with([
            'catalogue',
            'fournisseur.user',
            'lignesCommande.commande'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $produit
        ]);
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, string $id)
    {
        $produit = Produit::findOrFail($id);

        $validated = $request->validate([
            'reference' => 'sometimes|string|unique:produits,reference,' . $id,
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'categorie' => 'sometimes|string',
            'prix_unitaire' => 'sometimes|numeric|min:0',
            'unite' => 'sometimes|string',
            'stock_disponible' => 'sometimes|integer|min:0',
            'url_image' => 'nullable|url',
        ]);

        $produit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produit mis à jour avec succès',
            'data' => $produit
        ]);
    }

    /**
     * Remove the specified product
     */
    public function destroy(string $id)
    {
        $produit = Produit::findOrFail($id);
        $produit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produit supprimé avec succès'
        ]);
    }

    /**
     * Update product stock
     */
    public function updateStock(Request $request, string $id)
    {
        $produit = Produit::findOrFail($id);

        $validated = $request->validate([
            'stock_disponible' => 'required|integer|min:0',
        ]);

        $produit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Stock mis à jour avec succès',
            'data' => $produit
        ]);
    }
}
