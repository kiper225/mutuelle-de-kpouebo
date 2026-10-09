<?php

namespace App\Http\Controllers;

use App\Services\PaymentSettler;
use Illuminate\Http\Request;

/**
 * Notifications Paystack. La signature est un HMAC-SHA512 du corps brut, calculé avec la clé secrète,
 * envoyé dans l'en-tête x-paystack-signature.
 */
class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentSettler $settler)
    {
        $secret = config('mutuelle.paystack.secret_key');
        abort_if(! $secret, 503, 'Webhook non configuré.');

        $expected = hash_hmac('sha512', $request->getContent(), $secret);
        abort_unless(hash_equals($expected, (string) $request->header('x-paystack-signature')), 401, 'Signature invalide.');

        if ($request->input('event') !== 'charge.success') {
            return response()->json(['message' => 'Événement ignoré.']); // Paystack attend un 200
        }

        $data = (array) $request->input('data', []);
        $record = $settler->find((string) ($data['reference'] ?? ''));

        if ($record && ($data['currency'] ?? 'XOF') === 'XOF') {
            $settler->settle(
                $record,
                true,
                isset($data['id']) ? (string) $data['id'] : null,
                isset($data['amount']) ? intdiv((int) $data['amount'], 100) : null, // Paystack envoie le montant × 100
            );
        }

        return response()->json(['message' => 'OK']);
    }
}