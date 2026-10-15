<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Demande de lien par email ou téléphone.
     * La réponse est identique que le compte existe ou non : on ne révèle pas qui est membre.
     */
    public function forgot(Request $request)
    {
        $data = $request->validate(['login' => ['required', 'string', 'max:255']]);

        $login = str_contains($data['login'], '@')
            ? $data['login']
            : (preg_replace('/[\s.\-()]+/', '', $data['login']) ?? $data['login']);

        $user = User::where('email', $login)->orWhere('phone', $login)->first();

        // Le lien est envoyé à l'adresse email du compte ; sans email, rien n'est envoyé (le bureau peut réinitialiser).
        if ($user && $user->email) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return response()->json([
            'message' => 'Si un compte correspond et possède une adresse email, un lien de réinitialisation vient de lui être envoyé.',
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password])->save(); // haché automatiquement
            $user->tokens()->delete(); // déconnecte tous les appareils
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['Ce lien est invalide ou a expiré. Refaites une demande.'],
            ]);
        }

        return response()->json(['message' => 'Mot de passe modifié. Vous pouvez maintenant vous connecter.']);
    }
}