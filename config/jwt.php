<?php

return [
    'issuer'   => env('JWT_ISSUER', 'user-service'),
    'audience' => env('JWT_AUDIENCE', 'notification-platform'),

    'keys' => [
        'public' => env('JWT_PUBLIC_KEY', storage_path('app/keys/jwt-public.pem')),
    ],
];
