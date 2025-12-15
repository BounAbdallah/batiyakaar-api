<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Immeuble;
use App\Models\Etage;
use App\Models\Bien;
use App\Models\Bail;
use App\Models\Bailleur;
use App\Models\Agence;
use App\Models\Locataire;

class PropertiesSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n🏢 Création des immeubles, biens et baux...\n";
        echo "==========================================================\n\n";

        // Get first agence and bailleur
        $agence = Agence::first();
        $bailleur = Bailleur::first();

        if (!$agence || !$bailleur) {
            echo "❌ Aucune agence ou bailleur trouvé. Exécutez UsersSeeder d'abord.\n";
            return;
        }

        // === IMMEUBLE 1: Résidence Les Palmiers ===
        $immeuble1 = Immeuble::create([
            'nom' => 'Résidence Les Palmiers',
            'adresse' => 'Almadies, Route de Ngor, Dakar',
            'description' => 'Immeuble moderne de standing avec vue mer',
            'nombre_etages' => 3,
            'agence_id' => $agence->id,
            'bailleur_id' => $bailleur->id,
        ]);

        // Étages de l'immeuble 1
        $etage_rdc = Etage::create([
            'immeuble_id' => $immeuble1->id,
            'numero' => 0,
            'nom' => 'Rez-de-chaussée',
        ]);

        $etage_1 = Etage::create([
            'immeuble_id' => $immeuble1->id,
            'numero' => 1,
            'nom' => 'Premier étage',
        ]);

        $etage_2 = Etage::create([
            'immeuble_id' => $immeuble1->id,
            'numero' => 2,
            'nom' => 'Deuxième étage',
        ]);

        // Appartements RDC
        $appart_rdc_1 = Bien::create([
            'reference' => 'PALM-RDC-01',
            'type' => 'appartement',
            'adresse' => 'Résidence Les Palmiers, Almadies - Appart F3 RDC Gauche',
            'surface' => 85.5,
            'nombre_pieces' => 3,
            'loyer_mensuel' => 250000,
            'statut' => 'loue',
            'bailleur_id' => $bailleur->id,
            'agence_id' => $agence->id,
            'immeuble_id' => $immeuble1->id,
            'etage_id' => $etage_rdc->id,
        ]);

        $appart_rdc_2 = Bien::create([
            'reference' => 'PALM-RDC-02',
            'type' => 'appartement',
            'adresse' => 'Résidence Les Palmiers, Almadies - Appart F2 RDC Droite',
            'surface' => 65.0,
            'nombre_pieces' => 2,
            'loyer_mensuel' => 180000,
            'statut' => 'loue',
            'bailleur_id' => $bailleur->id,
            'agence_id' => $agence->id,
            'immeuble_id' => $immeuble1->id,
            'etage_id' => $etage_rdc->id,
        ]);

        // Appartements Étage 1
        $appart_1_1 = Bien::create([
            'reference' => 'PALM-E1-01',
            'type' => 'appartement',
            'adresse' => 'Résidence Les Palmiers, Almadies - Appart F4 Étage 1',
            'surface' => 120.0,
            'nombre_pieces' => 4,
            'loyer_mensuel' => 350000,
            'statut' => 'loue',
            'bailleur_id' => $bailleur->id,
            'agence_id' => $agence->id,
            'immeuble_id' => $immeuble1->id,
            'etage_id' => $etage_1->id,
        ]);

        $appart_1_2 = Bien::create([
            'reference' => 'PALM-E1-02',
            'type' => 'appartement',
            'adresse' => 'Résidence Les Palmiers, Almadies - Appart F3 Étage 1',
            'surface' => 90.0,
            'nombre_pieces' => 3,
            'loyer_mensuel' => 280000,
            'statut' => 'disponible',
            'bailleur_id' => $bailleur->id,
            'agence_id' => $agence->id,
            'immeuble_id' => $immeuble1->id,
            'etage_id' => $etage_1->id,
        ]);

        $appart_2_1 = Bien::create([
            'reference' => 'PALM-E2-01',
            'type' => 'appartement',
            'adresse' => 'Résidence Les Palmiers, Almadies - Penthouse Étage 2',
            'surface' => 150.0,
            'nombre_pieces' => 5,
            'loyer_mensuel' => 500000,
            'statut' => 'loue',
            'bailleur_id' => $bailleur->id,
            'agence_id' => $agence->id,
            'immeuble_id' => $immeuble1->id,
            'etage_id' => $etage_2->id,
        ]);

        echo "✓ Immeuble 1 créé: {$immeuble1->nom} (6 appartements)\n";

        // === IMMEUBLE 2: Résidence Océan ===
        $bailleur2 = Bailleur::skip(1)->first();
        $immeuble2 = Immeuble::create([
            'nom' => 'Résidence Océan',
            'adresse' => 'Mermoz, près VDN, Dakar',
            'description' => 'Résidence sécurisée avec parking',
            'nombre_etages' => 2,
            'agence_id' => $agence->id,
            'bailleur_id' => $bailleur2->id ?? $bailleur->id,
        ]);

        $ocean_rdc = Etage::create([
            'immeuble_id' => $immeuble2->id,
            'numero' => 0,
            'nom' => 'Rez-de-chaussée',
        ]);

        $ocean_1 = Etage::create([
            'immeuble_id' => $immeuble2->id,
            'numero' => 1,
            'nom' => 'Premier étage',
        ]);

        $ocean_appart_1 = Bien::create([
            'reference' => 'OCEAN-RDC-01',
            'type' => 'studio',
            'adresse' => 'Résidence Océan, Mermoz - Studio RDC',
            'surface' => 35.0,
            'nombre_pieces' => 1,
            'loyer_mensuel' => 120000,
            'statut' => 'loue',
            'bailleur_id' => $bailleur2->id ?? $bailleur->id,
            'agence_id' => $agence->id,
            'immeuble_id' => $immeuble2->id,
            'etage_id' => $ocean_rdc->id,
        ]);

        $ocean_appart_2 = Bien::create([
            'reference' => 'OCEAN-E1-01',
            'type' => 'appartement',
            'adresse' => 'Résidence Océan, Mermoz - Appart F2 Étage 1',
            'surface' => 70.0,
            'nombre_pieces' => 2,
            'loyer_mensuel' => 200000,
            'statut' => 'disponible',
            'bailleur_id' => $bailleur2->id ?? $bailleur->id,
            'agence_id' => $agence->id,
            'immeuble_id' => $immeuble2->id,
            'etage_id' => $ocean_1->id,
        ]);

        echo "✓ Immeuble 2 créé: {$immeuble2->nom} (2 appartements)\n";

        $villa1 = Bien::create([
            'reference' => 'VILLA-001',
            'type' => 'maison',
            'adresse' => 'Villa Standing Fann Résidence, Dakar',
            'surface' => 300.0,
            'nombre_pieces' => 6,
            'loyer_mensuel' => 800000,
            'statut' => 'loue',
            'bailleur_id' => $bailleur->id,
            'agence_id' => $agence->id,
        ]);

        $maison1 = Bien::create([
            'reference' => 'MAIS-001',
            'type' => 'maison',
            'adresse' => 'Maison Parcelles Assainies U10, Dakar',
            'surface' => 150.0,
            'nombre_pieces' => 4,
            'loyer_mensuel' => 300000,
            'statut' => 'disponible',
            'bailleur_id' => $bailleur->id,
            'agence_id' => $agence->id,
        ]);

        echo "✓ 2 biens individuels créés (villa + maison)\n\n";

        // === CRÉATION DES BAUX ===
        echo "📝 Création des baux...\n";

        $locataires = Locataire::limit(6)->get();

        if ($locataires->count() < 6) {
            echo "⚠️  Pas assez de locataires. Baux limités.\n";
        }

        $today = now();
        $sixMonthsAgo = $today->copy()->subMonths(6);

        // Baux pour les appartements loués
        $baux = [
            [
                'bien' => $appart_rdc_1,
                'locataire' => $locataires[0] ?? null,
                'date_debut' => $sixMonthsAgo->copy()->format('Y-m-d'),
                'date_fin' => $sixMonthsAgo->copy()->addYear()->format('Y-m-d'),
            ],
            [
                'bien' => $appart_rdc_2,
                'locataire' => $locataires[1] ?? null,
                'date_debut' => $sixMonthsAgo->copy()->format('Y-m-d'),
                'date_fin' => $sixMonthsAgo->copy()->addYear()->format('Y-m-d'),
            ],
            [
                'bien' => $appart_1_1,
                'locataire' => $locataires[2] ?? null,
                'date_debut' => $today->copy()->subMonths(3)->format('Y-m-d'),
                'date_fin' => $today->copy()->addMonths(9)->format('Y-m-d'),
            ],
            [
                'bien' => $appart_2_1,
                'locataire' => $locataires[3] ?? null,
                'date_debut' => $today->copy()->subMonth()->format('Y-m-d'),
                'date_fin' => $today->copy()->addMonths(11)->format('Y-m-d'),
            ],
            [
                'bien' => $ocean_appart_1,
                'locataire' => $locataires[4] ?? null,
                'date_debut' => $today->copy()->subMonths(4)->format('Y-m-d'),
                'date_fin' => $today->copy()->addMonths(8)->format('Y-m-d'),
            ],
            [
                'bien' => $villa1,
                'locataire' => $locataires[5] ?? null,
                'date_debut' => $today->copy()->subMonths(2)->format('Y-m-d'),
                'date_fin' => $today->copy()->addMonths(10)->format('Y-m-d'),
            ],
        ];

        $bauxCrees = 0;
        foreach ($baux as $bailData) {
            if ($bailData['locataire']) {
                Bail::create([
                    'bien_id' => $bailData['bien']->id,
                    'locataire_id' => $bailData['locataire']->id,
                    'agence_id' => $agence->id,
                    'date_debut' => $bailData['date_debut'],
                    'date_fin' => $bailData['date_fin'],
                    'loyer_mensuel' => $bailData['bien']->loyer_mensuel,
                    'caution' => $bailData['bien']->loyer_mensuel, // 1 mois de caution
                    'statut' => 'actif',
                ]);
                $bauxCrees++;
            }
        }

        echo "✓ {$bauxCrees} baux actifs créés\n";

        echo "\n==========================================================\n";
        echo "✅ Seeding des propriétés terminé!\n";
        echo "==========================================================\n\n";
        echo "📊 Résumé:\n";
        echo "  - 2 Immeubles\n";
        echo "  - 8 Appartements (6 loués, 2 disponibles)\n";
        echo "  - 2 Biens individuels (1 villa, 1 maison)\n";
        echo "  - {$bauxCrees} Baux actifs\n\n";
    }
}
