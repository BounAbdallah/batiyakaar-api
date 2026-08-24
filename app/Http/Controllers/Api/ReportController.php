<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bailleur;
use App\Models\NoteDepense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function bailleursWithExpenses(Request $request)
    {
        $authUser = \Illuminate\Support\Facades\Auth::user();

        $query = \App\Models\Bailleur::with(['user' => fn($q) => $q->withTrashed()])
            ->whereHas('noteDepenses')
            ->whereHas('noteDepenses', function ($q) use ($authUser) {
                if ($authUser->agence_id || ($authUser->agence && $authUser->agence->id)) {
                    $agenceId = $authUser->agence ? $authUser->agence->id : $authUser->agence_id;
                    $q->where('agence_id', $agenceId);
                }
            });

        $bailleurs = $query->get()->map(function ($b) {
            return [
                'id'   => $b->id,
                'user' => $b->user ? [
                    'nom'    => $b->user->nom,
                    'prenom' => $b->user->prenom,
                    'email'  => $b->user->email,
                ] : null,
            ];
        })->filter(fn($b) => $b['user'])->values();

        return response()->json(['success' => true, 'data' => $bailleurs]);
    }

    public function periodicExpenses(Request $request)
    {
        $request->validate([
            'bailleur_id'  => 'required|exists:bailleurs,id',
            'start_month'  => 'required|integer|between:1,12',
            'start_year'   => 'required|integer|min:2000',
            'end_month'    => 'required|integer|between:1,12',
            'end_year'     => 'required|integer|min:2000',
        ]);

        $authUser   = Auth::user();
        $bailleur   = Bailleur::with('user')->findOrFail($request->bailleur_id);
        $agence     = $authUser->agence;

        $startPeriod = (int)$request->start_year * 100 + (int)$request->start_month;
        $endPeriod   = (int)$request->end_year   * 100 + (int)$request->end_month;

        $notes = NoteDepense::with(['depenses', 'immeuble', 'bien'])
            ->where('bailleur_id', $request->bailleur_id)
            ->whereRaw('(annee * 100 + mois) >= ?', [$startPeriod])
            ->whereRaw('(annee * 100 + mois) <= ?', [$endPeriod])
            ->orderByRaw('annee ASC, mois ASC')
            ->get();

        $totalGeneral = $notes->sum('total_montant');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.rapport_periodique_depenses', compact(
            'bailleur', 'agence', 'notes', 'totalGeneral', 'request'
        ));

        $filename = 'Rapport_Depenses_'
            . $request->start_month . '_' . $request->start_year
            . '_au_'
            . $request->end_month . '_' . $request->end_year
            . '.pdf';

        return $pdf->download($filename);
    }
}
