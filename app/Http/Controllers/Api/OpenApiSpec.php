<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

/**
 * Root of the OtoGo OpenAPI document (served at /api/documentation).
 *
 * Holds the API info, security scheme, tag list and the schemas that every
 * module shares. Endpoint attributes live on their own controllers.
 *
 * The server URL comes from config('l5-swagger.defaults.paths.base') ("/"),
 * so "Try it out" always targets the origin the docs are served from.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'OtoGo API',
    description: "API resmi OtoGo untuk aplikasi mobile pelanggan dan mitra.\n\n"
        . "**Autentikasi**: login lewat `POST /api/v1/auth/login` atau `POST /api/v1/auth/register`, lalu kirim token di header `Authorization: Bearer <token>`. "
        . "Klik tombol **Authorize** dan tempel token (tanpa kata Bearer).\n\n"
        . "**Format**: semua respons JSON. Data ada di properti `data`; error berbentuk `{\"message\": \"...\", \"errors\": {\"field\": [\"...\"]}}`. "
        . "Uang selalu dalam rupiah bulat (integer).",
    contact: new OA\Contact(name: 'Core Technology Indonesia', url: 'https://github.com/rendyirawann/oto-go'),
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    description: 'Token Sanctum dari endpoint login/register.',
    bearerFormat: 'Token',
    scheme: 'bearer',
)]
#[OA\Tag(name: 'Sistem', description: 'Status layanan')]
#[OA\Tag(name: 'Auth', description: 'Registrasi, login, dan logout pelanggan')]
#[OA\Tag(name: 'Profil', description: 'Data akun yang sedang login')]
#[OA\Tag(name: 'Master Data', description: 'Data referensi publik: jenis & kategori kendaraan, merek, model, wilayah, estimasi harga')]
#[OA\Tag(name: 'Kendaraan Saya', description: 'Garasi pelanggan')]
#[OA\Tag(name: 'Blog', description: 'Artikel perawatan kendaraan')]
#[OA\Tag(name: 'Asisten AI', description: 'Asisten AI OtoGo')]
#[OA\Schema(
    schema: 'ErrorResponse',
    required: ['message'],
    properties: [new OA\Property(property: 'message', type: 'string', example: 'Endpoint atau data tidak ditemukan.')],
)]
#[OA\Schema(
    schema: 'ValidationError',
    required: ['message', 'errors'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Email wajib diisi.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['email' => ['Email wajib diisi.']],
            additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')),
        ),
    ],
)]
#[OA\Schema(
    schema: 'MessageResponse',
    required: ['message'],
    properties: [new OA\Property(property: 'message', type: 'string', example: 'Berhasil.')],
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
        new OA\Property(property: 'per_page', type: 'integer', example: 10),
        new OA\Property(property: 'total', type: 'integer', example: 27),
    ],
)]
#[OA\Schema(
    schema: 'User',
    required: ['id', 'name', 'email', 'roles'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Budi Santoso'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'budi@contoh.id'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '081234567890'),
        new OA\Property(property: 'avatar_url', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['customer']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'AuthToken',
    required: ['token', 'token_type', 'user'],
    properties: [
        new OA\Property(property: 'token', type: 'string', example: '1|aBcDeF...'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ],
)]
#[OA\Schema(
    schema: 'VehicleType',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'code', type: 'string', enum: ['motor', 'mobil']),
        new OA\Property(property: 'name', type: 'string', example: 'Motor'),
    ],
)]
#[OA\Schema(
    schema: 'VehicleCategory',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 7),
        new OA\Property(property: 'code', type: 'string', example: 'car_mpv'),
        new OA\Property(property: 'name', type: 'string', example: 'MPV (Avanza, Xenia, Ertiga, dll)'),
        new OA\Property(property: 'vehicle_type', type: 'string', enum: ['motor', 'mobil']),
    ],
)]
#[OA\Schema(
    schema: 'VehicleBrand',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string', example: 'Toyota'),
        new OA\Property(property: 'vehicle_type', type: 'string', enum: ['motor', 'mobil']),
    ],
)]
#[OA\Schema(
    schema: 'VehicleModel',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string', example: 'Avanza'),
        new OA\Property(property: 'brand_id', type: 'integer'),
        new OA\Property(property: 'brand_name', type: 'string', example: 'Toyota'),
        new OA\Property(
            property: 'default_category',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'code', type: 'string', example: 'car_mpv'),
                new OA\Property(property: 'name', type: 'string'),
            ],
            type: 'object',
        ),
    ],
)]
#[OA\Schema(
    schema: 'ServiceArea',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'city', type: 'string', example: 'Medan'),
        new OA\Property(property: 'district', type: 'string', example: 'Medan Baru'),
    ],
)]
#[OA\Schema(
    schema: 'PriceEstimate',
    properties: [
        new OA\Property(property: 'module', type: 'string', enum: ['workshop', 'carwash', 'sparepart']),
        new OA\Property(property: 'group', type: 'string', example: 'Cuci'),
        new OA\Property(property: 'service', type: 'string', example: 'Cuci reguler (cuci salju)'),
        new OA\Property(property: 'vehicle_category_code', type: 'string', nullable: true, example: 'car_mpv'),
        new OA\Property(property: 'price', type: 'integer', description: 'Rupiah', example: 45000),
        new OA\Property(property: 'duration_minutes', type: 'integer', nullable: true, example: 30),
        new OA\Property(property: 'is_addon', type: 'boolean', example: false),
    ],
)]
#[OA\Schema(
    schema: 'Vehicle',
    properties: [
        new OA\Property(property: 'id', type: 'string', description: 'ULID', example: '01JBX2Q7K8M5N4P3R2S1T0V9W8'),
        new OA\Property(property: 'plate_number', type: 'string', example: 'BK 1234 ABC'),
        new OA\Property(property: 'nickname', type: 'string', nullable: true, example: 'Si Merah'),
        new OA\Property(property: 'year', type: 'integer', nullable: true, example: 2021),
        new OA\Property(property: 'color', type: 'string', nullable: true, example: 'Merah'),
        new OA\Property(
            property: 'category',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'code', type: 'string', example: 'motor_matic'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'vehicle_type', type: 'string', enum: ['motor', 'mobil']),
            ],
            type: 'object',
        ),
        new OA\Property(
            property: 'model',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string', example: 'BeAT'),
                new OA\Property(property: 'brand_name', type: 'string', example: 'Honda'),
            ],
            type: 'object',
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'VehicleInput',
    required: ['vehicle_category_id', 'plate_number'],
    properties: [
        new OA\Property(property: 'vehicle_category_id', type: 'integer', example: 1),
        new OA\Property(property: 'vehicle_model_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'plate_number', type: 'string', example: 'BK 1234 ABC'),
        new OA\Property(property: 'year', type: 'integer', nullable: true, example: 2021),
        new OA\Property(property: 'color', type: 'string', nullable: true, example: 'Hitam'),
        new OA\Property(property: 'nickname', type: 'string', nullable: true, example: 'Motor kantor'),
    ],
)]
#[OA\Get(
    path: '/api/health',
    operationId: 'health',
    summary: 'Cek status layanan',
    tags: ['Sistem'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Layanan berjalan',
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'string', example: 'ok'),
                new OA\Property(property: 'app', type: 'string', example: 'OtoGo'),
                new OA\Property(property: 'version', type: 'string', example: 'v1'),
                new OA\Property(property: 'time', type: 'string', format: 'date-time'),
            ]),
        ),
    ],
)]
final class OpenApiSpec
{
}
