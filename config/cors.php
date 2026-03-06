<?php

return [

    /*
     * CORS paths — apply to all routes (API + docs).
     */
    'paths' => ['api/*', 'docs', 'docs/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    /*
     * Set to true if you need to send credentials (cookies / auth headers)
     * cross-origin. Must be combined with a specific origin instead of '*'.
     */
    'supports_credentials' => false,

];
