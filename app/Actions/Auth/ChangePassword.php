<?php

namespace App\Actions\Auth;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Changes the password and signs out every other device: all tokens except
 * the one making this request are revoked, and web sessions are dropped.
 */
class ChangePassword
{
    public function handle(User $user, string $newPassword, ?PersonalAccessToken $currentToken): void
    {
        $user->forceFill(['password' => $newPassword])->save();

        $user->tokens()
            ->when($currentToken !== null, fn ($query) => $query->whereKeyNot($currentToken->getKey()))
            ->delete();

        if (config('session.driver') === 'database') {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->delete();
        }
    }
}
