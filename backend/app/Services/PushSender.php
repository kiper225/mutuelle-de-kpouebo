<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Version minimale : le Web Push est désactivé.
 * Remplacez ce fichier par la version complète (message « Web Push ») quand les clés VAPID seront installées.
 */
class PushSender
{
    public function enabled(): bool
    {
        return false;
    }

    public function notify(Collection $userIds, array $payload): void
    {
        // Rien à envoyer tant que le Web Push n'est pas activé.
    }
}