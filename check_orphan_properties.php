<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Bien;

echo "--- Checking Property Counts ---\n";

// Total Properties
$totalBiens = Bien::count();
echo "Total Biens in DB: $totalBiens\n";

// Properties in Buildings (have immeuble_id or etage_id)
$biensInBuildings = Bien::whereNotNull('immeuble_id')->count();
echo "Biens with immeuble_id: $biensInBuildings\n";

$biensWithEtage = Bien::whereNotNull('etage_id')->count();
echo "Biens with etage_id: $biensWithEtage\n";

// Orphan Properties
$orphans = Bien::whereNull('immeuble_id')->get();
echo "Biens without immeuble_id (Independent?): " . $orphans->count() . "\n";

if ($orphans->count() > 0) {
    echo "\nList of Orphan Biens:\n";
    foreach ($orphans as $bien) {
        echo "- ID: {$bien->id} | Name: {$bien->nom} | Ref: {$bien->reference} | Status: {$bien->statut}\n";
    }
}
