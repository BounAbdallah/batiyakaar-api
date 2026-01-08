<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Immeuble;

echo "--- Verifying Immeuble Statistics logic ---\n";

// 1. Simulate the Current Controller Logic
echo "\n[Current Logic Simulation]\n";
$query = Immeuble::query();
$query->with([
    'etages.biens.baux' => function ($q) {
        $q->where('statut', 'actif');
    }
]);

// The problematic line in the controller:
// $immeubles = $query->with('etages')->latest()->get(); 
// We replicate it exactly:
$immeubles = $query->with('etages')->latest()->get();

$totalBiens = 0;
$totalLocataires = 0;
$totalEtages = 0;
$totalChiffreAffaires = 0;

foreach ($immeubles as $immeuble) {
    $immeubleChiffre = 0;
    $totalEtages += $immeuble->etages->count();

    foreach ($immeuble->etages as $etage) {
        if ($etage->biens) {
            $totalBiens += $etage->biens->count();
            foreach ($etage->biens as $bien) {
                if ($bien->baux) {
                    $totalLocataires += $bien->baux->count();
                    foreach ($bien->baux as $bail) {
                        $immeubleChiffre += $bail->loyer_mensuel ?? 0;
                    }
                }
            }
        }
    }
    $totalChiffreAffaires += $immeubleChiffre;
}

echo "Total Immeubles: " . $immeubles->count() . "\n";
echo "Total Etages: " . $totalEtages . "\n";
echo "Total Biens: " . $totalBiens . "\n";
echo "Total Locataires: " . $totalLocataires . "\n";
echo "Total Chiffre Affaires: " . $totalChiffreAffaires . "\n";


// 2. Simulate the Proposed Fix Logic
echo "\n[Proposed Fix Logic Simulation]\n";
$query2 = Immeuble::query();
// We define the eager load ONCE and correctly.
$query2->with([
    'etages.biens.baux' => function ($q) {
        $q->where('statut', 'actif');
    }
]);

// We DO NOT call ->with('etages') again.
$immeubles2 = $query2->latest()->get();

$totalBiens2 = 0;
$totalLocataires2 = 0;
$totalEtages2 = 0;
$totalChiffreAffaires2 = 0;

foreach ($immeubles2 as $immeuble) {
    $immeubleChiffre = 0;
    $totalEtages2 += $immeuble->etages->count();

    foreach ($immeuble->etages as $etage) {
        if ($etage->biens) {
            $totalBiens2 += $etage->biens->count();
            foreach ($etage->biens as $bien) {
                if ($bien->baux) {
                    $totalLocataires2 += $bien->baux->count();
                    foreach ($bien->baux as $bail) {
                        $immeubleChiffre += $bail->loyer_mensuel ?? 0;
                    }
                }
            }
        }
    }
    $totalChiffreAffaires2 += $immeubleChiffre;
}

echo "Total Immeubles: " . $immeubles2->count() . "\n";
echo "Total Etages: " . $totalEtages2 . "\n";
echo "Total Biens: " . $totalBiens2 . "\n";
echo "Total Locataires: " . $totalLocataires2 . "\n";
echo "Total Chiffre Affaires: " . $totalChiffreAffaires2 . "\n";

// Check if they differ
if ($totalBiens != $totalBiens2) {
    echo "\n!!! DIFFERENCE DETECTED !!!\n";
    echo "Current Logic Biens: $totalBiens vs Proposed Logic Biens: $totalBiens2\n";
} else {
    echo "\nNo difference detected in Biens count.\n";
}
