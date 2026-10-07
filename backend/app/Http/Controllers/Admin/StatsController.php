<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cotisation;
use App\Models\Publication;
use App\Models\User;

class StatsController extends Controller
{
    public function __invoke()
    {
        $period = now()->format('Y-m');

        return [
            'members_active' => User::members()->where('status', User::STATUS_ACTIVE)->count(),
            'members_pending' => User::members()->where('status', User::STATUS_PENDING)->count(),
            'cotisations_month_total' => (int) Cotisation::where('period', $period)->where('status', Cotisation::STATUS_PAID)->sum('amount'),
            'publications_published' => Publication::published()->count(),
            'period' => $period,
            'currency' => config('mutuelle.currency'),
        ];
    }
}
