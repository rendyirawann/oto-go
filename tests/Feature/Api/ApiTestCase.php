<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes + [
            'username' => 'u' . Str::lower(Str::random(10)),
            'is_active' => true,
        ]);

        $user->assignRole(Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']));

        return $user;
    }

    /** Real token round-trip (no actingAs) so Sanctum behaves like production. */
    protected function tokenFor(User $user, string $device = 'test'): string
    {
        return $user->createToken($device)->plainTextToken;
    }

    /** @return array<string,string> */
    protected function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'];
    }

    /** Sanctum caches the resolved user per guard; reset between requests. */
    protected function freshAuth(): void
    {
        $this->app['auth']->forgetGuards();
    }
}
