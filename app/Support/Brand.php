<?php

namespace App\Support;

use App\Models\Setting;
use App\Services\BrandingService;
use Illuminate\Support\Str;

/**
 * Read-only view of the application's identity (name, artwork, SEO meta),
 * resolved once per request and shared with every Blade view as $brand.
 */
class Brand
{
    /** Container binding key — scoped, so it resets on every Octane request. */
    public const KEY = 'brand.identity';

    /**
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        return app(self::KEY);
    }

    /**
     * Resolve the identity from settings. Called once per request through the
     * scoped container binding registered in AppServiceProvider.
     *
     * @return array<string,mixed>
     */
    public static function compute(): array
    {
        $settings = self::settings();
        $name = self::clean($settings['site_name'] ?? null) ?: config('app.name', 'StarterTemp');

        return [
            'name' => $name,
            'short_name' => self::clean($settings['site_short_name'] ?? null) ?: Str::limit($name, 12, ''),
            'tagline' => self::clean($settings['site_tagline'] ?? null) ?: 'Modern Admin Dashboard',
            'description' => self::clean($settings['site_description'] ?? null)
                ?: $name . ' — panel administrasi modern untuk mengelola pengguna, hak akses, dan operasional aplikasi.',
            'keywords' => self::clean($settings['site_keywords'] ?? null) ?: Str::lower($name) . ', admin panel, dashboard',
            'author' => self::clean($settings['site_author'] ?? null) ?: 'Rendy Irawan',
            'robots' => self::clean($settings['seo_robots'] ?? null) ?: 'noindex, nofollow',
            'twitter' => self::clean($settings['seo_twitter_handle'] ?? null),
            'google_verification' => self::clean($settings['seo_google_verification'] ?? null),
            'theme_color' => self::color($settings['site_theme_color'] ?? null),
            'initials' => Str::upper(Str::substr(preg_replace('/[^\p{L}\p{N}]/u', '', $name) ?: 'A', 0, 2)),

            'logo_url' => self::assetUrl($settings['site_logo'] ?? null, 'assets/media/branding/logo-mark.svg'),
            'favicon_url' => self::assetUrl($settings['site_favicon'] ?? null, 'assets/media/branding/favicon.ico'),
            'favicon_png_url' => self::publicUrl('assets/media/branding/favicon-32.png'),
            'apple_icon_url' => self::publicUrl('assets/media/branding/apple-touch-icon.png'),
            'manifest_url' => self::publicUrl('assets/media/branding/site.webmanifest'),
            'og_image_url' => self::assetUrl($settings['site_og_image'] ?? null, 'assets/media/branding/og-image.png'),

            'owner' => self::clean($settings['footer_owner'] ?? null) ?: 'Rendy Irawan',
            'github' => self::url($settings['footer_github'] ?? null) ?: 'https://github.com/rendyirawann',
            'linkedin' => self::url($settings['footer_linkedin'] ?? null) ?: 'https://linkedin.com/in/rendyirawann',
            'font' => self::clean($settings['site_font'] ?? null) ?: 'Plus Jakarta Sans',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    /** Drop the memoised copy — used after settings are saved. */
    public static function flush(): void
    {
        app()->forgetInstance(self::KEY);
    }

    // ------------------------------------------------------------------

    /** @return array<string,string> */
    private static function settings(): array
    {
        try {
            return Setting::allCached();
        } catch (\Throwable) {
            // Database not migrated / unavailable — fall back to config defaults.
            return [];
        }
    }

    /**
     * Resolve a stored asset value to a URL.
     *
     * Historically the logo was stored as a bare filename inside
     * assets/media/logos/. Generated assets are stored as a public-relative
     * path. Both forms are accepted, and a missing file falls back.
     */
    private static function assetUrl(?string $value, string $fallback): string
    {
        $value = self::clean($value);

        if ($value !== null) {
            if (Str::startsWith($value, ['http://', 'https://', '//'])) {
                return $value;
            }

            $path = str_contains($value, '/') ? ltrim($value, '/') : 'assets/media/logos/' . $value;

            if (is_file(public_path($path))) {
                return asset($path);
            }
        }

        return self::publicUrl($fallback);
    }

    /** asset() for a generated file, degrading to the shipped default logo. */
    private static function publicUrl(string $path): string
    {
        if (is_file(public_path($path))) {
            return asset($path);
        }

        // Generated assets have not been built yet (php artisan app:branding).
        return asset('assets/media/logos/base-logo.png');
    }

    private static function clean(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return ($value === null || $value === '') ? null : $value;
    }

    /** Only allow http(s) URLs through to the rendered markup. */
    private static function url(?string $value): ?string
    {
        $value = self::clean($value);

        if ($value === null || ! preg_match('#^https?://#i', $value)) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }

    /** Constrain the theme colour to a safe hex literal. */
    private static function color(?string $value): string
    {
        $value = self::clean($value);

        if ($value !== null && preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
            return $value;
        }

        try {
            return app(BrandingService::class)->palette(
                self::clean(self::settings()['site_name'] ?? null) ?: config('app.name', 'App')
            )['primary'];
        } catch (\Throwable) {
            return '#4f46e5';
        }
    }
}
