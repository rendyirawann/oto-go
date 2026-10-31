<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\Blog\PublishedPosts;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(PublishedPosts $posts): View
    {
        $template = $this->template();

        return view('frontend.home', [
            'template' => $template,
            'estimator' => $this->estimatorData($template),
            'plans' => $template['plans'] ?? [],
            'areas' => $template['service_areas'] ?? [],
            'categoryCount' => count($template['vehicle_categories'] ?? []),
            'serviceCount' => count($template['items'] ?? []),
            'latestPosts' => $this->safely(fn () => $posts->latest(3), collect()),
        ]);
    }

    /** @return array<string,mixed> */
    private function template(): array
    {
        $path = database_path('data/medan_price_template.php');

        return is_file($path) ? (array) require $path : [];
    }

    /**
     * Shapes the price template for the estimator: categories grouped by
     * vehicle type and, per category, the services that have a price.
     *
     * @return array<string,mixed>
     */
    private function estimatorData(array $template): array
    {
        $modules = ['carwash' => 'Doorsmeer', 'workshop' => 'Bengkel'];
        $categories = $template['vehicle_categories'] ?? [];
        $prices = [];

        foreach ($categories as $category) {
            $code = $category['code'];
            $rows = [];

            foreach ($template['items'] ?? [] as $item) {
                if (! isset($item['prices'][$code])) {
                    continue;
                }

                $rows[] = [
                    'module' => $item['module'],
                    'module_label' => $modules[$item['module']] ?? $item['module'],
                    'service' => $item['service'],
                    'group' => $item['group'],
                    'price' => (int) $item['prices'][$code],
                    'duration' => (int) ($item['duration'] ?? 0),
                    'addon' => (bool) ($item['addon'] ?? false),
                ];
            }

            $prices[$code] = $rows;
        }

        return [
            'categories' => array_map(fn ($c) => [
                'code' => $c['code'],
                'type' => $c['type'],
                'name' => $c['name'],
            ], $categories),
            'prices' => $prices,
            'modules' => $modules,
            'default' => 'car_mpv',
            'disclaimer' => $template['disclaimer'] ?? '',
            'template' => $template['name'] ?? '',
        ];
    }

    private function safely(\Closure $callback, mixed $fallback): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            report($e);

            return $fallback;
        }
    }
}
