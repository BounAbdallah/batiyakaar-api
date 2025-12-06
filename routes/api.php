<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjetConstructionController;
use App\Http\Controllers\Api\BienController;
use App\Http\Controllers\Api\BailController;
use App\Http\Controllers\Api\PaiementLoyerController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\ProduitController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\LivraisonController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\PortefeuilleVirtuelController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AgenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Bâti Yakaar
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::get('/auth/user', [AuthController::class, 'user']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // User Profile
        Route::get('/user/profile', [UserController::class, 'profile']);
        Route::put('/user/profile', [UserController::class, 'updateProfile']);
        Route::get('/user/portefeuille', [UserController::class, 'portefeuille']);
        Route::get('/user/notifications', [UserController::class, 'notifications']);
        Route::put('/user/notifications/{id}/read', [UserController::class, 'markNotificationRead']);

        // Projets Construction
        Route::apiResource('projets', ProjetConstructionController::class);

        // Biens
        Route::apiResource('biens', BienController::class);

        // Baux
        Route::apiResource('baux', BailController::class);

        // Paiements Loyer
        Route::apiResource('paiements-loyer', PaiementLoyerController::class);

        // Incidents
        Route::apiResource('incidents', IncidentController::class);
        Route::post('/incidents/{id}/assign', [IncidentController::class, 'assign']);
        Route::post('/incidents/{id}/resolve', [IncidentController::class, 'resolve']);

        // Produits
        Route::apiResource('produits', ProduitController::class);
        Route::put('/produits/{id}/stock', [ProduitController::class, 'updateStock']);

        // Commandes
        Route::apiResource('commandes', CommandeController::class);

        // Livraisons
        Route::apiResource('livraisons', LivraisonController::class);

        // Transactions
        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::get('/transactions/{id}', [TransactionController::class, 'show']);

        // Portefeuille Virtuel
        Route::get('/portefeuille', [PortefeuilleVirtuelController::class, 'show']);
        Route::get('/portefeuille/history', [PortefeuilleVirtuelController::class, 'history']);

        // Agence Dashboard
        Route::get('/agence/dashboard', [AgenceController::class, 'dashboard']);
        Route::get('/agence', [AgenceController::class, 'show']);
    });
});
