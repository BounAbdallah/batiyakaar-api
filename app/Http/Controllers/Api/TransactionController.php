<?php

namespace App\Http\Controllers\Api;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['emetteur', 'beneficiaire', 'commissions']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $transactions = $query->latest('date_transaction')->paginate(15);
        return response()->json(['success' => true, 'data' => $transactions]);
    }

    public function show(string $id)
    {
        $transaction = Transaction::with(['emetteur', 'beneficiaire', 'commissions'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $transaction]);
    }
}
