<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Cotisation;
use App\Models\Donation;
use App\Models\Fundraiser;
use App\Models\User;
use App\Services\NullPaymentGateway;
use App\Services\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FundraiserTest extends TestCase
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

    private function fundraiser(array $attributes = []): Fundraiser
    {
        return Fundraiser::forceCreate(array_merge(['title' => 'Puits du village', 'is_active' => true, 'goal_amount' => 100000], $attributes));
    }

    private function paid(Fundraiser $fundraiser, User $member, int $amount, ?User $recordedBy = null): Contribution
    {
        return Contribution::forceCreate([
            'fundraiser_id' => $fundraiser->id, 'user_id' => $member->id, 'amount' => $amount, 'status' => 'paid',
            'reference' => 'COL-'.uniqid(), 'paid_at' => now(), 'recorded_by' => $recordedBy?->id,
        ]);
    }

    public function test_the_public_list_shows_active_collections_with_their_totals(): void
    {
        $fundraiser = $this->fundraiser();
        $this->fundraiser(['title' => 'Masquée', 'is_active' => false]);
        $member = $this->user();
        $this->paid($fundraiser, $member, 5000);
        Contribution::forceCreate([
            'fundraiser_id' => $fundraiser->id, 'user_id' => $member->id, 'amount' => 999,
            'status' => 'pending', 'reference' => 'COL-PENDING1',
        ]);

        $this->getJson('/api/v1/fundraisers')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.raised_amount', 5000)
            ->assertJsonPath('0.is_open', true);
    }

    public function test_a_member_can_start_a_contribution_but_a_guest_cannot(): void
    {
        $fundraiser = $this->fundraiser();

        $this->postJson("/api/v1/fundraisers/{$fundraiser->id}/contribute", ['amount' => 2000, 'provider' => 'wave'])
            ->assertUnauthorized();

        Sanctum::actingAs($this->user());

        $this->postJson("/api/v1/fundraisers/{$fundraiser->id}/contribute", ['amount' => 2000, 'provider' => 'wave'])
            ->assertCreated()
            ->assertJsonPath('contribution.status', 'pending');

        $this->assertStringStartsWith('COL-', Contribution::firstOrFail()->reference);
    }

    public function test_a_closed_or_hidden_collection_refuses_contributions(): void
    {
        $ended = $this->fundraiser(['ends_on' => now()->subDay()->toDateString()]);
        $hidden = $this->fundraiser(['title' => 'Masquée', 'is_active' => false]);
        Sanctum::actingAs($this->user());

        $this->postJson("/api/v1/fundraisers/{$ended->id}/contribute", ['amount' => 2000, 'provider' => 'wave'])->assertUnprocessable();
        $this->postJson("/api/v1/fundraisers/{$hidden->id}/contribute", ['amount' => 2000, 'provider' => 'wave'])->assertNotFound();
        $this->getJson("/api/v1/fundraisers/{$hidden->id}")->assertNotFound();
    }

    public function test_a_confirmed_payment_marks_the_contribution_as_paid(): void
    {
        config(['mutuelle.webhook_secret' => 'secret']);
        $fundraiser = $this->fundraiser();
        $contribution = Contribution::forceCreate([
            'fundraiser_id' => $fundraiser->id, 'user_id' => $this->user()->id, 'amount' => 2000,
            'status' => 'pending', 'reference' => 'COL-ABC123',
        ]);

        $payload = ['reference' => 'COL-ABC123', 'status' => 'success'];
        $signature = hash_hmac('sha256', json_encode($payload), 'secret');

        $this->postJson('/api/v1/webhooks/payments', $payload, ['X-Signature' => $signature])->assertOk();

        $this->assertSame('paid', $contribution->fresh()->status);
    }

    public function test_admin_creates_a_collection_with_an_image(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->user('admin'));

        $this->postJson('/api/v1/admin/fundraisers', [
            'title' => 'Électrification du quartier', 'summary' => 'Poser des lampadaires', 'goal_amount' => 500000,
            'starts_on' => '2026-11-01', 'ends_on' => '2026-12-31',
            'image' => UploadedFile::fake()->image('c.jpg', 1280, 720),
        ])->assertCreated();

        $fundraiser = Fundraiser::firstOrFail();
        $this->assertSame(500000, $fundraiser->goal_amount);
        Storage::disk('public')->assertExists($fundraiser->image_path);
    }

    public function test_the_end_date_cannot_precede_the_start_date(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $this->postJson('/api/v1/admin/fundraisers', [
            'title' => 'Test', 'starts_on' => '2026-12-31', 'ends_on' => '2026-11-01',
        ])->assertUnprocessable();
    }

    public function test_a_collection_with_payments_cannot_be_deleted_but_an_empty_one_can(): void
    {
        $used = $this->fundraiser();
        $empty = $this->fundraiser(['title' => 'Vide']);
        $this->paid($used, $this->user(), 1000);
        Sanctum::actingAs($this->user('admin'));

        $this->deleteJson("/api/v1/admin/fundraisers/{$used->id}")->assertUnprocessable();
        $this->deleteJson("/api/v1/admin/fundraisers/{$empty->id}")->assertNoContent();

        $this->assertDatabaseHas('fundraisers', ['id' => $used->id]);
        $this->assertDatabaseMissing('fundraisers', ['id' => $empty->id]);
    }

    public function test_the_admin_can_record_a_cash_contribution_for_an_active_member(): void
    {
        $fundraiser = $this->fundraiser();
        $member = $this->user();
        $pending = $this->user('member', 'pending');
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/admin/fundraisers/{$fundraiser->id}/contributions", ['user_id' => $member->id, 'amount' => 1500])
            ->assertCreated();
        $this->postJson("/api/v1/admin/fundraisers/{$fundraiser->id}/contributions", ['user_id' => $pending->id, 'amount' => 1500])
            ->assertUnprocessable();

        $this->assertDatabaseHas('contributions', ['user_id' => $member->id, 'recorded_by' => $admin->id, 'status' => 'paid']);
        $this->getJson("/api/v1/admin/fundraisers/{$fundraiser->id}/contributions")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_member_cannot_manage_collections(): void
    {
        Sanctum::actingAs($this->user());

        $this->postJson('/api/v1/admin/fundraisers', ['title' => 'Piratage'])->assertForbidden();
        $this->getJson('/api/v1/admin/stats/income')->assertForbidden();
    }

    public function test_the_income_report_splits_online_and_cash_payments(): void
    {
        $admin = $this->user('admin');
        $member = $this->user();
        $period = now()->format('Y-m');

        Cotisation::forceCreate(['user_id' => $member->id, 'period' => $period, 'amount' => 1000, 'status' => 'paid', 'reference' => 'COT-I1']);
        Cotisation::forceCreate(['user_id' => $this->user()->id, 'period' => $period, 'amount' => 500, 'status' => 'paid', 'reference' => 'COT-I2', 'recorded_by' => $admin->id]);
        Donation::forceCreate(['donor_name' => 'A', 'amount' => 2000, 'status' => 'paid', 'reference' => 'DON-I1', 'paid_at' => now()]);
        Donation::forceCreate(['donor_name' => 'B', 'amount' => 300, 'status' => 'paid', 'reference' => 'DON-I2', 'paid_at' => now(), 'recorded_by' => $admin->id]);
        Donation::forceCreate(['donor_name' => 'C', 'amount' => 9999, 'status' => 'pending', 'reference' => 'DON-I3']); // en attente : ignorée

        $fundraiser = $this->fundraiser();
        $this->paid($fundraiser, $member, 700);
        $this->paid($fundraiser, $member, 100, $admin);

        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/admin/stats/income?period={$period}")
            ->assertOk()
            ->assertJsonPath('month.cotisations.online', 1000)
            ->assertJsonPath('month.cotisations.cash', 500)
            ->assertJsonPath('month.donations.online', 2000)
            ->assertJsonPath('month.donations.cash', 300)
            ->assertJsonPath('month.fundraisers.online', 700)
            ->assertJsonPath('month.fundraisers.cash', 100)
            ->assertJsonPath('month.total.online', 3700)
            ->assertJsonPath('month.total.cash', 900)
            ->assertJsonPath('month.total.total', 4600)
            ->assertJsonPath('all_time.total.total', 4600);
    }
}