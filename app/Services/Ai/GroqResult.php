<?php

namespace App\Services\Ai;

/**
 * Outcome of one chat-completion call. Never an exception: callers branch on
 * $ok and show $error to the user when it is false.
 */
final class GroqResult
{
    /**
     * @param  array<int,array<string,mixed>>  $toolCalls  OpenAI-format tool calls
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $content = '',
        public readonly array $toolCalls = [],
        public readonly ?string $model = null,
        public readonly ?string $error = null,
        public readonly ?int $status = null,
    ) {}

    public static function failure(string $error, ?int $status = null, ?string $model = null): self
    {
        return new self(false, '', [], $model, $error, $status);
    }

    public function hasToolCalls(): bool
    {
        return $this->toolCalls !== [];
    }
}
