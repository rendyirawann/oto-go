<?php

namespace App\Services\Ai\Tools;

use App\Services\Ai\AgentContext;
use Illuminate\Support\Str;

/**
 * Price estimates from the "Tarif Pasar Medan 2026" template
 * (database/data/medan_price_template.php - shared with the seeder).
 */
class PriceEstimateTool implements AiTool
{
    /** Loose words a user (or the model) might use for a vehicle category. */
    private const ALIASES = [
        'matic' => 'motor_matic', 'skuter' => 'motor_matic', 'scooter' => 'motor_matic',
        'bebek' => 'motor_bebek', 'cub' => 'motor_bebek',
        'sport' => 'motor_sport', 'naked' => 'motor_sport',
        'moge' => 'motor_big', 'motor besar' => 'motor_big', 'big bike' => 'motor_big',
        'city car' => 'car_city', 'hatchback' => 'car_city', 'lcgc' => 'car_city',
        'sedan' => 'car_sedan',
        'mpv' => 'car_mpv', 'avanza' => 'car_mpv', 'xenia' => 'car_mpv', 'ertiga' => 'car_mpv', 'innova' => 'car_mpv',
        'suv' => 'car_suv', 'fortuner' => 'car_suv', 'pajero' => 'car_suv', 'cr-v' => 'car_suv', 'crv' => 'car_suv',
        'pickup' => 'car_pickup', 'pick up' => 'car_pickup', 'double cabin' => 'car_pickup', 'hilux' => 'car_pickup',
        'van' => 'car_van', 'minibus' => 'car_van', 'hiace' => 'car_van', 'elf' => 'car_van',
        'premium' => 'car_premium', 'eropa' => 'car_premium', 'mewah' => 'car_premium',
    ];

    private const MODULES = ['carwash', 'workshop', 'sparepart', 'logistics', 'plans'];

    public function name(): string
    {
        return 'price_estimate';
    }

    public function availableFor(AgentContext $context): bool
    {
        return is_file((string) config('ai.price_template_path'));
    }

