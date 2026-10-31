<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | The shipped policy is deliberately permissive: the Metronic theme relies
    | on inline scripts/styles and a few CDN plugins. It still blocks framing,
    | plugin objects and cross-origin form posts. Set SECURITY_CSP_ENABLED=false
    | if a third-party integration needs to be unblocked temporarily, or use
    | report-only mode while tightening `extra_hosts`.
    |
    */

    'csp' => [
        'enabled' => env('SECURITY_CSP_ENABLED', true),
        'report_only' => env('SECURITY_CSP_REPORT_ONLY', false),
        'upgrade_insecure' => env('SECURITY_CSP_UPGRADE_INSECURE', false),

        // Extra origins appended to script-src / style-src / connect-src.
        // e.g. SECURITY_CSP_EXTRA_HOSTS="https://maps.googleapis.com https://tiles.example.com"
        'extra_hosts' => array_filter(explode(' ', (string) env('SECURITY_CSP_EXTRA_HOSTS', ''))),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Strict Transport Security
    |--------------------------------------------------------------------------
    |
    | Only sent over HTTPS. Keep this off until the production certificate is
    | confirmed working — the header is sticky in browsers for a full year.
    |
    */

    'hsts' => env('SECURITY_HSTS', env('APP_ENV') === 'production'),

    /*
    |--------------------------------------------------------------------------
    | Suspicious Request Blocking
    |--------------------------------------------------------------------------
    |
    | Rejects URLs carrying unmistakable SQLi / traversal / XSS signatures.
    | Form bodies are never inspected, so normal content is unaffected.
    |
    */

    'block_suspicious' => env('SECURITY_BLOCK_SUSPICIOUS', true),

    /*
    |--------------------------------------------------------------------------
    | Rate Limits (requests per minute)
    |--------------------------------------------------------------------------
    */

    'rate_limits' => [
        // Whole-app guard against scraping / flooding, keyed by user or IP.
        'web' => (int) env('RATE_LIMIT_WEB', 300),

        // Login attempts per IP+username, on top of the per-account lockout.
        'login' => (int) env('RATE_LIMIT_LOGIN', 5),

        // Password reset / verification e-mail sending.
        'password' => (int) env('RATE_LIMIT_PASSWORD', 5),

        // Write operations (POST/PUT/PATCH/DELETE) per authenticated user.
        'write' => (int) env('RATE_LIMIT_WRITE', 60),

        // JSON API surface.
        'api' => (int) env('RATE_LIMIT_API', 60),
    ],

];
