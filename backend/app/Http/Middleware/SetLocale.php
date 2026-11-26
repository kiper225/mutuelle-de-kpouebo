<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Langue de la requête : celle que le site envoie dans l'en-tête « Accept-Language » (français par défaut). */
class SetLocale
{
    public const SUPPORTED = ['fr', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->getPreferredLanguage(self::SUPPORTED);

        app()->setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : 'fr');

        return $next($request);
    }
}