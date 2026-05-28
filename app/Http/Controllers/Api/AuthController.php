<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Bailleur;
use App\Models\Agence;
use App\Models\Entrepreneur;
use App\Models\Fournisseur;
use App\Models\Locataire;
use App\Models\PortefeuilleVirtuel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'telephone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'user_type' => 'required|in:bailleur,agence,entrepreneur,fournisseur,locataire',

            // Type-specific fields
            'pays' => 'required_if:user_type,bailleur',
            'adresse_diaspora' => 'required_if:user_type,bailleur',
            'raison_sociale' => 'required_if:user_type,agence',
            'ninea' => 'required_if:user_type,agence',
            'specialite' => 'required_if:user_type,entrepreneur',
            'nom_entreprise' => 'required_if:user_type,fournisseur',
            'profession' => 'required_if:user_type,locataire',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        // Determine active status: Agency users are inactive by default
        $isActive = $request->user_type !== 'agence';

        // Create user
        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
            'actif' => $isActive,
        ]);

        $agenceData = null;

        // Create type-specific record
        switch ($request->user_type) {
            case 'bailleur':
                Bailleur::create([
                    'user_id' => $user->id,
                    'pays' => $request->pays,
                    'adresse_diaspora' => $request->adresse_diaspora,
                ]);
                break;

            case 'agence':
                $agenceData = Agence::create([
                    'user_id' => $user->id,
                    'raison_sociale' => $request->raison_sociale,
                    'ninea' => $request->ninea,
                    'adresse' => $request->adresse,
                ]);

                // Notify Admins
                $admins = User::where('user_type', 'admin')->get();
                \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewAgencyRegistered($user, $agenceData));

                // Handle Subscription if plan_id is provided
                if ($request->has('plan_id')) {
                    $plan = \App\Models\Plan::find($request->plan_id);
                    if ($plan) {
                        \App\Models\Abonnement::create([
                            'agence_id' => $agenceData->id,
                            'plan_id' => $plan->id,
                            'date_debut' => now(),
                            'date_fin' => now()->addMonths(12), // Default 1 year
                            'statut' => 'en_attente',
                            'auto_renouvellement' => true,
                        ]);

                        // Notify specifically about subscription request
                        \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewSubscriptionRequest($agenceData, $plan));

                        // Send email to agency about subscription
                        try {
                            \Illuminate\Support\Facades\Mail::send('emails.subscription-created', [
                                'agence' => $agenceData,
                                'plan' => $plan,
                                'user' => $user
                            ], function ($message) use ($user, $agenceData) {
                                $message->to($user->email, $agenceData->raison_sociale)
                                    ->subject('Merci pour votre abonnement à Noor Immo');
                            });
                        } catch (\Exception $e) {
                            \Log::error('Failed to send subscription email: ' . $e->getMessage());
                        }
                    }
                }
                break;

            case 'entrepreneur':
                Entrepreneur::create([
                    'user_id' => $user->id,
                    'specialite' => $request->specialite,
                    'registre_commerce' => $request->registre_commerce,
                    'tarif_journalier' => $request->tarif_journalier ?? 0,
                ]);
                break;

            case 'fournisseur':
                Fournisseur::create([
                    'user_id' => $user->id,
                    'nom_entreprise' => $request->nom_entreprise,
                    'categorie_materiaux' => $request->categorie_materiaux,
                    'adresse_entrepot' => $request->adresse_entrepot,
                ]);
                break;

            case 'locataire':
                Locataire::create([
                    'user_id' => $user->id,
                    'profession' => $request->profession,
                    'employeur' => $request->employeur,
                    'revenu_mensuel' => $request->revenu_mensuel ?? 0,
                ]);
                break;
        }

        // Create virtual wallet
        PortefeuilleVirtuel::create([
            'user_id' => $user->id,
            'solde' => 0,
            'devise' => 'XOF',
        ]);

        // If inactive (Agency), return message without token
        if (!$isActive) {
            return response()->json([
                'success' => true,
                'message' => 'Compte créé avec succès. Il sera activé après validation par un administrateur.',
                'data' => [
                    'user' => $user,
                    'requires_activation' => true,
                ],
            ], 201);
        }

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        $login = $request->login;
        $user = User::where(function ($query) use ($login) {
            $query->where('email', $login)
                  ->orWhere('telephone', $login);
        })->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        if (!$user->actif) {
            throw ValidationException::withMessages([
                'login' => ['Votre compte est désactivé.'],
            ]);
        }

        // Check if user is restricted by Agency Plan
        if (in_array($user->user_type, ['locataire', 'bailleur']) && $user->employeurAgence) {
            $plan = $user->employeurAgence->abonnement?->plan;
            if ($plan) {
                // Ensure functionalities is an array (it should be cast in Plan model, but verifying)
                $features = $plan->fonctionnalites;
                if (is_string($features)) {
                    $features = json_decode($features, true);
                }

                $canAccess = false;
                if ($user->user_type === 'locataire' && ($features['accies_locataires'] ?? false)) {
                    $canAccess = true;
                }
                if ($user->user_type === 'bailleur' && ($features['accies_bailleurs'] ?? false)) {
                    $canAccess = true;
                }

                if (!$canAccess) {
                    throw ValidationException::withMessages([
                        'login' => ['L\'abonnement de votre agence ne permet pas votre accès.'],
                    ]);
                }
            }
        }

        // Revoke all previous tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        try {
            \Illuminate\Support\Facades\Log::channel('discord')->info("Nouvelle Connexion : {$user->prenom} {$user->nom}", [
                'email' => $user->email,
                'role' => $user->user_type,
                'ip' => $request->ip(),
                'time' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            // Fail silently
        }

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load([
                'bailleur',
                'agence',
                'entrepreneur',
                'fournisseur',
                'locataire',
                'portefeuilleVirtuel',
            ]),
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Always return success to avoid email enumeration
        if (!$user) {
            return response()->json([
                'success' => true,
                'message' => 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation.',
            ]);
        }

        // Delete any existing token for this email
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Generate a secure token
        $token = Str::random(64);

        DB::table('password_reset_tokens')->insert([
            'email'      => $request->email,
            'token'      => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
        $resetUrl = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        try {
            Mail::send('emails.password-reset', ['user' => $user, 'resetUrl' => $resetUrl], function ($message) use ($user) {
                $message->to($user->email, $user->prenom . ' ' . $user->nom)
                    ->subject('Réinitialisation de votre mot de passe - Noor Immo');
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send password reset email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation.',
        ]);
    }

    /**
     * Reset password using token
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'token'                 => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Ce lien de réinitialisation est invalide ou a expiré.',
            ], 422);
        }

        // Check token validity (60 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Ce lien de réinitialisation a expiré. Veuillez en demander un nouveau.',
            ], 422);
        }

        if (!Hash::check($request->token, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce lien de réinitialisation est invalide.',
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun compte trouvé avec cet email.',
            ], 404);
        }

        $user->update(['password' => Hash::make($request->password)]);
        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.',
        ]);
    }
}
