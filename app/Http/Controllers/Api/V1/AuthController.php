<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\AuthenticateWithPassword;
use App\Actions\Auth\IssueApiToken;
use App\Actions\Auth\RegisterCustomer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\AuthTokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/register',
        operationId: 'authRegister',
        summary: 'Daftar akun pelanggan baru',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name', 'email', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Budi Santoso'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'budi@contoh.id'),
                new OA\Property(property: 'phone', type: 'string', nullable: true, example: '081234567890'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'rahasia123'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'rahasia123'),
                new OA\Property(property: 'device_name', type: 'string', nullable: true, example: 'Pixel 8'),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Akun dibuat', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/AuthToken'),
            ])),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function register(RegisterRequest $request, RegisterCustomer $register): JsonResponse
    {
        $result = $register->handle($request->validated());

        return (new AuthTokenResource($result))->response()->setStatusCode(201);
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        operationId: 'authLogin',
        summary: 'Login dengan email dan kata sandi',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'budi@contoh.id'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'rahasia123'),
                new OA\Property(property: 'device_name', type: 'string', nullable: true, example: 'Pixel 8'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Login berhasil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/AuthToken'),
            ])),
            new OA\Response(response: 403, description: 'Akun diblokir atau tidak aktif', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Email atau kata sandi salah', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
            new OA\Response(response: 429, description: 'Terlalu banyak percobaan', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function login(LoginRequest $request, AuthenticateWithPassword $authenticate, IssueApiToken $issueToken): AuthTokenResource
    {
        $data = $request->validated();
        $user = $authenticate->handle($data['email'], $data['password']);

        return new AuthTokenResource([
            'user' => $user,
            'token' => $issueToken->handle($user, $data['device_name'] ?? null),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        operationId: 'authLogout',
        summary: 'Logout dari perangkat ini (mencabut token yang dipakai)',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Berhasil logout', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Belum login', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Berhasil logout.']);
    }
}
