<?php

namespace Database\Seeders;

use App\Models\Fonctionnalite;
use Illuminate\Database\Seeder;

class FonctionnalitesSeeder extends Seeder
{
    public function run(): void
    {
        $fonctionnalites = [
            [
                'code' => 'gestion_biens',
                'nom' => 'Gestion des Biens',
                'description' => 'Permet de créer, modifier et supprimer des biens immobiliers',
                'module' => 'biens',
                'icone' => 'Home',
                'route' => '/biens',
                'actif' => true,
                'ordre' => 10,
            ],
            [
                'code' => 'gestion_baux',
                'nom' => 'Gestion des Baux',
                'description' => 'Permet de gérer les contrats de location',
                'module' => 'baux',
                'icone' => 'FileText',
                'route' => '/leases',
                'actif' => true,
                'ordre' => 20,
            ],
            [
                'code' => 'paiements_loyers',
                'nom' => 'Paiements de Loyers',
                'description' => 'Permet de gérer les paiements et les loyers',
                'module' => 'paiements',
                'icone' => 'CreditCard',
                'route' => '/payments',
                'actif' => true,
                'ordre' => 30,
            ],
            [
                'code' => 'gestion_locataires',
                'nom' => 'Gestion des Locataires',
                'description' => 'Permet de gérer les locataires',
                'module' => 'locataires',
                'icone' => 'Users',
                'route' => '/tenants',
                'actif' => true,
                'ordre' => 40,
            ],
            [
                'code' => 'gestion_bailleurs',
                'nom' => 'Gestion des Bailleurs',
                'description' => 'Permet de gérer les propriétaires bailleurs',
                'module' => 'bailleurs',
                'icone' => 'Users',
                'route' => '/bailleurs',
                'actif' => true,
                'ordre' => 50,
            ],
            [
                'code' => 'gestion_immeubles',
                'nom' => 'Gestion des Immeubles',
                'description' => 'Permet de gérer les immeubles',
                'module' => 'immeubles',
                'icone' => 'Building',
                'route' => '/immeubles',
                'actif' => true,
                'ordre' => 60,
            ],
            [
                'code' => 'etats_lieux',
                'nom' => 'États des Lieux',
                'description' => 'Permet de créer et gérer les états des lieux',
                'module' => 'etats_lieux',
                'icone' => 'ClipboardCheck',
                'route' => '/dashboard/inventory',
                'actif' => true,
                'ordre' => 70,
            ],
            [
                'code' => 'gestion_incidents',
                'nom' => 'Gestion des Incidents',
                'description' => 'Permet de gérer les incidents et demandes de maintenance',
                'module' => 'incidents',
                'icone' => 'AlertTriangle',
                'route' => '/incidents',
                'actif' => true,
                'ordre' => 80,
            ],
            [
                'code' => 'notifications_whatsapp',
                'nom' => 'Notifications WhatsApp',
                'description' => 'Permet d\'envoyer des notifications via WhatsApp',
                'module' => null,
                'icone' => 'MessageSquare',
                'route' => null,
                'actif' => true,
                'ordre' => 90,
            ],
            [
                'code' => 'acces_locataires',
                'nom' => 'Accès Locataires',
                'description' => 'Permet aux locataires d\'accéder à leur espace personnel',
                'module' => null,
                'icone' => 'UserCheck',
                'route' => null,
                'actif' => true,
                'ordre' => 100,
            ],
            [
                'code' => 'acces_bailleurs',
                'nom' => 'Accès Bailleurs',
                'description' => 'Permet aux bailleurs d\'accéder à leur espace personnel',
                'module' => null,
                'icone' => 'UserCheck',
                'route' => null,
                'actif' => true,
                'ordre' => 110,
            ],
            [
                'code' => 'gestion_equipe',
                'nom' => 'Gestion d\'Équipe',
                'description' => 'Permet de gérer les membres de l\'équipe et leurs permissions',
                'module' => 'equipe',
                'icone' => 'Users',
                'route' => '/agency/team',
                'actif' => true,
                'ordre' => 120,
            ],
            [
                'code' => 'api_access',
                'nom' => 'Accès API',
                'description' => 'Permet d\'accéder à l\'API pour des intégrations personnalisées',
                'module' => null,
                'icone' => 'Code',
                'route' => null,
                'actif' => true,
                'ordre' => 130,
            ],
            [
                'code' => 'gestion_depenses',
                'nom' => 'Gestion des Dépenses',
                'description' => 'Permet de gérer et suivre les dépenses par bien et bailleur',
                'module' => 'depenses',
                'icone' => 'DollarSign',
                'route' => '/expenses',
                'actif' => true,
                'ordre' => 85,
            ],
            [
                'code' => 'multi_utilisateurs',
                'nom' => 'Multi-utilisateurs',
                'description' => 'Permet d\'avoir plusieurs utilisateurs dans l\'agence',
                'module' => null,
                'icone' => 'Users',
                'route' => null,
                'actif' => true,
                'ordre' => 140,
            ],
        ];

        foreach ($fonctionnalites as $fonctionnaliteData) {
            Fonctionnalite::updateOrCreate(
                ['code' => $fonctionnaliteData['code']],
                $fonctionnaliteData
            );
        }

        $this->command->info('✓ ' . count($fonctionnalites) . ' fonctionnalités créées avec succès');
    }
}
