<?php

namespace App\Http\Controllers;

use App\Services\PaymentSettler;
use Illuminate\Http\Request;

/**
 * Notification de paiement générique signée (HMAC-SHA256, en-tête X-Signature).
 * Utile pour un autre prestataire ou pour des tests ; Paystack utilise PaystackWebhookController.
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentSettler $settler)
    {
        $secret = config('mutuelle.webhook_secret');
        abort_if(! $secret, 503, 'Webhook non configuré.');

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        abort_unless(hash_equals($expected, (string) $request->header('X-Signature')), 401, 'Signature invalide.');

        $data = $request->validate([
            'reference' => ['required', 'string'],
            'status' => ['required', 'in:success,failed'],
            'provider_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $record = $settler->find($data['reference']);
        abort_if(! $record, 404, 'Référence inconnue.');

        $settler->settle($record, $data['status'] === 'success', $data['provider_reference'] ?? null);

        return response()->json(['message' => 'OK']);
    }
}