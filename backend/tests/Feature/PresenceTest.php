<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PresenceTest extends TestCase
{
    use RefreshDatabase;

    private function member(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->forceFill(['role' => 'member', 'status' => 'active'])->save();

        return $user;
    }

    private function talk(User $a, User $b): void
    {
        Message::forceCreate(['sender_id' => $a->id, 'recipient_id' => $b->id, 'body' => 'Salut']);
    }

    public function test_the_heartbeat_records_the_last_seen_time(): void
    {
        $member = $this->member();

        $this->postJson('/api/v1/presence')->assertUnauthorized();

        Sanctum::actingAs($member);
        $this->postJson('/api/v1/presence')->assertNoContent();

        $this->assertNotNull($member->fresh()->last_seen_at);
    }

    public function test_a_recently_seen_member_appears_online_and_an_old_one_does_not(): void
    {
        $viewer = $this->member();
        $online = $this->member();
        $away = $this->member();
        $online->forceFill(['last_seen_at' => now()->subSeconds(30)])->save();
        $away->forceFill(['last_seen_at' => now()->subMinutes(10)])->save();
        $this->talk($viewer, $online);
        $this->talk($viewer, $away);

        Sanctum::actingAs($viewer);

        $rows = collect($this->getJson('/api/v1/conversations')->assertOk()->json())->keyBy('user.id');

        $this->assertTrue($rows[$online->id]['user']['online']);
        $this->assertFalse($rows[$away->id]['user']['online']);
    }

    public function test_hiding_your_status_hides_the_status_of_others_too(): void
    {
        // Je masque mon statut : je ne vois personne en ligne.
        $hidden = $this->member(['show_presence' => false]);
        $active = $this->member();
        $active->forceFill(['last_seen_at' => now()])->save();
        $this->talk($hidden, $active);

        Sanctum::actingAs($hidden);
        $this->getJson('/api/v1/conversations')->assertJsonPath('0.user.online', false);

        // L'autre masque le sien : je ne le vois pas en ligne.
        $viewer = $this->member();
        $shy = $this->member(['show_presence' => false]);
        $shy->forceFill(['last_seen_at' => now()])->save();
        $this->talk($viewer, $shy);

        Sanctum::actingAs($viewer);
        $this->getJson('/api/v1/conversations')->assertJsonPath('0.user.online', false);
    }

    public function test_the_thread_and_the_directory_expose_the_flag_but_never_the_timestamp(): void
    {
        $viewer = $this->member();
        $other = $this->member(['name' => 'Zorglub Test']);
        $other->forceFill(['last_seen_at' => now()])->save();

        Sanctum::actingAs($viewer);

        $this->getJson("/api/v1/conversations/{$other->id}")
            ->assertOk()
            ->assertJsonPath('user.online', true)
            ->assertJsonMissingPath('user.last_seen_at');

        $this->getJson('/api/v1/directory?q=Zorglub')
            ->assertOk()
            ->assertJsonPath('0.online', true)
            ->assertJsonMissingPath('0.last_seen_at');
    }

    public function test_a_member_can_hide_their_online_status(): void
    {
        $member = $this->member();
        Sanctum::actingAs($member);

        $this->postJson('/api/v1/me/profile', ['show_presence' => false])->assertOk();

        $this->assertFalse($member->fresh()->show_presence);
    }

    public function test_an_offline_member_shows_how_long_ago_they_were_active(): void
    {
        $viewer = $this->member();
        $online = $this->member();
        $recent = $this->member();
        $old = $this->member();
        $shy = $this->member(['show_presence' => false]);

        $online->forceFill(['last_seen_at' => now()])->save();
        $recent->forceFill(['last_seen_at' => now()->subMinutes(5)])->save();
        $old->forceFill(['last_seen_at' => now()->subDays(3)])->save();
        $shy->forceFill(['last_seen_at' => now()->subMinutes(5)])->save();

        foreach ([$online, $recent, $old, $shy] as $other) {
            $this->talk($viewer, $other);
        }

        Sanctum::actingAs($viewer);
        $rows = collect($this->getJson('/api/v1/conversations')->assertOk()->json())->keyBy('user.id');

        $this->assertTrue($rows[$online->id]['user']['online']);
        $this->assertNull($rows[$online->id]['user']['last_seen_minutes']);

        $this->assertFalse($rows[$recent->id]['user']['online']);
        $this->assertSame(5, $rows[$recent->id]['user']['last_seen_minutes']);

        $this->assertNull($rows[$old->id]['user']['last_seen_minutes']);  // plus de 24 h : rien n'est affiché
        $this->assertNull($rows[$shy->id]['user']['last_seen_minutes']);  // statut masqué : rien n'est affiché
    }
}