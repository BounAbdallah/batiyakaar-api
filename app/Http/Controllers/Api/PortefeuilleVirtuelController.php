<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class PortefeuilleVirtuelController extends Controller
{
    public function show(Request $request)
    {
        $portefeuille = $request->user()->portefeuilleVirtuel;
        return response()->json(['success' => true, 'data' => $portefeuille]);
    }

    public function history(Request $request)
    {
        $transactions = \App\Models\Transaction::where('emetteur_id', $request->user()->id)
            ->orWhere('beneficiaire_id', $request->user()->id)
            ->with(['emetteur', 'beneficiaire'])
            ->latest('date_transaction')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $transactions]);
    }
}
