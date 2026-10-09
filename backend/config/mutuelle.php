<?php

return [
    // Montant de la cotisation mensuelle en FCFA. À définir dans .env (aucune valeur par défaut).
    'cotisation_amount' => (int) env('MUTUELLE_COTISATION_AMOUNT', 0),
    // Montant minimum d'un don en FCFA (modifiable dans .env : DONATION_MIN).
    'donation_min' => (int) env('DONATION_MIN', 100),
    'currency' => 'XOF',
    // Secret partagé pour la notification de paiement générique (voir PaymentWebhookController).
    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET'),
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),

    'paystack' => [
        // Clé SECRÈTE Paystack (sk_test_… en essai, sk_live_… en production). Ne jamais la mettre côté Vue ni dans Git.
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        // Paystack exige un email pour chaque paiement. Pour les membres ou donateurs sans email,
        // on fabrique une adresse « reference@domaine ». Indiquez un domaine dont vous contrôlez la boîte
        // (ex. paiements.votre-mutuelle.ci). Si vide, un email devient obligatoire pour payer en ligne.
        'fallback_email_domain' => env('PAYSTACK_FALLBACK_EMAIL_DOMAIN'),
    ],
];