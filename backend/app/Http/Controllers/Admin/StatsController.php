<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\Cotisation;
use App\Models\Donation;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

    /**
     * Entrées d'argent d'un mois (et depuis le début), par source et par canal :
     * « online » = payé sur le site ; « cash » = enregistré à la main par le bureau.
     * Cotisations : celles dues pour le mois. Dons et levées de fonds : sommes reçues pendant le mois.
     */
    public function income(Request $request)
    {
        $period = $request->validate(['period' => ['nullable', 'date_format:Y-m']])['period'] ?? now()->format('Y-m');

        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $cotisations = Cotisation::where('status', Cotisation::STATUS_PAID);
        $donations = Donation::where('status', Donation::STATUS_PAID);
        $contributions = Contribution::where('status', Contribution::STATUS_PAID);

        $month = $this->withTotal([
            'cotisations' => $this->split((clone $cotisations)->where('period', $period)),
            'donations' => $this->split((clone $donations)->whereBetween('paid_at', [$start, $end])),
            'fundraisers' => $this->split((clone $contributions)->whereBetween('paid_at', [$start, $end])),
        ]);

        $allTime = $this->withTotal([
            'cotisations' => $this->split($cotisations),
            'donations' => $this->split($donations),
            'fundraisers' => $this->split($contributions),
        ]);

        return [
            'period' => $period,
            'month' => $month,
            'all_time' => $allTime,
            'currency' => config('mutuelle.currency'),
        ];
    }

    /** @return array{online: int, cash: int, total: int} */
    private function split(Builder $query): array
    {
        $row = $query->selectRaw(
            'COALESCE(SUM(CASE WHEN recorded_by IS NULL THEN amount ELSE 0 END), 0) AS online, '
            .'COALESCE(SUM(CASE WHEN recorded_by IS NOT NULL THEN amount ELSE 0 END), 0) AS cash'
        )->first();

        $online = (int) $row->online;
        $cash = (int) $row->cash;

        return ['online' => $online, 'cash' => $cash, 'total' => $online + $cash];
    }

    private function withTotal(array $sources): array
    {
        $sum = fn (string $key) => array_sum(array_column($sources, $key));

        $sources['total'] = ['online' => $sum('online'), 'cash' => $sum('cash'), 'total' => $sum('total')];

        return $sources;
    }
}