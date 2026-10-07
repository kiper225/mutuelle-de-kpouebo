<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    // Adresse du frontend Vue (définie dans .env : FRONTEND_URL)
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false, // authentification par jeton (Bearer), pas par cookie
];
