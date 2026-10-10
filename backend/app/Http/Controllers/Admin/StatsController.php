<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cotisation;
use App\Models\Donation;
use App\Models\Publication;
use App\Models\User;

class StatsController extends Controller
{
    public function __invoke()
    {
        $period = now()->format('Y-m');
        $paidDonations = Donation::where('status', Donation::STATUS_PAID);

        return [
            'members_active' => User::members()->where('status', User::STATUS_ACTIVE)->count(),
            'members_pending' => User::members()->where('status', User::STATUS_PENDING)->count(),
            'cotisations_month_total' => (int) Cotisation::where('period', $period)->where('status', Cotisation::STATUS_PAID)->sum('amount'),
            'donations_month_total' => (int) (clone $paidDonations)->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'donations_total' => (int) (clone $paidDonations)->sum('amount'),
            'publications_published' => Publication::published()->count(),
            'period' => $period,
            'currency' => config('mutuelle.currency'),
        ];
    }

    /** Totaux des 12 derniers mois (le dernier élément est le mois en cours), pour les graphiques. */
    public function monthly()
    {
        $start = now()->startOfMonth()->subMonths(11);
        $months = collect(range(11, 0))
            ->map(fn (int $i) => now()->startOfMonth()->subMonths($i)->format('Y-m'))
            ->values();

        $cotisations = Cotisation::where('status', Cotisation::STATUS_PAID)
            ->whereIn('period', $months)
            ->selectRaw('period, SUM(amount) as total')
            ->groupBy('period')
            ->pluck('total', 'period');

        $donations = Donation::where('status', Donation::STATUS_PAID)
            ->where('paid_at', '>=', $start)
            ->get(['amount', 'paid_at'])
            ->groupBy(fn (Donation $d) => $d->paid_at->format('Y-m'))
            ->map(fn ($group) => (int) $group->sum('amount'));

        $newMembers = User::members()
            ->whereNotNull('joined_at')
            ->where('joined_at', '>=', $start)
            ->get(['joined_at'])
            ->groupBy(fn (User $u) => $u->joined_at->format('Y-m'))
            ->map(fn ($group) => $group->count());

        return [
            'months' => $months,
            'cotisations' => $months->map(fn (string $m) => (int) ($cotisations[$m] ?? 0))->values(),
            'donations' => $months->map(fn (string $m) => (int) ($donations[$m] ?? 0))->values(),
            'new_members' => $months->map(fn (string $m) => (int) ($newMembers[$m] ?? 0))->values(),
            'currency' => config('mutuelle.currency'),
        ];
    }
}