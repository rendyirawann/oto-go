<?php

namespace App\Http\Controllers\Backend\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\ChatRequest;
use App\Models\AiConversation;
use App\Services\Ai\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = AiConversation::query()
            ->ownedBy($request->user()->id)
            ->latest('updated_at')
            ->limit(40)
            ->get(['id', 'title', 'channel', 'updated_at']);

        return view('backend.ai.index', [
            'conversations' => $conversations,
            'configured' => (string) config('ai.groq.key') !== '',
        ]);
    }

    public function chat(ChatRequest $request, ChatService $chat): JsonResponse
    {
        $result = $chat->sendFromWeb(
            $request->user(),
            (string) $request->validated('message'),
            $request->validated('conversation_id'),
        );

        return response()->json($result);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $conversation = $this->owned($request, $id);

        $messages = $conversation->messages()
            ->get(['id', 'role', 'content', 'meta', 'created_at'])
            ->map(fn ($m) => [
                'id' => $m->id,
                'role' => $m->role,
                'content' => $m->content,
                'sources' => $m->meta['sources'] ?? [],
                'created_at' => $m->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'messages' => $messages,
            ],
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->owned($request, $id)->delete();

        return response()->json(['ok' => true, 'message' => 'Percakapan dihapus.']);
    }

    private function owned(Request $request, string $id): AiConversation
    {
        return AiConversation::query()->ownedBy($request->user()->id)->whereKey($id)->firstOrFail();
    }
}
