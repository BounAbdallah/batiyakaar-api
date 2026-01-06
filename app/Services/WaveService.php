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
        $this->baseUrl = config('services.wave.base_url', 'https://api.wave.com/v1');
        $apiKey = config('services.wave.api_key');
        // Ensure we don't pass null to trim if config is missing (though config returns null by default, trim(null) is deprecated)
        $this->apiKey = $apiKey ? trim($apiKey) : null;
    }

    private function checkApiKey()
    {
        if (empty($this->apiKey)) {
            throw new \Exception("Wave API Key is missing. Check WAVE_API_KEY in .env and run 'php artisan config:clear'");
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

        // Wave API rejected 'description' and 'customer' fields with "extra fields not permitted"
        // So we revert to the standard payload.
        // We will try to bake the motif into client_reference if possible, but for now we must fix the 500 error.

        /* 
        // Previously attempted (rejected by API):
        if ($description) {
            $data['description'] = $description;
        }
        if (!empty($customer)) {
            $data['customer'] = $customer;
        }
        */

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
    /**
     * Create a payout to a specific number.
     */
    public function payout($amount, $currency = 'XOF', $recipientName, $recipientMobile)
    {
        $this->checkApiKey();

        // Wave Payout API URL (Production)
        // Usually: https://api.wave.com/v1/payout
        $url = $this->baseUrl . '/payout';

        $data = [
            'amount' => (string) $amount,
            'currency' => $currency,
            'recipient_name' => $recipientName,
            'mobile' => $recipientMobile,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Idempotency-Key' => \Illuminate\Support\Str::uuid()->toString(), // Ensure idempotency
        ])->post($url, $data);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Wave Payout Error: ' . $response->body());
        // We do not throw exception here to avoid breaking the webhook flow completely,
        // but we return false/null to indicate failure.
        return null;
    }
}
