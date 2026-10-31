<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Verifies email + password for the API and refuses inactive or banned
 * accounts. Returns the user; token issuing is a separate step.
 */
class AuthenticateWithPassword
{
    public function handle(string $email, string $password): User
    {
        $user = User::query()->where('email', strtolower(trim($email)))->first();

        // Same message whether the email or the password is wrong.
        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi salah.'],
            ])->status(422);
        }

        if ($user->isBanned()) {
            $this->deny('Akun Anda sedang diblokir. Hubungi admin OtoGo untuk bantuan.');
        }

        if (! $user->is_active) {
            $this->deny('Akun Anda tidak aktif. Hubungi admin OtoGo untuk mengaktifkan kembali.');
        }

        return $user;
    }

    private function deny(string $message): never
    {
        throw new HttpResponseException(response()->json(['message' => $message], 403));
    }
}
