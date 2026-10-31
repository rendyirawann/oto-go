<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

/**
 * Allowlist HTML sanitizer for blog content (no external packages).
 *
 * - Elements outside the allowlist are unwrapped (their text survives).
 * - Executable / embedding elements are removed together with their content.
 * - Only listed attributes survive; URLs must be http(s), relative or anchors.
 * - Images must be same-origin (relative path or APP_URL).
 */
class HtmlSanitizer
{
    /** tag => allowed attributes */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [],
        'h2' => [], 'h3' => [], 'h4' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [],
        'a' => ['href', 'title'],
        'blockquote' => [], 'code' => [], 'pre' => [],
        'figure' => [], 'figcaption' => [],
        'img' => ['src', 'alt', 'width', 'height'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan', 'scope'], 'td' => ['colspan', 'rowspan'],
    ];

    /** Removed with everything inside them. */
    private const DROP = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet',
        'form', 'input', 'button', 'textarea', 'select', 'option', 'svg', 'math',
        'template', 'noscript', 'link', 'meta', 'base', 'head', 'title', 'audio', 'video', 'canvas',
    ];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        [$doc, $root] = self::load($html);
        self::walk($root);

        return trim(self::innerHtml($doc, $root));
    }

    /**
     * Adds stable id anchors to h2 elements and returns the table of contents.
     *
     * @return array{html:string,toc:array<int,array{id:string,title:string}>}
     */
    public static function withHeadingAnchors(string $html): array
    {
        if (trim($html) === '') {
            return ['html' => '', 'toc' => []];
        }

        [$doc, $root] = self::load($html);
        $toc = [];
        $used = [];

        foreach ($root->getElementsByTagName('h2') as $heading) {
            $title = trim(preg_replace('/\s+/u', ' ', $heading->textContent) ?? '');
            if ($title === '') {
                continue;
            }

            $id = Str::slug($title) ?: 'bagian';
            $base = $id;
            $n = 2;
            while (isset($used[$id])) {
                $id = $base . '-' . $n++;
            }
            $used[$id] = true;

            $heading->setAttribute('id', $id);
            $toc[] = ['id' => $id, 'title' => $title];
        }

        return ['html' => trim(self::innerHtml($doc, $root)), 'toc' => $toc];
    }

    // ------------------------------------------------------------------

    /** @return array{0:DOMDocument,1:DOMElement} */
    private static function load(string $html): array
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="__og_root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = null;
        foreach ($doc->getElementsByTagName('div') as $div) {
            if ($div->getAttribute('id') === '__og_root') {
                $root = $div;
                break;
            }
        }

        if (! $root instanceof DOMElement) {
            $root = $doc->createElement('div');
        }

        return [$doc, $root];
    }

    private static function walk(DOMNode $node): void
    {
        // Snapshot children: the list mutates while we unwrap/remove.
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $node->removeChild($child);
                continue;
            }

            if (! $child instanceof DOMElement) {
                continue; // text nodes are escaped on output
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);
                continue;
            }

            if (! array_key_exists($tag, self::ALLOWED)) {
                self::walk($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            self::filterAttributes($child, self::ALLOWED[$tag]);

            if ($tag === 'img' && ! $child->hasAttribute('src')) {
                $node->removeChild($child);
                continue;
            }

            if ($tag === 'a') {
                if ($child->hasAttribute('href')) {
                    $child->setAttribute('rel', 'noopener noreferrer');
                }
            }

            self::walk($child);
        }
    }

    private static function filterAttributes(DOMElement $el, array $allowed): void
    {
        $names = [];
        foreach ($el->attributes as $attr) {
            $names[] = $attr->nodeName;
        }

        foreach ($names as $name) {
            $lower = strtolower($name);
            $value = trim((string) $el->getAttribute($name));

            if (! in_array($lower, $allowed, true)) {
                $el->removeAttribute($name);
                continue;
            }

            $ok = match ($lower) {
                'href' => self::safeHref($value),
                'src' => self::safeImageSrc($value),
                'width', 'height', 'colspan', 'rowspan' => ctype_digit($value) && (int) $value > 0 && (int) $value <= 4000,
                'scope' => in_array($value, ['row', 'col', 'rowgroup', 'colgroup'], true),
                default => true,
            };

            if (! $ok) {
                $el->removeAttribute($name);
            }
        }
    }

    private static function safeHref(string $url): bool
    {
        $url = preg_replace('/[\x00-\x20]+/', '', $url) ?? '';

        if ($url === '') {
            return false;
        }

        if (Str::startsWith($url, ['#', '/']) && ! Str::startsWith($url, '//')) {
            return true;
        }

        if (preg_match('#^https?://#i', $url)) {
            return filter_var($url, FILTER_VALIDATE_URL) !== false;
        }

        if (preg_match('#^mailto:[^\s@]+@[^\s@]+$#i', $url)) {
            return true;
        }

        // Relative path without a scheme ("blog/foo"): reject anything with ':' before a '/'.
        return ! preg_match('#^[^/?\#]*:#', $url);
    }

    private static function safeImageSrc(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || Str::startsWith($url, '//')) {
            return false;
        }

        if (Str::startsWith($url, '/')) {
            return true;
        }

        $app = rtrim((string) config('app.url'), '/');

        if ($app !== '' && Str::startsWith($url, $app . '/')) {
            return true;
        }

        // Plain relative path (assets/media/...), no scheme.
        return ! preg_match('#^[^/?\#]*:#', $url);
    }

    private static function innerHtml(DOMDocument $doc, DOMElement $root): string
    {
        $html = '';
        foreach ($root->childNodes as $child) {
            $html .= $doc->saveHTML($child);
        }

        return $html;
    }
}
