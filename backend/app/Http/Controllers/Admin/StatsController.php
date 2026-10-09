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
}