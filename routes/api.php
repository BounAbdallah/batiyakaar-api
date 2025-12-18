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
use App\Http\Controllers\Api\TeamController;
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
        Route::get('/biens/{id}/mandat/download', [BienController::class, 'downloadMandat'])->middleware('permission:biens.view');
        Route::get('/biens', [BienController::class, 'index'])->middleware('permission:biens.view');
        Route::post('/biens', [BienController::class, 'store'])->middleware('permission:biens.create');
        Route::get('/biens/{id}', [BienController::class, 'show'])->middleware('permission:biens.view');
        Route::put('/biens/{id}', [BienController::class, 'update'])->middleware('permission:biens.edit');
        Route::delete('/biens/{id}', [BienController::class, 'destroy'])->middleware('permission:biens.delete');

        // Baux
        Route::get('/baux/{id}/contract/download', [BailController::class, 'downloadContract'])->middleware('permission:baux.view');
        Route::get('/baux/{id}/dette/download', [BailController::class, 'downloadDebtForBail'])->middleware('permission:baux.view');
        Route::get('/baux/{id}/demande/download', [BailController::class, 'downloadDemandLetter'])->middleware('permission:baux.view');
        Route::get('/baux', [BailController::class, 'index'])->middleware('permission:baux.view');
        Route::post('/baux', [BailController::class, 'store'])->middleware('permission:baux.create');
        Route::get('/baux/{id}', [BailController::class, 'show'])->middleware('permission:baux.view');
        Route::put('/baux/{id}', [BailController::class, 'update'])->middleware('permission:baux.edit');
        Route::delete('/baux/{id}', [BailController::class, 'destroy'])->middleware('permission:baux.delete');

        // Paiements Loyer
        Route::get('/paiements-loyer/unpaid', [PaiementLoyerController::class, 'getUnpaidRents'])->middleware('permission:paiements.view');
        Route::get('/paiements-loyer/{id}/quittance', [PaiementLoyerController::class, 'downloadQuittance'])->middleware('permission:paiements.view');
        Route::get('/paiements-loyer/{id}/dette/download', [PaiementLoyerController::class, 'downloadDebtAcknowledgment'])->middleware('permission:paiements.view');
        Route::get('/paiements-loyer/{id}/dette/view', [PaiementLoyerController::class, 'viewDebtAcknowledgment'])->middleware('permission:paiements.view');
        Route::get('/paiements-loyer', [PaiementLoyerController::class, 'index'])->middleware('permission:paiements.view');
        Route::post('/paiements-loyer', [PaiementLoyerController::class, 'store'])->middleware('permission:paiements.create');
        Route::get('/paiements-loyer/{id}', [PaiementLoyerController::class, 'show'])->middleware('permission:paiements.view');
        Route::put('/paiements-loyer/{id}', [PaiementLoyerController::class, 'update'])->middleware('permission:paiements.edit');
        Route::delete('/paiements-loyer/{id}', [PaiementLoyerController::class, 'destroy'])->middleware('permission:paiements.delete');

        // Incidents
        Route::post('/incidents/{id}/assign', [IncidentController::class, 'assign'])->middleware('permission:incidents.edit');
        Route::post('/incidents/{id}/resolve', [IncidentController::class, 'resolve'])->middleware('permission:incidents.edit');
        Route::get('/incidents', [IncidentController::class, 'index'])->middleware('permission:incidents.view');
        Route::post('/incidents', [IncidentController::class, 'store'])->middleware('permission:incidents.create');
        Route::get('/incidents/{id}', [IncidentController::class, 'show'])->middleware('permission:incidents.view');
        Route::put('/incidents/{id}', [IncidentController::class, 'update'])->middleware('permission:incidents.edit');
        Route::delete('/incidents/{id}', [IncidentController::class, 'destroy'])->middleware('permission:incidents.delete');

        // Dashboard Stats
        Route::get('/dashboard/stats', [\App\Http\Controllers\Api\DashboardController::class, 'stats']);
        Route::get('/dashboard/sidebar-counts', [\App\Http\Controllers\Api\DashboardController::class, 'sidebarCounts']);

        // Etats des lieux
        Route::get('/etats-des-lieux/{id}/download', [\App\Http\Controllers\Api\EtatDesLieuxController::class, 'generatePdf']);
        Route::apiResource('etats-des-lieux', \App\Http\Controllers\Api\EtatDesLieuxController::class);

        // Bailleurs
        Route::get('/bailleurs', [\App\Http\Controllers\Api\BailleurController::class, 'index'])->middleware('permission:bailleurs.view');
        Route::post('/bailleurs', [\App\Http\Controllers\Api\BailleurController::class, 'store'])->middleware('permission:bailleurs.create');
        Route::get('/bailleurs/{id}', [\App\Http\Controllers\Api\BailleurController::class, 'show'])->middleware('permission:bailleurs.view');
        Route::put('/bailleurs/{id}', [\App\Http\Controllers\Api\BailleurController::class, 'update'])->middleware('permission:bailleurs.edit');
        Route::delete('/bailleurs/{id}', [\App\Http\Controllers\Api\BailleurController::class, 'destroy'])->middleware('permission:bailleurs.delete');

        // Immeubles
        Route::get('/immeubles', [\App\Http\Controllers\Api\ImmeubleController::class, 'index'])->middleware('permission:immeubles.view');
        Route::post('/immeubles', [\App\Http\Controllers\Api\ImmeubleController::class, 'store'])->middleware('permission:immeubles.create');
        Route::get('/immeubles/{id}', [\App\Http\Controllers\Api\ImmeubleController::class, 'show'])->middleware('permission:immeubles.view');
        Route::put('/immeubles/{id}', [\App\Http\Controllers\Api\ImmeubleController::class, 'update'])->middleware('permission:immeubles.edit');
        Route::delete('/immeubles/{id}', [\App\Http\Controllers\Api\ImmeubleController::class, 'destroy'])->middleware('permission:immeubles.delete');

        // Locataires
        Route::get('/locataires', [\App\Http\Controllers\Api\LocataireController::class, 'index'])->middleware('permission:locataires.view');
        Route::post('/locataires', [\App\Http\Controllers\Api\LocataireController::class, 'store'])->middleware('permission:locataires.create');
        Route::get('/locataires/{id}', [\App\Http\Controllers\Api\LocataireController::class, 'show'])->middleware('permission:locataires.view');
        Route::put('/locataires/{id}', [\App\Http\Controllers\Api\LocataireController::class, 'update'])->middleware('permission:locataires.edit');
        Route::delete('/locataires/{id}', [\App\Http\Controllers\Api\LocataireController::class, 'destroy'])->middleware('permission:locataires.delete');

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
        Route::get('/agence/logo', [AgenceController::class, 'uploadLogo']); // Fix: should be POST/PUT usually
        Route::post('/agence/logo', [AgenceController::class, 'uploadLogo']);
        Route::delete('/agence/logo', [AgenceController::class, 'deleteLogo']);

        // Team Management
        Route::get('/agence/equipe', [\App\Http\Controllers\Api\TeamController::class, 'index']);
        Route::post('/agence/equipe', [\App\Http\Controllers\Api\TeamController::class, 'store']);
        Route::put('/agence/equipe/{id}', [\App\Http\Controllers\Api\TeamController::class, 'update']);
        Route::delete('/agence/equipe/{id}', [\App\Http\Controllers\Api\TeamController::class, 'destroy']);

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

            // Commissions Management
            Route::get('/commissions', [AdminController::class, 'commissions']);
            Route::post('/commissions/{id}/pay', [AdminController::class, 'payCommission']);

            // Custom Plan Requests Management
            Route::get('/custom-plan-requests', [\App\Http\Controllers\Api\CustomPlanRequestController::class, 'index']);
            Route::get('/custom-plan-requests/{id}', [\App\Http\Controllers\Api\CustomPlanRequestController::class, 'show']);
            Route::put('/custom-plan-requests/{id}', [\App\Http\Controllers\Api\CustomPlanRequestController::class, 'update']);
            Route::post('/custom-plan-requests/{id}/approve', [\App\Http\Controllers\Api\CustomPlanRequestController::class, 'approve']);

            // Plans management
            Route::get('/plans', [AdminController::class, 'plans']);
            Route::get('/plans/{id}', [AdminController::class, 'showPlan']);
            Route::get('/plans/{id}/subscribers', [\App\Http\Controllers\Api\PlanController::class, 'getSubscribers']);
            Route::post('/plans', [\App\Http\Controllers\Api\PlanController::class, 'store']);
            Route::put('/plans/{id}', [\App\Http\Controllers\Api\PlanController::class, 'update']);
            Route::delete('/plans/{id}', [\App\Http\Controllers\Api\PlanController::class, 'destroy']);

            Route::put('/users/{id}/status', [AdminController::class, 'toggleUserStatus']);
            Route::put('/agencies/{id}/subscription', [AdminController::class, 'updateAgencySubscription']);

            // Contact Messages
            Route::get('/contact-messages', [\App\Http\Controllers\Api\ContactController::class, 'index']);
            Route::get('/contact-messages/{id}', [\App\Http\Controllers\Api\ContactController::class, 'show']);
            Route::put('/contact-messages/{id}', [\App\Http\Controllers\Api\ContactController::class, 'update']);
        });

    });

    // Public Plans Route (Outside protected group if you want them visible to anyone,
    // but the task says "Portal", usually pricing is public)
    Route::get('/admin/commissions', [App\Http\Controllers\Api\AdminController::class, 'commissions']);
    Route::post('/admin/plans', [App\Http\Controllers\Api\AdminController::class, 'storePlan']);    // Plans (Public)
    Route::get('/plans', [\App\Http\Controllers\Api\PlanController::class, 'index']);
    Route::post('/plans/validate-token', [\App\Http\Controllers\Api\PlanController::class, 'validateToken']);

    // Custom Plan Requests (Public)
    Route::post('/custom-plan-requests', [\App\Http\Controllers\Api\CustomPlanRequestController::class, 'store']);

    // Contact (Public)
    Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'store']);
});
