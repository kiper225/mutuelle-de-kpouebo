<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cotisation;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DonationController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        return Donation::query()
            ->when($request->query('status'), fn ($query, $s) => $query->where('status', $s))
            ->when($q !== '', fn ($query) => $query->where('donor_name', 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->latest()
            ->paginate(30);
    }

    /** Enregistre un don reçu hors ligne (espèces, virement, Mobile Money direct…). */
    public function recordOffline(Request $request)
    {
        $data = $request->validate([
            'donor_name' => ['required', 'string', 'max:150'],
            'donor_phone' => ['nullable', 'string', 'max:20'],
            'amount' => ['required', 'integer', 'min:1'],
            'provider' => ['nullable', Rule::in([...Cotisation::PROVIDERS, 'cash'])],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $donation = new Donation($data);
        $donation->forceFill([
            'provider' => $data['provider'] ?? 'cash',
            'status' => Donation::STATUS_PAID,
            'reference' => Donation::newReference(),
            'paid_at' => now(),
            'recorded_by' => $request->user()->id,
        ])->save();

        return response()->json($donation, 201);
    }
}