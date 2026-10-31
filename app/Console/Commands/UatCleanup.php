<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Removes the throwaway accounts tools/uat.py registers through the API
 * (uat.<timestamp>@example.test) together with their tokens and vehicles.
 */
class UatCleanup extends Command
{
    protected $signature = 'otogo:uat-cleanup';

    protected $description = 'Hapus akun uji yang dibuat oleh tools/uat.py';

    public function handle(): int
    {
        $users = User::query()->where('email', 'like', 'uat.%@example.test')->get();

        foreach ($users as $user) {
            $user->tokens()->delete();
            $user->roles()->detach();
            $user->delete();
        }

        $this->info("{$users->count()} akun uji dihapus.");

        return self::SUCCESS;
    }
}
