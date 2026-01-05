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

// Mock WaveService to check received arguments
$mockWaveService = \Mockery::mock(\App\Services\WaveService::class);
$mockWaveService->shouldReceive('createCheckoutSession')
->once()
->withArgs(function($amount, $currency, $errorUrl, $successUrl, $ref, $description, $customer) {
echo "Verifying arguments passed to createCheckoutSession:\n";
echo "Amount: $amount\n";
echo "Description: $description\n";
echo "Customer: " . json_encode($customer) . "\n";

if (empty($customer['name'])) {
echo "FAILURE: Customer name missing.\n";
return false;
}

echo "SUCCESS: Customer data present.\n";
return true;
})
->andReturn(['url' => 'http://mock-url']);

$controller = new \App\Http\Controllers\Api\PaiementLoyerController();
try {
$response = $controller->initiateWavePayment($request, $mockWaveService);
echo "Controller response: " . $response->getContent() . "\n";
} catch (\Exception $e) {
echo "Exception: " . $e->getMessage() . "\n";
}