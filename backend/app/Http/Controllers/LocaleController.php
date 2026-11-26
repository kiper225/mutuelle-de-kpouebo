<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /** Enregistre la langue du membre : ses notifications et alertes seront écrites dans cette langue. */
    public function update(Request $request)
    {
        $data = $request->validate(['locale' => ['required', Rule::in(['fr', 'en'])]]);

        $request->user()->forceFill(['locale' => $data['locale']])->save();

        return response()->noContent();
    }
}