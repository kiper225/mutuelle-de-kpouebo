<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;

/** Modification de son propre profil. Les champs d'identité sont réservés au bureau. */
class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'marital_status' => ['sometimes', 'nullable', Rule::in(User::MARITAL_STATUSES)],
            'children_count' => ['sometimes', 'required', 'integer', 'min:0', 'max:30'],
            'profession' => ['sometimes', 'nullable', 'string', 'max:150'],
            'residence' => ['sometimes', 'nullable', 'string', 'max:150'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // Seuls les champs validés ci-dessus sont appliqués : nom, poste, rôle, statut… sont ignorés.
        $user->fill(collect($data)->except('photo')->all());

        if ($request->hasFile('photo')) {
            $old = $user->photo_path;
            $user->photo_path = $request->file('photo')->store('photos', 'public');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
        }

        $user->save();

        return $user->fresh();
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:sanctum'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();
        $user->password = $data['password']; // haché automatiquement
        $user->save();

        // Déconnecte les autres appareils et garde la session courante.
        $current = $user->currentAccessToken();
        $tokens = $user->tokens();
        if ($current instanceof PersonalAccessToken) {
            $tokens->where('id', '!=', $current->id);
        }
        $tokens->delete();

        return response()->json(['message' => 'Mot de passe modifié.']);
    }
}