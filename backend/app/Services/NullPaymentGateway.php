<?php

namespace App\Services;

use App\Models\Cotisation;

/**
 * Passerelle de remplacement : aucune transaction réelle.
 * À remplacer par une implémentation (CinetPay, PayDunya, etc.) en suivant
 * la documentation officielle du prestataire choisi.
 */
class NullPaymentGateway implements PaymentGateway
{
    public function initiate(Cotisation $cotisation): array
    {
        return [
            'payment_url' => null,
            'message' => 'Passerelle de paiement non configurée.',
        ];
    }
}
