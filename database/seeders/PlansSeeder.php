<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'nom' => 'Starter',
                'description' => 'Plan de démarrage pour petites agences',
                'prix_mensuel' => 25000,
                'limite_utilisateurs' => 1,
                'limite_biens' => 20,
                'fonctionnalites' => [
                    'Gestion des biens',
                    'Gestion des baux',
                    'Paiements de loyers',
                    'accies_locataires' => false,
                    'accies_bailleurs' => false,
                ],
                'actif' => true,
            ],
            [
                'nom' => 'Pro',
                'description' => 'Plan professionnel pour agences en croissance',
                'prix_mensuel' => 50000,
                'limite_utilisateurs' => 5,
                'limite_biens' => 100,
                'fonctionnalites' => [
                    'Toutes fonctionnalités Starter',
                    'Gestion multi-utilisateurs (jusqu\'à 5)',
                    'Notifications WhatsApp',
                    'accies_locataires' => false,
                    'accies_bailleurs' => true,
                ],
                'actif' => true,
            ],
            [
                'nom' => 'Enterprise',
                'description' => 'Plan entreprise pour grandes agences',
                'prix_mensuel' => 100000,
                'limite_utilisateurs' => 15,
                'limite_biens' => 500,
                'fonctionnalites' => [
                    'Toutes fonctionnalités Pro',
                    'Utilisateurs illimités',
                    'Biens illimités',
                    'API access',
                    'accies_locataires' => true,
                    'accies_bailleurs' => true,
                ],
                'actif' => true,
            ],
            [
                'nom' => 'Sur Mesure',
                'description' => 'Plan personnalisé pour besoins spécifiques',
                'prix_mensuel' => 0, // A définir
                'limite_utilisateurs' => 100,
                'limite_biens' => 1000,
                'fonctionnalites' => [
                    'Fonctionnalités personnalisées',
                ],
                'actif' => false, // Inactif par défaut, activable manuellement
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['nom' => $planData['nom']],
                $planData
            );
        }

        $this->command->info('✓ 3 plans créés avec succès');
    }
}
