<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bailleur;
use App\Models\NoteDepense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
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

        $startDate  = Carbon::createFromDate($request->start_year, $request->start_month, 1)->startOfMonth();
        $endDate    = Carbon::createFromDate($request->end_year,   $request->end_month,   1)->endOfMonth();

        $notes = NoteDepense::with(['depenses', 'immeuble', 'bien'])
            ->where('bailleur_id', $request->bailleur_id)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereRaw("STR_TO_DATE(CONCAT(annee, '-', LPAD(mois, 2, '0'), '-01'), '%Y-%m-%d') >= ?", [$startDate])
                  ->whereRaw("STR_TO_DATE(CONCAT(annee, '-', LPAD(mois, 2, '0'), '-01'), '%Y-%m-%d') <= ?", [$endDate]);
            })
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
