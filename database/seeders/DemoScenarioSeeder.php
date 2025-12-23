<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Agence;
use App\Models\Plan;
use App\Models\Abonnement;
use App\Models\Bailleur;
use App\Models\Immeuble;
use App\Models\Etage;
use App\Models\Bien;
use App\Models\Locataire;
use App\Models\Bail;
use App\Models\PaiementLoyer;
use App\Models\Quittance;
use App\Models\Technicien;
use App\Models\Incident;
use App\Models\ProjetConstruction;
use App\Models\Chantier;
use App\Models\EtatDesLieux;

class DemoScenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $this->command->info('Début du seeding complet du scénario de démonstration...');

            // 1. Assurer que les Plans existent
            $this->ensurePlansExist();

            // Récupérer les plans
            $planStarter = Plan::where('nom', 'Starter')->firstOrFail();
            $planPro = Plan::where('nom', 'Pro')->firstOrFail();
            $planEntreprise = Plan::where('nom', 'Entreprise')->firstOrFail();

            // 2. Créer l'administrateur
            $this->createAdmin();

            // 3. Créer ou Récupérer les Bailleurs Spécifiques
            $bailleurs = $this->createLandlords();

            // 4. Créer les Agences (avec permissions complètes)
            $this->createAgenciesForPlan($planStarter, 'Basique', $bailleurs);
            $this->createAgenciesForPlan($planPro, 'Pro', $bailleurs);
            $this->createAgenciesForPlan($planEntreprise, 'Elite', $bailleurs);

            DB::commit();
            $this->command->info('Seeding terminé avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Erreur lors du seeding : ' . $e->getMessage());
            throw $e;
        }
    }

    private function ensurePlansExist()
    {
        $plans = [
            [
                'nom' => 'Starter',
                'prix_mensuel' => 5000,
                'prix_annuel' => 50000,
                'limite_biens' => 10,
                'limite_utilisateurs' => 1,
                'fonctionnalites' => ['Gestion de 10 biens', 'Suivi des loyers'],
                'actif' => true,
                'est_public' => true,
                'est_personnalise' => false,
            ],
            [
                'nom' => 'Pro',
                'prix_mensuel' => 15000,
                'prix_annuel' => 150000,
                'limite_biens' => 50,
                'limite_utilisateurs' => 3,
                'fonctionnalites' => ['Gestion de 50 biens', '3 Comptes utilisateurs'],
                'actif' => true,
                'est_public' => true,
                'est_personnalise' => false,
            ],
            [
                'nom' => 'Entreprise',
                'prix_mensuel' => 45000,
                'prix_annuel' => 450000,
                'limite_biens' => 999999,
                'limite_utilisateurs' => 10,
                'fonctionnalites' => ['Biens illimités', '10 Comptes utilisateurs'],
                'actif' => true,
                'est_public' => true,
                'est_personnalise' => false,
            ],
        ];

        foreach ($plans as $p) {
            Plan::firstOrCreate(['nom' => $p['nom']], $p);
        }
    }

    private function createAdmin()
    {
        User::firstOrCreate(
            ['email' => 'admin@batiyakaar.com'],
            [
                'nom' => 'Super',
                'prenom' => 'Admin',
                'telephone' => '770000000',
                'password' => Hash::make('password123'),
                'user_type' => 'admin',
                'actif' => true,
                'email_verified_at' => now(),
                'permissions' => ['*'], // Super Admin wildcard if supported, or full list handled by gate/policy
            ]
        );
    }

    private function createLandlords()
    {
        $landlordData = [
            ['prenom' => 'Abdallah', 'nom' => 'Dramé', 'email' => 'abdallah.drame@demo.com'],
            ['prenom' => 'Mariam', 'nom' => 'Diallo', 'email' => 'mariam.diallo@demo.com'],
            ['prenom' => 'Hapsatou', 'nom' => 'Thiam', 'email' => 'hapsatou.thiam@demo.com'],
            ['prenom' => 'Moussa', 'nom' => 'Sow', 'email' => 'moussa.sow@demo.com'],
            ['prenom' => 'Fatou', 'nom' => 'Ndiaye', 'email' => 'fatou.ndiaye@demo.com'],
        ];

        $bailleursObjs = [];

        foreach ($landlordData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'nom' => $data['nom'],
                    'prenom' => $data['prenom'],
                    'telephone' => '77' . rand(1000000, 9999999),
                    'password' => Hash::make('password123'),
                    'user_type' => 'bailleur',
                    'actif' => true,
                    'email_verified_at' => now(),
                    'permissions' => [], // Bailleurs don't need agency permissions usually
                ]
            );

            $bailleur = Bailleur::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'pays' => 'Sénégal',
                    'adresse_diaspora' => rand(0, 1) ? '123 Rue Paris, France' : null,
                    'numero_cni' => '1' . rand(100000000000, 999999999999),
                ]
            );

            $bailleursObjs[] = $bailleur;
        }

        return $bailleursObjs;
    }

    private function createAgenciesForPlan(Plan $plan, string $prefixName, array $bailleurs)
    {
        // Full permissions set for Agency Director
        $allPermissions = [
            'biens.view',
            'biens.create',
            'biens.edit',
            'biens.delete',
            'baux.view',
            'baux.create',
            'baux.edit',
            'baux.delete',
            'paiements.view',
            'paiements.create',
            'paiements.edit',
            'paiements.delete',
            'incidents.view',
            'incidents.create',
            'incidents.edit',
            'incidents.delete',
            'bailleurs.view',
            'bailleurs.create',
            'bailleurs.edit',
            'bailleurs.delete',
            'immeubles.view',
            'immeubles.create',
            'immeubles.edit',
            'immeubles.delete',
            'locataires.view',
            'locataires.create',
            'locataires.edit',
            'locataires.delete',
        ];

        for ($i = 1; $i <= 3; $i++) {
            $agenceNom = "Agence $prefixName $i";
            $email = strtolower(str_replace(' ', '.', $prefixName)) . ".$i@demo.com";

            // 1. User Agence (Admin)
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'nom' => 'Directeur',
                    'prenom' => $agenceNom,
                    'telephone' => '33' . rand(1000000, 9999999),
                    'password' => Hash::make('password123'),
                    'user_type' => 'agence',
                    'actif' => true,
                    'email_verified_at' => now(),
                    'permissions' => $allPermissions,
                ]
            );

            // 2. Agence Profil
            $agence = Agence::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'raison_sociale' => $agenceNom,
                    'ninea' => 'NINEA-' . rand(10000, 99999),
                    'rccm' => 'RCCM-' . rand(10000, 99999),
                    'adresse' => 'Dakar, Sénégal',
                    'taux_commission_agence' => 5.00,
                ]
            );

            // 3. Abonnement
            $existingSub = Abonnement::where('agence_id', $agence->id)->exists();
            if (!$existingSub) {
                Abonnement::create([
                    'agence_id' => $agence->id,
                    'plan_id' => $plan->id,
                    'date_debut' => now(),
                    'date_fin' => now()->addYear(),
                    'statut' => 'actif',
                    'auto_renouvellement' => true,
                ]);
            }

            // 4. Create Staff (Technicien)
            $this->createStaffForAgency($agence);

            // 5. Create Tenants
            $locataires = $this->createTenantsForAgency($agence);

            // 6. Immeubles & Biens & Baux
            $this->createBuildingsForAgency($agence, $bailleurs, $locataires);
        }
    }

    private function createStaffForAgency(Agence $agence)
    {
        // Créer un Technicien
        $techEmail = 'tech.' . $agence->id . '@demo.com';
        $techUser = User::firstOrCreate(['email' => $techEmail], [
            'nom' => 'Tech',
            'prenom' => 'Super',
            'telephone' => '70' . rand(1000000, 9999999),
            'password' => Hash::make('password123'),
            'user_type' => 'technicien',
            'actif' => true,
            'email_verified_at' => now(),
            'agence_id' => $agence->id, // Employé par agence
            'permissions' => ['incidents.view', 'incidents.edit'],
        ]);

        Technicien::firstOrCreate(['user_id' => $techUser->id], [
            'agence_id' => $agence->id,
            'nom' => $techUser->nom,
            'telephone' => $techUser->telephone,
            'specialite' => 'Plomberie & Electricité',
            'disponible' => true,
        ]);
    }

    private function createTenantsForAgency(Agence $agence)
    {
        $locataires = [];
        // Create 10 tenants for this agency
        for ($j = 1; $j <= 10; $j++) {
            $email = 'locataire.' . $agence->id . '.' . $j . '@demo.com';
            $user = User::firstOrCreate(['email' => $email], [
                'nom' => 'Locataire',
                'prenom' => 'Numéro ' . $j,
                'telephone' => '76' . rand(1000000, 9999999),
                'password' => Hash::make('password123'),
                'user_type' => 'locataire',
                'actif' => true,
                'email_verified_at' => now(),
                'permissions' => [],
            ]);

            $locataire = Locataire::firstOrCreate(['user_id' => $user->id], [
                'profession' => 'Salarié',
                'employeur' => 'Société ' . $j,
                'revenu_mensuel' => rand(300000, 1000000),
                'numero_cni' => '1' . rand(100000000000, 999999999999),
                'agence_id' => $agence->id,
            ]);
            $locataires[] = $locataire;
        }
        return $locataires;
    }

    private function createBuildingsForAgency(Agence $agence, array $bailleurs, array $locataires)
    {
        $configs = [
            ['nom' => 'Résidence Mixte Nord', 'etages' => 3, 'types' => ['appartement', 'studio', 'commerce']],
            ['nom' => 'Résidence Mixte Sud', 'etages' => 3, 'types' => ['appartement', 'studio', 'commerce']],
            ['nom' => 'Résidence Résidentielle Est', 'etages' => 3, 'types' => ['appartement', 'studio']],
            ['nom' => 'Résidence Résidentielle Ouest', 'etages' => 3, 'types' => ['appartement', 'studio']],
        ];

        $locataireIndex = 0;

        foreach ($configs as $k => $config) {
            $bailleur = $bailleurs[$k % count($bailleurs)];

            $immeuble = Immeuble::create([
                'nom' => $config['nom'] . " (" . $agence->raison_sociale . ")",
                'adresse' => 'Quartier ' . ($k + 1) . ', Dakar',
                'description' => 'Immeuble de standing.',
                'nombre_etages' => $config['etages'],
                'bailleur_id' => $bailleur->id, // via user_id -> Bailleur
                'agence_id' => $agence->id,
            ]);

            // Projet Construction & Chantier (Simulé pour le dernier immeuble)
            if ($k === 3) {
                // ... logic for construction project simulation
            }

            for ($e = 0; $e < $config['etages']; $e++) {
                $nomEtage = ($e == 0) ? 'Rez-de-chaussée' : "Étage $e";
                $etage = Etage::create([
                    'nom' => $nomEtage,
                    'numero' => $e,
                    'immeuble_id' => $immeuble->id,
                ]);

                foreach ($config['types'] as $type) {
                    if ($type === 'commerce' && $e !== 0)
                        continue;

                    $surface = ($type === 'studio') ? rand(20, 35) : (($type === 'commerce') ? rand(50, 200) : rand(60, 150));
                    $loyer = $surface * rand(2500, 5000);

                    // Déterminer si le bien est loué (70% chance)
                    $isRented = (rand(1, 100) <= 70) && isset($locataires[$locataireIndex]);
                    $statut = $isRented ? 'loue' : 'disponible';

                    $bien = Bien::create([
                        'bailleur_id' => $bailleur->id,
                        'agence_id' => $agence->id,
                        'immeuble_id' => $immeuble->id,
                        'etage_id' => $etage->id,
                        'projet_construction_id' => null,
                        'reference' => strtoupper(substr($agence->raison_sociale, 0, 3)) . "-IMM{$immeuble->id}-ET{$e}-" . strtoupper(substr($type, 0, 3)) . rand(10, 99),
                        'adresse' => $immeuble->adresse . ", Porte " . ($e * 10 + rand(1, 9)),
                        'type' => $type,
                        'nombre_pieces' => ($type === 'studio' || $type === 'commerce') ? 1 : rand(3, 5),
                        'surface' => $surface,
                        'loyer_mensuel' => $loyer,
                        'taux_commission' => 10.0,
                        'statut' => $statut,
                    ]);

                    // Si loué, créer un Bail + Paiements
                    if ($isRented) {
                        $locataire = $locataires[$locataireIndex];
                        $locataireIndex = ($locataireIndex + 1) % count($locataires);

                        $this->createLeaseAndPayments($agence, $bien, $locataire);
                    }
                }
            }
        }
    }

    private function createLeaseAndPayments(Agence $agence, Bien $bien, Locataire $locataire)
    {
        $dateDebut = now()->subMonths(rand(2, 12));
        $dateFin = $dateDebut->copy()->addYear();

        $bail = Bail::create([
            'bien_id' => $bien->id,
            'locataire_id' => $locataire->id,
            'agence_id' => $agence->id,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'loyer_mensuel' => $bien->loyer_mensuel,
            'caution' => $bien->loyer_mensuel * 2,
            'statut' => 'actif',
            'type_duree' => 'determine',
            'jour_paiement' => 5,
        ]);

        // État des lieux entrée
        EtatDesLieux::create([
            'bail_id' => $bail->id,
            'type' => 'entree',
            'date_etat' => $dateDebut,
            'remarques' => 'RAS, appartement en bon état.',
            'effectue_par' => 'Agence',
        ]);

        // Générer paiements pour les mois passés
        $currentDate = $dateDebut->copy();
        while ($currentDate->lt(now())) {
            $month = $currentDate->format('Y-m');

            // 90% chance payé à temps
            $statut = 'paye';
            $datePaiement = $currentDate->copy()->addDays(rand(0, 10)); // Payé entre le 1 et 10

            // Créer paiement
            $datePrevue = $currentDate->copy()->startOfMonth()->addDays(4); // 5th of month

            $paiement = PaiementLoyer::create([
                'bail_id' => $bail->id,
                'montant' => $bien->loyer_mensuel,
                'montant_attendu' => $bien->loyer_mensuel,
                'date_paiement' => $datePaiement,
                'date_prevue' => $datePrevue,
                'statut' => $statut,
                'mode_paiement' => 'wave',
                'reference_transaction' => uniqid('WAVE-'),
            ]);

            // Quittance
            Quittance::create([
                'paiement_loyer_id' => $paiement->id,
                'numero' => 'QUIT-' . $paiement->id,
                'date_emission' => $datePaiement,
                'url_pdf' => null, // PDF à générer
            ]);

            $currentDate->addMonth();
        }

        // Créer un incident (10% chance)
        if (rand(1, 100) <= 10) {
            Incident::create([
                'bail_id' => $bail->id,
                'titre' => 'Fuite d\'eau salle de bain',
                'description' => 'Le robinet fuit depuis hier.',
                'categorie' => 'plomberie',
                'statut' => 'en_cours',
                'priorite' => 'haute',
                'date_declaration' => now()->subDays(2),
                'locataire_id' => $locataire->id,
            ]);
        }
    }
}
