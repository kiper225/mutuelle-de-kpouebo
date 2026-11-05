<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Crée une notification pour chaque membre concerné (par défaut : tous les membres actifs). */
class MemberNotifier
{
    /**
     * @param Collection<int, int>|null $userIds destinataires ; null = tous les membres actifs
     * @param array<string, mixed> $extra données supplémentaires (ex. clé anti-doublon)
     * @return int nombre de notifications créées
     */
    public function broadcast(string $kind, string $title, string $body, string $url, ?Collection $userIds = null, array $extra = []): int
    {
        $ids = $userIds ?? User::members()->where('status', User::STATUS_ACTIVE)->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        $now = now();
        $data = json_encode(
            ['kind' => $kind, 'title' => $title, 'body' => $body, 'url' => $url] + $extra,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        foreach ($ids->chunk(500) as $chunk) {
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

        return $ids->count();
    }
}