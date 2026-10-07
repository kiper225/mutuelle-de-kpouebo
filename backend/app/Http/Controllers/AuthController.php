<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Demande d'adhésion : le compte reste "pending" jusqu'à validation par l'admin. */
    public function register(Request $request): JsonResponse
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);

        $data = $request->validate([
            'last_name' => ['required', 'string', 'max:100'],
            'first_names' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'regex:/^\+?[0-9]{8,15}$/', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'profession' => ['nullable', 'string', 'max:150'],
            'residence' => ['nullable', 'string', 'max:150'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'accept_terms' => ['accepted'],
        ]);

        $user = new User(collect($data)->except(['photo', 'accept_terms'])->all());
        $user->name = trim($data['first_names'].' '.mb_strtoupper($data['last_name']));

        if ($request->hasFile('photo')) {
            $user->photo_path = $request->file('photo')->store('photos', 'public');
        }

        $user->save(); // role = member, status = pending (valeurs par défaut)

        return response()->json([
            'message' => 'Demande envoyée. Un administrateur validera votre adhésion.',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = str_contains($data['login'], '@') ? $data['login'] : $this->normalizePhone($data['login']);

        $user = User::where('phone', $login)->orWhere('email', $login)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => ['Identifiants incorrects.']]);
        }

        if ($user->status === User::STATUS_PENDING) {
            return response()->json(['message' => 'Votre adhésion est en attente de validation.'], 403);
        }

        if ($user->status === User::STATUS_SUSPENDED) {
            return response()->json(['message' => 'Votre compte est suspendu.'], 403);
        }

        return response()->json([
            'token' => $user->createToken('web')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[\s.\-()]+/', '', $phone) ?? $phone;
    }
}
