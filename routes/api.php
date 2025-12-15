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
use App\Http\Controllers\Api\AdminController;
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

    // Public PDF view routes (with manual token auth)
    Route::get('/biens/{id}/mandat/view', [BienController::class, 'viewMandat']);
    Route::get('/baux/{id}/contract/view', [BailController::class, 'viewContract']);
    Route::get('/baux/{id}/dette/view', [BailController::class, 'viewDebtForBail']);
    Route::get('/baux/{id}/demande/view', [BailController::class, 'viewDemandLetter']);
    Route::get('/paiements-loyer/{id}/dette/view', [PaiementLoyerController::class, 'viewDebtAcknowledgment']);

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
        Route::post('/projets/{id}/assign', [\App\Http\Controllers\Api\ProjectAssignmentController::class, 'store']);
        Route::post('/projets/{id}/invite', [\App\Http\Controllers\Api\ProjectInvitationController::class, 'store']);
        Route::post('/invitations/{token}/accept', [\App\Http\Controllers\Api\ProjectInvitationController::class, 'accept']);
        Route::get('/projets/{id}/partenaires', [\App\Http\Controllers\Api\ProjectAssignmentController::class, 'index']);

        // Biens
        Route::get('/biens/{id}/mandat/download', [BienController::class, 'downloadMandat']);
        Route::apiResource('biens', BienController::class);

        // Baux
        Route::get('/baux/{id}/contract/download', [BailController::class, 'downloadContract']);
        Route::get('/baux/{id}/dette/download', [BailController::class, 'downloadDebtForBail']);
        Route::get('/baux/{id}/demande/download', [BailController::class, 'downloadDemandLetter']);
        Route::apiResource('baux', BailController::class);

        // Paiements Loyer
        Route::get('/paiements-loyer/unpaid', [PaiementLoyerController::class, 'getUnpaidRents']);
        Route::get('/paiements-loyer/{id}/quittance', [PaiementLoyerController::class, 'downloadQuittance']);
        Route::get('/paiements-loyer/{id}/dette/download', [PaiementLoyerController::class, 'downloadDebtAcknowledgment']);
        Route::get('/paiements-loyer/{id}/dette/view', [PaiementLoyerController::class, 'viewDebtAcknowledgment']);
        Route::apiResource('paiements-loyer', PaiementLoyerController::class);

        // Incidents
        Route::apiResource('incidents', IncidentController::class);
        Route::post('/incidents/{id}/assign', [IncidentController::class, 'assign']);
        Route::post('/incidents/{id}/resolve', [IncidentController::class, 'resolve']);

        // Dashboard Stats
        Route::get('/dashboard/stats', [\App\Http\Controllers\Api\DashboardController::class, 'stats']);

        // Etats des lieux
        Route::apiResource('etats-des-lieux', \App\Http\Controllers\Api\EtatDesLieuxController::class);

        // Bailleurs
        Route::apiResource('bailleurs', \App\Http\Controllers\Api\BailleurController::class);

        // Immeubles
        Route::apiResource('immeubles', \App\Http\Controllers\Api\ImmeubleController::class);

        // Locataires
        Route::apiResource('locataires', \App\Http\Controllers\Api\LocataireController::class);

        // Agency Settings
        Route::get('agency/settings', [\App\Http\Controllers\Api\AgenceSettingsController::class, 'show']);
        Route::put('agency/settings', [\App\Http\Controllers\Api\AgenceSettingsController::class, 'update']);

        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
        Route::get('/notifications/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'show']);
        Route::put('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
        Route::put('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
        Route::delete('/notifications/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);

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
        Route::get('/portefeuille/stats', [PortefeuilleVirtuelController::class, 'stats']);

        // Agence
        Route::get('/agence/dashboard', [AgenceController::class, 'dashboard']);
        Route::get('/agence/profile', [AgenceController::class, 'show']);
        Route::put('/agence/settings', [AgenceController::class, 'updateSettings']);
        Route::post('/agence/logo', [AgenceController::class, 'uploadLogo']);
        Route::delete('/agence/logo', [AgenceController::class, 'deleteLogo']);

        // --- Construction Module Routes (MVP) ---

        // Etapes
        Route::apiResource('etapes', \App\Http\Controllers\Api\EtapeController::class);

        // Preuves Visuelles
        Route::apiResource('preuves', \App\Http\Controllers\Api\PreuveVisuelleController::class)->only(['index', 'store']);


        // Paiements Escrow
        Route::apiResource('paiements-escrow', \App\Http\Controllers\Api\PaiementEscrowController::class)->only(['index', 'store']);
        Route::post('/paiements-escrow/{id}/release', [\App\Http\Controllers\Api\PaiementEscrowController::class, 'release']);
        Route::post('/paiements-escrow/{id}/cancel', [\App\Http\Controllers\Api\PaiementEscrowController::class, 'cancel']);

        // Abonnements & Plans (Protected)
        Route::post('/subscriptions/subscribe', [\App\Http\Controllers\Api\SubscriptionController::class, 'subscribe']);

        // Super Admin Routes
        Route::prefix('admin')->group(function () {
            Route::get('/stats', [AdminController::class, 'stats']);
            Route::get('/agencies', [AdminController::class, 'agencies']);
            Route::get('/agencies/{id}', [AdminController::class, 'showAgency']);

            // Plans management
            Route::get('/plans', [AdminController::class, 'plans']);
            Route::post('/plans', [AdminController::class, 'storePlan']);
            Route::put('/plans/{id}', [AdminController::class, 'updatePlan']);

            Route::put('/users/{id}/status', [AdminController::class, 'toggleUserStatus']);
            Route::put('/agencies/{id}/subscription', [AdminController::class, 'updateAgencySubscription']);
        });

    });

    // Public Plans Route (Outside protected group if you want them visible to anyone, 
    // but the task says "Portal", usually pricing is public)
    Route::get('/admin/commissions', [App\Http\Controllers\Api\AdminController::class, 'commissions']);
    Route::post('/admin/plans', [App\Http\Controllers\Api\AdminController::class, 'storePlan']);
    Route::get('/plans', [\App\Http\Controllers\Api\PlanController::class, 'index']);
});
