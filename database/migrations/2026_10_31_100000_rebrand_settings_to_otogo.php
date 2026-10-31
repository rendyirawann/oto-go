<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves the identity settings from the StarterTemp template to OtoGo.
 *
 * Only values that still hold a template default are replaced, so anything an
 * admin already customised is left alone.
 */
return new class extends Migration
{
    private const TEMPLATE_VALUES = ['', 'StarterTemp', 'Rendy Irawan', 'Modern Admin Dashboard', 'base-logo.png', '#4f46e5', 'noindex, nofollow'];

    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $values = [
            'site_name' => 'OtoGo',
            'site_short_name' => 'OtoGo',
            'site_tagline' => 'Bengkel, doorsmeer & sparepart dalam satu aplikasi',
            'site_description' => 'OtoGo menghubungkan pemilik kendaraan di Kota Medan dengan bengkel, doorsmeer, dan toko sparepart terpercaya. Booking servis, cuci, antar jemput, dan layanan di rumah dalam satu aplikasi.',
            'site_keywords' => 'otogo, bengkel medan, doorsmeer medan, cuci mobil medan, sparepart medan, servis motor, servis mobil, antar jemput kendaraan, home service',
            'site_author' => 'Core Technology Indonesia',
            'footer_owner' => 'Core Technology Indonesia',
            'seo_robots' => 'index, follow',
            'site_logo' => 'assets/media/branding/logo-mark.svg',
            'site_favicon' => 'assets/media/branding/favicon.ico',
            'site_og_image' => 'assets/media/branding/og-image.png',
            'site_theme_color' => '#FF5A1F',
        ];

        $now = now();

        foreach ($values as $key => $value) {
            $current = DB::table('settings')->where('key', $key)->value('value');

            if ($current === null) {
                DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
            } elseif ($this->isTemplateValue((string) $current)) {
                DB::table('settings')->where('key', $key)->update(['value' => $value, 'updated_at' => $now]);
            }
        }

        try {
            Cache::forget('settings.all');
            foreach (array_keys($values) as $key) {
                Cache::forget("setting.{$key}");
            }
        } catch (\Throwable) {
            // Cache store unavailable during migration - values refresh on expiry.
        }
    }

    public function down(): void
    {
        // Identity data: nothing to undo safely.
    }

    private function isTemplateValue(string $value): bool
    {
        return in_array(trim($value), self::TEMPLATE_VALUES, true)
            || str_contains($value, 'StarterTemp')
            || str_contains($value, 'startertemp');
    }
};
