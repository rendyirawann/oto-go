<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Installs the OtoGo brand kit (logo, favicon set, OG image, manifest).
 *
 * The artwork is drawn once by resources/brand/make_brand.py and committed
 * under resources/brand. "Generate ulang branding" copies that kit into
 * public/ again and rewrites the web manifest with the current app name, so a
 * deleted or overwritten file can always be restored from the admin panel.
 */
class BrandingService
{
    /** Directory (inside public/) where brand assets are served from. */
    public const DIR = 'assets/media/branding';

    /** Source of truth for the artwork. */
    public const SOURCE = 'resources/brand';

    public const COLORS = [
        'primary' => '#FF5A1F',   // OtoGo orange
        'secondary' => '#111418', // ink
        'accent' => '#FFC531',    // lane-marking yellow
        'paper' => '#F5F3EE',
    ];

    private const FILES = [
        'logo-mark.svg', 'logo-full.svg', 'logo-full-light.svg', 'logotype.svg', 'favicon.svg',
        'favicon.ico', 'favicon-16.png', 'favicon-32.png', 'favicon-48.png', 'favicon-64.png',
        'apple-touch-icon.png', 'icon-192.png', 'icon-512.png', 'icon-maskable-512.png', 'og-image.png',
    ];

    /**
     * Copy the brand kit into public/ and write the manifest.
     *
     * @return array<string,string> map of logical name => public-relative path
     */
    public function generate(string $appName): array
    {
        $appName = trim($appName) !== '' ? trim($appName) : 'OtoGo';
        $target = public_path(self::DIR);
        $source = base_path(self::SOURCE);

        File::ensureDirectoryExists($target, 0755);

        foreach (self::FILES as $file) {
            if (is_file("{$source}/{$file}")) {
                File::copy("{$source}/{$file}", "{$target}/{$file}");
            }
        }

        if (is_file("{$source}/favicon.ico")) {
            File::copy("{$source}/favicon.ico", public_path('favicon.ico'));
        }

        File::put("{$target}/site.webmanifest", $this->manifest($appName));

        return [
            'logo' => self::DIR . '/logo-mark.svg',
            'logo_full' => self::DIR . '/logo-full.svg',
            'favicon' => self::DIR . '/favicon.ico',
            'favicon_png' => self::DIR . '/favicon-32.png',
            'apple_touch_icon' => self::DIR . '/apple-touch-icon.png',
            'og_image' => self::DIR . '/og-image.png',
            'manifest' => self::DIR . '/site.webmanifest',
            'theme_color' => self::COLORS['primary'],
        ];
    }

    /** True when the brand kit is present in public/. */
    public function exists(): bool
    {
        return is_file(public_path(self::DIR . '/logo-mark.svg'));
    }

    /**
     * Brand colours. The name argument is kept for backwards compatibility with
     * callers that used to derive a palette from the app name.
     *
     * @return array{primary:string,secondary:string,accent:string,paper:string}
     */
    public function palette(string $appName = ''): array
    {
        return self::COLORS;
    }

    /** Up to two uppercase initials taken from the application name. */
    public function initials(string $appName): string
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', $appName, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) >= 2) {
            return Str::upper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        }

        $single = $words[0] ?? 'O';

        if (preg_match_all('/\p{Lu}/u', $single, $m) && count($m[0]) >= 2) {
            return Str::upper($m[0][0] . $m[0][1]);
        }

        return Str::upper(mb_substr($single, 0, 2));
    }

    private function manifest(string $appName): string
    {
        $base = '/' . self::DIR;

        return (string) json_encode([
            'name' => $appName,
            'short_name' => Str::limit($appName, 12, ''),
            'description' => 'Booking bengkel, doorsmeer, dan sparepart di Kota Medan.',
            'lang' => 'id',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'theme_color' => self::COLORS['secondary'],
            'background_color' => self::COLORS['paper'],
            'icons' => [
                ['src' => "{$base}/icon-192.png", 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => "{$base}/icon-512.png", 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => "{$base}/icon-maskable-512.png", 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => "{$base}/favicon.svg", 'sizes' => 'any', 'type' => 'image/svg+xml'],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
