<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

// Mock data structure mimicking the real object graph
$mockEtat = new \App\Models\EtatDesLieux();
$mockEtat->type = 'entrant';
$mockEtat->date_etat_des_lieux = now();
$mockEtat->observations = 'Test observations';
$mockEtat->content = [];

$mockBail = new \App\Models\Bail();
$mockEtat->setRelation('bail', $mockBail);

$mockBien = new \App\Models\Bien();
$mockBien->adresse = '123 Test St';
$mockBail->setRelation('bien', $mockBien);

$mockImmeuble = new \App\Models\Immeuble();
$mockImmeuble->nom = 'Immeuble Test';
$mockBien->setRelation('immeuble', $mockImmeuble);

$mockAgence = new \App\Models\Agence();
$mockAgence->raison_sociale = 'Test Agence';
$mockAgence->adresse = 'Agence Address';
$mockAgence->ninea = 'NINEA123';
$mockAgence->rccm = 'RCCM123';
$mockAgence->logo = 'non_existent_logo.png'; // DELIBERATE ERROR TESTING
$mockBail->setRelation('agence', $mockAgence);

$mockAgenceUser = new \App\Models\User();
$mockAgenceUser->telephone = '770000000';
$mockAgenceUser->nom = 'Agence';
$mockAgence->setRelation('user', $mockAgenceUser);

$mockLocataire = new \App\Models\Locataire();
$mockLocataire->adresse = 'Tenant Address';
$mockBail->setRelation('locataire', $mockLocataire);

$mockLocataireUser = new \App\Models\User();
$mockLocataireUser->prenom = 'Tenant';
$mockLocataireUser->nom = 'User';
$mockLocataireUser->telephone = '771111111';
$mockLocataireUser->email = 'tenant@test.com';
$mockLocataireUser->cin = '1234567890';
$mockLocataire->setRelation('user', $mockLocataireUser);

try {
    echo "Attempting to generate PDF...\n";
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.etat_des_lieux', ['etat' => $mockEtat]);
    $output = $pdf->output();
    echo "PDF Generated Successfully (Size: " . strlen($output) . " bytes)\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
