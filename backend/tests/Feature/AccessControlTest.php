<?php

namespace Tests\Feature;

use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $status = 'active'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'status' => $status])->save();

        return $user;
    }

    private function publication(User $author, string $slug, ?string $publishedAt): Publication
    {
        return Publication::forceCreate([
            'user_id' => $author->id, 'title' => $slug, 'slug' => $slug,
            'category' => 'actualite', 'body' => 'Texte', 'published_at' => $publishedAt,
        ]);
    }

    public function test_guests_only_see_published_publications(): void
    {
        $admin = $this->user('admin');
        $this->publication($admin, 'visible', now()->subDay());
        $this->publication($admin, 'brouillon', null);

        $this->getJson('/api/v1/publications')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/publications/brouillon')->assertNotFound();
    }

    public function test_member_cannot_create_a_publication(): void
    {
        Sanctum::actingAs($this->user('member'));

        $this->postJson('/api/v1/admin/publications', [
            'title' => 'Test', 'category' => 'actualite', 'body' => 'Texte',
        ])->assertForbidden();
    }

    public function test_admin_can_create_a_publication(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $this->postJson('/api/v1/admin/publications', [
            'title' => 'Nouveau projet', 'category' => 'projet_en_cours', 'body' => 'Texte',
        ])->assertCreated()->assertJsonPath('slug', 'nouveau-projet');

        $this->getJson('/api/v1/publications')->assertJsonCount(1, 'data');
    }

    public function test_pending_member_cannot_use_member_area(): void
    {
        Sanctum::actingAs($this->user('member', 'pending'));

        $this->getJson('/api/v1/cotisations')->assertForbidden();
    }

    public function test_member_cannot_read_admin_stats(): void
    {
        Sanctum::actingAs($this->user('member'));

        $this->getJson('/api/v1/admin/stats')->assertForbidden();
    }
}
