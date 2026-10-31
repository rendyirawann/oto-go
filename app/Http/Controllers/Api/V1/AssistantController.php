<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\ApiChatRequest;
use App\Services\Ai\ChatService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AssistantController extends Controller
{
    #[OA\Post(
        path: '/api/v1/assistant/chat',
        operationId: 'assistantChat',
        summary: 'Kirim pesan ke Asisten AI OtoGo',
        description: 'Agen AI (Groq) dengan alat: estimasi tarif pasar Medan, pencarian artikel blog, dan pencarian web. '
            . 'Kirim riwayat percakapan (maks 20 pesan, pesan terakhir dari "user"). '
            . 'Bila layanan AI sedang tidak tersedia, respons tetap HTTP 200 dengan "ok": false.',
        security: [['sanctum' => []]],
        tags: ['Asisten AI'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['messages'],
                properties: [
                    new OA\Property(
                        property: 'messages',
                        type: 'array',
                        maxItems: 20,
                        minItems: 1,
                        items: new OA\Items(
                            required: ['role', 'content'],
                            properties: [
                                new OA\Property(property: 'role', type: 'string', enum: ['user', 'assistant'], example: 'user'),
                                new OA\Property(property: 'content', type: 'string', maxLength: 4000, example: 'Berapa estimasi cuci salju untuk SUV?'),
                            ],
                        ),
                    ),
                    new OA\Property(property: 'conversation_id', type: 'string', nullable: true, example: '01JBQ7Z9S3X8M2K4Y6T1V0W5RA'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Jawaban asisten (ok: true) atau layanan AI tidak tersedia (ok: false).',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', nullable: true, example: null),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'reply', type: 'string', description: 'Markdown', example: 'Estimasi cuci salju untuk SUV di Medan sekitar **Rp 50.000**.'),
                                new OA\Property(
                                    property: 'sources',
                                    type: 'array',
                                    items: new OA\Items(properties: [
                                        new OA\Property(property: 'title', type: 'string'),
                                        new OA\Property(property: 'url', type: 'string', format: 'uri'),
                                    ]),
                                ),
                                new OA\Property(property: 'model', type: 'string', example: 'openai/gpt-oss-120b'),
                                new OA\Property(property: 'conversation_id', type: 'string'),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Token tidak valid atau belum login.'),
            new OA\Response(response: 422, description: 'Validasi gagal.'),
            new OA\Response(response: 429, description: 'Terlalu banyak permintaan.'),
        ],
    )]
    public function chat(ApiChatRequest $request, ChatService $chat): JsonResponse
    {
        $messages = array_map(
            fn (array $m) => ['role' => (string) $m['role'], 'content' => (string) $m['content']],
            array_values((array) $request->validated('messages')),
        );

        $result = $chat->sendFromApi($request->user(), $messages, $request->validated('conversation_id'));

        if (! $result['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'],
            ]);
        }

        $data = $result['data'];

        return response()->json([
            'ok' => true,
            'data' => [
                'reply' => $data['reply'],
                'sources' => $data['sources'],
                'model' => $data['model'],
                'conversation_id' => $data['conversation_id'],
            ],
        ]);
    }
}
