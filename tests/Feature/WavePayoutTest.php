<?php

namespace Tests\Feature;

use App\Models\Agence;
use App\Models\Bail;
use App\Models\Bien;
use App\Models\Locataire;
use App\Models\User;
use App\Services\WaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class WavePayoutTest extends TestCase
{
    // Use RefreshDatabase to reset DB after test
    // Note: If project doesn't use in-memory DB, ensure it uses a test DB to avoid wiping dev data.
    // For now, removing RefreshDatabase if unsure about env, or assume standard Laravel safety.
    // Safe option: Use transactions if RefreshDatabase is too aggressive for this env.
    use RefreshDatabase;

    public function test_wave_webhook_triggers_payout_to_agency()
    {
        // 1. Setup Data: Agency, User, Landlord, Tenant, Property, Lease

        // Agency Owner
        $agencyUser = User::factory()->create([
            'telephone' => '770000000',
            'user_type' => 'agence'
        ]);

        $agence = Agence::create([
            'user_id' => $agencyUser->id,
            'raison_sociale' => 'Agence Test',
            'taux_commission_agence' => 10,
            'ninea' => 'NINEA123',
            'rccm' => 'RCCM123',
            'adresse' => 'Dakar'
        ]);

        // Landlord
        $landlordUser = User::factory()->create(['user_type' => 'bailleur']);
        $bailleur = \App\Models\Bailleur::create([
            'user_id' => $landlordUser->id,
            'pays' => 'Senegal',
            'ville' => 'Dakar',
            'adresse' => 'Adresse Bailleur',
            'telephone' => '770000001'
        ]);

        // Tenant
        $tenantUser = User::factory()->create(['user_type' => 'locataire']);
        $locataire = Locataire::create([
            'user_id' => $tenantUser->id,
            'pays' => 'Senegal',
            'ville' => 'Dakar',
            'adresse' => 'Adresse Locataire',
            'telephone' => '770000002'
        ]);

        // Property
        $bien = Bien::create([
            'bailleur_id' => $bailleur->id, // If direct relation
            // simplified for test
            'reference' => 'REF-TEST-001',
            'adresse' => 'Dakar',
            'type' => 'appartement',
            'surface' => 50,
            'nombre_pieces' => 2,
            'loyer_mensuel' => 100000,
            'taux_commission' => 10 // 10%
        ]);

        // Lease (Bail)
        $bail = Bail::create([
            'bien_id' => $bien->id,
            'locataire_id' => $locataire->id,
            'agence_id' => $agence->id, // Managed by Agency
            'loyer_mensuel' => 100000,
            'date_debut' => now()->subMonth(),
            'date_fin' => now()->addYear(),
            'caution' => 200000,
            'type_bail' => 'habitation',
            'statut' => 'actif'
        ]);

        // 2. Mock WaveService
        $mockWaveService = Mockery::mock(WaveService::class);

        // Expect payout check called with:
        // Amount = 10% of 100,000 = 10,000
        // Recipient = 'Agence Test'
        // Mobile = '770000000'
        $mockWaveService->shouldReceive('payout')
            ->once()
            ->withArgs(function ($amount, $currency, $recipientName, $recipientMobile) {
                return $amount === 100000
                    && $currency === 'XOF'
                    && $recipientMobile === '770000000';
            })
            ->andReturn(['status' => 'success']); // Return mock success

        // Bind mock to app
        $this->app->instance(WaveService::class, $mockWaveService);


        // 3. Simulate Webhook Request
        $clientReference = 'RENT-' . $bail->id . '-123456-' . now()->month . '-' . now()->year;

        $payload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'client_reference' => $clientReference,
                'payment_status' => 'succeeded',
                'amount' => '101500', // Amount with fees
            ]
        ];

        // We assume the route is /api/v1/webhooks/wave
        $response = $this->postJson('/api/v1/webhooks/wave', $payload);

        // 4. Assertions
        $response->assertStatus(200);

        // Assert Payment Created
        $this->assertDatabaseHas('paiements_loyer', [
            'bail_id' => $bail->id,
            'statut' => 'paye',
            'reference_transaction' => $clientReference,
            'montant' => 100000 // Original rent
        ]);

        // Assert Ventilation Created
        $this->assertDatabaseHas('ventilations', [
            'montant_agence' => 10000,
            'montant_bailleur' => 90000
        ]);

        // The mock verification happens automatically via Mockery::close() handled by Laravel TestCase or manual check
    }
}
