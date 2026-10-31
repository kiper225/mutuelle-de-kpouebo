<?php

namespace Tests\Feature;

use App\Models\Fundraiser;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'member'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    private function project(array $attributes = []): Project
    {
        return Project::forceCreate(array_merge(
            ['title' => 'Puits du village', 'status' => 'en_cours', 'is_active' => true, 'position' => 0],
            $attributes,
        ));
    }

    public function test_the_public_list_shows_only_active_projects_and_can_filter_by_status(): void
    {
        $this->project(['title' => 'Chantier A', 'status' => 'en_cours']);
        $this->project(['title' => 'Chantier B', 'status' => 'realise']);
        $this->project(['title' => 'Masqué', 'status' => 'en_cours', 'is_active' => false]);

        $this->getJson('/api/v1/projects')->assertOk()->assertJsonCount(2);

        $this->getJson('/api/v1/projects?status=realise')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Chantier B');
    }

    public function test_a_hidden_project_is_not_found(): void
    {
        $hidden = $this->project(['is_active' => false]);

        $this->getJson("/api/v1/projects/{$hidden->id}")->assertNotFound();
    }

    public function test_the_detail_shows_the_linked_collection_only_while_it_is_visible(): void
    {
        $fundraiser = Fundraiser::forceCreate(['title' => 'Collecte du puits', 'is_active' => true]);
        $project = $this->project(['fundraiser_id' => $fundraiser->id]);

        $this->getJson("/api/v1/projects/{$project->id}")->assertOk()->assertJsonPath('fundraiser.title', 'Collecte du puits');

        $fundraiser->forceFill(['is_active' => false])->save();

        $this->getJson("/api/v1/projects/{$project->id}")->assertOk()->assertJsonPath('fundraiser', null);
    }

    public function test_admin_creates_a_project_with_an_image(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->user('admin'));

        $this->postJson('/api/v1/admin/projects', [
            'title' => 'Électrification', 'summary' => 'Poser des lampadaires', 'status' => 'en_cours', 'progress' => 40,
            'location' => 'Quartier centre', 'budget' => 2500000, 'starts_on' => '2026-11-01', 'ends_on' => '2027-03-31',
            'image' => UploadedFile::fake()->image('c.jpg', 1280, 720),
        ])->assertCreated();

        $project = Project::firstOrFail();
        $this->assertSame(40, $project->progress);
        $this->assertSame(2500000, $project->budget);
        $this->assertSame(1, $project->position);
        Storage::disk('public')->assertExists($project->image_path);
    }

    public function test_invalid_values_are_rejected(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $this->postJson('/api/v1/admin/projects', ['title' => 'Test', 'status' => 'inconnu'])->assertUnprocessable();
        $this->postJson('/api/v1/admin/projects', ['title' => 'Test', 'status' => 'en_cours', 'progress' => 150])->assertUnprocessable();
        $this->postJson('/api/v1/admin/projects', ['title' => 'Test', 'status' => 'en_cours', 'fundraiser_id' => 999])->assertUnprocessable();
        $this->postJson('/api/v1/admin/projects', [
            'title' => 'Test', 'status' => 'en_cours', 'starts_on' => '2026-12-31', 'ends_on' => '2026-11-01',
        ])->assertUnprocessable();
    }

    public function test_projects_can_be_reordered_and_deleted_with_their_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('projects/a.jpg', 'x');
        $a = $this->project(['title' => 'A', 'position' => 0, 'image_path' => 'projects/a.jpg']);
        $b = $this->project(['title' => 'B', 'position' => 1]);
        Sanctum::actingAs($this->user('admin'));

        $this->postJson('/api/v1/admin/projects/reorder', ['ids' => [$b->id, $a->id]])
            ->assertOk()
            ->assertJsonPath('0.title', 'B');

        $this->deleteJson("/api/v1/admin/projects/{$a->id}")->assertNoContent();
        Storage::disk('public')->assertMissing('projects/a.jpg');
    }

    public function test_a_member_cannot_manage_projects(): void
    {
        Sanctum::actingAs($this->user());

        $this->postJson('/api/v1/admin/projects', ['title' => 'Piratage', 'status' => 'en_cours'])->assertForbidden();
    }
}