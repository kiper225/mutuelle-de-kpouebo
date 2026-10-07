<?php

namespace App\Services;

use App\Models\Cotisation;

interface PaymentGateway
{
    /**
     * Démarre un paiement et retourne au minimum ['payment_url' => ?string].
     */
    public function initiate(Cotisation $cotisation): array;
}
