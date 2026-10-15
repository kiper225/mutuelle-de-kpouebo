<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\MemberResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'member', array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_a_reset_link_is_sent_when_asking_with_the_email(): void
    {
        Notification::fake();
        $user = $this->user('member', ['email' => 'membre@example.com']);

        $this->postJson('/api/v1/forgot-password', ['login' => 'membre@example.com'])->assertOk();

        Notification::assertSentTo($user, MemberResetPassword::class);
    }

    public function test_a_reset_link_is_sent_when_asking_with_the_phone(): void
    {
        Notification::fake();
        $user = $this->user('member', ['email' => 'membre@example.com', 'phone' => '0700000001']);

        $this->postJson('/api/v1/forgot-password', ['login' => '07 00 00 00 01'])->assertOk();

        Notification::assertSentTo($user, MemberResetPassword::class);
    }

    public function test_nothing_is_sent_for_an_unknown_account_or_an_account_without_email(): void
    {
        Notification::fake();
        $this->user('member', ['email' => null, 'phone' => '0700000003']);

        $unknown = $this->postJson('/api/v1/forgot-password', ['login' => 'inconnu@example.com'])->assertOk();
        $noEmail = $this->postJson('/api/v1/forgot-password', ['login' => '0700000003'])->assertOk();

        Notification::assertNothingSent();
        // même réponse dans les deux cas : on ne révèle pas si le compte existe
        $this->assertSame($unknown->json('message'), $noEmail->json('message'));
    }

    public function test_a_valid_token_changes_the_password_and_logs_the_member_out(): void
    {
        $user = $this->user();
        $user->createToken('web');
        $token = Password::createToken($user);

        $this->postJson('/api/v1/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => 'nouveaumdp1', 'password_confirmation' => 'nouveaumdp1',
        ])->assertOk();

        $this->assertTrue(Hash::check('nouveaumdp1', $user->fresh()->password));
        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_an_invalid_token_is_refused(): void
    {
        $user = $this->user();

        $this->postJson('/api/v1/reset-password', [
            'token' => 'faux-jeton', 'email' => $user->email,
            'password' => 'nouveaumdp1', 'password_confirmation' => 'nouveaumdp1',
        ])->assertUnprocessable();

        $this->assertFalse(Hash::check('nouveaumdp1', $user->fresh()->password));
    }

    public function test_an_admin_can_generate_a_temporary_password_that_works_for_login(): void
    {
        $member = $this->user('member', ['phone' => '0700000002']);
        Sanctum::actingAs($this->user('admin'));

        $temporary = $this->postJson("/api/v1/admin/members/{$member->id}/reset-password")
            ->assertOk()
            ->json('temporary_password');

        $this->assertSame(10, strlen($temporary));

        $this->postJson('/api/v1/login', ['login' => '0700000002', 'password' => $temporary])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_a_member_cannot_reset_another_members_password(): void
    {
        $other = $this->user();
        Sanctum::actingAs($this->user());

        $this->postJson("/api/v1/admin/members/{$other->id}/reset-password")->assertForbidden();
    }
}