<?php

namespace App\Services;

use App\Models\Cotisation;
use App\Models\Donation;
use Illuminate\Support\Facades\Log;

/** Enregistre le résultat d'un paiement (cotisation « COT-… » ou don « DON-… »), quelle que soit la source. */
class PaymentSettler
{
    public function find(string $reference): Cotisation|Donation|null
    {
        return str_starts_with($reference, 'DON-')
            ? Donation::where('reference', $reference)->first()
            : Cotisation::where('reference', $reference)->first();
    }

    /**
     * @param int|null $paidAmount montant réellement payé en FCFA ; s'il ne correspond pas, le paiement n'est pas validé
     */
    public function settle(Cotisation|Donation $record, bool $success, ?string $providerReference = null, ?int $paidAmount = null): void
    {
        if ($record->status === $record::STATUS_PAID) {
            return; // déjà traité (idempotent)
        }

        if ($success && $paidAmount !== null && $paidAmount !== $record->amount) {
            Log::warning('Paiement ignoré : montant inattendu', [
                'reference' => $record->reference, 'attendu' => $record->amount, 'recu' => $paidAmount,
            ]);

            return;
        }

        $record->forceFill([
            'status' => $success ? $record::STATUS_PAID : $record::STATUS_FAILED,
            'provider_reference' => $providerReference,
            'paid_at' => $success ? now() : null,
        ])->save();
    }
}