<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Agence;
use App\Models\Bailleur;
use App\Models\Locataire;
use App\Models\Bien;
use App\Models\Bail;
use App\Models\PaiementLoyer;
use App\Models\Incident;
use Carbon\Carbon;
use Faker\Factory as Faker;

class AgencyDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        $targetEmail = 'contact@immoplus.sn';
        
        $agenceUser = User::where('email', $targetEmail)->first();
        
        if (!$agenceUser) {
            $this->command->error("L'utilisateur avec l'email {$targetEmail} n'existe pas. Veuillez exécuter UsersSeeder d'abord.");
            return;
        }

        $agence = Agence::where('user_id', $agenceUser->id)->first();
        
        if (!$agence) {
            $this->command->error("L'agence correspondante n'a pas été trouvée.");
            return;
        }

        $this->command->info("Début de la génération des données pour l'agence: " . $agence->raison_sociale);
        
        $password = Hash::make('password123');

        // 1. Création de Bailleurs
        $bailleurs = [];
        for ($i = 0; $i < 5; $i++) {
            $user = User::create([
                'nom' => $faker->lastName,
                'prenom' => $faker->firstName,
                'email' => $faker->unique()->safeEmail,
                'telephone' => $faker->phoneNumber,
                'password' => $password,
                'user_type' => 'bailleur',
                'actif' => true,
            ]);

            $bailleur = Bailleur::create([
                'user_id' => $user->id,
                'pays' => $faker->country,
                'adresse_diaspora' => $faker->address,
                'numero_cni' => $faker->numerify('###########'),
                'date_naissance' => $faker->date('Y-m-d', '-30 years'),
                'lieu_naissance' => $faker->city,
            ]);
            $bailleurs[] = $bailleur;
        }
        $this->command->info("✓ 5 bailleurs créés.");

        // 2. Création de Biens
        $biens = [];
        $types = ['appartement', 'maison', 'studio', 'commerce', 'villa', 'terrain', 'chambre'];
        $statuts = ['disponible', 'loue', 'maintenance'];
        
        for ($i = 0; $i < 15; $i++) {
            $bailleur = $bailleurs[array_rand($bailleurs)];
            
            $bien = Bien::create([
                'bailleur_id' => $bailleur->id,
                'agence_id' => $agence->id,
                'reference' => 'REF-' . strtoupper(Str::random(6)),
                'adresse' => $faker->address,
                'type' => $types[array_rand($types)],
                'nombre_pieces' => rand(1, 8),
                'nombre_chambres' => rand(1, 4),
                'nombre_salons' => rand(1, 2),
                'nombre_cuisines' => rand(1, 2),
                'nombre_salles_bain' => rand(1, 3),
                'surface' => rand(30, 300),
                'loyer_mensuel' => rand(100000, 1000000),
                'taux_commission' => rand(5, 10),
                'statut' => $statuts[array_rand($statuts)],
            ]);
            $biens[] = $bien;
        }
        $this->command->info("✓ 15 biens immobiliers créés.");

        // 3. Création de Locataires
        $locataires = [];
        for ($i = 0; $i < 10; $i++) {
            $user = User::create([
                'nom' => $faker->lastName,
                'prenom' => $faker->firstName,
                'email' => $faker->unique()->safeEmail,
                'telephone' => $faker->phoneNumber,
                'password' => $password,
                'user_type' => 'locataire',
                'actif' => true,
            ]);

            $locataire = Locataire::create([
                'user_id' => $user->id,
                'agence_id' => $agence->id,
                'profession' => $faker->jobTitle,
                'employeur' => $faker->company,
                'revenu_mensuel' => rand(300000, 2000000),
                'numero_cni' => $faker->numerify('###########'),
                'date_naissance' => $faker->date('Y-m-d', '-25 years'),
                'lieu_naissance' => $faker->city,
            ]);
            $locataires[] = $locataire;
        }
        $this->command->info("✓ 10 locataires créés.");

        // 4. Création de Baux
        $baux = [];
        // On va associer des locataires à certains biens
        $biensLoues = array_filter($biens, fn($b) => $b->statut == 'loue');
        
        foreach ($biensLoues as $bien) {
            $locataire = $locataires[array_rand($locataires)];
            
            $dateDebut = Carbon::now()->subMonths(rand(1, 12));
            $dateFin = (clone $dateDebut)->addYears(rand(1, 3));
            
            $bail = Bail::create([
                'bien_id' => $bien->id,
                'locataire_id' => $locataire->id,
                'agence_id' => $agence->id,
                'date_debut' => $dateDebut->format('Y-m-d'),
                'date_fin' => $dateFin->format('Y-m-d'),
                'type_duree' => 'determinee',
                'loyer_mensuel' => $bien->loyer_mensuel,
                'caution' => $bien->loyer_mensuel * rand(1, 2),
                'jour_paiement' => rand(1, 10),
                'statut' => 'actif',
            ]);
            $baux[] = $bail;
        }
        $this->command->info("✓ " . count($baux) . " baux créés.");

        // 5. Création de Paiements de loyers
        $paiementsCount = 0;
        foreach ($baux as $bail) {
            // Générer 1 à 3 paiements par bail
            $numPaiements = rand(1, 3);
            for ($i = 0; $i < $numPaiements; $i++) {
                $datePaiement = Carbon::parse($bail->date_debut)->addMonths($i);
                PaiementLoyer::create([
                    'bail_id' => $bail->id,
                    'montant' => $bail->loyer_mensuel,
                    'montant_attendu' => $bail->loyer_mensuel,
                    'date_paiement' => $datePaiement->format('Y-m-d'),
                    'date_prevue' => $datePaiement->copy()->startOfMonth()->addDays($bail->jour_paiement - 1)->format('Y-m-d'),
                    'periode_debut' => $datePaiement->copy()->startOfMonth()->format('Y-m-d'),
                    'periode_fin' => $datePaiement->copy()->endOfMonth()->format('Y-m-d'),
                    'mode_paiement' => array_rand(array_flip(['Wave', 'Orange Money', 'Virement', 'Espèces'])),
                    'statut' => array_rand(array_flip(['paye', 'impaye', 'en_attente'])),
                    'reference_transaction' => 'TRX-' . strtoupper(Str::random(8)),
                ]);
                $paiementsCount++;
            }
        }
        $this->command->info("✓ {$paiementsCount} paiements de loyers générés.");

        // 6. Création d'incidents
        $incidentsCount = 0;
        foreach ($baux as $bail) {
            if (rand(0, 1)) { // 50% de chances d'avoir un incident
                Incident::create([
                    'bail_id' => $bail->id,
                    'locataire_id' => $bail->locataire_id,
                    'titre' => 'Problème de ' . array_rand(array_flip(['plomberie', 'fuite d\'eau', 'climatiseur en panne', 'électricité'])),
                    'description' => $faker->paragraph,
                    'categorie' => array_rand(array_flip(['plomberie', 'electricite', 'serrurerie', 'autre'])),
                    'priorite' => array_rand(array_flip(['basse', 'moyenne', 'haute'])),
                    'statut' => array_rand(array_flip(['ouvert', 'en_cours', 'resolu'])),
                    'date_declaration' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
                ]);
                $incidentsCount++;
            }
        }
        $this->command->info("✓ {$incidentsCount} incidents créés.");

        $this->command->info('========================================');
        $this->command->info("🎉 Génération terminée avec succès pour " . $agence->raison_sociale);
    }
}
