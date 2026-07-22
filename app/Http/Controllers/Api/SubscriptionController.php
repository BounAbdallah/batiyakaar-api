<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Abonnement;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Subscribe an agency to a plan.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'duree_mois' => 'required|integer|min:1|max:24' // Example: 1 month, 12 months
        ]);

        $user = $request->user();

        if ($user->user_type !== 'agence') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les agences peuvent souscrire à un abonnement.'
            ], 403);
        }

        $agence = $user->agence;
        $plan = Plan::find($request->plan_id);

        // Check active subscription
        $currentSubscription = $agence->abonnement;
        if ($currentSubscription && in_array($currentSubscription->statut, ['actif', 'en_attente'])) {
            $message = $currentSubscription->statut === 'actif'
                ? 'Vous avez déjà un abonnement actif.'
                : 'Vous avez déjà une demande en cours de validation.';

            return response()->json([
                'success' => false,
                'message' => $message
            ], 400);
        }

        // Create new subscription request
        $abonnement = new Abonnement();
        $abonnement->agence_id = $agence->id;
        $abonnement->plan_id = $plan->id;
        $abonnement->date_debut = Carbon::now()->format('Y-m-d');
        $abonnement->date_fin = Carbon::now()->addMonths($request->duree_mois)->format('Y-m-d');
        $abonnement->statut = 'en_attente'; 
        $abonnement->auto_renouvellement = true;
        $abonnement->save();

        // Notify Admins
        $admins = \App\Models\User::where('user_type', 'admin')->get();
        \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewSubscriptionRequest($agence, $plan));

        return response()->json([
            'success' => true,
            'message' => 'Votre demande d\'abonnement a été envoyée. Un administrateur vous contactera pour l\'activation.',
            'data' => $abonnement->load('plan')
        ], 201);
    }
}
