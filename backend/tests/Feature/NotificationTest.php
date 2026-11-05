<?php

namespace Tests\Feature;

use App\Models\Cotisation;
use App\Models\Message;
use App\Models\User;
use App\Services\MemberNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'member', string $status = 'active'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'status' => $status])->save();

        return $user;
    }

    public function test_a_new_fundraiser_notifies_active_members_only_and_only_once(): void
    {
        $a = $this->user();
        $this->user();
        $this->user('member', 'pending');
        Sanctum::actingAs($this->user('admin'));

        $id = $this->postJson('/api/v1/admin/fundraisers', ['title' => 'Puits du village'])->assertCreated()->json('id');

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $a->id, 'type' => 'collecte']);

        // Modifier la collecte ne renvoie pas de notification.
        $this->putJson("/api/v1/admin/fundraisers/{$id}", ['title' => 'Puits rénové'])->assertOk();
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_a_hidden_project_notifies_only_when_it_becomes_visible(): void
    {
        $a = $this->user();
        Sanctum::actingAs($this->user('admin'));

        $id = $this->postJson('/api/v1/admin/projects', ['title' => 'Chantier', 'status' => 'en_cours', 'is_active' => false])
            ->assertCreated()
            ->json('id');
        $this->assertDatabaseCount('notifications', 0);

        $this->putJson("/api/v1/admin/projects/{$id}", ['is_active' => true])->assertOk();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $a->id, 'type' => 'activity']);
    }

    public function test_cotisation_reminders_skip_members_who_paid_and_are_not_repeated(): void
    {
        $paid = $this->user();
        $late = $this->user();
        Cotisation::forceCreate([
            'user_id' => $paid->id, 'period' => now()->format('Y-m'), 'amount' => 1000,
            'status' => 'paid', 'reference' => 'COT-N1',
        ]);

        $this->artisan('notifications:cotisation-reminders', ['--force' => true])->assertSuccessful();
        $this->artisan('notifications:cotisation-reminders', ['--force' => true])->assertSuccessful(); // pas de doublon le même jour

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $late->id, 'type' => 'cotisation_reminder']);
    }

    public function test_no_reminder_is_sent_on_a_day_without_reminders(): void
    {
        $this->user();
        $this->travelTo(Carbon::parse('2026-11-10 09:00'));

        $this->artisan('notifications:cotisation-reminders')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_a_member_reads_only_their_own_notifications(): void
    {
        $a = $this->user();
        $b = $this->user();
        app(MemberNotifier::class)->broadcast('publication', 'Nouvelle information : Test', 'Texte', '/actualites/test');

        Sanctum::actingAs($a);
        $list = $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('items.0.kind', 'publication')
            ->assertJsonPath('items.0.read', false);
        $id = $list->json('items.0.id');

        // Un autre membre ne peut pas marquer cette notification comme lue.
        Sanctum::actingAs($b);
        $this->postJson("/api/v1/notifications/{$id}/read")->assertNoContent();
        $this->assertNull(DB::table('notifications')->where('id', $id)->value('read_at'));

        Sanctum::actingAs($a);
        $this->postJson("/api/v1/notifications/{$id}/read")->assertNoContent();
        $this->getJson('/api/v1/notifications')->assertJsonPath('unread', 0)->assertJsonPath('items.0.read', true);
    }

    public function test_read_all_and_the_unread_message_summary(): void
    {
        $a = $this->user();
        $b = $this->user();
        $notifier = app(MemberNotifier::class);
        $notifier->broadcast('communique', 'Nouveau communiqué : A', '', '/actualites/a');
        $notifier->broadcast('publication', 'Nouvelle information : B', '', '/actualites/b');
        Message::forceCreate(['sender_id' => $b->id, 'recipient_id' => $a->id, 'body' => 'Bonjour à tous']);

        Sanctum::actingAs($a);
        $this->getJson('/api/v1/notifications')
            ->assertJsonPath('unread', 2)
            ->assertJsonPath('messages.unread', 1)
            ->assertJsonPath('messages.latest.sender_id', $b->id);

        $this->postJson('/api/v1/notifications/read-all')->assertNoContent();
        $this->getJson('/api/v1/notifications')->assertJsonPath('unread', 0);
    }
}