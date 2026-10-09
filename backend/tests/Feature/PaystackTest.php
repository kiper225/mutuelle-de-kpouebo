<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Services\PaymentGateway;
use App\Services\PaystackGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaystackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mutuelle.paystack.secret_key' => 'sk_test_123',
            'mutuelle.paystack.base_url' => 'https://api.paystack.co',
            'mutuelle.paystack.fallback_email_domain' => null,
            'mutuelle.frontend_url' => 'http://localhost:5173',
        ]);
        $this->app->bind(PaymentGateway::class, PaystackGateway::class);
    }

    private function pendingDonation(string $reference, int $amount = 5000): Donation
    {
        return Donation::forceCreate([
            'donor_name' => 'Visiteur', 'amount' => $amount, 'status' => 'pending', 'reference' => $reference,
        ]);
    }

    private function signed(array $payload): array
    {
        return ['x-paystack-signature' => hash_hmac('sha512', json_encode($payload), 'sk_test_123')];
    }

    public function test_a_donation_starts_a_paystack_checkout(): void
    {
        Http::fake(['api.paystack.co/*' => Http::response([
            'status' => true,
            'data' => ['authorization_url' => 'https://checkout.paystack.com/abc123', 'access_code' => 'abc123'],
        ])]);

        $this->postJson('/api/v1/donations', [
            'donor_name' => 'Visiteur', 'donor_email' => 'visiteur@example.com', 'amount' => 5000, 'provider' => 'wave',
        ])->assertCreated()->assertJsonPath('payment.payment_url', 'https://checkout.paystack.com/abc123');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.paystack.co/transaction/initialize'
            && $request['amount'] === 500000
            && $request['currency'] === 'XOF'
            && $request['email'] === 'visiteur@example.com'
            && $request->hasHeader('Authorization', 'Bearer sk_test_123'));
    }

    public function test_without_any_email_the_donation_is_refused_and_not_kept(): void
    {
        $this->postJson('/api/v1/donations', [
            'donor_name' => 'Visiteur', 'amount' => 5000, 'provider' => 'wave',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('donations', 0);
    }

    public function test_a_paystack_failure_returns_502_and_keeps_nothing(): void
    {
        Http::fake(['api.paystack.co/*' => Http::response(['status' => false, 'message' => 'Erreur'], 400)]);

        $this->postJson('/api/v1/donations', [
            'donor_name' => 'Visiteur', 'donor_email' => 'visiteur@example.com', 'amount' => 5000, 'provider' => 'wave',
        ])->assertStatus(502);

        $this->assertDatabaseCount('donations', 0);
    }

    public function test_a_signed_webhook_marks_the_payment_as_paid(): void
    {
        $donation = $this->pendingDonation('DON-ABC123');
        $payload = ['event' => 'charge.success', 'data' => [
            'id' => 123, 'reference' => 'DON-ABC123', 'amount' => 500000, 'currency' => 'XOF', 'status' => 'success',
        ]];

        $this->postJson('/api/v1/webhooks/paystack', $payload, $this->signed($payload))->assertOk();

        $this->assertSame('paid', $donation->fresh()->status);
        $this->assertSame('123', $donation->fresh()->provider_reference);
    }

    public function test_a_webhook_with_the_wrong_amount_does_not_validate_the_payment(): void
    {
        $donation = $this->pendingDonation('DON-ABC123');
        $payload = ['event' => 'charge.success', 'data' => [
            'id' => 123, 'reference' => 'DON-ABC123', 'amount' => 10000, 'currency' => 'XOF', 'status' => 'success',
        ]];

        $this->postJson('/api/v1/webhooks/paystack', $payload, $this->signed($payload))->assertOk();

        $this->assertSame('pending', $donation->fresh()->status);
    }

    public function test_a_webhook_with_a_bad_signature_is_rejected(): void
    {
        $donation = $this->pendingDonation('DON-ABC123');
        $payload = ['event' => 'charge.success', 'data' => ['reference' => 'DON-ABC123', 'amount' => 500000]];

        $this->postJson('/api/v1/webhooks/paystack', $payload, ['x-paystack-signature' => 'faux'])->assertUnauthorized();

        $this->assertSame('pending', $donation->fresh()->status);
    }

    public function test_the_status_endpoint_verifies_with_paystack_and_settles(): void
    {
        $donation = $this->pendingDonation('DON-XYZ789');
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => ['id' => 9, 'status' => 'success', 'amount' => 500000, 'reference' => 'DON-XYZ789'],
        ])]);

        $this->getJson('/api/v1/payments/DON-XYZ789')
            ->assertOk()
            ->assertJsonPath('status', 'paid')
            ->assertJsonPath('type', 'don');

        $this->assertSame('paid', $donation->fresh()->status);
    }

    public function test_the_status_endpoint_returns_404_for_an_unknown_reference(): void
    {
        $this->getJson('/api/v1/payments/DON-INCONNU')->assertNotFound();
    }
}