<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        return User::members()
            ->when($request->query('status'), fn ($query, $s) => $query->where('status', $s))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('phone', 'like', $like));
            })
            ->latest()
            ->paginate(20);
    }

    /** Dossier complet d'un membre (utilisé pour la modification et l'impression des documents). */
    public function show(User $user)
    {
        $this->ensureMember($user);

        return $user;
    }

    /** Correction du dossier par le bureau (notamment le poste déclaré à l'inscription). */
    public function update(Request $request, User $user)
    {
        $this->ensureMember($user);

        if ($request->has('phone')) {
            $request->merge(['phone' => preg_replace('/[\s.\-()]+/', '', (string) $request->input('phone'))]);
        }

        $data = $request->validate([
            // État civil (obligatoire)
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'first_names' => ['sometimes', 'required', 'string', 'max:150'],
            'gender' => ['sometimes', 'required', Rule::in(User::GENDERS)],
            'birth_date' => ['sometimes', 'required', 'date', 'before:today', 'after:1900-01-01'],
            'birth_place' => ['sometimes', 'required', 'string', 'max:150'],
            'children_count' => ['sometimes', 'required', 'integer', 'min:0', 'max:30'],
            'photo' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            // Informations personnelles
            'marital_status' => ['sometimes', 'nullable', Rule::in(User::MARITAL_STATUSES)],
            'profession' => ['sometimes', 'nullable', 'string', 'max:150'],
            'residence' => ['sometimes', 'nullable', 'string', 'max:150'],
            'mutuelle_role' => ['sometimes', 'nullable', 'string', 'max:100'],
            // Compte
            'phone' => ['sometimes', 'required', 'regex:/^\+?[0-9]{8,15}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', 'confirmed', 'string', 'min:8'],
        ]);

        $user->fill($data);

        if (isset($data['last_name']) || isset($data['first_names'])) {
            $user->name = trim($user->first_names.' '.mb_strtoupper($user->last_name));
        }

        $user->save();

        return $user;
    }

    /** Remplace la photo d'un membre (envoyée déjà recadrée par le site). */
    public function updatePhoto(Request $request, User $user)
    {
        $this->ensureMember($user);

        $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);

        $old = $user->photo_path;
        $user->photo_path = $request->file('photo')->store('photos', 'public');
        $user->save();

        if ($old) {
            Storage::disk('public')->delete($old);
        }

        return $user;
    }

    /** Validation de l'adhésion : attribue le numéro de membre et la date d'adhésion (une seule fois). */
    public function approve(User $user)
    {
        $this->ensureMember($user);

        $user->forceFill([
            'status' => User::STATUS_ACTIVE,
            'member_number' => $user->member_number ?? $this->newMemberNumber($user),
            'joined_at' => $user->joined_at ?? now(),
        ])->save();

        return $user;
    }

    public function suspend(User $user)
    {
        $this->ensureMember($user);
        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();
        $user->tokens()->delete(); // déconnecte immédiatement le membre

        return $user;
    }

    private function newMemberNumber(User $user): string
    {
        return sprintf('%s-%s-%04d', config('mutuelle.member_prefix', 'MBR'), now()->format('Y'), $user->id);
    }

    private function ensureMember(User $user): void
    {
        abort_if($user->isAdmin(), 422, 'Action impossible sur un administrateur.');
    }
}