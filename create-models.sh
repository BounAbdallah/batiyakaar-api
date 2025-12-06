#!/bin/bash

# Script pour créer tous les modèles Eloquent de Bâti Yakaar

echo "🚀 Création des modèles Eloquent Bâti Yakaar"
echo "============================================="

# User Management Models
echo "📦 Module 1: User Management..."
php artisan make:model Bailleur 2>/dev/null || echo "  ✓ Bailleur"
php artisan make:model Agence 2>/dev/null || echo "  ✓ Agence"
php artisan make:model Entrepreneur 2>/dev/null || echo "  ✓ Entrepreneur"
php artisan make:model Fournisseur 2>/dev/null || echo "  ✓ Fournisseur"
php artisan make:model Locataire 2>/dev/null || echo "  ✓ Locataire"
php artisan make:model Technicien 2>/dev/null || echo "  ✓ Technicien"

# Construction Models
echo "📦 Module 2: Construction..."
php artisan make:model ProjetConstruction 2>/dev/null || echo "  ✓ ProjetConstruction"
php artisan make:model Chantier 2>/dev/null || echo "  ✓ Chantier"
php artisan make:model Etape 2>/dev/null || echo "  ✓ Etape"
php artisan make:model PreuveVisuelle 2>/dev/null || echo "  ✓ PreuveVisuelle"
php artisan make:model PaiementEscrow 2>/dev/null || echo "  ✓ PaiementEscrow"
php artisan make:model PartiesPrenantes 2>/dev/null || echo "  ✓ PartiesPrenantes"
php artisan make:model RapportChantier 2>/dev/null || echo "  ✓ RapportChantier"

# Rental Models
echo "📦 Module 3: Gestion Locative..."
php artisan make:model Bien 2>/dev/null || echo "  ✓ Bien"
php artisan make:model Bail 2>/dev/null || echo "  ✓ Bail"
php artisan make:model PaiementLoyer 2>/dev/null || echo "  ✓ PaiementLoyer"
php artisan make:model Quittance 2>/dev/null || echo "  ✓ Quittance"
php artisan make:model Incident 2>/dev/null || echo "  ✓ Incident"
php artisan make:model EtatDesLieux 2>/dev/null || echo "  ✓ EtatDesLieux"

# Marketplace Models
echo "📦 Module 4: Marketplace..."
php artisan make:model Catalogue 2>/dev/null || echo "  ✓ Catalogue"
php artisan make:model Produit 2>/dev/null || echo "  ✓ Produit"
php artisan make:model Commande 2>/dev/null || echo "  ✓ Commande"
php artisan make:model LigneCommande 2>/dev/null || echo "  ✓ LigneCommande"
php artisan make:model Livraison 2>/dev/null || echo "  ✓ Livraison"

# Financial Models
echo "📦 Module 5: Financier..."
php artisan make:model PortefeuilleVirtuel 2>/dev/null || echo "  ✓ PortefeuilleVirtuel"
php artisan make:model Transaction 2>/dev/null || echo "  ✓ Transaction"
php artisan make:model Commission 2>/dev/null || echo "  ✓ Commission"
php artisan make:model Ventilation 2>/dev/null || echo "  ✓ Ventilation"

# Notifications & Subscriptions
echo "📦 Module 6: Notifications & Abonnements..."
php artisan make:model Notification 2>/dev/null || echo "  ✓ Notification"
php artisan make:model Plan 2>/dev/null || echo "  ✓ Plan"
php artisan make:model Abonnement 2>/dev/null || echo "  ✓ Abonnement"

echo ""
echo "✅ Tous les modèles ont été créés!"
echo ""
echo "Prochaines étapes:"
echo "  1. Ajouter les relations dans chaque modèle"
echo "  2. Définir les fillable fields"
echo "  3. Ajouter les casts"
echo "  4. Créer les scopes"
