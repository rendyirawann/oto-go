<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for Groq's OpenAI-compatible chat completions endpoint.
 *
 * Resilience policy, per call:
 *  - transient failure (timeout, connection error, 429, 5xx) -> retry the same
 *    model once, then move on to the fallback model;
 *  - model rejected (404, 400 "model"/"tool_use_failed") -> fallback model;
 *  - bad credentials (401/403) -> stop, nothing else will work.
 * It never throws; the caller always gets a GroqResult.
 */
class GroqClient
{
    /**
     * @param  array<int,array<string,mixed>>  $messages
     * @param  array<int,array<string,mixed>>  $tools  OpenAI "tools" definitions
     * @param  array<string,mixed>  $options  extra payload keys, e.g. ['tool_choice' => 'none']
     */
    public function chat(array $messages, array $tools = [], ?string $preferredModel = null, array $options = []): GroqResult
    {
        $key = (string) config('ai.groq.key');

        if ($key === '') {
            return GroqResult::failure('GROQ_API_KEY belum diisi.');
        }

        $models = array_values(array_unique(array_filter([
            $preferredModel,
            config('ai.groq.model'),
            config('ai.groq.fallback_model'),
        ])));

        $last = GroqResult::failure('Model AI tidak tersedia.');

        foreach ($models as $model) {
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                $result = $this->request($key, $model, $messages, $tools, $options);

                if ($result->ok) {
                    return $result;
                }

                $last = $result;

                if (in_array($result->status, [401, 403], true)) {
                    return $result;
                }

                // Only transient problems are worth a second try on the same model.
                if (! $this->isTransient($result->status)) {
                    break;
                }
            }
        }

        return $last;
    }

    /**
     * @param  array<int,array<string,mixed>>  $messages
     * @param  array<int,array<string,mixed>>  $tools
     * @param  array<string,mixed>  $options
     */
    private function request(string $key, string $model, array $messages, array $tools, array $options = []): GroqResult
    {
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => config('ai.groq.temperature', 0.4),
            'max_completion_tokens' => config('ai.groq.max_tokens', 1800),
        ];

        if ($tools !== []) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $payload = array_merge($payload, $options);

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->asJson()
                ->connectTimeout(10)
                ->timeout((int) config('ai.groq.timeout', 45))
                ->post(rtrim((string) config('ai.groq.base_url'), '/') . '/chat/completions', $payload);
        } catch (ConnectionException $e) {
            Log::warning('Groq connection failed', ['model' => $model, 'error' => $e->getMessage()]);

            return GroqResult::failure('Tidak bisa terhubung ke layanan AI.', null, $model);
        } catch (\Throwable $e) {
            Log::warning('Groq request error', ['model' => $model, 'error' => $e->getMessage()]);

            return GroqResult::failure('Permintaan ke layanan AI gagal.', null, $model);
        }

        if (! $response->successful()) {
            return $this->failureFrom($response, $model);
        }

        $message = $response->json('choices.0.message') ?? [];
        $toolCalls = is_array($message['tool_calls'] ?? null) ? array_values($message['tool_calls']) : [];
        $content = self::cleanContent((string) ($message['content'] ?? ''));

        if ($content === '' && $toolCalls === []) {
            return GroqResult::failure('Model tidak memberikan jawaban.', 502, $model);
        }

        return new GroqResult(true, $content, $toolCalls, (string) ($response->json('model') ?: $model));
    }

    private function failureFrom(Response $response, string $model): GroqResult
    {
        $status = $response->status();
        $detail = (string) ($response->json('error.message') ?? $response->json('error.code') ?? '');

        Log::warning('Groq returned an error', [
            'model' => $model,
            'status' => $status,
            'error' => mb_substr($detail, 0, 300),
        ]);

        return GroqResult::failure('Layanan AI mengembalikan galat (' . $status . ').', $status, $model);
    }

    private function isTransient(?int $status): bool
    {
        return $status === null || $status === 429 || $status >= 500;
    }

    /**
     * Remove chain-of-thought that some models (Qwen3, DeepSeek-R1) put in the
     * content, including an unterminated or opening-less <think> block.
     */
    public static function cleanContent(string $content): string
    {
        $content = preg_replace('#<think\b[^>]*>.*?</think>#is', '', $content) ?? $content;

        // "...reasoning</think> answer" with the opening tag missing.
        if (stripos($content, '</think>') !== false) {
            $content = substr($content, strripos($content, '</think>') + strlen('</think>'));
        }

        // "<think> reasoning..." that was cut off before closing: nothing usable.
        if (preg_match('#<think\b#i', $content) === 1) {
            $content = (string) preg_replace('#<think\b.*$#is', '', $content);
        }

        return trim($content);
    }
}
