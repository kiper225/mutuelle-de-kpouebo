<?php

namespace App\Services;

use App\Models\Cotisation;
use App\Models\Donation;

interface PaymentGateway
{
    /** Démarre le paiement d'une cotisation. Retourne au minimum ['payment_url' => ?string]. */
    public function initiate(Cotisation $cotisation): array;

    /** Démarre le paiement d'un don. Retourne au minimum ['payment_url' => ?string]. */
    public function initiateDonation(Donation $donation): array;
}