    public function definition(): array
    {
        $codes = array_column($this->template()['vehicle_categories'] ?? [], 'code');

        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Ambil estimasi harga pasar Kota Medan 2026 dari data OtoGo: jasa bengkel (workshop), '
                    . 'cuci/doorsmeer (carwash), sparepart, biaya antar jemput/home service (logistics), dan paket '
                    . 'langganan usaha (plans). WAJIB dipakai untuk setiap pertanyaan harga.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'vehicle_category' => [
                            'type' => 'string',
                            'description' => 'Kode kategori kendaraan, salah satu: ' . implode(', ', $codes)
                                . '. Boleh juga kata umum seperti "SUV", "matic", "Avanza".',
                        ],
                        'keyword' => [
                            'type' => 'string',
                            'description' => 'Kata kunci jasa/produk, misal "cuci salju", "ganti oli", "spooring", "aki".',
                        ],
                        'module' => [
                            'type' => 'string',
                            'enum' => self::MODULES,
                            'description' => 'Batasi ke satu jenis layanan.',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function run(array $arguments, AgentContext $context): array
    {
        $template = $this->template();

        if ($template === []) {
            return ['content' => ['error' => 'Data tarif belum tersedia.']];
        }

        $category = $this->resolveCategory((string) ($arguments['vehicle_category'] ?? ''), $template);
        $keyword = Str::lower(trim((string) ($arguments['keyword'] ?? '')));
        $module = in_array($arguments['module'] ?? null, self::MODULES, true) ? $arguments['module'] : null;

        $out = [
            'template' => $template['name'] ?? 'Tarif Pasar Medan',
            'disclaimer' => $template['disclaimer'] ?? 'Estimasi, bukan harga resmi.',
            'kategori_kendaraan' => $category,
            'catatan' => 'Harga jasa belum termasuk sparepart/oli kecuali disebutkan. Semua angka dalam rupiah.',
        ];

        if ($module === null || in_array($module, ['carwash', 'workshop'], true)) {
            $out['jasa'] = $this->services($template['items'] ?? [], $category, $keyword, $module);
        }

        if ($module === 'sparepart' || ($module === null && $keyword !== '')) {
            $out['sparepart'] = $this->products($template['products'] ?? [], $keyword, $category);
        }

        if ($module === 'logistics' || Str::contains($keyword, ['antar', 'jemput', 'home', 'rumah', 'ongkir', 'jarak'])) {
            $out['logistik'] = array_map(fn (array $row) => $row + [
                'rumus' => 'biaya_dasar + max(0, ceil(jarak_km - termasuk_km)) x per_km (pulang-pergi untuk antar jemput)',
            ], $template['logistics'] ?? []);
        }

        if ($module === 'plans' || Str::contains($keyword, ['langganan', 'paket usaha', 'berlangganan', 'harga paket'])) {
            $out['paket_usaha'] = $template['plans'] ?? [];
        }

        $empty = empty($out['jasa']) && empty($out['sparepart']) && empty($out['logistik']) && empty($out['paket_usaha']);

        if ($empty) {
            $out['info'] = 'Tidak ada data yang cocok. Coba kata kunci lain atau tanyakan tanpa filter.';
        }

        return ['content' => $out];
    }

    /**
     * @param  array<int,array<string,mixed>>  $items
     * @return array<int,array<string,mixed>>
     */
    private function services(array $items, ?array $category, string $keyword, ?string $module): array
    {
        $rows = [];

        foreach ($items as $item) {
            if ($module !== null && $item['module'] !== $module) {
                continue;
            }

            if ($keyword !== '' && ! $this->matches($keyword, $item['service'] . ' ' . $item['group'] . ' ' . $item['module'])) {
                continue;
            }

            $prices = (array) $item['prices'];
            $row = [
                'layanan' => $item['module'] === 'carwash' ? 'doorsmeer' : 'bengkel',
                'kelompok' => $item['group'],
                'jasa' => $item['service'],
                'durasi_menit' => $item['duration'] ?? null,
            ];

            if ($category !== null) {
                if (! isset($prices[$category['code']])) {
                    continue;
                }
                $row['harga'] = $prices[$category['code']];
                $row['harga_teks'] = $this->rupiah($prices[$category['code']]);
            } else {
                $row['harga_per_kategori'] = $prices;
                $row['kisaran_teks'] = $this->rupiah(min($prices)) . ' - ' . $this->rupiah(max($prices));
            }

            $rows[] = $row;
        }

        return array_slice($rows, 0, 25);
    }

    /**
     * @param  array<int,array<string,mixed>>  $products
     * @return array<int,array<string,mixed>>
     */
    private function products(array $products, string $keyword, ?array $category): array
    {
        $type = $category['type'] ?? null;
        $rows = [];

        foreach ($products as $product) {
            if ($type !== null && ($product['vehicle_type'] ?? null) !== $type) {
                continue;
            }

            if ($keyword !== '' && ! $this->matches($keyword, $product['name'])) {
                continue;
            }

            $rows[] = $product + ['harga_teks' => $this->rupiah((int) $product['price'])];
        }

        return array_slice($rows, 0, 20);
    }

    private function matches(string $keyword, string $haystack): bool
    {
        $haystack = Str::lower($haystack);

        if (Str::contains($haystack, $keyword)) {
            return true;
        }

        // Every meaningful word of the keyword must appear somewhere.
        $words = array_filter(preg_split('/[\s,\/]+/', $keyword) ?: [], fn ($w) => mb_strlen($w) >= 3);

        if ($words === []) {
            return false;
        }

        foreach ($words as $word) {
            if (! Str::contains($haystack, $word)) {
                return false;
            }
        }

        return true;
    }

    /** @return array{code:string,name:string,type:string}|null */
    private function resolveCategory(string $input, array $template): ?array
    {
        $input = Str::lower(trim($input));

        if ($input === '') {
            return null;
        }

        $categories = collect($template['vehicle_categories'] ?? [])->keyBy('code');

        $code = $categories->has($input) ? $input : null;

        if ($code === null) {
            foreach (self::ALIASES as $alias => $candidate) {
                if (Str::contains($input, $alias)) {
                    $code = $candidate;
                    break;
                }
            }
        }

        if ($code === null || ! $categories->has($code)) {
            return null;
        }

        $row = $categories->get($code);

        return ['code' => $row['code'], 'name' => $row['name'], 'type' => $row['type']];
    }

    private function rupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /** @var array<string,mixed>|null */
    private ?array $template = null;

    /** @return array<string,mixed> */
    private function template(): array
    {
        if ($this->template !== null) {
            return $this->template;
        }

        $path = (string) config('ai.price_template_path');

        if (! is_file($path)) {
            return $this->template = [];
        }

        $data = require $path;

        return $this->template = is_array($data) ? $data : [];
    }
}
