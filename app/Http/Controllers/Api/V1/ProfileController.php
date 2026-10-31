<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\ChangePassword;
use App\Actions\Auth\UpdateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;

class ProfileController extends Controller
{
    #[OA\Get(
        path: '/api/v1/me',
        operationId: 'meShow',
        summary: 'Profil akun yang sedang login',
        security: [['sanctum' => []]],
        tags: ['Profil'],
        responses: [
            new OA\Response(response: 200, description: 'Profil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/User'),
            ])),
            new OA\Response(response: 401, description: 'Belum login', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user()->load('roles:id,name'));
    }

    #[OA\Put(
        path: '/api/v1/me',
        operationId: 'meUpdate',
        summary: 'Ubah nama dan nomor HP',
        security: [['sanctum' => []]],
        tags: ['Profil'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Budi Santoso'),
                new OA\Property(property: 'phone', type: 'string', nullable: true, example: '081234567890'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Profil diperbarui', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/User'),
            ])),
            new OA\Response(response: 401, description: 'Belum login', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function update(UpdateProfileRequest $request, UpdateProfile $updateProfile): UserResource
    {
        $user = $updateProfile->handle($request->user(), $request->validated());

        return new UserResource($user->load('roles:id,name'));
    }

    #[OA\Put(
        path: '/api/v1/me/password',
        operationId: 'mePassword',
        summary: 'Ganti kata sandi (perangkat lain otomatis logout)',
        security: [['sanctum' => []]],
        tags: ['Profil'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['current_password', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'current_password', type: 'string', format: 'password'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Kata sandi diganti', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Belum login', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function password(ChangePasswordRequest $request, ChangePassword $changePassword): JsonResponse
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        $changePassword->handle(
            $user,
            $request->validated('password'),
            $token instanceof PersonalAccessToken ? $token : null,
        );

        return response()->json(['message' => 'Kata sandi berhasil diganti. Perangkat lain telah dikeluarkan.']);
    }
}
