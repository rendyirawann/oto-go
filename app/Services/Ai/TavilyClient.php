<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Tavily web search. Returns plain arrays and never throws.
 */
class TavilyClient
{
    /**
     * @return array{ok:bool, results:array<int,array{title:string,url:string,snippet:string}>, error:?string}
     */
    public function search(string $query, ?int $maxResults = null): array
    {
        $key = (string) config('ai.tavily.key');
        $query = trim($query);

        if ($key === '') {
            return ['ok' => false, 'results' => [], 'error' => 'Pencarian web belum dikonfigurasi (TAVILY_API_KEY kosong).'];
        }

        if ($query === '') {
            return ['ok' => false, 'results' => [], 'error' => 'Kata kunci pencarian kosong.'];
        }

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->asJson()
                ->connectTimeout(8)
                ->timeout((int) config('ai.tavily.timeout', 20))
                ->post(rtrim((string) config('ai.tavily.base_url'), '/') . '/search', [
                    'query' => Str::limit($query, 380, ''),
                    'max_results' => $maxResults ?? (int) config('ai.tavily.max_results', 5),
                    'search_depth' => 'basic',
                    'include_answer' => false,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Tavily search failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'results' => [], 'error' => 'Pencarian web sedang tidak tersedia.'];
        }

        if (! $response->successful()) {
            Log::warning('Tavily returned an error', ['status' => $response->status()]);

            return ['ok' => false, 'results' => [], 'error' => 'Pencarian web sedang tidak tersedia.'];
        }

        $results = [];

        foreach ((array) $response->json('results', []) as $row) {
            $url = (string) ($row['url'] ?? '');

            if (! preg_match('#^https?://#i', $url)) {
                continue;
            }

            $results[] = [
                'title' => Str::limit(trim((string) ($row['title'] ?? $url)), 140),
                'url' => $url,
                'snippet' => Str::limit(trim(preg_replace('/\s+/', ' ', (string) ($row['content'] ?? '')) ?? ''), 500),
            ];
        }

        return ['ok' => true, 'results' => $results, 'error' => null];
    }
}
