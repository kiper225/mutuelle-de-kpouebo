<?php

namespace App\Services;

use App\Models\Contribution;
use App\Models\Cotisation;
use App\Models\Donation;
use Illuminate\Support\Facades\Log;

/** Enregistre le résultat d'un paiement (cotisation « COT-… », don « DON-… » ou contribution « COL-… »). */
class PaymentSettler
{
    public function find(string $reference): Cotisation|Donation|Contribution|null
    {
        return match (true) {
            str_starts_with($reference, 'DON-') => Donation::where('reference', $reference)->first(),
            str_starts_with($reference, 'COL-') => Contribution::where('reference', $reference)->first(),
            default => Cotisation::where('reference', $reference)->first(),
        };
    }

    /**
     * @param int|null $paidAmount montant réellement payé en FCFA ; s'il ne correspond pas, le paiement n'est pas validé
     */
    public function settle(Cotisation|Donation|Contribution $record, bool $success, ?string $providerReference = null, ?int $paidAmount = null): void
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