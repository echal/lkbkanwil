<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',  // Next.js development
        'http://127.0.0.1:3000',
        'https://esaraku.gaspul.com',
        'https://helpdesk.gaspul.com',
        'http://localhost',       // esaraku_helpdesk (XAMPP default port 80)
        'http://localhost:8000',  // esaraku_helpdesk artisan serve
        'http://127.0.0.1',
        'http://127.0.0.1:8000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,  // Token-based auth tidak butuh credentials

];
