<?php

namespace App\Services\Ai;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Glue between HTTP and the agent: resolves/creates the conversation,
 * persists the exchange, and shapes the JSON both endpoints return.
 */
class ChatService
{
    public function __construct(private readonly AgentService $agent) {}

    /**
     * Web flow: the server owns the history.
     *
     * @return array{ok:bool, message?:string, data?:array<string,mixed>}
     */
    public function sendFromWeb(User $user, string $message, ?string $conversationId): array
    {
        $conversation = $this->resolveConversation($user, $conversationId, 'web', $message);

        $this->store($conversation, 'user', $message);

        $history = $conversation->messages()
            ->latest('id')
            ->limit((int) config('ai.max_history', 20))
            ->get(['role', 'content'])
            ->reverse()
            ->map(fn (AiMessage $m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();

        return $this->respond($user, $conversation, $history, 'web');
    }

    /**
     * API flow: the client sends its own history (mobile keeps it locally).
     *
     * @param  array<int,array{role:string,content:string}>  $messages
     * @return array{ok:bool, message?:string, data?:array<string,mixed>}
     */
    public function sendFromApi(User $user, array $messages, ?string $conversationId): array
    {
        $last = (string) (end($messages)['content'] ?? '');
        $conversation = $this->resolveConversation($user, $conversationId, 'mobile', $last);

        $this->store($conversation, 'user', $last);

        return $this->respond($user, $conversation, $messages, 'mobile');
    }

    /**
     * @param  array<int,array{role:string,content:string}>  $history
     * @return array{ok:bool, message?:string, data?:array<string,mixed>}
     */
    private function respond(User $user, AiConversation $conversation, array $history, string $channel): array
    {
        $reply = $this->agent->reply(AgentContext::for($user, $channel), $history);

        if (! $reply->ok) {
            return [
                'ok' => false,
                'message' => (string) config('ai.unavailable_message'),
                'data' => ['conversation_id' => $conversation->id],
            ];
        }

        $stored = $this->store($conversation, 'assistant', $reply->reply, [
            'sources' => $reply->sources,
            'model' => $reply->model,
            'tools' => $reply->toolsUsed,
        ]);

        $conversation->touch();

        return [
            'ok' => true,
            'data' => [
                'reply' => $reply->reply,
                'sources' => $reply->sources,
                'model' => $reply->model,
                'tools' => $reply->toolsUsed,
                'conversation_id' => $conversation->id,
                'conversation_title' => $conversation->title,
                'message_id' => $stored->id,
            ],
        ];
    }

    private function resolveConversation(User $user, ?string $id, string $channel, string $firstMessage): AiConversation
    {
        if ($id !== null && $id !== '') {
            $existing = AiConversation::query()->ownedBy($user->id)->whereKey($id)->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return AiConversation::create([
            'user_id' => $user->id,
            'channel' => $channel,
            'title' => $this->titleFrom($firstMessage),
        ]);
    }

    private function store(AiConversation $conversation, string $role, string $content, ?array $meta = null): AiMessage
    {
        return DB::transaction(fn () => AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role' => $role,
            'content' => mb_substr($content, 0, 20000),
            'meta' => $meta,
        ]));
    }

    private function titleFrom(string $message): string
    {
        $title = trim(preg_replace('/\s+/', ' ', strip_tags($message)) ?? '');

        return $title === '' ? 'Percakapan baru' : Str::limit($title, 60);
    }
}
