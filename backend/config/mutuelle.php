<?php

return [
    // Montant de la cotisation mensuelle en FCFA. À définir dans .env (aucune valeur par défaut).
    'cotisation_amount' => (int) env('MUTUELLE_COTISATION_AMOUNT', 0),
    'currency' => 'XOF',
    // Secret partagé avec la passerelle pour signer les notifications de paiement.
    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET'),
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
];
