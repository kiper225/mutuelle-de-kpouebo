<?php

namespace Tests\Feature;

use App\Models\Cotisation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;


class GroupMessageTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'member', string $status = 'active'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'status' => $status])->save();

        return $user;
    }

    public function test_admin_sends_a_group_message_to_all_active_members_only(): void
    {
        $this->withoutExceptionHandling();

        $admin = $this->user('admin');
        $a = $this->user();
        $this->user();
        $pending = $this->user('member', 'pending');
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/admin/group-messages', ['audience' => 'all', 'body' => 'Réunion samedi'])
            ->assertCreated()
            ->assertJsonPath('recipients_count', 2);

        $this->assertSame(2, Message::count());
        $this->assertDatabaseHas('messages', ['sender_id' => $admin->id, 'recipient_id' => $a->id, 'body' => 'Réunion samedi']);
        $this->assertDatabaseMissing('messages', ['recipient_id' => $pending->id]);
        $this->assertDatabaseMissing('messages', ['recipient_id' => $admin->id]);

        // le membre voit une notification (message non lu)
        Sanctum::actingAs($a);
        $this->getJson('/api/v1/messages/unread-count')->assertJsonPath('count', 1);
    }

    public function test_the_overdue_audience_skips_members_who_paid_this_month(): void
    {
        $paid = $this->user();
        $late = $this->user();
        Cotisation::forceCreate([
            'user_id' => $paid->id, 'period' => now()->format('Y-m'), 'amount' => 1000,
            'status' => 'paid', 'reference' => 'COT-GM1',
        ]);
        Sanctum::actingAs($this->user('admin'));

        $this->getJson('/api/v1/admin/group-messages/audience?audience=overdue')->assertOk()->assertJsonPath('count', 1);

        $this->postJson('/api/v1/admin/group-messages', ['audience' => 'overdue', 'body' => 'Rappel'])->assertCreated();

        $this->assertDatabaseHas('messages', ['recipient_id' => $late->id]);
        $this->assertDatabaseMissing('messages', ['recipient_id' => $paid->id]);
    }

    public function test_a_selection_only_reaches_active_members(): void
    {
        $active = $this->user();
        $pending = $this->user('member', 'pending');
        Sanctum::actingAs($this->user('admin'));

        $this->postJson('/api/v1/admin/group-messages', [
            'audience' => 'selected', 'member_ids' => [$active->id, $pending->id], 'body' => 'Bonjour',
        ])->assertCreated()->assertJsonPath('recipients_count', 1);

        $this->assertDatabaseMissing('messages', ['recipient_id' => $pending->id]);

        $this->postJson('/api/v1/admin/group-messages', [
            'audience' => 'selected', 'member_ids' => [$pending->id], 'body' => 'Bonjour',
        ])->assertUnprocessable();
    }

    public function test_a_member_cannot_send_group_messages(): void
    {
        Sanctum::actingAs($this->user());

        $this->postJson('/api/v1/admin/group-messages', ['audience' => 'all', 'body' => 'Spam'])->assertForbidden();
    }

    public function test_the_history_shows_how_many_members_read_the_message(): void
    {
        $this->user();
        $this->user();
        Sanctum::actingAs($this->user('admin'));
        $this->postJson('/api/v1/admin/group-messages', ['audience' => 'all', 'body' => 'Annonce'])->assertCreated();

        Message::first()->forceFill(['read_at' => now()])->save();

        $this->getJson('/api/v1/admin/group-messages')
            ->assertOk()
            ->assertJsonPath('data.0.recipients_count', 2)
            ->assertJsonPath('data.0.read_count', 1);
    }
}