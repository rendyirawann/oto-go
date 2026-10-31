<?php

namespace App\Services\Ai\Tools;

use App\Services\Ai\AgentContext;

interface AiTool
{
    public function name(): string;

    /** Whether this tool is offered to the given user at all. */
    public function availableFor(AgentContext $context): bool;

    /** OpenAI-format tool definition ({type: function, function: {...}}). */
    public function definition(): array;

    /**
     * Execute with model-supplied arguments.
     *
     * @return array{content: array<string,mixed>, sources?: array<int,array{title:string,url:string}>}
     */
    public function run(array $arguments, AgentContext $context): array;
}
