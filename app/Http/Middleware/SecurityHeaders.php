<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies a pragmatic set of browser security headers.
 *
 * The policy is deliberately permissive enough for the Metronic theme
 * (inline scripts/styles, Google Fonts, CDN plugins, Reverb websockets)
 * while still closing off clickjacking, MIME sniffing, referrer leakage
 * and cross-origin form hijacking.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-site');
        $response->headers->set(
            'Permissions-Policy',
            'accelerometer=(), autoplay=(), camera=(), display-capture=(), encrypted-media=(), '
            . 'geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), payment=(), usb=()'
        );

        // Hide server fingerprints where PHP lets us.
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        if ($request->secure() && config('security.hsts', false)) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (config('security.csp.enabled', true) && $this->isHtml($response)) {
            $header = config('security.csp.report_only', false)
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $response->headers->set($header, $this->policy());
        }

        // Never let a browser or proxy cache an authenticated page.
        if ($request->user() !== null && $this->isHtml($response)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }

    private function isHtml(Response $response): bool
    {
        return str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'text/html');
    }

    private function policy(): string
    {
        $extra = array_filter(array_map('trim', (array) config('security.csp.extra_hosts', [])));
        $hosts = $extra === [] ? '' : ' ' . implode(' ', $extra);

        $directives = [
            "default-src 'self'",
            // Metronic ships inline bootstrapping code and some plugins eval templates.
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com" . $hosts,
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com" . $hosts,
            "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            // Avatars, generated branding, data: sprites and blob: canvas exports.
            "img-src 'self' data: blob: https:",
            "media-src 'self' data: blob:",
            // Same-origin XHR plus the Reverb websocket.
            "connect-src 'self' ws: wss: https:" . $hosts,
            "worker-src 'self' blob:",
            "frame-src 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        if (config('security.csp.upgrade_insecure', false)) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}
