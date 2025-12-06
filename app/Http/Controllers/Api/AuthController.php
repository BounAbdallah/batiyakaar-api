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
use Illuminate\Validation\ValidationException;

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
            'telephone' => 'required|string|max:20',
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
        ]);

        // Create user
        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
            'actif' => true,
        ]);

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
                Agence::create([
                    'user_id' => $user->id,
                    'raison_sociale' => $request->raison_sociale,
                    'ninea' => $request->ninea,
                    'adresse' => $request->adresse,
                ]);
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
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        if (!$user->actif) {
            throw ValidationException::withMessages([
                'email' => ['Votre compte est désactivé.'],
            ]);
        }

        // Revoke all previous tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

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
}
