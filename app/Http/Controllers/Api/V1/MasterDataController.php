<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Vehicle\MasterDataCache;
use App\Http\Controllers\Controller;
use App\Http\Resources\PriceEstimateResource;
use App\Http\Resources\ServiceAreaResource;
use App\Http\Resources\VehicleBrandResource;
use App\Http\Resources\VehicleCategoryResource;
use App\Http\Resources\VehicleModelResource;
use App\Http\Resources\VehicleTypeResource;
use App\Models\PriceTemplate;
use App\Models\PriceTemplateItem;
use App\Models\ServiceArea;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Public reference data. Every response is cached (MasterDataCache, 1 hour,
 * flushed by the seeders) and only selects the columns it renders.
 */
class MasterDataController extends Controller
{
    private const TYPES = ['motor', 'mobil'];

    private const MODULES = ['workshop', 'carwash', 'sparepart'];

    #[OA\Get(
        path: '/api/v1/vehicle-types',
        operationId: 'vehicleTypes',
        summary: 'Jenis kendaraan (motor, mobil)',
        tags: ['Master Data'],
        responses: [new OA\Response(response: 200, description: 'Daftar jenis', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/VehicleType')),
        ]))],
    )]
    public function vehicleTypes(Request $request): JsonResponse
    {
        $data = MasterDataCache::remember('vehicle-types', fn () => VehicleTypeResource::collection(
            VehicleType::query()->orderBy('sort')->get(['id', 'code', 'name'])
        )->resolve($request));

        return $this->ok($data);
    }

    #[OA\Get(
        path: '/api/v1/vehicle-categories',
        operationId: 'vehicleCategories',
        summary: 'Kategori kendaraan untuk tarif',
        tags: ['Master Data'],
        parameters: [new OA\QueryParameter(name: 'type', description: 'Filter jenis kendaraan', required: false, schema: new OA\Schema(type: 'string', enum: ['motor', 'mobil']))],
        responses: [new OA\Response(response: 200, description: 'Daftar kategori', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/VehicleCategory')),
        ]))],
    )]
    public function vehicleCategories(Request $request): JsonResponse
    {
        $type = $this->typeFilter($request);

        $data = MasterDataCache::remember('vehicle-categories:' . ($type ?? 'all'), fn () => VehicleCategoryResource::collection(
            VehicleCategory::query()
                ->select(['id', 'vehicle_type_id', 'code', 'name', 'sort'])
                ->with('type:id,code')
                ->when($type, fn ($q) => $q->whereHas('type', fn ($t) => $t->where('code', $type)))
                ->orderBy('vehicle_type_id')
                ->orderBy('sort')
                ->get()
        )->resolve($request));

        return $this->ok($data);
    }

    #[OA\Get(
        path: '/api/v1/vehicle-brands',
        operationId: 'vehicleBrands',
        summary: 'Merek kendaraan',
        tags: ['Master Data'],
        parameters: [new OA\QueryParameter(name: 'type', required: false, schema: new OA\Schema(type: 'string', enum: ['motor', 'mobil']))],
        responses: [new OA\Response(response: 200, description: 'Daftar merek', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/VehicleBrand')),
        ]))],
    )]
    public function vehicleBrands(Request $request): JsonResponse
    {
        $type = $this->typeFilter($request);

        $data = MasterDataCache::remember('vehicle-brands:' . ($type ?? 'all'), fn () => VehicleBrandResource::collection(
            VehicleBrand::query()
                ->select(['id', 'vehicle_type_id', 'name'])
                ->with('type:id,code')
                ->when($type, fn ($q) => $q->whereHas('type', fn ($t) => $t->where('code', $type)))
                ->orderBy('name')
                ->get()
        )->resolve($request));

        return $this->ok($data);
    }

    #[OA\Get(
        path: '/api/v1/vehicle-models',
        operationId: 'vehicleModels',
        summary: 'Model kendaraan beserta kategori default',
        tags: ['Master Data'],
        parameters: [
            new OA\QueryParameter(name: 'brand_id', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'q', description: 'Cari nama model', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Daftar model', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/VehicleModel')),
        ]))],
    )]
    public function vehicleModels(Request $request): JsonResponse
    {
        $brandId = filter_var($request->query('brand_id'), FILTER_VALIDATE_INT) ?: null;
        $q = Str::limit(trim((string) $request->query('q', '')), 50, '');

        $key = 'vehicle-models:' . ($brandId ?? 'all') . ':' . md5(Str::lower($q));

        $data = MasterDataCache::remember($key, fn () => VehicleModelResource::collection(
            VehicleModel::query()
                ->select(['id', 'vehicle_brand_id', 'name', 'default_category_id'])
                ->with(['brand:id,name', 'defaultCategory:id,code,name'])
                ->when($brandId, fn ($query) => $query->where('vehicle_brand_id', $brandId))
                ->when($q !== '', fn ($query) => $query->where('name', 'like', '%' . addcslashes($q, '%_\\') . '%'))
                ->orderBy('name')
                ->limit(200)
                ->get()
        )->resolve($request));

        return $this->ok($data);
    }

    #[OA\Get(
        path: '/api/v1/service-areas',
        operationId: 'serviceAreas',
        summary: 'Wilayah layanan (kecamatan)',
        tags: ['Master Data'],
        responses: [new OA\Response(response: 200, description: 'Daftar wilayah', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ServiceArea')),
        ]))],
    )]
    public function serviceAreas(Request $request): JsonResponse
    {
        $data = MasterDataCache::remember('service-areas', fn () => ServiceAreaResource::collection(
            ServiceArea::query()->where('is_active', true)->orderBy('city')->orderBy('district')->get(['id', 'city', 'district'])
        )->resolve($request));

        return $this->ok($data);
    }

    #[OA\Get(
        path: '/api/v1/price-estimates',
        operationId: 'priceEstimates',
        summary: 'Estimasi harga pasar (template tarif Medan)',
        description: 'Tanpa `vehicle_category` semua baris dikembalikan. Harga dalam rupiah bulat dan hanya estimasi.',
        tags: ['Master Data'],
        parameters: [
            new OA\QueryParameter(name: 'vehicle_category', description: 'Kode kategori, mis. car_mpv', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'module', required: false, schema: new OA\Schema(type: 'string', enum: ['workshop', 'carwash', 'sparepart'])),
        ],
        responses: [new OA\Response(response: 200, description: 'Daftar estimasi', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PriceEstimate')),
            new OA\Property(property: 'meta', properties: [
                new OA\Property(property: 'template', type: 'string', example: 'Tarif Pasar Medan 2026'),
                new OA\Property(property: 'disclaimer', type: 'string'),
            ], type: 'object'),
        ]))],
    )]
    public function priceEstimates(Request $request): JsonResponse
    {
        $category = preg_match('/^[a-z_]{3,40}$/', (string) $request->query('vehicle_category')) === 1
            ? (string) $request->query('vehicle_category')
            : null;
        $module = in_array($request->query('module'), self::MODULES, true) ? (string) $request->query('module') : null;

        $payload = MasterDataCache::remember(
            'price-estimates:' . ($category ?? 'all') . ':' . ($module ?? 'all'),
            function () use ($request, $category, $module) {
                $template = PriceTemplate::query()
                    ->where('is_active', true)
                    ->orderByDesc('id')
                    ->first(['id', 'name', 'disclaimer']);

                if ($template === null) {
                    return ['data' => [], 'meta' => ['template' => null, 'disclaimer' => null]];
                }

                $items = PriceTemplateItem::query()
                    ->select(['module_code', 'group_name', 'service_name', 'vehicle_category_code', 'price', 'duration_minutes', 'is_addon'])
                    ->where('price_template_id', $template->id)
                    ->when($module, fn ($q) => $q->where('module_code', $module))
                    ->when($category, function ($q) use ($category, $module) {
                        // Sparepart rows have no category; match them by vehicle type.
                        if ($module === 'sparepart') {
                            $type = str_starts_with($category, 'motor_') ? 'Motor' : 'Mobil';

                            return $q->where('group_name', 'Sparepart ' . $type);
                        }

                        return $q->where('vehicle_category_code', $category);
                    })
                    ->orderBy('module_code')
                    ->orderBy('group_name')
                    ->orderBy('service_name')
                    ->orderBy('vehicle_category_code')
                    ->get();

                return [
                    'data' => PriceEstimateResource::collection($items)->resolve($request),
                    'meta' => ['template' => $template->name, 'disclaimer' => $template->disclaimer],
                ];
            }
        );

        return response()->json($payload);
    }

    /** @param array<int,array<string,mixed>> $data */
    private function ok(array $data): JsonResponse
    {
        return response()->json(['data' => $data]);
    }

    private function typeFilter(Request $request): ?string
    {
        $type = $request->query('type');

        return in_array($type, self::TYPES, true) ? $type : null;
    }
}
