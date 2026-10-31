<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Keys introduced for SEO / meta / branding management.
     * Values here are only defaults — everything is editable from Settings.
     */
    private function defaults(): array
    {
        $appName = config('app.name', 'StarterTemp');

        return [
            'site_short_name' => \Illuminate\Support\Str::limit($appName, 12, ''),
            'site_tagline' => 'Modern Admin Dashboard',
            'site_description' => $appName . ' — panel administrasi modern untuk mengelola pengguna, hak akses, dan operasional aplikasi Anda.',
            'site_keywords' => strtolower($appName) . ', admin panel, dashboard, manajemen pengguna, laravel',
            'site_author' => 'Rendy Irawan',
            'site_favicon' => 'assets/media/branding/favicon.ico',
            'site_og_image' => 'assets/media/branding/og-image.png',
            'site_theme_color' => '#4f46e5',
            'seo_robots' => 'noindex, nofollow',
            'seo_twitter_handle' => '',
            'seo_google_verification' => '',
            // Self-service registration is off by default on an admin panel.
            'allow_registration' => '0',
            'footer_owner' => 'Rendy Irawan',
            'footer_github' => 'https://github.com/rendyirawann',
            'footer_linkedin' => 'https://linkedin.com/in/rendyirawann',
        ];
    }

    public function up(): void
    {
        $now = now();

        foreach ($this->defaults() as $key => $value) {
            // Never clobber a value the operator has already set.
            if (DB::table('settings')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_keys($this->defaults()))->delete();
    }
};
