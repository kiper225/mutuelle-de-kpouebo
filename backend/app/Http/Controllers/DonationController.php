<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use App\Models\Donation;
use App\Services\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DonationController extends Controller
{
    /** Don ouvert à tous : le membre connecté n'a pas besoin de ressaisir son nom. */
    public function store(Request $request, PaymentGateway $gateway)
    {
        $user = $request->user('sanctum'); // null si le visiteur n'est pas connecté

        $request->merge(['donor_phone' => preg_replace('/[\s.\-()]+/', '', (string) $request->input('donor_phone')) ?: null]);

        $data = $request->validate([
            'donor_name' => [$user ? 'nullable' : 'required', 'string', 'max:150'],
            'donor_phone' => ['nullable', 'regex:/^\+?[0-9]{8,15}$/'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'amount' => ['required', 'integer', 'min:'.max(1, (int) config('mutuelle.donation_min'))],
            'provider' => ['required', Rule::in(Cotisation::PROVIDERS)],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        // Transaction : si la passerelle refuse (email manquant, service indisponible), le don n'est pas conservé.
        return DB::transaction(function () use ($data, $user, $gateway) {
            $donation = new Donation($data);
            $donation->forceFill([
                'user_id' => $user?->id,
                'donor_name' => $data['donor_name'] ?? $user->name,
                'status' => Donation::STATUS_PENDING,
                'reference' => Donation::newReference(),
            ])->save();

            return response()->json([
                'donation' => $donation->only(['reference', 'amount', 'status']),
                'payment' => $gateway->initiateDonation($donation),
            ], 201);
        });
    }
}