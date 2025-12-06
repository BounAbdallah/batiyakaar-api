<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Bailleur;
use App\Models\Agence;
use App\Models\Entrepreneur;
use App\Models\Fournisseur;
use App\Models\Locataire;
use App\Models\Technicien;
use App\Models\Plan;
use App\Models\Abonnement;
use App\Models\PortefeuilleVirtuel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Password for all test users
        $password = Hash::make('password123');

        // 1. BAILLEURS (5)
        $bailleurs = [
            ['nom' => 'Diop', 'prenom' => 'Amadou', 'email' => 'amadou.diop@diaspora.sn', 'telephone' => '+33612345678', 'pays' => 'France', 'adresse_diaspora' => '15 Rue de la Paix, Paris'],
            ['nom' => 'Ndiaye', 'prenom' => 'Fatou', 'email' => 'fatou.ndiaye@diaspora.sn', 'telephone' => '+12125551234', 'pays' => 'USA', 'adresse_diaspora' => '123 Broadway, New York'],
            ['nom' => 'Sow', 'prenom' => 'Moussa', 'email' => 'moussa.sow@diaspora.sn', 'telephone' => '+14165551234', 'pays' => 'Canada', 'adresse_diaspora' => '456 Yonge St, Toronto'],
            ['nom' => 'Fall', 'prenom' => 'Aissatou', 'email' => 'aissatou.fall@diaspora.sn', 'telephone' => '+33687654321', 'pays' => 'France', 'adresse_diaspora' => '28 Avenue des Champs-Élysées, Paris'],
            ['nom' => 'Kane', 'prenom' => 'Ibrahima', 'email' => 'ibrahima.kane@diaspora.sn', 'telephone' => '+390612345678', 'pays' => 'Italie', 'adresse_diaspora' => 'Via Roma 45, Milan'],
        ];

        foreach ($bailleurs as $bailleurData) {
            $user = User::create([
                'nom' => $bailleurData['nom'],
                'prenom' => $bailleurData['prenom'],
                'email' => $bailleurData['email'],
                'telephone' => $bailleurData['telephone'],
                'password' => $password,
                'user_type' => 'bailleur',
                'actif' => true,
            ]);

            Bailleur::create([
                'user_id' => $user->id,
                'pays' => $bailleurData['pays'],
                'adresse_diaspora' => $bailleurData['adresse_diaspora'],
            ]);

            // Create virtual wallet
            PortefeuilleVirtuel::create([
                'user_id' => $user->id,
                'solde' => rand(100000, 5000000),
                'devise' => 'XOF',
            ]);
        }

        $this->command->info('✓ 5 bailleurs créés');

        // 2. AGENCES (3)
        $plans = Plan::all();
        $agences = [
            ['nom' => 'Seck', 'prenom' => 'Cheikh', 'email' => 'contact@immoplus.sn', 'telephone' => '+221771234567', 'raison_sociale' => 'Immo Plus Sénégal', 'ninea' => '0012345678', 'adresse' => 'Almadies, Dakar', 'plan_id' => $plans[2]->id],
            ['nom' => 'Diallo', 'prenom' => 'Mariama', 'email' => 'info@dakarproperty.sn', 'telephone' => '+221779876543', 'raison_sociale' => 'Dakar Property Services', 'ninea' => '0098765432', 'adresse' => 'Mermoz, Dakar', 'plan_id' => $plans[1]->id],
            ['nom' => 'Sarr', 'prenom' => 'Ousmane', 'email' => 'contact@senegalimmo.sn', 'telephone' => '+221775551234', 'raison_sociale' => 'Sénégal Immo', 'ninea' => '0055512345', 'adresse' => 'Plateau, Dakar', 'plan_id' => $plans[0]->id],
        ];

        foreach ($agences as $agenceData) {
            $user = User::create([
                'nom' => $agenceData['nom'],
                'prenom' => $agenceData['prenom'],
                'email' => $agenceData['email'],
                'telephone' => $agenceData['telephone'],
                'password' => $password,
                'user_type' => 'agence',
                'actif' => true,
            ]);

            $agence = Agence::create([
                'user_id' => $user->id,
                'raison_sociale' => $agenceData['raison_sociale'],
                'ninea' => $agenceData['ninea'],
                'adresse' => $agenceData['adresse'],
            ]);

            // Create subscription
            Abonnement::create([
                'agence_id' => $agence->id,
                'plan_id' => $agenceData['plan_id'],
                'date_debut' => now(),
                'date_fin' => now()->addYear(),
                'statut' => 'actif',
                'auto_renouvellement' => true,
            ]);

            // Create virtual wallet
            PortefeuilleVirtuel::create([
                'user_id' => $user->id,
                'solde' => rand(500000, 10000000),
                'devise' => 'XOF',
            ]);
        }

        $this->command->info('✓ 3 agences créées avec abonnements');

        // 3. ENTREPRENEURS (4)
        $entrepreneurs = [
            ['nom' => 'Gueye', 'prenom' => 'Mamadou', 'email' => 'mamadou.gueye@construction.sn', 'telephone' => '+221781234567', 'specialite' => 'Maçonnerie', 'registre_commerce' => 'RC-2020-001', 'tarif_journalier' => 25000],
            ['nom' => 'Thiam', 'prenom' => 'Abdoulaye', 'email' => 'abdoulaye.thiam@plomberie.sn', 'telephone' => '+221789876543', 'specialite' => 'Plomberie', 'registre_commerce' => 'RC-2019-045', 'tarif_journalier' => 20000],
            ['nom' => 'Ba', 'prenom' => 'Souleymane', 'email' => 'souleymane.ba@electricite.sn', 'telephone' => '+221785551234', 'specialite' => 'Électricité', 'registre_commerce' => 'RC-2021-089', 'tarif_journalier' => 22000],
            ['nom' => 'Cisse', 'prenom' => 'Modou', 'email' => 'modou.cisse@menuiserie.sn', 'telephone' => '+221783334444', 'specialite' => 'Menuiserie', 'registre_commerce' => 'RC-2020-123', 'tarif_journalier' => 18000],
        ];

        foreach ($entrepreneurs as $entrepreneurData) {
            $user = User::create([
                'nom' => $entrepreneurData['nom'],
                'prenom' => $entrepreneurData['prenom'],
                'email' => $entrepreneurData['email'],
                'telephone' => $entrepreneurData['telephone'],
                'password' => $password,
                'user_type' => 'entrepreneur',
                'actif' => true,
            ]);

            Entrepreneur::create([
                'user_id' => $user->id,
                'specialite' => $entrepreneurData['specialite'],
                'registre_commerce' => $entrepreneurData['registre_commerce'],
                'tarif_journalier' => $entrepreneurData['tarif_journalier'],
            ]);

            PortefeuilleVirtuel::create([
                'user_id' => $user->id,
                'solde' => rand(50000, 1000000),
                'devise' => 'XOF',
            ]);
        }

        $this->command->info('✓ 4 entrepreneurs créés');

        // 4. FOURNISSEURS (3)
        $fournisseurs = [
            ['nom' => 'Sy', 'prenom' => 'Alioune', 'email' => 'contact@ciment-plus.sn', 'telephone' => '+221771112222', 'nom_entreprise' => 'Ciment Plus', 'categorie_materiaux' => 'Ciment et Béton', 'adresse_entrepot' => 'Zone Industrielle, Rufisque'],
            ['nom' => 'Mbaye', 'prenom' => 'Serigne', 'email' => 'info@fer-construction.sn', 'telephone' => '+221773334444', 'nom_entreprise' => 'Fer & Construction', 'categorie_materiaux' => 'Fer et Acier', 'adresse_entrepot' => 'Thiaroye, Dakar'],
            ['nom' => 'Ndao', 'prenom' => 'Pape', 'email' => 'contact@bois-senegal.sn', 'telephone' => '+221775556666', 'nom_entreprise' => 'Bois du Sénégal', 'categorie_materiaux' => 'Bois et Menuiserie', 'adresse_entrepot' => 'Mbao, Dakar'],
        ];

        foreach ($fournisseurs as $fournisseurData) {
            $user = User::create([
                'nom' => $fournisseurData['nom'],
                'prenom' => $fournisseurData['prenom'],
                'email' => $fournisseurData['email'],
                'telephone' => $fournisseurData['telephone'],
                'password' => $password,
                'user_type' => 'fournisseur',
                'actif' => true,
            ]);

            Fournisseur::create([
                'user_id' => $user->id,
                'nom_entreprise' => $fournisseurData['nom_entreprise'],
                'categorie_materiaux' => $fournisseurData['categorie_materiaux'],
                'adresse_entrepot' => $fournisseurData['adresse_entrepot'],
            ]);

            PortefeuilleVirtuel::create([
                'user_id' => $user->id,
                'solde' => rand(1000000, 20000000),
                'devise' => 'XOF',
            ]);
        }

        $this->command->info('✓ 3 fournisseurs créés');

        // 5. LOCATAIRES (10)
        $locataires = [
            ['nom' => 'Diouf', 'prenom' => 'Awa', 'email' => 'awa.diouf@email.sn', 'telephone' => '+221761111111', 'profession' => 'Enseignante', 'employeur' => 'Ministère de l\'Éducation', 'revenu_mensuel' => 350000],
            ['nom' => 'Faye', 'prenom' => 'Malick', 'email' => 'malick.faye@email.sn', 'telephone' => '+221762222222', 'profession' => 'Ingénieur', 'employeur' => 'Sonatel', 'revenu_mensuel' => 800000],
            ['nom' => 'Toure', 'prenom' => 'Bineta', 'email' => 'bineta.toure@email.sn', 'telephone' => '+221763333333', 'profession' => 'Infirmière', 'employeur' => 'Hôpital Principal', 'revenu_mensuel' => 400000],
            ['nom' => 'Diagne', 'prenom' => 'Youssou', 'email' => 'youssou.diagne@email.sn', 'telephone' => '+221764444444', 'profession' => 'Comptable', 'employeur' => 'KPMG Sénégal', 'revenu_mensuel' => 600000],
            ['nom' => 'Samb', 'prenom' => 'Khady', 'email' => 'khady.samb@email.sn', 'telephone' => '+221765555555', 'profession' => 'Avocate', 'employeur' => 'Cabinet Juridique', 'revenu_mensuel' => 900000],
            ['nom' => 'Wade', 'prenom' => 'Babacar', 'email' => 'babacar.wade@email.sn', 'telephone' => '+221766666666', 'profession' => 'Commercial', 'employeur' => 'Total Sénégal', 'revenu_mensuel' => 500000],
            ['nom' => 'Ly', 'prenom' => 'Aminata', 'email' => 'aminata.ly@email.sn', 'telephone' => '+221767777777', 'profession' => 'Médecin', 'employeur' => 'Clinique Pasteur', 'revenu_mensuel' => 1200000],
            ['nom' => 'Niang', 'prenom' => 'Omar', 'email' => 'omar.niang@email.sn', 'telephone' => '+221768888888', 'profession' => 'Développeur', 'employeur' => 'Freelance', 'revenu_mensuel' => 700000],
            ['nom' => 'Sall', 'prenom' => 'Ndèye', 'email' => 'ndeye.sall@email.sn', 'telephone' => '+221769999999', 'profession' => 'Pharmacienne', 'employeur' => 'Pharmacie du Plateau', 'revenu_mensuel' => 550000],
            ['nom' => 'Diaw', 'prenom' => 'Lamine', 'email' => 'lamine.diaw@email.sn', 'telephone' => '+221760000000', 'profession' => 'Architecte', 'employeur' => 'Studio Architecture', 'revenu_mensuel' => 850000],
        ];

        foreach ($locataires as $locataireData) {
            $user = User::create([
                'nom' => $locataireData['nom'],
                'prenom' => $locataireData['prenom'],
                'email' => $locataireData['email'],
                'telephone' => $locataireData['telephone'],
                'password' => $password,
                'user_type' => 'locataire',
                'actif' => true,
            ]);

            Locataire::create([
                'user_id' => $user->id,
                'profession' => $locataireData['profession'],
                'employeur' => $locataireData['employeur'],
                'revenu_mensuel' => $locataireData['revenu_mensuel'],
            ]);

            PortefeuilleVirtuel::create([
                'user_id' => $user->id,
                'solde' => rand(10000, 500000),
                'devise' => 'XOF',
            ]);
        }

        $this->command->info('✓ 10 locataires créés');

        // 6. TECHNICIENS (6 - 2 par agence)
        $agencesCreated = Agence::all();
        $techniciens = [
            ['agence_id' => $agencesCreated[0]->id, 'nom' => 'Moussa Diop', 'telephone' => '+221781111111', 'specialite' => 'Plomberie'],
            ['agence_id' => $agencesCreated[0]->id, 'nom' => 'Fatou Seck', 'telephone' => '+221782222222', 'specialite' => 'Électricité'],
            ['agence_id' => $agencesCreated[1]->id, 'nom' => 'Ibrahima Fall', 'telephone' => '+221783333333', 'specialite' => 'Climatisation'],
            ['agence_id' => $agencesCreated[1]->id, 'nom' => 'Aissatou Ndiaye', 'telephone' => '+221784444444', 'specialite' => 'Serrurerie'],
            ['agence_id' => $agencesCreated[2]->id, 'nom' => 'Cheikh Gueye', 'telephone' => '+221785555555', 'specialite' => 'Peinture'],
            ['agence_id' => $agencesCreated[2]->id, 'nom' => 'Mariama Ba', 'telephone' => '+221786666666', 'specialite' => 'Jardinage'],
        ];

        foreach ($techniciens as $technicienData) {
            Technicien::create($technicienData);
        }

        $this->command->info('✓ 6 techniciens créés');

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('✓ TOTAL: 31 utilisateurs créés');
        $this->command->info('  - 5 Bailleurs');
        $this->command->info('  - 3 Agences (avec abonnements)');
        $this->command->info('  - 4 Entrepreneurs');
        $this->command->info('  - 3 Fournisseurs');
        $this->command->info('  - 10 Locataires');
        $this->command->info('  - 6 Techniciens');
        $this->command->info('========================================');
    }
}
