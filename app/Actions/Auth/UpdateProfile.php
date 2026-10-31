<?php

namespace App\Actions\Auth;

use App\Models\User;

class UpdateProfile
{
    /** @param array{name:string,phone?:string|null} $data */
    public function handle(User $user, array $data): User
    {
        $user->fill([
            'name' => trim($data['name']),
            'phone' => $data['phone'] ?? null,
        ])->save();

        return $user->refresh();
    }
}
