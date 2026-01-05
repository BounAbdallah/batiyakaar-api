<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaveService
{
    private $baseUrl;
    private $apiKey;

    public function __construct()
    {
        $this->baseUrl = 'https://api.wave.com/v1';
        $apiKey = env('WAVE_API_KEY');
        $this->apiKey = $apiKey ? trim($apiKey) : null;
    }

    private function checkApiKey()
    {
        if (empty($this->apiKey)) {
            throw new \Exception("Wave API Key is missing. Check WAVE_API_KEY in .env");
        }
    }

    /**
     * Create a checkout session.
     * Note: This is an estimated implementation based on standard Wave integration patterns.
     * The official Checkout API documentation should be consulted for exact field names.
     */
    public function createCheckoutSession($amount, $currency, $errorUrl, $successUrl, $clientReference, $description = null, $customer = [])
    {
        $this->checkApiKey();

        // Standard Checkout Session payload structure
        // Verify this against your specific Wave Developer docs if it differs
        $data = [
            'amount' => (string) $amount, // Wave usually expects string for amounts
            'currency' => $currency,
            'error_url' => $errorUrl,
            'success_url' => $successUrl,
            'client_reference' => $clientReference,
        ];

        if ($description) {
            // "description" is a common field for payment intent/checkout sessions
            // If Wave API uses a different key (e.g. 'product_name'), update here.
            // Based on generic integration:
            $data['description'] = $description;
        }

        if (!empty($customer)) {
            $data['customer'] = $customer;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/checkout/sessions', $data);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Wave API Error: ' . $response->body());
        throw new \Exception('Failed to create Wave checkout session: ' . $response->body());
    }
}
