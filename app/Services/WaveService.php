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
        $this->apiKey = trim(env('WAVE_API_KEY'));
    }

    /**
     * Create a checkout session.
     * Note: This is an estimated implementation based on standard Wave integration patterns.
     * The official Checkout API documentation should be consulted for exact field names.
     */
    public function createCheckoutSession($amount, $currency = 'XOF', $errorUrl, $successUrl, $clientReference)
    {
        // Standard Checkout Session payload structure
        // Verify this against your specific Wave Developer docs if it differs
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/checkout/sessions', [
                    'amount' => (string) $amount, // Wave usually expects string for amounts
                    'currency' => $currency,
                    'error_url' => $errorUrl,
                    'success_url' => $successUrl,
                    'client_reference' => $clientReference,
                ]);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Wave API Error: ' . $response->body());
        throw new \Exception('Failed to create Wave checkout session: ' . $response->body());
    }
}
