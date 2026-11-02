<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

/** Signal de présence envoyé régulièrement par le site tant qu'il est ouvert et visible. */
class PresenceController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Au plus une écriture toutes les 20 secondes, pour ménager la base de données.
        if (! $user->last_seen_at || $user->last_seen_at->lt(now()->subSeconds(20))) {
            User::whereKey($user->id)->update(['last_seen_at' => now()]);
        }

        return response()->noContent();
    }
}