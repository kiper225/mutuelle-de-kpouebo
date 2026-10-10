<?php

namespace Tests\Feature;

use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'member', array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_a_member_can_update_their_profile(): void
    {
        $member = $this->user();
        Sanctum::actingAs($member);

        $this->postJson('/api/v1/me/profile', [
            'profession' => 'Agriculteur', 'residence' => 'Kpouèbo', 'children_count' => 2, 'marital_status' => 'marie',
        ])->assertOk();

        $member->refresh();
        $this->assertSame('Agriculteur', $member->profession);
        $this->assertSame(2, $member->children_count);
        $this->assertSame('marie', $member->marital_status);
    }

    public function test_identity_fields_cannot_be_changed_by_the_member(): void
    {
        $member = $this->user();
        $member->forceFill(['last_name' => 'KOUASSI', 'mutuelle_role' => 'Membre'])->save();
        Sanctum::actingAs($member);

        $this->postJson('/api/v1/me/profile', [
            'last_name' => 'AUTRE', 'mutuelle_role' => 'Président', 'role' => 'admin', 'status' => 'pending', 'profession' => 'Maçon',
        ])->assertOk();

        $member->refresh();
        $this->assertSame('KOUASSI', $member->last_name);
        $this->assertSame('Membre', $member->mutuelle_role);
        $this->assertSame('member', $member->role);
        $this->assertSame('active', $member->status);
        $this->assertSame('Maçon', $member->profession);
    }

    public function test_changing_the_photo_replaces_the_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('photos/old.jpg', 'x');
        $member = $this->user('member', ['photo_path' => 'photos/old.jpg']);
        Sanctum::actingAs($member);

        $this->postJson('/api/v1/me/profile', ['photo' => UploadedFile::fake()->image('photo.jpg', 300, 400)])->assertOk();

        $member->refresh();
        $this->assertNotSame('photos/old.jpg', $member->photo_path);
        Storage::disk('public')->assertExists($member->photo_path);
        Storage::disk('public')->assertMissing('photos/old.jpg');
    }

    public function test_the_email_must_stay_unique(): void
    {
        $this->user('member', ['email' => 'pris@example.com']);
        Sanctum::actingAs($this->user());

        $this->postJson('/api/v1/me/profile', ['email' => 'pris@example.com'])->assertUnprocessable();
    }

    public function test_changing_the_password_requires_the_current_one(): void
    {
        $member = $this->user();
        Sanctum::actingAs($member);

        $this->postJson('/api/v1/me/password', [
            'current_password' => 'mauvais', 'password' => 'nouveaumdp1', 'password_confirmation' => 'nouveaumdp1',
        ])->assertUnprocessable();

        $this->postJson('/api/v1/me/password', [
            'current_password' => 'password', 'password' => 'nouveaumdp1', 'password_confirmation' => 'nouveaumdp1',
        ])->assertOk();

        $this->assertTrue(Hash::check('nouveaumdp1', $member->fresh()->password));
    }

    public function test_an_admin_can_replace_a_members_photo_and_phone(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('photos/old.jpg', 'x');
        $member = $this->user('member', ['photo_path' => 'photos/old.jpg']);
        $other = $this->user();
        $other->forceFill(['phone' => '0700000099'])->save();
        Sanctum::actingAs($this->user('admin'));

        $this->postJson("/api/v1/admin/members/{$member->id}/photo", ['photo' => UploadedFile::fake()->image('p.jpg', 300, 400)])->assertOk();
        Storage::disk('public')->assertMissing('photos/old.jpg');

        $this->putJson("/api/v1/admin/members/{$member->id}", ['phone' => '0700000011'])->assertOk();
        $this->assertSame('0700000011', $member->fresh()->phone);

        $this->putJson("/api/v1/admin/members/{$member->id}", ['phone' => '0700000099'])->assertUnprocessable();
    }

    public function test_an_admin_can_remove_a_publication_cover(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('publications/cover.jpg', 'x');
        $admin = $this->user('admin');
        $publication = Publication::forceCreate([
            'user_id' => $admin->id, 'title' => 'Test', 'slug' => 'test', 'category' => 'actualite',
            'body' => 'Texte', 'cover_path' => 'publications/cover.jpg', 'published_at' => now(),
        ]);
        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/admin/publications/{$publication->id}", ['remove_cover' => true])->assertOk();

        $this->assertNull($publication->fresh()->cover_path);
        Storage::disk('public')->assertMissing('publications/cover.jpg');
    }
}