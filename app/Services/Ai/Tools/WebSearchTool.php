<?php

namespace App\Services\Ai\Tools;

use App\Services\Ai\AgentContext;
use App\Services\Ai\GroqClient;
use App\Services\Ai\TavilyClient;

/**
 * Web search for current or external facts.
 *
 * Uses Tavily by default. When GROQ_MODEL_WEB is set (a Groq model with
 * built-in browsing such as groq/compound), the query is answered by that
 * model instead and its summary is handed back to the agent.
 */
class WebSearchTool implements AiTool
{
    public function __construct(
        private readonly TavilyClient $tavily,
        private readonly GroqClient $groq,
    ) {}

    public function name(): string
    {
        return 'web_search';
    }

    public function availableFor(AgentContext $context): bool
    {
        return (string) config('ai.tavily.key') !== '' || config('ai.groq.web_model') !== null;
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Cari informasi terkini di internet (berita, harga resmi produsen, regulasi, '
                    . 'spesifikasi kendaraan, alamat). Gunakan hanya bila jawabannya tidak ada di data OtoGo.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Kata kunci pencarian yang spesifik, sebaiknya dalam Bahasa Indonesia.',
                        ],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
    }

    public function run(array $arguments, AgentContext $context): array
    {
        $query = trim((string) ($arguments['query'] ?? ''));

        if ($query === '') {
            return ['content' => ['error' => 'Parameter query wajib diisi.']];
        }

        $webModel = config('ai.groq.web_model');

        if ($webModel !== null && (string) config('ai.tavily.key') === '') {
            return $this->viaGroqWebModel((string) $webModel, $query);
        }

        $search = $this->tavily->search($query);

        if (! $search['ok']) {
            if ($webModel !== null) {
                return $this->viaGroqWebModel((string) $webModel, $query);
            }

            return ['content' => ['error' => $search['error']]];
        }

        return [
            'content' => [
                'query' => $query,
                'results' => $search['results'],
                'instruksi' => 'Rangkum hasil ini dan sebutkan sumbernya. Jangan mengarang di luar hasil.',
            ],
            'sources' => array_map(
                fn (array $r) => ['title' => $r['title'], 'url' => $r['url']],
                $search['results'],
            ),
        ];
    }

    private function viaGroqWebModel(string $model, string $query): array
    {
        $result = $this->groq->chat([
            ['role' => 'system', 'content' => 'Cari di web dan jawab ringkas dalam Bahasa Indonesia. Sertakan URL sumber.'],
            ['role' => 'user', 'content' => $query],
        ], [], $model);

        if (! $result->ok) {
            return ['content' => ['error' => 'Pencarian web sedang tidak tersedia.']];
        }

        return ['content' => ['query' => $query, 'ringkasan_web' => $result->content]];
    }
}
