<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class PortefeuilleVirtuelController extends Controller
{
    public function show(Request $request)
    {
        $portefeuille = \App\Models\PortefeuilleVirtuel::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['solde' => 0, 'devise' => 'CFA']
        );
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

    public function stats(Request $request)
    {
        $user = $request->user();
        $wallet = \App\Models\PortefeuilleVirtuel::firstOrCreate(
            ['user_id' => $user->id],
            ['solde' => 0, 'devise' => 'CFA']
        );

        $currentBalance = $wallet->solde;

        // Get transactions for last 6 months
        $transactions = \App\Models\Transaction::where(function ($q) use ($user) {
            $q->where('emetteur_id', $user->id)
                ->orWhere('beneficiaire_id', $user->id);
        })
            ->where('date_transaction', '>=', now()->subMonths(6))
            ->orderBy('date_transaction', 'desc')
            ->get();

        // Group by month YYYY-MM
        $monthlyChanges = [];
        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $months[] = $key;
            $monthlyChanges[$key] = 0;
        }

        // Calculate net change per transaction to reverse engineer balance
        foreach ($transactions as $tx) {
            $month = substr($tx->date_transaction, 0, 7); // YYYY-MM
            if (isset($monthlyChanges[$month])) {
                // If I was sender (Debit), my balance DECREASED. So to go back, I ADD it.
                // If I was receiver (Credit), my balance INCREASED. So to go back, I SUBTRACT it.
                // WE WANT BALANCE AT END OF MONTH.

                // Let's calculate Net Change in that month first.
                // Debit = -amount, Credit = +amount
                $change = 0;
                if ($tx->emetteur_id == $user->id) {
                    $change -= $tx->montant;
                }
                if ($tx->beneficiaire_id == $user->id) {
                    $change += $tx->montant;
                }
                $monthlyChanges[$month] += $change;
            }
        }

        // Reconstruct balances
        // Balance at end of current month = Current Balance (approx, ignoring future scheduled tx)
        // Balance at end of Month M-1 = Balance End M - NetChange M

        $chartData = [];
        $runningBalance = $currentBalance;

        // Sort months desc (current first)
        // Already built that way naturally if we iterate
        // But let's be explicit. $months = [Dec, Nov, Oct...]

        foreach ($months as $month) {
            $chartData[] = [
                'name' => \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M'), // Jan, Feb...
                'solde' => $runningBalance
            ];

            // Prepare for previous month
            // To get balance at END of previous month, we subtract the NET change of THIS month
            // Example: End Nov Balance = End Dec Balance (Current) - (Dec Income - Dec Expense)
            $runningBalance -= $monthlyChanges[$month];
        }

        return response()->json([
            'success' => true,
            'data' => array_reverse($chartData) // Return Jan -> Dec
        ]);
    }
}
