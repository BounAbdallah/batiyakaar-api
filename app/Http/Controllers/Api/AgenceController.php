<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgenceController extends Controller
{
    public function dashboard(Request $request)
    {
        $agence = $request->user()->agence;

        $stats = [
            'biens_total' => $agence->biens()->count(),
            'biens_loues' => $agence->biens()->where('statut', 'loue')->count(),
            'biens_disponibles' => $agence->biens()->where('statut', 'disponible')->count(),
            'baux_actifs' => $agence->baux()->where('statut', 'actif')->count(),
            'incidents_ouverts' => DB::table('incidents')
                ->join('baux', 'incidents.bail_id', '=', 'baux.id')
                ->where('baux.agence_id', $agence->id)
                ->where('incidents.statut', 'ouvert')
                ->count(),
            'revenus_mois' => DB::table('ventilations')
                ->join('paiements_loyer', 'ventilations.paiement_loyer_id', '=', 'paiements_loyer.id')
                ->join('baux', 'paiements_loyer.bail_id', '=', 'baux.id')
                ->where('baux.agence_id', $agence->id)
                ->whereMonth('ventilations.date_ventilation', now()->month)
                ->sum('ventilations.montant_agence'),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function show(Request $request)
    {
        $agence = $request->user()->agence->load(['user', 'abonnement.plan', 'techniciens', 'biens', 'baux']);
        return response()->json(['success' => true, 'data' => $agence]);
    }
}
