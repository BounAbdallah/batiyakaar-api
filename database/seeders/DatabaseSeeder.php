<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🚀 Démarrage du seeding de la base de données Bâti Yakaar');
        $this->command->info('==========================================================');
        $this->command->info('');

        // Order is important! Respect dependencies
        $this->call([
            PlansSeeder::class,
            UsersSeeder::class,
            // ProjetConstructionSeeder::class,  // À créer
            // BiensSeeder::class,                // À créer
            // ProduitsSeeder::class,             // À créer
            // BauxSeeder::class,                 // À créer
            // TransactionsSeeder::class,         // À créer
        ]);

        $this->command->info('');
        $this->command->info('==========================================================');
        $this->command->info('✅ Seeding terminé avec succès!');
        $this->command->info('==========================================================');
        $this->command->info('');
        $this->command->info('📊 Données créées:');
        $this->command->info('  - 3 Plans d\'abonnement');
        $this->command->info('  - 31 Utilisateurs (tous types)');
        $this->command->info('  - 31 Portefeuilles virtuels');
        $this->command->info('  - 3 Abonnements agences');
        $this->command->info('');
        $this->command->info('🔐 Credentials de test:');
        $this->command->info('  Email: amadou.diop@diaspora.sn (Bailleur)');
        $this->command->info('  Email: contact@immoplus.sn (Agence)');
        $this->command->info('  Email: mamadou.gueye@construction.sn (Entrepreneur)');
        $this->command->info('  Email: contact@ciment-plus.sn (Fournisseur)');
        $this->command->info('  Email: awa.diouf@email.sn (Locataire)');
        $this->command->info('  Password: password123 (pour tous)');
        $this->command->info('');
    }
}
