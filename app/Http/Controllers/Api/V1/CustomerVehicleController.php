<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Vehicle\SaveCustomerVehicle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vehicle\SaveCustomerVehicleRequest;
use App\Http\Resources\CustomerVehicleResource;
use App\Models\CustomerVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

/**
 * "Kendaraan Saya". Every lookup is scoped to the signed-in customer, so
 * another customer's vehicle id simply returns 404; the policy is a second
 * guard on top of that.
 */
class CustomerVehicleController extends Controller
{
    #[OA\Get(
        path: '/api/v1/me/vehicles',
        operationId: 'myVehiclesIndex',
        summary: 'Daftar kendaraan saya',
        security: [['sanctum' => []]],
        tags: ['Kendaraan Saya'],
        responses: [
            new OA\Response(response: 200, description: 'Daftar kendaraan', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Vehicle')),
            ])),
            new OA\Response(response: 401, description: 'Belum login', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', CustomerVehicle::class);

        $vehicles = CustomerVehicle::query()
            ->withDetails()
            ->where('user_id', $request->user()->getKey())
            ->latest()
            ->limit(100)
            ->get();

        return CustomerVehicleResource::collection($vehicles);
    }

    #[OA\Post(
        path: '/api/v1/me/vehicles',
        operationId: 'myVehiclesStore',
        summary: 'Tambah kendaraan',
        security: [['sanctum' => []]],
        tags: ['Kendaraan Saya'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/VehicleInput')),
        responses: [
            new OA\Response(response: 201, description: 'Kendaraan ditambahkan', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Vehicle'),
            ])),
            new OA\Response(response: 401, description: 'Belum login', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function store(SaveCustomerVehicleRequest $request, SaveCustomerVehicle $save): JsonResponse
    {
        Gate::authorize('create', CustomerVehicle::class);

        $vehicle = $save->handle($request->user(), $request->validated());

        return (new CustomerVehicleResource($vehicle))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/me/vehicles/{id}',
        operationId: 'myVehiclesShow',
        summary: 'Detail kendaraan',
        security: [['sanctum' => []]],
        tags: ['Kendaraan Saya'],
        parameters: [new OA\PathParameter(name: 'id', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Vehicle'),
            ])),
            new OA\Response(response: 404, description: 'Tidak ditemukan', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(Request $request, string $vehicle): CustomerVehicleResource
    {
        $model = $this->find($request, $vehicle);
        Gate::authorize('view', $model);

        return new CustomerVehicleResource($model);
    }

    #[OA\Put(
        path: '/api/v1/me/vehicles/{id}',
        operationId: 'myVehiclesUpdate',
        summary: 'Ubah kendaraan',
        security: [['sanctum' => []]],
        tags: ['Kendaraan Saya'],
        parameters: [new OA\PathParameter(name: 'id', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/VehicleInput')),
        responses: [
            new OA\Response(response: 200, description: 'Kendaraan diperbarui', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Vehicle'),
            ])),
            new OA\Response(response: 404, description: 'Tidak ditemukan', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function update(SaveCustomerVehicleRequest $request, SaveCustomerVehicle $save, string $vehicle): CustomerVehicleResource
    {
        $model = $this->find($request, $vehicle);
        Gate::authorize('update', $model);

        return new CustomerVehicleResource($save->handle($request->user(), $request->validated(), $model));
    }

    #[OA\Delete(
        path: '/api/v1/me/vehicles/{id}',
        operationId: 'myVehiclesDestroy',
        summary: 'Hapus kendaraan',
        security: [['sanctum' => []]],
        tags: ['Kendaraan Saya'],
        parameters: [new OA\PathParameter(name: 'id', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Dihapus', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 404, description: 'Tidak ditemukan', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(Request $request, string $vehicle): JsonResponse
    {
        $model = $this->find($request, $vehicle);
        Gate::authorize('delete', $model);

        $model->delete();

        return response()->json(['message' => 'Kendaraan berhasil dihapus.']);
    }

    private function find(Request $request, string $id): CustomerVehicle
    {
        return CustomerVehicle::query()
            ->withDetails()
            ->where('user_id', $request->user()->getKey())
            ->findOrFail($id);
    }
}
