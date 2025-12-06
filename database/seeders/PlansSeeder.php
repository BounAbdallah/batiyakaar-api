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
                'limite_utilisateurs' => 5,
                'limite_biens' => 20,
                'fonctionnalites' => [
                    'Gestion des biens',
                    'Gestion des baux',
                    'Paiements de loyers',
                    'Incidents et maintenance',
                    'Rapports basiques',
                ],
                'actif' => true,
            ],
            [
                'nom' => 'Pro',
                'description' => 'Plan professionnel pour agences en croissance',
                'prix_mensuel' => 50000,
                'limite_utilisateurs' => 15,
                'limite_biens' => 100,
                'fonctionnalites' => [
                    'Toutes fonctionnalités Starter',
                    'Gestion multi-utilisateurs',
                    'Rapports avancés',
                    'Tableaux de bord personnalisés',
                    'Notifications WhatsApp',
                    'États des lieux numériques',
                ],
                'actif' => true,
            ],
            [
                'nom' => 'Enterprise',
                'description' => 'Plan entreprise pour grandes agences',
                'prix_mensuel' => 100000,
                'limite_utilisateurs' => 999,
                'limite_biens' => 9999,
                'fonctionnalites' => [
                    'Toutes fonctionnalités Pro',
                    'Utilisateurs illimités',
                    'Biens illimités',
                    'API access',
                    'Support prioritaire 24/7',
                    'Formation personnalisée',
                    'Intégrations personnalisées',
                ],
                'actif' => true,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::create($planData);
        }

        $this->command->info('✓ 3 plans créés avec succès');
    }
}
