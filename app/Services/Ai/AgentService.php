<?php

namespace App\Services\Ai;

use App\Services\Ai\Tools\AiTool;
use App\Services\Ai\Tools\BlogSearchTool;
use App\Services\Ai\Tools\PlatformOverviewTool;
use App\Services\Ai\Tools\PriceEstimateTool;
use App\Services\Ai\Tools\WebSearchTool;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The OtoGo assistant: a small tool-calling agent loop on top of Groq.
 *
 *   history -> model -> (tool calls -> tools -> model)* -> answer
 *
 * At most config('ai.max_tool_iterations') tool rounds; after that the model
 * is asked to answer with what it has. Never throws.
 */
class AgentService
{
    /** @var array<string,AiTool> */
    private array $tools;

    public function __construct(
        private readonly GroqClient $groq,
        WebSearchTool $webSearch,
        PriceEstimateTool $priceEstimate,
        BlogSearchTool $blogSearch,
        PlatformOverviewTool $platformOverview,
    ) {
        foreach ([$priceEstimate, $webSearch, $blogSearch, $platformOverview] as $tool) {
            $this->tools[$tool->name()] = $tool;
        }
    }

    /**
     * @param  array<int,array{role:string,content:string}>  $history  oldest first; last entry is the user's message
     */
    public function reply(AgentContext $context, array $history): AgentReply
    {
        $history = $this->normaliseHistory($history);

        if ($history === [] || end($history)['role'] !== 'user') {
            return AgentReply::unavailable('Pesan pengguna kosong.');
        }

        $tools = array_filter($this->tools, fn (AiTool $t) => $this->safely(fn () => $t->availableFor($context), false));
        $definitions = array_values(array_map(fn (AiTool $t) => $t->definition(), $tools));

        $messages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt($context, array_keys($tools))]],
            $history,
        );

        $sources = [];
        $used = [];
        $seenCalls = [];
        $maxRounds = max(0, (int) config('ai.max_tool_iterations', 4));

        for ($round = 0; $round <= $maxRounds; $round++) {
            $finalRound = $round === $maxRounds || $definitions === [];
            $result = $this->groq->chat(
                $messages,
                $definitions,
                null,
                $finalRound && $definitions !== [] ? ['tool_choice' => 'none'] : [],
            );

            if (! $result->ok) {
                return AgentReply::unavailable($result->error);
            }

            if (! $result->hasToolCalls() || $finalRound) {
                if ($result->content === '') {
                    return AgentReply::unavailable('Model tidak memberikan jawaban.');
                }

                return new AgentReply(
                    ok: true,
                    reply: $result->content,
                    sources: $this->uniqueSources($sources),
                    model: $result->model,
                    toolsUsed: array_values(array_unique($used)),
                );
            }

            $messages[] = [
                'role' => 'assistant',
                'content' => $result->content !== '' ? $result->content : null,
                'tool_calls' => $result->toolCalls,
            ];

            foreach ($result->toolCalls as $call) {
                $name = (string) ($call['function']['name'] ?? '');
                $rawArgs = (string) ($call['function']['arguments'] ?? '{}');
                $args = json_decode($rawArgs !== '' ? $rawArgs : '{}', true);
                $args = is_array($args) ? $args : [];
                $signature = $name . '|' . json_encode($args);

                if (isset($seenCalls[$signature])) {
                    $output = ['content' => ['info' => 'Hasil alat ini sudah diberikan sebelumnya; gunakan hasil tersebut.']];
                } elseif (! isset($tools[$name])) {
                    $output = ['content' => ['error' => "Alat '{$name}' tidak tersedia untuk pengguna ini."]];
                } else {
                    $seenCalls[$signature] = true;
                    $used[] = $name;
                    $output = $this->safely(
                        fn () => $tools[$name]->run($args, $context),
                        ['content' => ['error' => 'Alat gagal dijalankan.']],
                    );
                }

                foreach ($output['sources'] ?? [] as $source) {
                    $sources[] = $source;
                }

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => (string) ($call['id'] ?? Str::random(8)),
                    'name' => $name,
                    'content' => json_encode($output['content'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
            }
        }

        return AgentReply::unavailable('Batas langkah agen tercapai.');
    }

    /**
     * @param  array<int,mixed>  $history
     * @return array<int,array{role:string,content:string}>
     */
    private function normaliseHistory(array $history): array
    {
        $cap = (int) config('ai.max_message_chars', 4000);
        $clean = [];

        foreach ($history as $row) {
            $role = is_array($row) ? ($row['role'] ?? null) : null;
            $content = is_array($row) ? trim((string) ($row['content'] ?? '')) : '';

            if (! in_array($role, ['user', 'assistant'], true) || $content === '') {
                continue;
            }

            $clean[] = ['role' => $role, 'content' => mb_substr($content, 0, $cap)];
        }

        return array_slice($clean, -max(1, (int) config('ai.max_history', 20)));
    }

    /** @param  array<int,string>  $toolNames */
    private function systemPrompt(AgentContext $context, array $toolNames): string
    {
        $role = $context->isStaff
            ? 'staf pengelola di portal admin OtoGo (superadmin/admin usaha). Bantu operasional, analisis, penulisan konten blog, dan penjelasan fitur.'
            : 'pelanggan OtoGo (pemilik kendaraan). Bantu soal perawatan kendaraan, estimasi biaya, dan cara memakai OtoGo.';

        $toolHints = [
            'price_estimate' => '- Setiap pertanyaan harga/biaya WAJIB memakai alat price_estimate. Jangan mengarang angka. Sebut bahwa itu estimasi pasar Medan dan sertakan disclaimer-nya.',
            'web_search' => '- Untuk informasi terkini atau di luar data OtoGo, pakai web_search lalu cantumkan sumbernya.',
            'blog_search' => '- Bila ada artikel blog OtoGo yang relevan, cari dengan blog_search dan sarankan tautannya.',
            'platform_overview' => '- Untuk angka/statistik platform, pakai platform_overview. Jangan menebak angka.',
        ];

        $hints = implode("\n", array_values(array_intersect_key($toolHints, array_flip($toolNames))));

        return <<<PROMPT
        Kamu adalah Asisten OtoGo, asisten AI di platform OtoGo milik Core Technology Indonesia.

        Tentang OtoGo: platform SaaS otomotif di Kota Medan, Sumatera Utara, untuk bengkel, toko sparepart, dan doorsmeer (cuci kendaraan). Pelanggan bisa booking servis/cuci, belanja sparepart, memilih mode layanan (datang ke outlet, antar jemput kendaraan, atau dikerjakan di rumah), dan membayar online lewat Tripay (VA, QRIS, e-wallet, retail) atau bayar di tempat bila diizinkan pemilik usaha. Pemilik usaha mengatur outlet, modul, tarif per kategori kendaraan, staf, dan pencairan dana. Zona waktu WIB, mata uang rupiah.

        Kamu sedang berbicara dengan {$context->firstName}, seorang {$role}

        Aturan:
        - Jawab dalam Bahasa Indonesia yang natural, ringkas, dan langsung ke inti. Pakai markdown sederhana (paragraf pendek, daftar, **tebal**). Tanpa emoji.
        {$hints}
        - Untuk gejala yang membahayakan keselamatan (rem blong, bau terbakar, asap, lampu oli menyala), sarankan berhenti berkendara dan segera ke bengkel.
        - Jangan pernah meminta atau menampilkan data pribadi (email, nomor HP, password, token, data rekening).
        - Fitur booking dan pembayaran sedang dibangun bertahap; jangan menjanjikan fitur yang belum ada. Bila tidak tahu, katakan terus terang.
        - Tulis harga dalam format "Rp 45.000".

        Sekarang: {$this->now()} WIB.
        PROMPT;
    }

    private function now(): string
    {
        return now()->timezone('Asia/Jakarta')->translatedFormat('l, d F Y H:i');
    }

    /**
     * @param  array<int,array{title:string,url:string}>  $sources
     * @return array<int,array{title:string,url:string}>
     */
    private function uniqueSources(array $sources): array
    {
        $unique = [];

        foreach ($sources as $source) {
            $url = (string) ($source['url'] ?? '');

            if ($url === '' || isset($unique[$url]) || ! preg_match('#^https?://#i', $url)) {
                continue;
            }

            $unique[$url] = ['title' => (string) ($source['title'] ?? $url), 'url' => $url];
        }

        return array_slice(array_values($unique), 0, 8);
    }

    private function safely(callable $fn, mixed $fallback): mixed
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            Log::warning('AI tool failure', ['error' => $e->getMessage()]);

            return $fallback;
        }
    }
}
