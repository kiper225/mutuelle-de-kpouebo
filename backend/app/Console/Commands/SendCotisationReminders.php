<?php

namespace App\Console\Commands;

use App\Models\Cotisation;
use App\Models\User;
use App\Services\MemberNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendCotisationReminders extends Command
{
    protected $signature = 'notifications:cotisation-reminders {--force : Envoyer même si ce n\'est pas un jour de rappel}';

    protected $description = 'Rappelle aux membres actifs la cotisation du mois qu\'ils n\'ont pas encore réglée';

    public function handle(MemberNotifier $notifier): int
    {
        $today = now();
        $days = array_map('intval', (array) config('mutuelle.reminder_days', [1, 15, 25]));

        if (! $this->option('force') && ! in_array((int) $today->day, $days, true)) {
            $this->info("Pas de rappel prévu aujourd'hui (jours de rappel : ".implode(', ', $days).').');

            return self::SUCCESS;
        }

        $period = $today->format('Y-m');
        $key = "cotisation:{$period}:{$today->day}"; // un seul rappel par membre et par jour de rappel

        $alreadyReminded = DB::table('notifications')
            ->where('type', 'cotisation_reminder')
            ->where('data', 'like', '%"key":"'.$key.'"%')
            ->pluck('notifiable_id');

        $ids = User::members()
            ->where('status', User::STATUS_ACTIVE)
            ->whereDoesntHave('cotisations', fn ($q) => $q->where('period', $period)->where('status', Cotisation::STATUS_PAID))
            ->whereNotIn('id', $alreadyReminded)
            ->pluck('id');

        $month = $today->copy()->locale('fr')->translatedFormat('F Y');

        $count = $notifier->broadcast(
            'cotisation_reminder',
            'Rappel de cotisation',
            "Votre cotisation de {$month} n'est pas encore réglée. Vous pouvez payer en ligne en quelques clics.",
            '/espace',
            $ids,
            ['key' => $key],
        );

        $this->info("{$count} rappel(s) envoyé(s).");

        return self::SUCCESS;
    }
}