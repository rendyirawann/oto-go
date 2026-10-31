<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

/** Web chat: one new message; the server holds the history. */
class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:' . (int) config('ai.max_message_chars', 4000)],
            'conversation_id' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Tulis pertanyaan terlebih dahulu.',
            'message.max' => 'Pesan terlalu panjang (maksimal :max karakter).',
        ];
    }
}
