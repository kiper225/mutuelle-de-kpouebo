<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicationSocialTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'member'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    private function publication(User $author, bool $published = true): Publication
    {
        return Publication::forceCreate([
            'user_id' => $author->id, 'title' => 'Titre', 'slug' => 'titre-'.uniqid(), 'category' => 'actualite',
            'body' => 'Texte', 'published_at' => $published ? now()->subHour() : null,
        ]);
    }

    public function test_a_member_can_like_and_unlike_a_publication(): void
    {
        $publication = $this->publication($this->user('admin'));
        Sanctum::actingAs($this->user());

        $this->postJson("/api/v1/publications/{$publication->id}/like")
            ->assertOk()->assertJsonPath('liked', true)->assertJsonPath('likes_count', 1);

        $this->getJson('/api/v1/publications')
            ->assertJsonPath('data.0.likes_count', 1)
            ->assertJsonPath('data.0.liked_by_me', true);

        $this->postJson("/api/v1/publications/{$publication->id}/like")
            ->assertOk()->assertJsonPath('liked', false)->assertJsonPath('likes_count', 0);
    }

    public function test_guests_cannot_like(): void
    {
        $publication = $this->publication($this->user('admin'));

        $this->postJson("/api/v1/publications/{$publication->id}/like")->assertUnauthorized();
    }

    public function test_an_unpublished_publication_cannot_be_liked(): void
    {
        $publication = $this->publication($this->user('admin'), false);
        Sanctum::actingAs($this->user());

        $this->postJson("/api/v1/publications/{$publication->id}/like")->assertNotFound();
    }

    public function test_the_comment_counter_ignores_hidden_comments(): void
    {
        $author = $this->user('admin');
        $publication = $this->publication($author);
        Comment::forceCreate(['publication_id' => $publication->id, 'user_id' => $author->id, 'body' => 'Visible']);
        Comment::forceCreate(['publication_id' => $publication->id, 'user_id' => $author->id, 'body' => 'Masqué', 'hidden_at' => now()]);

        $this->getJson('/api/v1/publications')
            ->assertOk()
            ->assertJsonPath('data.0.comments_count', 1)
            ->assertJsonPath('data.0.likes_count', 0);
    }
}