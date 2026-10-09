<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberProfileTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'member', string $status = 'active'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'status' => $status])->save();

        return $user;
    }

    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Kouassi', 'first_names' => 'Yao Jean',
            'birth_date' => '1985-04-12', 'birth_place' => 'Kpouèbo', 'children_count' => 3,
            'photo' => UploadedFile::fake()->image('photo.jpg', 300, 400),
            'marital_status' => 'marie', 'profession' => 'Enseignant', 'residence' => 'Abidjan',
            'phone' => '0700000000', 'password' => 'motdepasse1', 'password_confirmation' => 'motdepasse1',
            'accept_terms' => true,
            'email' => 'membre@example.com',
        ], $overrides);
    }

    public function test_registration_stores_the_civil_status_and_the_photo(): void
    {
        Storage::fake('public');

        $this->postJson('/api/v1/register', $this->registrationData())->assertCreated();

        $user = User::where('phone', '0700000000')->firstOrFail();
        $this->assertSame('Kpouèbo', $user->birth_place);
        $this->assertSame(3, $user->children_count);
        $this->assertSame('Membre', $user->mutuelle_role);
        $this->assertSame('pending', $user->status);
        Storage::disk('public')->assertExists($user->photo_path);
    }

    public function test_registration_requires_birth_place_children_and_photo(): void
    {
        $data = $this->registrationData();
        unset($data['birth_place'], $data['children_count'], $data['photo']);

        $this->postJson('/api/v1/register', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['birth_place', 'children_count', 'photo']);
    }

    public function test_approval_assigns_a_member_number_once(): void
    {
        config(['mutuelle.member_prefix' => 'MBR']);
        $pending = $this->user('member', 'pending');
        Sanctum::actingAs($this->user('admin'));

        $this->postJson("/api/v1/admin/members/{$pending->id}/approve")->assertOk();

        $first = $pending->fresh();
        $this->assertMatchesRegularExpression('/^MBR-\d{4}-\d{4}$/', $first->member_number);
        $this->assertNotNull($first->joined_at);

        $this->postJson("/api/v1/admin/members/{$pending->id}/approve")->assertOk();
        $this->assertSame($first->member_number, $pending->fresh()->member_number);
    }

    public function test_admin_can_correct_the_declared_role(): void
    {
        $member = $this->user();
        Sanctum::actingAs($this->user('admin'));

        $this->putJson("/api/v1/admin/members/{$member->id}", ['mutuelle_role' => 'Trésorier'])->assertOk();

        $this->assertSame('Trésorier', $member->fresh()->mutuelle_role);
    }

    public function test_an_invalid_marital_status_is_rejected(): void
    {
        $member = $this->user();
        Sanctum::actingAs($this->user('admin'));

        $this->putJson("/api/v1/admin/members/{$member->id}", ['marital_status' => 'inconnu'])->assertUnprocessable();
    }

    public function test_a_member_cannot_read_or_edit_other_members_files(): void
    {
        $other = $this->user();
        Sanctum::actingAs($this->user('member'));

        $this->getJson("/api/v1/admin/members/{$other->id}")->assertForbidden();
        $this->putJson("/api/v1/admin/members/{$other->id}", ['mutuelle_role' => 'Président'])->assertForbidden();
    }
}