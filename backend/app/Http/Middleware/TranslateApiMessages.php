<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Traduit le champ « message » des réponses JSON quand la requête demande une autre langue que le français. */
class TranslateApiMessages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Langue demandée par le site (en-tête « Accept-Language »), français par défaut.
        $locale = $request->getPreferredLanguage(SetLocale::SUPPORTED) ?? 'fr';

        if ($locale === 'fr' || ! $response instanceof JsonResponse) {
            return $response;
        }

        $data = $response->getData(true);

        if (is_array($data) && isset($data['message']) && is_string($data['message'])) {
            $data['message'] = __($data['message'], [], $locale); // clé = texte français, valeur = texte anglais (lang/en.json)
            $response->setData($data);
        }

        return $response;
    }
}