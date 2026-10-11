<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'member'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    private function publication(User $author, string $title, string $slug, string $category, ?string $publishedAt = 'now'): void
    {
        Publication::forceCreate([
            'user_id' => $author->id, 'title' => $title, 'slug' => $slug, 'category' => $category, 'body' => 'Texte',
            'published_at' => $publishedAt ? now()->subDay() : null,
        ]);
    }

    public function test_guests_search_published_publications_only(): void
    {
        $admin = $this->user('admin');
        $this->publication($admin, 'Puits du village', 'puits', 'projet_en_cours');
        $this->publication($admin, 'Communiqué sur le puits', 'communique', 'communique');
        $this->publication($admin, 'Puits brouillon', 'brouillon', 'actualite', null);

        $this->getJson('/api/v1/search?q=puits')
            ->assertOk()
            ->assertJsonPath('authenticated', false)
            ->assertJsonCount(1, 'activities')
            ->assertJsonCount(1, 'publications')
            ->assertJsonCount(0, 'messages')
            ->assertJsonCount(0, 'members');
    }

    public function test_a_too_short_query_is_rejected(): void
    {
        $this->getJson('/api/v1/search?q=a')->assertUnprocessable();
    }

    public function test_a_member_only_finds_their_own_messages_and_other_members_by_name(): void
    {
        $alice = $this->user();
        $bob = $this->user();
        $carol = $this->user();
        $bob->forceFill(['name' => 'Zorglub Dupont'])->save();

        Message::forceCreate(['sender_id' => $alice->id, 'recipient_id' => $bob->id, 'body' => 'Réunion zorglub samedi']);
        Message::forceCreate(['sender_id' => $bob->id, 'recipient_id' => $carol->id, 'body' => 'zorglub confidentiel']);

        Sanctum::actingAs($alice);

        $this->getJson('/api/v1/search?q=zorglub')
            ->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.with.id', $bob->id)
            ->assertJsonCount(1, 'members');
    }
}