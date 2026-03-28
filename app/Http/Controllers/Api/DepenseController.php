<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Depense;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class DepenseController extends Controller
{
    /**
     * Generate PDF for a specific expense.
     */
    public function generatePDF(string $id)
    {
        $user = Auth::user();
        $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
        $depense = Depense::with(['bailleur.user', 'immeuble', 'bien', 'agence'])->find($id);

        if (!$depense) {
            return response()->json(['success' => false, 'message' => 'Dépense non trouvée'], 404);
        }

        // Security check
        if ($agenceId && $depense->agence_id !== $agenceId) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $pdf = Pdf::loadView('pdfs.depense', compact('depense'));

        $filename = 'depense_' . str_pad($depense->id, 5, '0', STR_PAD_LEFT) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Display a listing of the resource.

     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
        $query = Depense::query();

        // Filter by Agency (if user belongs to one)
        if ($agenceId) {
            $query->where('agence_id', $agenceId);
        }

        // Filters
        if ($request->has('bailleur_id')) {
            $query->where('bailleur_id', $request->bailleur_id);
        }
        if ($request->has('immeuble_id')) {
            $query->where('immeuble_id', $request->immeuble_id);
        }
        if ($request->has('bien_id')) {
            $query->where('bien_id', $request->bien_id);
        }
        if ($request->has('date_debut')) {
            $query->whereDate('date_depense', '>=', $request->date_debut);
        }
        if ($request->has('date_fin')) {
            $query->whereDate('date_depense', '<=', $request->date_fin);
        }

        $depenses = $query->with(['bailleur.user', 'immeuble', 'bien'])
            ->orderBy('date_depense', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $depenses
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0',
            'date_depense' => 'required|date',
            'categorie' => 'required|in:electricite,eau,gardiennage,entretien,reparation,autre',
            'statut' => 'required|in:paye,en_attente,annule',
            'bailleur_id' => 'required|exists:bailleurs,id',
            'immeuble_id' => 'nullable|exists:immeubles,id',
            'bien_id' => 'nullable|exists:biens,id',
            'preuve' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Always get agence_id from the bailleur's user
        // This ensures the expense is linked to the correct agency regardless of who creates it
        $bailleur = \App\Models\Bailleur::with('user')->find($request->bailleur_id);

        if (!$bailleur) {
            return response()->json(['success' => false, 'message' => 'Bailleur non trouvé'], 404);
        }

        $bailleurAgenceId = $bailleur->user ? ($bailleur->user->agence ? $bailleur->user->agence->id : $bailleur->user->agence_id) : null;

        if (!$bailleurAgenceId) {
            return response()->json([
                'success' => false,
                'message' => 'Ce bailleur doit être associé à une agence avant de pouvoir enregistrer des dépenses'
            ], 400);
        }

        $agence_id = $bailleurAgenceId;

        $data = $request->all();
        $data['agence_id'] = $agence_id;

        if ($request->hasFile('preuve')) {
            $path = $request->file('preuve')->store('preuves_depenses', 'public');
            $data['preuve'] = $path;
        }

        $depense = Depense::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Dépense enregistrée avec succès',
            'data' => $depense
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
        $depense = Depense::with(['bailleur.user', 'immeuble', 'bien'])->find($id);

        if (!$depense) {
            return response()->json(['success' => false, 'message' => 'Dépense non trouvée'], 404);
        }

        // Security check
        if ($agenceId && $depense->agence_id !== $agenceId) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $depense
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
        $depense = Depense::find($id);

        if (!$depense) {
            return response()->json(['success' => false, 'message' => 'Dépense non trouvée'], 404);
        }

        if ($agenceId && $depense->agence_id !== $agenceId) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'titre' => 'sometimes|required|string|max:255',
            'montant' => 'sometimes|required|numeric|min:0',
            'date_depense' => 'sometimes|required|date',
            'categorie' => 'sometimes|required|in:electricite,eau,gardiennage,entretien,reparation,autre',
            'statut' => 'sometimes|required|in:paye,en_attente,annule',
            'bailleur_id' => 'sometimes|required|exists:bailleurs,id',
            'immeuble_id' => 'nullable|exists:immeubles,id',
            'bien_id' => 'nullable|exists:biens,id',
            'preuve' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->except(['agence_id', 'preuve']); // Prevent changing agency

        if ($request->hasFile('preuve')) {
            // Delete old proof if exists
            if ($depense->preuve) {
                Storage::disk('public')->delete($depense->preuve);
            }
            $path = $request->file('preuve')->store('preuves_depenses', 'public');
            $data['preuve'] = $path;
        }

        $depense->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Dépense mise à jour avec succès',
            'data' => $depense
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
        $depense = Depense::find($id);

        if (!$depense) {
            return response()->json(['success' => false, 'message' => 'Dépense non trouvée'], 404);
        }

        if ($agenceId && $depense->agence_id !== $agenceId) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        if ($depense->preuve) {
            Storage::disk('public')->delete($depense->preuve);
        }

        $depense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dépense supprimée avec succès'
        ]);
    }
}
