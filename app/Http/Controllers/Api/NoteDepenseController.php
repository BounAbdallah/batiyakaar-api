<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NoteDepense;
use App\Models\Depense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoteDepenseController extends Controller
{
    public function index(Request $request)
    {
        $query = NoteDepense::with(['depenses', 'bailleur.user', 'immeuble', 'bien']);

        $user = $request->user();
        if ($user->user_type === 'agence') {
            $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
            $query->where('agence_id', $agenceId);
        }

        if ($request->has('bailleur_id') && $request->bailleur_id) {
            $query->where('bailleur_id', $request->bailleur_id);
        }
        if ($request->has('immeuble_id') && $request->immeuble_id) {
            $query->where('immeuble_id', $request->immeuble_id);
        }
        if ($request->has('bien_id') && $request->bien_id) {
            $query->where('bien_id', $request->bien_id);
        }
        if ($request->has('mois') && $request->mois) {
            $query->where('mois', $request->mois);
        }
        if ($request->has('annee') && $request->annee) {
            $query->where('annee', $request->annee);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(15)
        ]);
    }

    public function show($id)
    {
        $note = NoteDepense::with(['depenses', 'bailleur.user', 'immeuble', 'bien'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $note]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'mois' => 'required|integer',
            'annee' => 'required|integer',
            'bailleur_id' => 'required|exists:bailleurs,id',
            'depenses' => 'required|array',
            'immeuble_id' => 'nullable|exists:immeubles,id',
            'bien_id' => 'nullable|exists:biens,id'
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $agenceId = $user->user_type === 'agence' && $user->agence ? $user->agence->id : null;

            $note = NoteDepense::create([
                'numero' => 'ND-' . strtoupper(uniqid()),
                'mois' => $request->mois,
                'annee' => $request->annee,
                'bailleur_id' => $request->bailleur_id,
                'immeuble_id' => $request->immeuble_id,
                'bien_id' => $request->bien_id,
                'agence_id' => $agenceId,
                'description' => $request->description,
                'statut' => $request->statut ?? 'en_attente',
                'total_montant' => collect($request->depenses)->sum('montant')
            ]);

            foreach ($request->depenses as $depenseData) {
                $note->depenses()->create([
                    'titre' => $depenseData['titre'] ?? 'Dépense',
                    'montant' => $depenseData['montant'],
                    'date_depense' => $depenseData['date_depense'] ?? now(),
                    'categorie' => $depenseData['categorie'] ?? 'autre',
                    'bailleur_id' => $request->bailleur_id,
                    'immeuble_id' => $request->immeuble_id,
                    'bien_id' => $request->bien_id,
                    'agence_id' => $agenceId
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Note de dépense créée avec succès',
                'data' => $note->load('depenses')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'mois' => 'required|integer',
            'annee' => 'required|integer',
            'bailleur_id' => 'required|exists:bailleurs,id',
            'depenses' => 'required|array',
            'immeuble_id' => 'nullable|exists:immeubles,id',
            'bien_id' => 'nullable|exists:biens,id'
        ]);

        try {
            DB::beginTransaction();

            $note = NoteDepense::findOrFail($id);
            
            $note->update([
                'mois' => $request->mois,
                'annee' => $request->annee,
                'bailleur_id' => $request->bailleur_id,
                'immeuble_id' => $request->immeuble_id,
                'bien_id' => $request->bien_id,
                'description' => $request->description,
                'statut' => $request->statut ?? 'en_attente',
                'total_montant' => collect($request->depenses)->sum('montant')
            ]);
            
            // Delete old depenses and recreate
            $note->depenses()->delete();

            $user = $request->user();
            $agenceId = $user->user_type === 'agence' && $user->agence ? $user->agence->id : null;

            foreach ($request->depenses as $depenseData) {
                $note->depenses()->create([
                    'titre' => $depenseData['titre'] ?? 'Dépense',
                    'montant' => $depenseData['montant'],
                    'date_depense' => $depenseData['date_depense'] ?? now(),
                    'categorie' => $depenseData['categorie'] ?? 'autre',
                    'bailleur_id' => $request->bailleur_id,
                    'immeuble_id' => $request->immeuble_id,
                    'bien_id' => $request->bien_id,
                    'agence_id' => $agenceId
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Note mise à jour', 'data' => $note->load('depenses')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function downloadPDF($id)
    {
        $note = NoteDepense::with(['depenses', 'bailleur.user', 'immeuble', 'bien', 'agence.user'])->findOrFail($id);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.note_depense', compact('note'));
        return $pdf->download('note_depense_' . $note->numero . '.pdf');
    }

    public function destroy($id)
    {
        $note = NoteDepense::findOrFail($id);
        $note->depenses()->delete();
        $note->delete();

        return response()->json(['success' => true, 'message' => 'Note supprimée']);
    }
}
