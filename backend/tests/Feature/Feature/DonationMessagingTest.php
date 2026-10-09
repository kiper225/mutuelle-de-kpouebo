<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\Message;
use App\Models\User;
use App\Services\NullPaymentGateway;
use App\Services\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DonationMessagingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->bind(PaymentGateway::class, NullPaymentGateway::class);
    }

    private function user(string $role = 'member', string $status = 'active'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'status' => $status])->save();

        return $user;
    }

    public function test_a_visitor_can_register_a_pending_donation(): void
    {
        $this->postJson('/api/v1/donations', [
            'donor_name' => 'Visiteur', 'amount' => 5000, 'provider' => 'wave',
        ])->assertCreated()->assertJsonPath('donation.status', 'pending');

        $this->assertDatabaseCount('donations', 1);
    }

    public function test_donation_below_minimum_is_rejected(): void
    {
        config(['mutuelle.donation_min' => 100]);

        $this->postJson('/api/v1/donations', [
            'donor_name' => 'Visiteur', 'amount' => 10, 'provider' => 'wave',
        ])->assertUnprocessable();
    }

    public function test_webhook_marks_a_donation_as_paid(): void
    {
        config(['mutuelle.webhook_secret' => 'secret']);
        $donation = Donation::forceCreate([
            'donor_name' => 'Visiteur', 'amount' => 5000, 'status' => 'pending', 'reference' => 'DON-TEST123',
        ]);

        $payload = ['reference' => 'DON-TEST123', 'status' => 'success'];
        $signature = hash_hmac('sha256', json_encode($payload), 'secret');

        $this->postJson('/api/v1/webhooks/payments', $payload, ['X-Signature' => $signature])->assertOk();

        $this->assertSame('paid', $donation->fresh()->status);
    }

    public function test_only_admin_can_list_donations(): void
    {
        Sanctum::actingAs($this->user('member'));
        $this->getJson('/api/v1/admin/donations')->assertForbidden();

        Sanctum::actingAs($this->user('admin'));
        $this->getJson('/api/v1/admin/donations')->assertOk();
    }

    public function test_members_can_exchange_messages_and_unread_count_updates(): void
    {
        $alice = $this->user();
        $bob = $this->user();

        Sanctum::actingAs($alice);
        $this->postJson("/api/v1/conversations/{$bob->id}", ['body' => 'Bonjour Bob'])->assertCreated();

        Sanctum::actingAs($bob);
        $this->getJson('/api/v1/messages/unread-count')->assertJsonPath('count', 1);
        $this->getJson('/api/v1/conversations')->assertJsonPath('0.unread', 1);

        $this->getJson("/api/v1/conversations/{$alice->id}")->assertOk()->assertJsonCount(1, 'messages');
        $this->getJson('/api/v1/messages/unread-count')->assertJsonPath('count', 0);
    }

    public function test_cannot_message_oneself_or_a_non_active_member(): void
    {
        $alice = $this->user();
        $pending = $this->user('member', 'pending');

        Sanctum::actingAs($alice);
        $this->postJson("/api/v1/conversations/{$alice->id}", ['body' => 'Moi'])->assertUnprocessable();
        $this->postJson("/api/v1/conversations/{$pending->id}", ['body' => 'Salut'])->assertNotFound();
        $this->assertSame(0, Message::count());
    }

    public function test_a_stranger_cannot_read_a_private_conversation(): void
    {
        $alice = $this->user();
        $bob = $this->user();
        $eve = $this->user();

        Sanctum::actingAs($alice);
        $this->postJson("/api/v1/conversations/{$bob->id}", ['body' => 'Secret'])->assertCreated();

        Sanctum::actingAs($eve);
        $this->getJson("/api/v1/conversations/{$alice->id}")->assertOk()->assertJsonCount(0, 'messages');
    }
}