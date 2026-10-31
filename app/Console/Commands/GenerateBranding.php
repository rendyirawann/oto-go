<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\BrandingService;
use Illuminate\Console\Command;

class GenerateBranding extends Command
{
    protected $signature = 'app:branding
                            {name? : Application name to build the artwork from (defaults to the saved site_name)}
                            {--apply : Point the site_logo / site_favicon settings at the generated files}';

    protected $description = 'Generate the logo, favicon set and OG image from the application name';

    public function handle(BrandingService $branding): int
    {
        $name = $this->argument('name')
            ?: Setting::get('site_name', config('app.name', 'App'));

        $this->info("Generating brand assets for \"{$name}\" ...");

        $assets = $branding->generate($name);

        foreach ($assets as $key => $value) {
            $this->line(sprintf('  <fg=gray>%-18s</> %s', $key, $value));
        }

        if ($this->option('apply')) {
            Setting::set('site_logo', $assets['logo']);
            Setting::set('site_favicon', $assets['favicon']);
            Setting::set('site_og_image', $assets['og_image']);
            Setting::set('site_theme_color', $assets['theme_color']);
            Setting::clearCache();
            $this->info('Settings updated to use the generated assets.');
        }

        return self::SUCCESS;
    }
}
