<?php

namespace App\Services;

use App\Models\Cotisation;
use App\Models\Donation;

class PaystackGateway implements PaymentGateway
{
    public function __construct(private PaystackClient $client)
    {
    }

    public function initiate(Cotisation $cotisation): array
    {
        return $this->start($cotisation->reference, $cotisation->amount, $cotisation->user?->email, [
            'type' => 'cotisation',
            'period' => $cotisation->period,
            'user_id' => $cotisation->user_id,
        ]);
    }

    public function initiateDonation(Donation $donation): array
    {
        return $this->start($donation->reference, $donation->amount, $donation->donor_email ?: $donation->user?->email, [
            'type' => 'don',
            'donor' => $donation->donor_name,
        ]);
    }

    private function start(string $reference, int $amount, ?string $email, array $metadata): array
    {
        $data = $this->client->initialize([
            'email' => $email ?: $this->fallbackEmail($reference),
            // XOF : pas de sous-unité, mais la documentation Paystack impose de multiplier le montant par 100.
            'amount' => $amount * 100,
            'currency' => 'XOF',
            'reference' => $reference,
            'callback_url' => rtrim(config('mutuelle.frontend_url'), '/').'/paiement/retour',
            'metadata' => $metadata,
        ]);

        return ['payment_url' => $data['authorization_url'] ?? null, 'message' => null];
    }

    private function fallbackEmail(string $reference): string
    {
        $domain = config('mutuelle.paystack.fallback_email_domain');
        abort_if(! $domain, 422, 'Une adresse email est nécessaire pour payer en ligne.');

        return strtolower($reference).'@'.$domain;
    }
}