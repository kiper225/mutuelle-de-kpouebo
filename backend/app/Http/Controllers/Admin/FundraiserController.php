<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\Cotisation;
use App\Models\Fundraiser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FundraiserController extends Controller
{
    public function index()
    {
        return Fundraiser::query()
            ->withSum(['contributions as raised_amount' => fn ($q) => $q->where('status', Contribution::STATUS_PAID)], 'amount')
            ->withCount(['contributions as contributions_count' => fn ($q) => $q->where('status', Contribution::STATUS_PAID)])
            ->orderByDesc('created_at')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules($request, true));

        $fundraiser = new Fundraiser(Arr::only($data, ['title', 'summary', 'description', 'goal_amount', 'suggested_amount', 'starts_on', 'ends_on']));
        $fundraiser->is_active = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $fundraiser->image_path = $request->file('image')->store('fundraisers', 'public');
        }

        $fundraiser->save();

        return response()->json($fundraiser, 201);
    }

    /** Envoi d'image : POST avec le champ `_method=PUT` (formulaire multipart). */
    public function update(Request $request, Fundraiser $fundraiser)
    {
        $data = $request->validate($this->rules($request, false));

        $fundraiser->fill(Arr::only($data, ['title', 'summary', 'description', 'goal_amount', 'suggested_amount', 'starts_on', 'ends_on']));

        if ($request->has('is_active')) {
            $fundraiser->is_active = $request->boolean('is_active');
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($fundraiser);
            $fundraiser->image_path = $request->file('image')->store('fundraisers', 'public');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($fundraiser);
            $fundraiser->image_path = null;
        }

        $fundraiser->save();

        return $fundraiser;
    }

    public function destroy(Fundraiser $fundraiser)
    {
        // On garde la comptabilité : une collecte qui a reçu des paiements ne se supprime pas, elle se masque.
        abort_if(
            $fundraiser->contributions()->where('status', Contribution::STATUS_PAID)->exists(),
            422,
            'Impossible de supprimer : cette collecte a reçu des contributions. Masquez-la plutôt (décochez « Afficher sur le site »).'
        );

        $this->deleteImage($fundraiser);
        $fundraiser->delete();

        return response()->noContent();
    }

    public function contributions(Fundraiser $fundraiser)
    {
        return $fundraiser->contributions()->with('user:id,name,phone')->latest()->paginate(20);
    }

    /** Enregistre une contribution reçue en espèces (ou hors ligne) pour un membre actif. */
    public function recordOffline(Request $request, Fundraiser $fundraiser)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'amount' => ['required', 'integer', 'min:1'],
            'provider' => ['nullable', Rule::in([...Cotisation::PROVIDERS, 'cash'])],
        ]);

        $member = User::members()->where('status', User::STATUS_ACTIVE)->find($data['user_id']);
        abort_if(! $member, 422, 'Membre introuvable ou inactif.');

        $contribution = new Contribution(['amount' => $data['amount'], 'provider' => $data['provider'] ?? 'cash']);
        $contribution->forceFill([
            'fundraiser_id' => $fundraiser->id,
            'user_id' => $member->id,
            'status' => Contribution::STATUS_PAID,
            'reference' => Contribution::newReference(),
            'paid_at' => now(),
            'recorded_by' => $request->user()->id,
        ])->save();

        return response()->json($contribution->load('user:id,name,phone'), 201);
    }

    private function rules(Request $request, bool $creating): array
    {
        return [
            'title' => [$creating ? 'required' : 'sometimes', 'required', 'string', 'max:150'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:300'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'goal_amount' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:2000000000'],
            'suggested_amount' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:2000000000'],
            'starts_on' => ['sometimes', 'nullable', 'date'],
            'ends_on' => ['sometimes', 'nullable', 'date', Rule::when($request->filled('starts_on'), 'after_or_equal:starts_on')],
            'is_active' => ['sometimes', 'boolean'],
            'remove_image' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    private function deleteImage(Fundraiser $fundraiser): void
    {
        if ($fundraiser->image_path) {
            Storage::disk('public')->delete($fundraiser->image_path);
        }
    }
}