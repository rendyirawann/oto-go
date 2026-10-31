<?php

namespace App\Services\Ai;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Who the assistant is talking to. Only the first name ever reaches the model.
 */
final class AgentContext
{
    public function __construct(
        public readonly User $user,
        public readonly bool $isStaff,
        public readonly string $firstName,
        public readonly string $channel = 'web',
    ) {}

    public static function for(User $user, string $channel = 'web'): self
    {
        $first = Str::of((string) $user->name)->trim()->before(' ')->limit(40, '')->value();

        return new self(
            user: $user,
            isStaff: $user->can('view_dashboard'),
            firstName: $first !== '' ? $first : 'Pengguna',
            channel: $channel,
        );
    }
}
