<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Cotisation;
use App\Models\Fundraiser;
use App\Services\PaymentGateway;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FundraiserController extends Controller
{
    /** Collectes affichées sur le site, avec les sommes déjà collectées. */
    public function index()
    {
        return $this->withTotals(Fundraiser::where('is_active', true))
            ->orderByDesc('created_at')
            ->get();
    }

    public function show(Request $request, int $id)
    {
        $fundraiser = $this->withTotals(Fundraiser::where('is_active', true))->findOrFail($id);

        // Connecté : on indique ce que le membre a déjà versé.
        if ($user = $request->user('sanctum')) {
            $fundraiser->setAttribute('my_total', (int) $fundraiser->contributions()
                ->where('user_id', $user->id)
                ->where('status', Contribution::STATUS_PAID)
                ->sum('amount'));
        }

        return $fundraiser;
    }

    /** Un membre actif démarre une contribution ; le statut passe à « payée » via la confirmation de la passerelle. */
    public function contribute(Request $request, Fundraiser $fundraiser, PaymentGateway $gateway)
    {
        abort_unless($fundraiser->is_active, 404);
        abort_unless($fundraiser->is_open, 422, 'Cette collecte n\'est pas ouverte aux contributions.');

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:'.max(1, (int) config('mutuelle.donation_min'))],
            'provider' => ['required', Rule::in(Cotisation::PROVIDERS)],
        ]);

        $user = $request->user();

        // Transaction : si la passerelle refuse, la tentative n'est pas conservée.
        return DB::transaction(function () use ($data, $fundraiser, $user, $gateway) {
            $contribution = new Contribution($data);
            $contribution->forceFill([
                'fundraiser_id' => $fundraiser->id,
                'user_id' => $user->id,
                'status' => Contribution::STATUS_PENDING,
                'reference' => Contribution::newReference(),
            ])->save();

            return response()->json([
                'contribution' => $contribution->only(['reference', 'amount', 'status']),
                'payment' => $gateway->initiateContribution($contribution),
            ], 201);
        });
    }

    private function withTotals(Builder $query): Builder
    {
        return $query
            ->withSum(['contributions as raised_amount' => fn ($q) => $q->where('status', Contribution::STATUS_PAID)], 'amount')
            ->withCount(['contributions as contributions_count' => fn ($q) => $q->where('status', Contribution::STATUS_PAID)]);
    }
}