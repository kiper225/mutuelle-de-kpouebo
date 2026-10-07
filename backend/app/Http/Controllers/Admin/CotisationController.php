<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cotisation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CotisationController extends Controller
{
    public function index(Request $request)
    {
        return Cotisation::with('user:id,name,phone')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('period'), fn ($q, $p) => $q->where('period', $p))
            ->when($request->query('user_id'), fn ($q, $u) => $q->where('user_id', $u))
            ->latest()
            ->paginate(30);
    }

    /** Membres actifs n'ayant pas réglé la période (par défaut le mois en cours). */
    public function overdue(Request $request)
    {
        $period = $request->validate(['period' => ['nullable', 'date_format:Y-m']])['period'] ?? now()->format('Y-m');

        return User::members()
            ->where('status', User::STATUS_ACTIVE)
            ->whereDoesntHave('cotisations', fn ($q) => $q->where('period', $period)->where('status', Cotisation::STATUS_PAID))
            ->orderBy('name')
            ->paginate(50);
    }

    /** Enregistre un paiement fait hors ligne (espèces, virement…). */
    public function recordOffline(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'period' => ['required', 'date_format:Y-m'],
            'amount' => ['nullable', 'integer', 'min:1'],
            'provider' => ['nullable', Rule::in([...Cotisation::PROVIDERS, 'cash'])],
        ]);

        $amount = $data['amount'] ?? (int) config('mutuelle.cotisation_amount');
        abort_if($amount <= 0, 422, 'Indiquez un montant (le montant par défaut n\'est pas configuré).');

        $cotisation = Cotisation::where('user_id', $data['user_id'])->where('period', $data['period'])->first();
        abort_if($cotisation?->status === Cotisation::STATUS_PAID, 422, 'Cette cotisation est déjà réglée.');

        $cotisation ??= new Cotisation(['period' => $data['period']]);
        $cotisation->forceFill([
            'user_id' => $data['user_id'],
            'amount' => $amount,
            'provider' => $data['provider'] ?? 'cash',
            'status' => Cotisation::STATUS_PAID,
            'reference' => $cotisation->reference ?? Cotisation::newReference(),
            'paid_at' => now(),
            'recorded_by' => $request->user()->id,
        ])->save();

        return response()->json($cotisation->load('user:id,name,phone'), 201);
    }
}
