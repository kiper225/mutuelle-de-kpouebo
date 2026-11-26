<?php

namespace App\Services;

use App\Models\User;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Crée une notification pour chaque membre concerné (par défaut : tous les membres actifs), avec une alerte Web Push, dans sa langue. */
class MemberNotifier
{
    public function __construct(private PushSender $push)
    {
    }

    /**
     * @param string|Closure(string): string $title texte, ou fonction qui reçoit la langue (« fr » ou « en ») et retourne le texte
     * @param string|Closure(string): string $body idem
     * @param Collection<int, int>|null $userIds destinataires ; null = tous les membres actifs
     * @param array<string, mixed> $extra données supplémentaires (ex. clé anti-doublon)
     * @return int nombre de notifications créées
     */
    public function broadcast(string $kind, string|Closure $title, string|Closure $body, string $url, ?Collection $userIds = null, array $extra = []): int
    {
        $ids = ($userIds ?? User::members()->where('status', User::STATUS_ACTIVE)->pluck('id'))->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $locales = User::whereIn('id', $ids)->pluck('locale', 'id');
        $now = now();
        $pushes = [];

        foreach ($ids->groupBy(fn ($id) => $locales[$id] ?? 'fr') as $locale => $group) {
            $text = [
                'title' => $title instanceof Closure ? $title($locale) : $title,
                'body' => $body instanceof Closure ? $body($locale) : $body,
            ];

            $data = json_encode(
                ['kind' => $kind, 'title' => $text['title'], 'body' => $text['body'], 'url' => $url] + $extra,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );

            foreach ($group->chunk(500) as $chunk) {
                DB::table('notifications')->insert($chunk->map(fn ($id) => [
                    'id' => (string) Str::uuid(),
                    'type' => $kind,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $id,
                    'data' => $data,
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            }

            $pushes[] = [
                $group->values(),
                ['title' => $text['title'], 'body' => $text['body'], 'url' => $url, 'kind' => $kind, 'tag' => $kind.'-'.$now->timestamp],
            ];
        }

        // Web Push : envoyé juste après la réponse, pour ne pas ralentir l'administrateur.
        if ($this->push->enabled()) {
            dispatch(function () use ($pushes) {
                foreach ($pushes as [$group, $payload]) {
                    $this->push->notify($group, $payload);
                }
            })->afterResponse();
        }

        return $ids->count();
    }
}