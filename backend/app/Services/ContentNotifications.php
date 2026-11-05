<?php

namespace App\Services;

use App\Models\Fundraiser;
use App\Models\Project;
use App\Models\Publication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Notifie les membres quand un contenu est mis en ligne, une seule fois par contenu. */
class ContentNotifications
{
    public function __construct(private MemberNotifier $notifier)
    {
    }

    public function publication(Publication $pub): void
    {
        // Déjà notifiée, ou pas encore en ligne (brouillon, date de publication future).
        if ($pub->notified_at !== null || ! Publication::published()->whereKey($pub->getKey())->exists()) {
            return;
        }

        $category = Str::lower((string) $pub->category);

        [$kind, $label] = match (true) {
            str_contains($category, 'communi') => ['communique', 'Nouveau communiqué'],
            str_contains($category, 'projet'),
            str_contains($category, 'realis'),
            str_contains($category, 'activ'),
            str_contains($category, 'event'),
            str_contains($category, 'evenement') => ['activity', 'Nouvelle activité'],
            default => ['publication', 'Nouvelle information'],
        };

        // Si votre page de détail utilise l'identifiant au lieu du « slug », remplacez $pub->slug par $pub->getKey().
        $this->send($pub, $kind, "{$label} : {$pub->title}", $this->excerpt($pub->excerpt ?? $pub->body ?? ''), '/actualites/'.$pub->slug);
    }

    public function project(Project $project): void
    {
        if ($project->notified_at !== null || ! $project->is_active) {
            return;
        }

        $this->send($project, 'activity', 'Nouvelle activité : '.$project->title, $this->excerpt($project->summary ?? ''), '/chantiers/'.$project->getKey());
    }

    public function fundraiser(Fundraiser $fundraiser): void
    {
        if ($fundraiser->notified_at !== null || ! $fundraiser->is_active) {
            return;
        }

        $this->send($fundraiser, 'collecte', 'Nouvelle levée de fonds : '.$fundraiser->title, $this->excerpt($fundraiser->summary ?? ''), '/collectes/'.$fundraiser->getKey());
    }

    private function send(Model $model, string $kind, string $title, string $body, string $url): void
    {
        // On marque le contenu et on crée les notifications ensemble : tout ou rien.
        DB::transaction(function () use ($model, $kind, $title, $body, $url) {
            $model->forceFill(['notified_at' => now()])->saveQuietly();
            $this->notifier->broadcast($kind, $title, $body, $url);
        });
    }

    private function excerpt(?string $text): string
    {
        return Str::limit(trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $text))), 140);
    }
}