<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Issues a Sanctum personal access token for a device. Expiry follows
 * config('sanctum.expiration') (SANCTUM_TOKEN_EXPIRATION, minutes).
 */
class IssueApiToken
{
    public function handle(User $user, ?string $deviceName = null): string
    {
        $name = Str::limit(trim((string) $deviceName) !== '' ? trim((string) $deviceName) : 'otogo-mobile', 100, '');
        $minutes = config('sanctum.expiration');

        $user->forceFill(['last_login' => now()])->saveQuietly();

        return $user->createToken(
            $name,
            ['*'],
            $minutes ? now()->addMinutes((int) $minutes) : null,
        )->plainTextToken;
    }
}
