<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomPlanRequest;
use App\Models\Plan;
use App\Services\DiscordNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomPlanRequestController extends Controller
{
    /**
     * Store a new custom plan request (Public endpoint)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telephone' => 'nullable|string|max:20',
            'entreprise' => 'nullable|string|max:255',
            'nombre_biens' => 'required|integer|min:1',
            'nombre_utilisateurs' => 'required|integer|min:1',
            'fonctionnalites_souhaitees' => 'required|array|min:1',
            'besoins_specifiques' => 'nullable|string',
            'budget_mensuel' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $customRequest = CustomPlanRequest::create($request->all());

        // Send Discord notification
        try {
            $discordService = new DiscordNotificationService();
            $discordService->notifyNewCustomPlanRequest($customRequest);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to send Discord notification: ' . $e->getMessage());
        }

        // TODO: Send email notification to admin
        // Notification::route('mail', config('mail.admin_email'))
        //     ->notify(new NewCustomPlanRequest($customRequest));

        return response()->json([
            'success' => true,
            'message' => 'Votre demande a été envoyée avec succès. Nous vous contacterons bientôt.',
            'data' => $customRequest
        ], 201);
    }

    /**
     * Get all custom plan requests (Admin only)
     */
    public function index(Request $request)
    {
        $query = CustomPlanRequest::with('plan');

        // Filter by status
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('entreprise', 'like', "%{$search}%");
            });
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Get a single custom plan request (Admin only)
     */
    public function show($id)
    {
        $customRequest = CustomPlanRequest::with('plan')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $customRequest
        ]);
    }

    /**
     * Update custom plan request (Admin only)
     */
    public function update(Request $request, $id)
    {
        $customRequest = CustomPlanRequest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'statut' => 'sometimes|in:en_attente,en_cours,approuve,refuse',
            'notes_admin' => 'nullable|string',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $customRequest->update($request->only(['statut', 'notes_admin', 'plan_id']));

        return response()->json([
            'success' => true,
            'message' => 'Demande mise à jour avec succès',
            'data' => $customRequest->load('plan')
        ]);
    }

    /**
     * Approve request and create custom plan (Admin only)
     */
    public function approve(Request $request, $id)
    {
        $customRequest = CustomPlanRequest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nom_plan' => 'required|string|max:255',
            'prix_mensuel' => 'required|numeric|min:0',
            'prix_annuel' => 'required|numeric|min:0',
            'nombre_biens_max' => 'required|integer|min:1',
            'nombre_utilisateurs_max' => 'required|integer|min:1',
            'fonctionnalites' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create custom plan with unique access token
        $plan = Plan::create([
            'nom' => $request->nom_plan,
            'prix_mensuel' => $request->prix_mensuel,
            'prix_annuel' => $request->prix_annuel,
            'limite_biens' => $request->nombre_biens_max,
            'limite_utilisateurs' => $request->nombre_utilisateurs_max,
            'fonctionnalites' => $request->fonctionnalites,
            'est_personnalise' => true,
            'est_public' => false, // Plan is private
            'access_token' => bin2hex(random_bytes(32)), // Generate unique token
            'token_expires_at' => now()->addDays(7), // Token expires in 7 days
        ]);

        // Update request
        $customRequest->update([
            'statut' => 'approuve',
            'plan_id' => $plan->id,
        ]);

        // Send Discord notification for approval
        try {
            $discordService = new DiscordNotificationService();
            $discordService->notifyCustomPlanApproved($customRequest, $plan);
        } catch (\Exception $e) {
            \Log::error('Failed to send Discord approval notification: ' . $e->getMessage());
        }

        // Send email to client with unique access link
        try {
            \Mail::to($customRequest->email)->send(new \App\Mail\CustomPlanApproved($customRequest, $plan));
        } catch (\Exception $e) {
            \Log::error('Failed to send custom plan approval email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Plan personnalisé créé et demande approuvée',
            'data' => [
                'request' => $customRequest->load('plan'),
                'plan' => $plan
            ]
        ]);
    }
}
