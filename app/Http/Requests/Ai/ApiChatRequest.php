<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Mobile chat: the client sends the running history (max 20 turns). */
class ApiChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $max = (int) config('ai.max_message_chars', 4000);

        return [
            'messages' => ['required', 'array', 'min:1', 'max:' . (int) config('ai.max_history', 20)],
            'messages.*.role' => ['required', 'string', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:' . $max],
            'conversation_id' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $messages = $this->input('messages');

                if (is_array($messages) && $messages !== [] && (end($messages)['role'] ?? null) !== 'user') {
                    $validator->errors()->add('messages', 'Pesan terakhir harus dari pengguna (role "user").');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'messages.required' => 'Riwayat pesan wajib diisi.',
            'messages.max' => 'Maksimal :max pesan per permintaan.',
            'messages.*.role.in' => 'Role pesan harus "user" atau "assistant".',
            'messages.*.content.required' => 'Isi pesan tidak boleh kosong.',
            'messages.*.content.max' => 'Pesan terlalu panjang (maksimal :max karakter).',
        ];
    }
}
