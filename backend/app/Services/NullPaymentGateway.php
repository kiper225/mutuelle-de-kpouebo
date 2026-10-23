<?php

namespace App\Services;

use App\Models\Contribution;
use App\Models\Cotisation;
use App\Models\Donation;

/**
 * Passerelle de remplacement : aucune transaction réelle.
 */
class NullPaymentGateway implements PaymentGateway
{
    public function initiate(Cotisation $cotisation): array
    {
        return ['payment_url' => null, 'message' => 'Passerelle de paiement non configurée.'];
    }

    public function initiateDonation(Donation $donation): array
    {
        return ['payment_url' => null, 'message' => 'Passerelle de paiement non configurée.'];
    }

    public function initiateContribution(Contribution $contribution): array
    {
        return ['payment_url' => null, 'message' => 'Passerelle de paiement non configurée.'];
    }
}