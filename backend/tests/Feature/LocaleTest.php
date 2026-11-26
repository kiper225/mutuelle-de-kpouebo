<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MemberNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $locale = 'fr'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'member', 'status' => 'active', 'locale' => $locale])->save();

        return $user;
    }

    public function test_a_member_can_choose_the_language_of_their_account(): void
    {
        $member = $this->member();
        Sanctum::actingAs($member);

        $this->postJson('/api/v1/me/locale', ['locale' => 'en'])->assertNoContent();
        $this->assertSame('en', $member->fresh()->locale);

        $this->postJson('/api/v1/me/locale', ['locale' => 'es'])->assertUnprocessable();
    }

    public function test_notifications_are_written_in_each_members_language(): void
    {
        $fr = $this->member('fr');
        $en = $this->member('en');

        app(MemberNotifier::class)->broadcast(
            'communique',
            fn (string $locale) => __('notifications.titled', ['label' => __('notifications.communique', [], $locale), 'title' => 'Test'], $locale),
            '',
            '/actualites/test',
        );

        $title = fn (User $user) => json_decode(DB::table('notifications')->where('notifiable_id', $user->id)->value('data'), true)['title'];

        $this->assertSame('Nouveau communiqué : Test', $title($fr));
        $this->assertSame('New announcement: Test', $title($en));
    }

    public function test_api_messages_follow_the_language_of_the_request(): void
    {
        $member = $this->member();
        Sanctum::actingAs($member);

        // Les requêtes de test portent par défaut « Accept-Language: en-us » (valeur de Symfony) : on précise la langue voulue.
        $this->withHeaders(['Accept-Language' => 'fr'])
            ->postJson("/api/v1/conversations/{$member->id}", ['body' => 'Salut'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Vous ne pouvez pas vous écrire à vous-même.');

        $this->withHeaders(['Accept-Language' => 'en'])
            ->postJson("/api/v1/conversations/{$member->id}", ['body' => 'Hi'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'You cannot message yourself.');
    }
}