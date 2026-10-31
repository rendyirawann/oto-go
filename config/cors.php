<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|--------------------------------------------------------------------------
|
| CORS is applied ONLY to the paths listed below. Static assets, Blade pages
| and the admin panel are served same-origin and are deliberately left out,
| so tightening this can never break the UI, fonts, images or plugin files.
|
| Allowed origins default to APP_URL. Add more (a mobile app host, a separate
| SPA domain) via the CORS_ALLOWED_ORIGINS env var, comma separated.
|
*/

// An empty (not just absent) CORS_ALLOWED_ORIGINS falls back to APP_URL,
// so the list is never accidentally blank after copying .env.example.
$configured = trim((string) env('CORS_ALLOWED_ORIGINS', ''));

if ($configured === '') {
    $configured = (string) env('APP_URL', 'http://localhost');
}

$origins = array_values(array_filter(array_map('trim', explode(',', $configured))));

return [

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'broadcasting/auth',
    ],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $origins,

    // e.g. CORS_ALLOWED_ORIGIN_PATTERNS="#^https://.*\.example\.com$#"
    'allowed_origins_patterns' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGIN_PATTERNS', ''))
    ))),

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'X-Socket-Id',
    ],

    'exposed_headers' => [],

    'max_age' => 600,

    // Required for cookie-authenticated XHR; safe because the origin list is
    // explicit (a wildcard origin with credentials would be rejected anyway).
    'supports_credentials' => true,

];
