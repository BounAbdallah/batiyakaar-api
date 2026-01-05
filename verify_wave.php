
$request = \Illuminate\Http\Request::create('/api/paiements-loyer/wave/initiate', 'POST', [
    'bail_id' => 1,
    'montant' => 50000,
    'month' => 2,
    'year' => 2026
]);

$user = \App\Models\User::first();
$request->setUserResolver(function () use ($user) {
    return $user;
});

$mockWaveService = \Mockery::mock(\App\Services\WaveService::class);
$mockWaveService->shouldReceive('createCheckoutSession')
    ->once()
    ->withArgs(function($amount, $currency, $errorUrl, $successUrl, $ref, $description) {
        echo "Verifying arguments passed to createCheckoutSession:\n";
        echo "Description: " . $description . "\n";
        
        if ($description === "Paiement Loyer - Février 2026") {
            echo "SUCCESS: Description matches expected format.\n";
            return true;
        } else {
            echo "FAILURE: Description does not match. Got: '$description'\n";
            return false;
        }
    })
    ->andReturn(['url' => 'http://mock-url']);

$controller = new \App\Http\Controllers\Api\PaiementLoyerController();
try {
    $response = $controller->initiateWavePayment($request, $mockWaveService);
    echo "Controller response: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}