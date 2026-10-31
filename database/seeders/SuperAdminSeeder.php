<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    /**
     * Creates the first Superadmin account.
     *
     * Credentials come from SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD in .env so
     * they never live in the repository. When no password is configured a
     * random one is generated and printed once.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);

        $email = (string) env('SUPERADMIN_EMAIL', 'superadmin@otogo.id');
        $password = (string) env('SUPERADMIN_PASSWORD', '');
        $generated = $password === '';

        if ($generated) {
            $password = Str::password(16);
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Administrator',
                'username' => 'superadmin',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        if (! $user->hasRole('Superadmin')) {
            $user->assignRole($role);
        }

        if (! $user->wasRecentlyCreated) {
            $this->command?->info("Superadmin {$email} sudah ada, password tidak diubah.");

            return;
        }

        $this->command?->info("Superadmin dibuat: {$email}");

        if ($generated) {
            $this->command?->warn("Password acak (simpan sekarang, tidak ditampilkan lagi): {$password}");
        }
    }
}
