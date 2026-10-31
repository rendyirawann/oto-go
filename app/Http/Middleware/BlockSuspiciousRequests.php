<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects requests whose URL carries an unmistakable attack payload
 * (SQL injection probes, path traversal, PHP stream wrappers, XSS in the
 * query string).
 *
 * Scope is intentionally narrow — only the path and query string are
 * inspected, never form bodies — so ordinary content that happens to
 * contain the word "select" or an apostrophe is never blocked. Anything
 * subtler than these signatures is left to validation and Eloquent's
 * parameter binding.
 */
class BlockSuspiciousRequests
{
    /** Signatures that have no legitimate reason to appear in a URL. */
    private const SIGNATURES = [
        // SQL injection probes
        '/\bunion\b[\s\/*]+\bselect\b/i',
        '/\bselect\b[\s\S]{0,40}\bfrom\b[\s\S]{0,40}\binformation_schema\b/i',
        '/\b(?:sleep|benchmark|pg_sleep)\s*\(\s*\d/i',
        '/\bdrop\s+table\b/i',
        '/\binto\s+(?:out|dump)file\b/i',
        '/\bor\b\s+["\']?\d+["\']?\s*=\s*["\']?\d+/i',
        '/(?:--|#|\/\*)\s*$/',

        // Path traversal and local file probes
        '#(?:\.\./){2,}#',
        '#(?:%2e%2f|%2e%2e%2f){2,}#i',
        '#/etc/(?:passwd|shadow|hosts)\b#i',
        '#\bboot\.ini\b#i',

        // PHP stream wrappers / remote code execution
        '#\b(?:php|expect|zip|phar)://#i',
        '/\b(?:base64_decode|shell_exec|passthru|proc_open|popen|eval)\s*\(/i',

        // Reflected XSS in the query string
        '#<\s*script\b#i',
        '#\bon(?:error|load|click)\s*=#i',
        '#javascript\s*:#i',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.block_suspicious', true)) {
            return $next($request);
        }

        $target = rawurldecode($request->getRequestUri());

        foreach (self::SIGNATURES as $pattern) {
            if (preg_match($pattern, $target) !== 1) {
                continue;
            }

            Log::channel(config('logging.default'))->warning('Blocked suspicious request', [
                'ip' => $request->ip(),
                'method' => $request->method(),
                'uri' => $request->getRequestUri(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'signature' => $pattern,
            ]);

            abort(400, 'Permintaan tidak valid.');
        }

        return $next($request);
    }
}
