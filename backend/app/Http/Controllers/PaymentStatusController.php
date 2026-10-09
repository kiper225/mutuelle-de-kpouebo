<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Services\PaymentSettler;
use App\Services\PaystackClient;

/**
 * État d'un paiement, utilisé par la page de retour du site.
 * Si le paiement est encore « en attente », on interroge Paystack (utile si la notification n'est pas encore arrivée,
 * ou en local où Paystack ne peut pas joindre le serveur). La référence aléatoire sert de secret : aucune donnée personnelle n'est renvoyée.
 */
class PaymentStatusController extends Controller
{
    public function __invoke(string $reference, PaymentSettler $settler, PaystackClient $paystack)
    {
        $record = $settler->find($reference);
        abort_if(! $record, 404, 'Paiement introuvable.');

        if ($record->status === $record::STATUS_PENDING && config('mutuelle.paystack.secret_key')) {
            $data = $paystack->verify($reference);

            if ($data) {
                $providerId = isset($data['id']) ? (string) $data['id'] : null;

                if (($data['status'] ?? null) === 'success') {
                    $settler->settle($record, true, $providerId, isset($data['amount']) ? intdiv((int) $data['amount'], 100) : null);
                } elseif (($data['status'] ?? null) === 'failed') {
                    $settler->settle($record, false, $providerId);
                }

                $record->refresh();
            }
        }

        return [
            'type' => $record instanceof Donation ? 'don' : 'cotisation',
            'reference' => $record->reference,
            'amount' => $record->amount,
            'status' => $record->status,
        ];
    }
}