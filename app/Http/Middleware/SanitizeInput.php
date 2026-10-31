<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;

/**
 * Normalises incoming request input before validation runs.
 *
 * This is a hygiene pass, not a substitute for validation or for Eloquent's
 * parameter binding: it strips NUL/control characters and removes embedded
 * <script>/<iframe> markup and inline event handlers that would otherwise be
 * persisted and later echoed back.
 *
 * Extending TransformsRequest (rather than rewriting $request->all()) means
 * the query and request bags are walked separately and uploaded files are
 * never touched.
 *
 * Password-ish and explicitly whitelisted rich-text fields are left alone so
 * a legitimate value is never silently mangled.
 */
class SanitizeInput extends TransformsRequest
{
    /** Values under these keys are passed through verbatim. */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'new_password_confirmation',
        'token',
        '_token',
        'remember',
    ];

    /** Markup is preserved for these keys (still NUL/control stripped). */
    protected array $allowHtml = [
        'content',
        'body_html',
        'description_html',
        'editor',
    ];

    /**
     * @param  string  $key  dot-notated path, e.g. "items.0.name"
     */
    protected function transform($key, $value)
    {
        if (! is_string($value)) {
            return $value;
        }

        // Compare on the last path segment so nested arrays behave the same.
        $name = str_contains($key, '.') ? substr(strrchr($key, '.'), 1) : $key;

        if (in_array($name, $this->except, true)) {
            return $value;
        }

        return $this->sanitizeString($value, in_array($name, $this->allowHtml, true));
    }

    /** Renamed from clean() to avoid clashing with the parent's own clean(). */
    private function sanitizeString(string $value, bool $allowHtml): string
    {
        // Strip NUL bytes and C0/C1 control characters, keeping tab/LF/CR.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;

        if ($allowHtml) {
            return $value;
        }

        // Remove whole executable/embedding elements including their content.
        $value = preg_replace(
            '#<\s*(script|iframe|object|embed|applet|style|form)\b[^>]*>.*?<\s*/\s*\1\s*>#is',
            '',
            $value
        ) ?? $value;

        // ...and their self-closing / unterminated variants.
        $value = preg_replace(
            '#<\s*/?\s*(script|iframe|object|embed|applet)\b[^>]*>?#is',
            '',
            $value
        ) ?? $value;

        // Inline event handlers: onclick="...", onerror='...', onload=...
        $value = preg_replace(
            '/\son[a-z]{3,20}\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i',
            '',
            $value
        ) ?? $value;

        // javascript: / vbscript: / data:text/html URLs.
        return preg_replace(
            '#(?:java|vb)script\s*:|data\s*:\s*text/html#i',
            '',
            $value
        ) ?? $value;
    }
}
