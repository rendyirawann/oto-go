<?php

namespace App\Services\Ai;

final class AgentReply
{
    /**
     * @param  array<int,array{title:string,url:string}>  $sources
     * @param  array<int,string>  $toolsUsed
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $reply = '',
        public readonly array $sources = [],
        public readonly ?string $model = null,
        public readonly array $toolsUsed = [],
        public readonly ?string $error = null,
    ) {}

    public static function unavailable(?string $detail = null): self
    {
        return new self(false, '', [], null, [], $detail);
    }
}
