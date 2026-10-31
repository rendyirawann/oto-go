<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers, tuned for an "A" on securityheaders.com without
 * breaking any page:
 *
 *   Content-Security-Policy, X-Frame-Options, X-Content-Type-Options,
 *   Referrer-Policy, Permissions-Policy and (over HTTPS) Strict-Transport-
 *   Security, plus Cross-Origin-Opener/Resource-Policy.
 *
 * The CSP is chosen per area:
 *  - admin portal and Swagger UI: the Metronic bundles and Swagger need
 *    inline scripts and eval, and the admin talks to the Reverb websocket;
 *  - public site (landing, blog): no eval, same-origin connections only;
 *  - JSON / API responses: a lock-down policy (nothing may load from them).
 *
 * Only Google Fonts is allowed as a third-party origin; everything else is
 * served from this host so no network or ad-blocker can strip an asset.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $headers->set('Cross-Origin-Resource-Policy', 'same-site');
        $headers->set('Permissions-Policy', implode(', ', [
            'accelerometer=()', 'autoplay=()', 'browsing-topics=()', 'camera=()', 'display-capture=()',
            'encrypted-media=()', 'geolocation=(self)', 'gyroscope=()', 'magnetometer=()', 'microphone=()',
            'midi=()', 'payment=()', 'usb=()',
        ]));

        // Hide server fingerprints where PHP lets us.
        $headers->remove('X-Powered-By');
        $headers->remove('Server');

        if ($request->secure() && config('security.hsts', false)) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! config('security.csp.enabled', true)) {
            return $response;
        }

        $cspHeader = config('security.csp.report_only', false)
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        if ($this->isHtml($response)) {
            $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
            $headers->set($cspHeader, $this->htmlPolicy($request));
        } elseif ($this->isJson($response)) {
            $headers->set($cspHeader, "default-src 'none'; frame-ancestors 'none'; base-uri 'none'");
        }

        // Never let a browser or proxy cache an authenticated page.
        if ($request->user() !== null && $this->isHtml($response)) {
            $headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $headers->set('Pragma', 'no-cache');
        }

        return $response;
    }

    private function isHtml(Response $response): bool
    {
        return str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'text/html');
    }

    private function isJson(Response $response): bool
    {
        return str_contains((string) $response->headers->get('Content-Type', ''), 'json');
    }

    /** Admin portal and the Swagger UI need the more permissive script policy. */
    private function isAppShell(Request $request): bool
    {
        return $request->is('admin', 'admin/*', 'api/documentation', 'api/documentation/*', 'docs', 'docs/*');
    }

    private function htmlPolicy(Request $request): string
    {
        $extra = array_filter(array_map('trim', (array) config('security.csp.extra_hosts', [])));
        $hosts = $extra === [] ? '' : ' ' . implode(' ', $extra);
        $shell = $this->isAppShell($request);

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'" . ($shell ? " 'unsafe-eval'" : '') . $hosts,
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com" . $hosts,
            "font-src 'self' data: https://fonts.gstatic.com",
            // Avatars, uploaded covers, data: sprites and blob: canvas exports.
            "img-src 'self' data: blob: https:",
            "media-src 'self' data: blob:",
            // Admin: same-origin XHR plus the Reverb websocket.
            "connect-src 'self'" . ($shell ? ' ws: wss: https:' : '') . $hosts,
            "worker-src 'self' blob:",
            "manifest-src 'self'",
            "frame-src 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        if ($request->secure() || config('security.csp.upgrade_insecure', false)) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}
