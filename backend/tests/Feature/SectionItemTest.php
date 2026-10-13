<?php

namespace Tests\Feature;

use App\Models\SectionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SectionItemTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'member'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_public_content_exposes_active_items_and_the_managed_sections(): void
    {
        SectionItem::forceCreate(['section' => 'bureau', 'title' => 'Visible', 'position' => 0, 'is_active' => true]);
        SectionItem::forceCreate(['section' => 'bureau', 'title' => 'Cachée', 'position' => 1, 'is_active' => false]);
        SectionItem::forceCreate(['section' => 'axes', 'title' => 'Seul axe masqué', 'position' => 0, 'is_active' => false]);

        $this->getJson('/api/v1/home-content')
            ->assertOk()
            ->assertJsonCount(1, 'bureau')
            ->assertJsonPath('bureau.0.title', 'Visible')
            ->assertJsonCount(0, 'axes')
            ->assertJsonPath('managed.bureau', true)
            ->assertJsonPath('managed.axes', true) // section gérée même si tout est masqué
            ->assertJsonPath('managed.valeurs', false);
    }

    public function test_only_admin_can_manage_sections(): void
    {
        Sanctum::actingAs($this->user('member'));

        $this->postJson('/api/v1/admin/sections/bureau', ['title' => 'Test'])->assertForbidden();
    }

    public function test_admin_adds_a_board_member_with_a_photo(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->user('admin'));

        $this->postJson('/api/v1/admin/sections/bureau', [
            'title' => 'Nom Prénom', 'subtitle' => 'Président', 'text' => 'Ensemble, nous construirons.',
            'image' => UploadedFile::fake()->image('p.jpg', 600, 800),
        ])->assertCreated();

        $item = SectionItem::firstOrFail();
        $this->assertSame('bureau', $item->section);
        $this->assertSame(1, $item->position);
        Storage::disk('public')->assertExists($item->image_path);
    }

    public function test_only_the_board_section_accepts_a_photo(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $this->postJson('/api/v1/admin/sections/axes', [
            'title' => 'Axe', 'image' => UploadedFile::fake()->image('p.jpg'),
        ])->assertUnprocessable();
    }

    public function test_an_unknown_section_is_not_found(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $this->getJson('/api/v1/admin/sections/inconnue')->assertNotFound();
    }

    public function test_an_item_cannot_be_edited_through_another_section(): void
    {
        $item = SectionItem::forceCreate(['section' => 'bureau', 'title' => 'A', 'position' => 0, 'is_active' => true]);
        Sanctum::actingAs($this->user('admin'));

        $this->putJson("/api/v1/admin/sections/axes/{$item->id}", ['title' => 'Piraté'])->assertNotFound();
        $this->assertSame('A', $item->fresh()->title);
    }

    public function test_items_can_be_reordered_hidden_and_deleted_with_their_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('sections/bureau/a.jpg', 'x');
        $a = SectionItem::forceCreate(['section' => 'bureau', 'title' => 'A', 'position' => 0, 'is_active' => true, 'image_path' => 'sections/bureau/a.jpg']);
        $b = SectionItem::forceCreate(['section' => 'bureau', 'title' => 'B', 'position' => 1, 'is_active' => true]);
        Sanctum::actingAs($this->user('admin'));

        $this->postJson('/api/v1/admin/sections/bureau/reorder', ['ids' => [$b->id, $a->id]])
            ->assertOk()
            ->assertJsonPath('0.title', 'B');

        $this->putJson("/api/v1/admin/sections/bureau/{$b->id}", ['is_active' => false])->assertOk();
        $this->assertFalse($b->fresh()->is_active);

        $this->deleteJson("/api/v1/admin/sections/bureau/{$a->id}")->assertNoContent();
        Storage::disk('public')->assertMissing('sections/bureau/a.jpg');
    }
}