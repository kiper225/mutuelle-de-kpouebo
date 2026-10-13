<?php

namespace Tests\Feature;

use App\Models\Face;
use App\Models\HeroSlide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'member'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_public_content_returns_only_active_items_in_order(): void
    {
        HeroSlide::forceCreate(['title' => 'Seconde', 'position' => 1, 'is_active' => true]);
        HeroSlide::forceCreate(['title' => 'Première', 'position' => 0, 'is_active' => true]);
        HeroSlide::forceCreate(['title' => 'Masquée', 'position' => 2, 'is_active' => false]);
        Face::forceCreate(['name' => 'Visible', 'position' => 0, 'is_active' => true]);
        Face::forceCreate(['name' => 'Cachée', 'position' => 1, 'is_active' => false]);

        $this->getJson('/api/v1/home-content')
            ->assertOk()
            ->assertJsonCount(2, 'slides')
            ->assertJsonPath('slides.0.title', 'Première')
            ->assertJsonPath('slides.1.title', 'Seconde')
            ->assertJsonCount(1, 'faces')
            ->assertJsonPath('faces.0.name', 'Visible');
    }

    public function test_only_admin_can_manage_slides_and_faces(): void
    {
        Sanctum::actingAs($this->user('member'));

        $this->postJson('/api/v1/admin/hero-slides', ['title' => 'Test'])->assertForbidden();
        $this->postJson('/api/v1/admin/faces', ['name' => 'Test'])->assertForbidden();
    }

    public function test_admin_creates_a_slide_with_an_image(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->user('admin'));

        $response = $this->postJson('/api/v1/admin/hero-slides', [
            'title' => 'Bienvenue', 'cta_label' => 'Faire un don', 'cta_to' => '/don',
            'image' => UploadedFile::fake()->image('slide.jpg', 1600, 700),
        ])->assertCreated();

        $slide = HeroSlide::firstOrFail();
        $this->assertSame('/don', $slide->cta_to);
        $this->assertSame(1, $slide->position);
        Storage::disk('public')->assertExists($slide->image_path);
        $this->assertNotNull($response->json('image_url'));
    }

    public function test_unsafe_links_are_rejected(): void
    {
        Sanctum::actingAs($this->user('admin'));

        foreach (['javascript:alert(1)', '//evil.com', 'https://evil.com'] as $link) {
            $this->postJson('/api/v1/admin/hero-slides', ['title' => 'Test', 'cta_to' => $link])->assertUnprocessable();
        }
    }

    public function test_slides_can_be_reordered_and_deleted_with_their_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('slides/a.jpg', 'x');
        $a = HeroSlide::forceCreate(['title' => 'A', 'position' => 0, 'is_active' => true, 'image_path' => 'slides/a.jpg']);
        $b = HeroSlide::forceCreate(['title' => 'B', 'position' => 1, 'is_active' => true]);
        Sanctum::actingAs($this->user('admin'));

        $this->postJson('/api/v1/admin/hero-slides/reorder', ['ids' => [$b->id, $a->id]])
            ->assertOk()
            ->assertJsonPath('0.title', 'B');

        $this->deleteJson("/api/v1/admin/hero-slides/{$a->id}")->assertNoContent();
        Storage::disk('public')->assertMissing('slides/a.jpg');
        $this->assertDatabaseCount('hero_slides', 1);
    }

    public function test_admin_can_manage_faces_and_remove_a_photo(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->user('admin'));

        $this->postJson('/api/v1/admin/faces', [
            'name' => 'Nom Prénom', 'role' => 'Doyen du village',
            'photo' => UploadedFile::fake()->image('p.jpg', 800, 1000),
        ])->assertCreated();

        $face = Face::firstOrFail();
        Storage::disk('public')->assertExists($face->photo_path);

        $this->putJson("/api/v1/admin/faces/{$face->id}", ['remove_photo' => true, 'is_active' => false])->assertOk();

        $face->refresh();
        $this->assertNull($face->photo_path);
        $this->assertFalse($face->is_active);
    }
}