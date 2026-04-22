<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'downloads/*', 'rest/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
