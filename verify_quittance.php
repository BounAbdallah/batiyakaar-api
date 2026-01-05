
try {
    // Find a payment to attach to
    $paiement = \App\Models\PaiementLoyer::first();

    if (!$paiement) {
        echo "No PaiementLoyer found to test with.\n";
        exit(0);
    }

    echo "Testing Quittance creation for Paiement ID: " . $paiement->id . "\n";

    $numero = 'TEST-Q-' . time();
    $quittance = \App\Models\Quittance::create([
        'paiement_loyer_id' => $paiement->id,
        'numero_quittance' => $numero,
        'montant' => 10000,
        'periode_debut' => now(),
        'periode_fin' => now()->addMonth(),
        'date_emission' => now(),
        'url_pdf' => null,
    ]);

    echo "Quittance created successfully with ID: " . $quittance->id . "\n";
    echo "Numero Quittance: " . $quittance->numero_quittance . "\n";
    
    // Clean up
    $quittance->delete();
    echo "Test Quittance deleted.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}