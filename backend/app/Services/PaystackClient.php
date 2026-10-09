<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Appels HTTP vers l'API Paystack (jamais depuis le navigateur : la clé secrète reste sur le serveur). */
class PaystackClient
{
    private function http(): PendingRequest
    {
        $secret = config('mutuelle.paystack.secret_key');
        abort_if(! $secret, 503, 'Le paiement en ligne n\'est pas configuré.');

        return Http::withToken($secret)
            ->acceptJson()
            ->baseUrl(rtrim(config('mutuelle.paystack.base_url'), '/'))
            ->timeout(15);
    }

    /** Crée la transaction ; retourne les données Paystack (dont authorization_url). */
    public function initialize(array $payload): array
    {
        $response = $this->http()->post('/transaction/initialize', $payload);

        if (! $response->successful() || ! $response->json('status')) {
            Log::warning('Paystack : échec de initialize', [
                'http' => $response->status(),
                'message' => $response->json('message'),
            ]);
            abort(502, 'Le service de paiement est momentanément indisponible. Réessayez dans quelques instants.');
        }

        return $response->json('data') ?? [];
    }

    /** Interroge Paystack sur l'état réel d'une transaction. Retourne null si la réponse est inexploitable. */
    public function verify(string $reference): ?array
    {
        $response = $this->http()->get('/transaction/verify/'.rawurlencode($reference));

        if (! $response->successful() || ! $response->json('status')) {
            return null;
        }

        return $response->json('data');
    }
}