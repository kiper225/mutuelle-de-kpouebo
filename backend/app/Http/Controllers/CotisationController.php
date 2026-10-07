<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use App\Services\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CotisationController extends Controller
{
    /** Historique des cotisations du membre connecté. */
    public function index(Request $request)
    {
        return $request->user()->cotisations()->orderByDesc('period')->paginate(24);
    }

    /** Démarre le paiement d'une cotisation (le statut passe à "paid" via le webhook de la passerelle). */
    public function store(Request $request, PaymentGateway $gateway)
    {
        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')],
            'provider' => ['required', Rule::in(Cotisation::PROVIDERS)],
        ]);

        $amount = (int) config('mutuelle.cotisation_amount');
        abort_if($amount <= 0, 422, 'Le montant de la cotisation n\'est pas encore configuré.');

        $user = $request->user();
        $cotisation = $user->cotisations()->where('period', $data['period'])->first();

        abort_if($cotisation?->status === Cotisation::STATUS_PAID, 422, 'Cette cotisation est déjà réglée.');

        $cotisation ??= new Cotisation(['period' => $data['period']]);
        $cotisation->forceFill([
            'user_id' => $user->id,
            'amount' => $amount,
            'provider' => $data['provider'],
            'status' => Cotisation::STATUS_PENDING,
            'reference' => Cotisation::newReference(), // nouvelle référence à chaque tentative
        ])->save();

        return response()->json([
            'cotisation' => $cotisation,
            'payment' => $gateway->initiate($cotisation),
        ], 201);
    }
}
