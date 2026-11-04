<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatManagementTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role = 'member'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_blocking_stops_messages_in_both_directions_until_unblocked(): void
    {
        $a = $this->member();
        $b = $this->member();

        Sanctum::actingAs($a);
        $this->postJson("/api/v1/conversations/{$b->id}/block")->assertOk()->assertJsonPath('blocked_by_me', true);
        $this->postJson("/api/v1/conversations/{$b->id}", ['body' => 'Salut'])->assertForbidden(); // le bloqueur doit débloquer d'abord

        Sanctum::actingAs($b);
        $this->postJson("/api/v1/conversations/{$a->id}", ['body' => 'Salut'])->assertForbidden();
        $this->assertSame(0, Message::count());

        Sanctum::actingAs($a);
        $this->deleteJson("/api/v1/conversations/{$b->id}/block")->assertOk()->assertJsonPath('blocked_by_me', false);
        $this->postJson("/api/v1/conversations/{$b->id}", ['body' => 'Salut'])->assertCreated();
    }

    public function test_the_administration_cannot_be_blocked_by_a_member(): void
    {
        $admin = $this->member('admin');
        $member = $this->member();

        Sanctum::actingAs($member);
        $this->postJson("/api/v1/conversations/{$admin->id}/block")->assertUnprocessable();
        $this->getJson("/api/v1/members/{$admin->id}")
            ->assertOk()
            ->assertJsonPath('can_block', false)
            ->assertJsonPath('is_admin', true);
    }

    public function test_the_member_profile_shows_only_public_information(): void
    {
        $a = $this->member();
        $b = $this->member();
        $b->forceFill(['mutuelle_role' => 'Trésorier', 'joined_at' => '2024-03-01'])->save();

        Sanctum::actingAs($a);

        $this->getJson("/api/v1/members/{$b->id}")
            ->assertOk()
            ->assertJsonPath('mutuelle_role', 'Trésorier')
            ->assertJsonPath('member_since', '2024')
            ->assertJsonPath('blocked_by_me', false)
            ->assertJsonMissingPath('phone')
            ->assertJsonMissingPath('birth_date');
    }

    public function test_presence_is_hidden_between_blocked_members(): void
    {
        $a = $this->member();
        $b = $this->member();
        $b->forceFill(['last_seen_at' => now()])->save();
        Message::forceCreate(['sender_id' => $a->id, 'recipient_id' => $b->id, 'body' => 'Salut']);

        Sanctum::actingAs($a);
        $this->getJson('/api/v1/conversations')->assertJsonPath('0.user.online', true);

        $this->postJson("/api/v1/conversations/{$b->id}/block")->assertOk();
        $this->getJson('/api/v1/conversations')->assertJsonPath('0.user.online', false);
    }

    public function test_deleting_a_discussion_only_hides_it_for_the_member_who_deleted_it(): void
    {
        $a = $this->member();
        $b = $this->member();
        Message::forceCreate(['sender_id' => $a->id, 'recipient_id' => $b->id, 'body' => 'Ancien message']);

        $this->travel(1)->minutes();
        Sanctum::actingAs($b);
        $this->deleteJson("/api/v1/conversations/{$a->id}")->assertNoContent();
        $this->getJson('/api/v1/conversations')->assertOk()->assertJsonCount(0);
        $this->getJson('/api/v1/messages/unread-count')->assertJsonPath('count', 0);

        Sanctum::actingAs($a);
        $this->getJson('/api/v1/conversations')->assertJsonCount(1); // l'autre membre garde la sienne

        $this->travel(1)->minutes();
        $this->postJson("/api/v1/conversations/{$b->id}", ['body' => 'Nouveau message'])->assertCreated();

        Sanctum::actingAs($b);
        $this->getJson('/api/v1/conversations')->assertJsonCount(1);
        $this->getJson("/api/v1/conversations/{$a->id}")
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.body', 'Nouveau message');
    }

    public function test_stickers_are_validated_and_flagged(): void
    {
        $a = $this->member();
        $b = $this->member();
        Sanctum::actingAs($a);

        $this->postJson("/api/v1/conversations/{$b->id}", ['body' => 'bravo', 'kind' => 'sticker'])
            ->assertCreated()
            ->assertJsonPath('kind', 'sticker');
        $this->getJson('/api/v1/conversations')->assertJsonPath('0.last_message.kind', 'sticker');

        $this->postJson("/api/v1/conversations/{$b->id}", ['body' => 'inconnu', 'kind' => 'sticker'])->assertUnprocessable();

        $this->postJson("/api/v1/conversations/{$b->id}", ['body' => 'Bonjour 😀'])
            ->assertCreated()
            ->assertJsonPath('kind', 'text');
    }
}