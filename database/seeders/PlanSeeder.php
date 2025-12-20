<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cleanup unwanted plans created by mistake
        Plan::whereIn('nom', ['Basique', 'Empire'])->delete();

        $plans = [
            [
                'nom' => 'Starter', // Formerly Basique
                'description' => 'Idéal pour les propriétaires indépendants qui démarrent.',
                'prix_mensuel' => 5000,
                'prix_annuel' => 50000,
                'limite_utilisateurs' => 1,
                'limite_biens' => 10,
                'fonctionnalites' => [
                    'Gestion de 10 biens maximum',
                    'Suivi des loyers et paiements',
                    'Génération de quittances PDF',
                    'Tableau de bord basique',
                    'Gestion des locataires',
                    'Support par email'
                ],
                'actif' => true,
                'est_public' => true,
                'est_personnalise' => false,
            ],
            [
                'nom' => 'Pro',
                'description' => 'Pour les agences en croissance qui veulent automatiser.',
                'prix_mensuel' => 15000,
                'prix_annuel' => 150000,
                'limite_utilisateurs' => 3,
                'limite_biens' => 50,
                'fonctionnalites' => [
                    'Jusqu\'à 50 biens immobiliers',
                    '3 Comptes utilisateurs (Agents)',
                    'Rappels automatiques (SMS/Email)',
                    'Gestion des dépenses et travaux',
                    'Tableau de bord financier avancé',
                    'Gestion documentaire (Baux, Mandats)',
                    'Statistiques en temps réel',
                    'Support prioritaire 7j/7'
                ],
                'actif' => true,
                'est_public' => true,
                'est_personnalise' => false,
            ],
            [
                'nom' => 'Entreprise', // Formerly Empire
                'description' => 'La solution ultime pour les grands réseaux immobiliers.',
                'prix_mensuel' => 45000,
                'prix_annuel' => 450000,
                'limite_utilisateurs' => 10,
                'limite_biens' => 999999, // Illimité
                'fonctionnalites' => [
                    'Biens illimités',
                    '10 Comptes utilisateurs inclus',
                    'Gestion Multi-Agences',
                    'Rapports financiers expert',
                    'API pour site web agence',
                    'Personnalisation en marque blanche',
                    'Formation des équipes incluse',
                    'Gestionnaire de compte dédié'
                ],
                'actif' => true,
                'est_public' => true,
                'est_personnalise' => false,
            ]
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['nom' => $planData['nom']],
                $planData
            );
        }
    }
}
