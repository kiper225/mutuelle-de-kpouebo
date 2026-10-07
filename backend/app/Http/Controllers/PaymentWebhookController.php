<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use Illuminate\Http\Request;

/**
 * Notification de paiement envoyée par la passerelle.
 * ATTENTION : le nom de l'en-tête de signature, l'algorithme et les champs du corps
 * sont ici génériques. Ils doivent être alignés sur la documentation du prestataire retenu.
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request)
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

        $cotisation = Cotisation::where('reference', $data['reference'])->firstOrFail();

        if ($cotisation->status === Cotisation::STATUS_PAID) {
            return response()->json(['message' => 'Déjà traité.']); // idempotent
        }

        $cotisation->forceFill([
            'status' => $data['status'] === 'success' ? Cotisation::STATUS_PAID : Cotisation::STATUS_FAILED,
            'provider_reference' => $data['provider_reference'] ?? null,
            'paid_at' => $data['status'] === 'success' ? now() : null,
        ])->save();

        return response()->json(['message' => 'OK']);
    }
}
