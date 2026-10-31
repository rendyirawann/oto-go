<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\BrandingService;
use App\Support\ActivityRecorder;
use App\Support\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    /** Google Fonts offered for the global type face. */
    private const FONTS = [
        'Plus Jakarta Sans',
        'Inter',
        'Outfit',
        'Poppins',
        'DM Sans',
        'Nunito',
        'Figtree',
        'Manrope',
    ];

    /** Allowed values for the robots meta tag. */
    public const ROBOTS = [
        'index, follow',
        'noindex, nofollow',
        'index, nofollow',
        'noindex, follow',
    ];

    private const SOCIAL_PROVIDERS = ['google', 'facebook', 'github', 'linkedin'];

    public function index(): View
    {
        return view('backend.settings.index', [
            'settings' => Setting::allCached(),
            'fonts' => self::FONTS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $before = Setting::allCached();

        $validated = $request->validate([
            // --- Identity ---
            'site_name' => ['required', 'string', 'max:60'],
            'site_short_name' => ['nullable', 'string', 'max:20'],
            'site_tagline' => ['nullable', 'string', 'max:80'],
            'site_font' => ['required', 'string', Rule::in(self::FONTS)],
            'site_theme_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],

            // --- SEO ---
            'site_description' => ['nullable', 'string', 'max:300'],
            'site_keywords' => ['nullable', 'string', 'max:255'],
            'site_author' => ['nullable', 'string', 'max:80'],
            'seo_robots' => ['nullable', 'string', Rule::in(self::ROBOTS)],
            'seo_twitter_handle' => ['nullable', 'string', 'max:40', 'regex:/^@?[A-Za-z0-9_]*$/'],
            'seo_google_verification' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\-]*$/'],

            // --- Footer ---
            'footer_owner' => ['nullable', 'string', 'max:80'],
            'footer_github' => ['nullable', 'url', 'max:200'],
            'footer_linkedin' => ['nullable', 'url', 'max:200'],

            // --- Uploads ---
            'site_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'site_favicon' => ['nullable', 'file', 'mimes:png,ico,svg', 'max:512'],
            'site_og_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        foreach ($validated as $key => $value) {
            // Files are handled separately below.
            if ($request->hasFile($key)) {
                continue;
            }

            Setting::set($key, (string) $value);
        }

        // Checkboxes are absent from the payload when unticked.
        Setting::set('maintenance_mode', $request->boolean('maintenance_mode') ? '1' : '0');
        Setting::set('allow_registration', $request->boolean('allow_registration') ? '1' : '0');

        $this->storeUpload($request, 'site_logo', 'logo');
        $this->storeUpload($request, 'site_favicon', 'favicon');
        $this->storeUpload($request, 'site_og_image', 'og');

        $this->saveSocialProviders($request);

        Setting::clearCache();
        Brand::flush();

        ActivityRecorder::updated(
            'pengaturan aplikasi',
            'Memperbarui pengaturan aplikasi',
            $this->auditable($before),
            $this->auditable(Setting::allCached()),
        );

        return redirect()
            ->route('settings.index')
            ->with('success', 'Pengaturan berhasil diperbarui.');
    }

    /** Rebuild the logo/favicon/OG artwork from the current application name. */
    public function regenerateBranding(Request $request, BrandingService $branding): RedirectResponse
    {
        $name = Setting::get('site_name', config('app.name', 'App'));
        $assets = $branding->generate($name);

        Setting::set('site_logo', $assets['logo']);
        Setting::set('site_favicon', $assets['favicon']);
        Setting::set('site_og_image', $assets['og_image']);
        Setting::set('site_theme_color', $assets['theme_color']);

        Setting::clearCache();
        Brand::flush();

        ActivityRecorder::log(
            'pengaturan aplikasi',
            'Membuat ulang logo & favicon dari nama aplikasi "' . $name . '"',
            ['new' => $assets],
        );

        return redirect()
            ->route('settings.index')
            ->with('success', 'Logo, favicon dan OG image berhasil dibuat ulang dari nama aplikasi.');
    }

    /**
     * Move an uploaded brand asset into public/assets/media/branding and point
     * the matching setting at it, removing the file it replaces.
     */
    private function storeUpload(Request $request, string $key, string $prefix): void
    {
        if (! $request->hasFile($key)) {
            return;
        }

        $file = $request->file($key);
        $directory = public_path(BrandingService::DIR);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = $prefix . '-' . time() . '.' . Str::lower($file->getClientOriginalExtension());
        $file->move($directory, $filename);

        $previous = Setting::get($key);
        Setting::set($key, BrandingService::DIR . '/' . $filename);

        $this->deleteReplaced($previous, $filename);
    }

    /**
     * Delete the file a new upload replaced — but never the generated
     * defaults, which the app falls back to.
     */
    private function deleteReplaced(?string $previous, string $justUploaded): void
    {
        if (! is_string($previous) || $previous === '' || ! str_contains($previous, '/')) {
            return;
        }

        $protected = [
            BrandingService::DIR . '/logo-mark.svg',
            BrandingService::DIR . '/logo-full.svg',
            BrandingService::DIR . '/favicon.ico',
            BrandingService::DIR . '/og-image.png',
        ];

        if (in_array($previous, $protected, true) || str_ends_with($previous, $justUploaded)) {
            return;
        }

        $path = public_path(ltrim($previous, '/'));

        // Guard against a stored value escaping the branding directory.
        $root = realpath(public_path(BrandingService::DIR));
        $real = realpath($path);

        if ($root && $real && str_starts_with($real, $root) && is_file($real)) {
            @unlink($real);
        }
    }

    private function saveSocialProviders(Request $request): void
    {
        foreach (self::SOCIAL_PROVIDERS as $provider) {
            Setting::set("social_{$provider}_enabled", $request->boolean("social_{$provider}_enabled") ? '1' : '0');

            if ($request->filled("social_{$provider}_client_id")) {
                Setting::set("social_{$provider}_client_id", (string) $request->input("social_{$provider}_client_id"));
            }

            // An empty secret means "keep the stored one".
            if ($request->filled("social_{$provider}_client_secret")) {
                Setting::set("social_{$provider}_client_secret", (string) $request->input("social_{$provider}_client_secret"));
            }
        }
    }

    /**
     * Strip credentials out of the audit snapshot.
     *
     * @param  array<string,string>  $settings
     * @return array<string,string>
     */
    private function auditable(array $settings): array
    {
        foreach ($settings as $key => $value) {
            if (str_contains($key, 'secret') || str_contains($key, 'client_id')) {
                $settings[$key] = $value === '' ? '' : '••••••••';
            }
        }

        ksort($settings);

        return $settings;
    }
}
