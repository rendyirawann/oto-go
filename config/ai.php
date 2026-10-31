<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Asisten AI OtoGo
    |--------------------------------------------------------------------------
    |
    | The browser only ever talks to our own server; the server talks to Groq
    | (OpenAI-compatible chat completions) and Tavily (web search). Missing keys
    | never break a page - the assistant simply answers "tidak tersedia".
    |
    */

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),
        'fallback_model' => env('GROQ_MODEL_CADANGAN', 'openai/gpt-oss-120b'),
        // Optional Groq model with built-in web search (e.g. groq/compound).
        // Empty = web questions go through the Tavily tool instead.
        'web_model' => env('GROQ_MODEL_WEB') ?: null,
        'timeout' => (int) env('GROQ_TIMEOUT', 45),
        'temperature' => (float) env('GROQ_TEMPERATURE', 0.4),
        'max_tokens' => (int) env('GROQ_MAX_TOKENS', 1800),
    ],

    'tavily' => [
        'key' => env('TAVILY_API_KEY'),
        'base_url' => env('TAVILY_BASE_URL', 'https://api.tavily.com'),
        'timeout' => (int) env('TAVILY_TIMEOUT', 20),
        'max_results' => 5,
    ],

    // Chat requests per minute per user (named limiter "ai").
    'rate_limit' => (int) env('AI_RATE_LIMIT', 30),

    // Agent loop: at most this many model <-> tool round trips per message.
    'max_tool_iterations' => 4,

    // Messages of history sent to the model, and per-message character cap.
    'max_history' => 20,
    'max_message_chars' => 4000,

    // Price data shared with the seeder and the public estimator.
    'price_template_path' => database_path('data/medan_price_template.php'),

    'unavailable_message' => 'Asisten AI sedang tidak tersedia. Coba lagi sebentar lagi.',
];
