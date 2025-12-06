<?php

/**
 * Script pour générer automatiquement le contenu des modèles Eloquent
 * Usage: php generate-models-content.php
 */

$modelsConfig = [
    // User Management
    'Bailleur' => [
        'fillable' => ['user_id', 'pays', 'adresse_diaspora'],
        'relationships' => [
            'user' => 'belongsTo:User',
            'projetsConstruction' => 'hasMany:ProjetConstruction',
            'biens' => 'hasMany:Bien',
            'commandes' => 'hasMany:Commande',
        ],
    ],

    'Agence' => [
        'fillable' => ['user_id', 'raison_sociale', 'ninea', 'adresse'],
        'relationships' => [
            'user' => 'belongsTo:User',
            'techniciens' => 'hasMany:Technicien',
            'baux' => 'hasMany:Bail',
            'biens' => 'hasMany:Bien',
            'abonnement' => 'hasOne:Abonnement',
        ],
    ],

    'Entrepreneur' => [
        'fillable' => ['user_id', 'specialite', 'registre_commerce', 'tarif_journalier'],
        'casts' => ['tarif_journalier' => 'decimal:2'],
        'relationships' => [
            'user' => 'belongsTo:User',
            'preuvesVisuelles' => 'hasMany:PreuveVisuelle',
            'paiementsEscrow' => 'hasMany:PaiementEscrow',
        ],
    ],

    'Fournisseur' => [
        'fillable' => ['user_id', 'nom_entreprise', 'categorie_materiaux', 'adresse_entrepot'],
        'relationships' => [
            'user' => 'belongsTo:User',
            'catalogue' => 'hasOne:Catalogue',
            'produits' => 'hasMany:Produit',
            'livraisons' => 'hasMany:Livraison',
        ],
    ],

    'Locataire' => [
        'fillable' => ['user_id', 'profession', 'employeur', 'revenu_mensuel'],
        'casts' => ['revenu_mensuel' => 'decimal:2'],
        'relationships' => [
            'user' => 'belongsTo:User',
            'baux' => 'hasMany:Bail',
            'incidents' => 'hasMany:Incident',
        ],
    ],

    'Technicien' => [
        'fillable' => ['agence_id', 'nom', 'telephone', 'specialite'],
        'relationships' => [
            'agence' => 'belongsTo:Agence',
            'incidents' => 'hasMany:Incident',
        ],
    ],
];

echo "Modèles configurés : " . count($modelsConfig) . "\n";
echo "Utilisez ce fichier comme référence pour compléter les modèles.\n";
