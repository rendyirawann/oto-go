<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthApiTest extends ApiTestCase
{
    public function test_register_creates_customer_and_returns_working_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi Santoso',
            'email' => 'Budi.Santoso@Contoh.id',
            'phone' => '0812-3456-7890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'budi.santoso@contoh.id')
            ->assertJsonPath('data.user.phone', '081234567890')
            ->assertJsonPath('data.user.roles', ['customer'])
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user' => ['id', 'name', 'email', 'phone', 'avatar_url', 'roles', 'created_at']]]);

        $user = User::where('email', 'budi.santoso@contoh.id')->firstOrFail();
        $this->assertSame('budisantoso', $user->username);
        $this->assertTrue($user->hasRole('customer'));

        $this->getJson('/api/v1/me', $this->bearer($response->json('data.token')))
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_register_generates_unique_username(): void
    {
        $this->makeUser(['username' => 'budi', 'email' => 'budi@lain.id']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi',
            'email' => 'budi@contoh.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertCreated();

        $this->assertSame('budi2', User::where('email', 'budi@contoh.id')->value('username'));
    }

    public function test_register_validation_errors_are_indonesian(): void
    {
        $this->makeUser(['email' => 'ada@contoh.id']);

        $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'ada@contoh.id',
            'phone' => '12345',
            'password' => 'rahasia123',
            'password_confirmation' => 'beda',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'password'])
            ->assertJsonPath('errors.name.0', 'Nama wajib diisi.')
            ->assertJsonPath('errors.email.0', 'Email sudah terdaftar.');
    }

    public function test_login_success_returns_token(): void
    {
        $user = $this->makeUser(['email' => 'sari@contoh.id', 'password' => Hash::make('rahasia123')]);

        $this->postJson('/api/v1/auth/login', ['email' => 'SARI@contoh.id', 'password' => 'rahasia123'])
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $this->makeUser(['email' => 'sari@contoh.id', 'password' => Hash::make('rahasia123')]);

        $this->postJson('/api/v1/auth/login', ['email' => 'sari@contoh.id', 'password' => 'salah123'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Email atau kata sandi salah.');

        $this->postJson('/api/v1/auth/login', ['email' => 'tidakada@contoh.id', 'password' => 'salah123'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Email atau kata sandi salah.');
    }

    public function test_inactive_and_banned_users_cannot_login(): void
    {
        $this->makeUser(['email' => 'nonaktif@contoh.id', 'password' => Hash::make('rahasia123'), 'is_active' => false]);
        $banned = $this->makeUser(['email' => 'blokir@contoh.id', 'password' => Hash::make('rahasia123')]);
        $banned->ban();

        $this->postJson('/api/v1/auth/login', ['email' => 'nonaktif@contoh.id', 'password' => 'rahasia123'])
            ->assertForbidden()
            ->assertJsonStructure(['message']);

        $this->postJson('/api/v1/auth/login', ['email' => 'blokir@contoh.id', 'password' => 'rahasia123'])
            ->assertForbidden()
            ->assertJsonStructure(['message']);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = $this->makeUser();
        $phone = $this->tokenFor($user, 'hp');
        $tablet = $this->tokenFor($user, 'tablet');

        $this->postJson('/api/v1/auth/logout', [], $this->bearer($phone))
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->freshAuth();
        $this->getJson('/api/v1/me', $this->bearer($phone))->assertUnauthorized();

        $this->freshAuth();
        $this->getJson('/api/v1/me', $this->bearer($tablet))->assertOk();
    }

    public function test_protected_endpoints_require_token(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized()->assertJsonStructure(['message']);
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
        $this->getJson('/api/v1/me/vehicles')->assertUnauthorized();
        $this->getJson('/api/v1/me', $this->bearer('1|tokenpalsu'))->assertUnauthorized();
    }
}
