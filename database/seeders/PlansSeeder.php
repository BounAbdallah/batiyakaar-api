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
            $plan = Plan::updateOrCreate(
                ['nom' => $planData['nom']],
                $planData
            );

            // Associate features with plans
            $this->associateFeaturesWithPlan($plan);
        }

        $this->command->info('✓ ' . count($plans) . ' plans créés avec succès');
    }

    private function associateFeaturesWithPlan($plan)
    {
        $featureMapping = [
            'Starter' => [
                'gestion_biens',
                'gestion_baux',
                'paiements_loyers',
                'gestion_locataires',
                'gestion_depenses',
            ],
            'Pro' => [
                'gestion_biens',
                'gestion_baux',
                'paiements_loyers',
                'gestion_locataires',
                'gestion_bailleurs',
                'gestion_immeubles',
                'gestion_depenses',
                'multi_utilisateurs',
                'notifications_whatsapp',
                'acces_bailleurs',
            ],
            'Enterprise' => [
                'gestion_biens',
                'gestion_baux',
                'paiements_loyers',
                'gestion_locataires',
                'gestion_bailleurs',
                'gestion_immeubles',
                'etats_lieux',
                'gestion_incidents',
                'gestion_depenses',
                'multi_utilisateurs',
                'notifications_whatsapp',
                'acces_locataires',
                'acces_bailleurs',
                'gestion_equipe',
                'api_access',
            ],
        ];

        if (isset($featureMapping[$plan->nom])) {
            $featureCodes = $featureMapping[$plan->nom];
            $featureIds = \App\Models\Fonctionnalite::whereIn('code', $featureCodes)->pluck('id');
            $plan->fonctionnalites()->sync($featureIds);
        }
    }
}
