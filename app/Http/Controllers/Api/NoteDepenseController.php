<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NoteDepense;
use App\Models\Depense;
use App\Models\Bailleur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class NoteDepenseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = NoteDepense::with(['bailleur.user', 'immeuble', 'depenses']);

        if ($user->agence_id) {
            $query->where('agence_id', $user->agence_id);
        }

        if ($request->has('bailleur_id')) {
            $query->where('bailleur_id', $request->bailleur_id);
        }
        if ($request->has('immeuble_id')) {
            $query->where('immeuble_id', $request->immeuble_id);
        }
        if ($request->has('mois')) {
            $query->where('mois', $request->mois);
        }
        if ($request->has('annee')) {
            $query->where('annee', $request->annee);
        }

        $notes = $query->orderBy('annee', 'desc')
            ->orderBy('mois', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $notes
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bailleur_id' => 'required|exists:bailleurs,id',
            'immeuble_id' => 'nullable|exists:immeubles,id',
            'mois' => 'required|integer|between:1,12',
            'annee' => 'required|integer',
            'description' => 'nullable|string',
            'depenses' => 'required|array|min:1',
            'depenses.*.titre' => 'required|string|max:255',
            'depenses.*.montant' => 'required|numeric|min:0',
            'depenses.*.categorie' => 'required|string',
            'depenses.*.description' => 'nullable|string',
            'depenses.*.date_depense' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $bailleur = Bailleur::with('user')->find($request->bailleur_id);
        $agence_id = $bailleur->user->agence_id;

        if (!$agence_id) {
            return response()->json(['success' => false, 'message' => 'Ce bailleur n\'est pas associé à une agence.'], 400);
        }

        try {
            return DB::transaction(function () use ($request, $agence_id) {
                $note = NoteDepense::create([
                    'numero' => 'ND-' . time() . '-' . rand(1000, 9999),
                    'mois' => $request->mois,
                    'annee' => $request->annee,
                    'description' => $request->description,
                    'agence_id' => $agence_id,
                    'bailleur_id' => $request->bailleur_id,
                    'immeuble_id' => $request->immeuble_id,
                    'statut' => 'en_attente',
                    'total_montant' => 0,
                ]);

                $total = 0;
                foreach ($request->depenses as $item) {
                    $depense = new Depense($item);
                    $depense->agence_id = $agence_id;
                    $depense->bailleur_id = $request->bailleur_id;
                    $depense->immeuble_id = $request->immeuble_id;
                    $depense->statut = 'en_attente';
                    $note->depenses()->save($depense);
                    $total += $item['montant'];
                }

                $note->update(['total_montant' => $total]);

                return response()->json([
                    'success' => true,
                    'message' => 'Note de dépense créée avec succès',
                    'data' => $note->load('depenses')
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        $note = NoteDepense::with(['bailleur.user', 'immeuble', 'depenses', 'agence'])->find($id);

        if (!$note) {
            return response()->json(['success' => false, 'message' => 'Note non trouvée'], 404);
        }

        if ($user->agence_id && $note->agence_id !== $user->agence_id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $note
        ]);
    }

    public function generatePDF($id)
    {
        $user = Auth::user();
        $note = NoteDepense::with(['bailleur.user', 'immeuble', 'depenses', 'agence'])->find($id);

        if (!$note) {
            return response()->json(['success' => false, 'message' => 'Note non trouvée'], 404);
        }

        if ($user->agence_id && $note->agence_id !== $user->agence_id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $pdf = Pdf::loadView('pdfs.depense', compact('note'));
        return $pdf->download('Note_Depense_' . $note->numero . '.pdf');
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $note = NoteDepense::find($id);

        if (!$note) {
            return response()->json(['success' => false, 'message' => 'Note non trouvée'], 404);
        }

        if ($user->agence_id && $note->agence_id !== $user->agence_id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $request->validate([
            'mois' => 'required|integer|between:1,12',
            'annee' => 'required|integer',
            'immeuble_id' => 'required|exists:immeubles,id',
            'statut' => 'required|in:paye,en_attente,annule',
            'depenses' => 'required|array|min:1',
            'depenses.*.titre' => 'required|string',
            'depenses.*.montant' => 'required|numeric',
            'depenses.*.categorie' => 'required|string',
            'depenses.*.date_depense' => 'required|date',
        ]);

        try {
            return DB::transaction(function () use ($request, $note) {
                $note->update([
                    'mois' => $request->mois,
                    'annee' => $request->annee,
                    'description' => $request->description,
                    'immeuble_id' => $request->immeuble_id,
                    'bailleur_id' => $request->bailleur_id, // Should match immeuble landlord
                    'statut' => $request->statut,
                ]);

                // Sync expenses: delete and recreate
                $note->depenses()->delete();

                $total = 0;
                foreach ($request->depenses as $item) {
                    $depense = new Depense($item);
                    $depense->agence_id = $note->agence_id;
                    $depense->bailleur_id = $note->bailleur_id;
                    $depense->immeuble_id = $note->immeuble_id;
                    $depense->statut = $note->statut;
                    $note->depenses()->save($depense);
                    $total += $item['montant'];
                }

                $note->update(['total_montant' => $total]);

                return response()->json([
                    'success' => true,
                    'message' => 'Note de dépense mise à jour avec succès',
                    'data' => $note->load('depenses')
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $note = NoteDepense::find($id);

        if (!$note) {
            return response()->json(['success' => false, 'message' => 'Note non trouvée'], 404);
        }

        if ($user->agence_id && $note->agence_id !== $user->agence_id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'Note de dépense supprimée avec succès'
        ]);
    }
    public function generatePeriodicReport(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'bailleur_id' => 'required|exists:bailleurs,id',
            'start_month' => 'required|integer|between:1,12',
            'start_year' => 'required|integer',
            'end_month' => 'required|integer|between:1,12',
            'end_year' => 'required|integer',
        ]);

        $bailleur = Bailleur::with('user')->find($request->bailleur_id);

        if ($user->agence_id && $bailleur->user->agence_id !== $user->agence_id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        // Fetch notes within the period
        $notes = NoteDepense::with(['immeuble', 'depenses', 'agence.user'])
            ->where('bailleur_id', $request->bailleur_id)
            ->where(function ($query) use ($request) {
                $start = $request->start_year * 100 + $request->start_month;
                $end = $request->end_year * 100 + $request->end_month;

                $query->whereRaw('(annee * 100 + mois) >= ?', [$start])
                    ->whereRaw('(annee * 100 + mois) <= ?', [$end]);
            })
            ->orderBy('annee', 'asc')
            ->orderBy('mois', 'asc')
            ->get();

        if ($notes->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Aucune dépense trouvée pour cette période'], 404);
        }

        $agence = $notes->first()->agence;
        $period = [
            'start' => date('F Y', mktime(0, 0, 0, $request->start_month, 1, $request->start_year)),
            'end' => date('F Y', mktime(0, 0, 0, $request->end_month, 1, $request->end_year)),
        ];

        $total_period = $notes->sum('total_montant');

        $pdf = Pdf::loadView('pdfs.periodic_report', compact('notes', 'bailleur', 'agence', 'period', 'total_period'));

        $filename = 'Rapport_Depenses_' . str_replace(' ', '_', $bailleur->user->nom) . '_' . time() . '.pdf';

        return $pdf->download($filename);
    }
}
