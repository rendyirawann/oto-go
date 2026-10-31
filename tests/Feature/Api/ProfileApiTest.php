<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Hash;

class ProfileApiTest extends ApiTestCase
{
    public function test_show_profile(): void
    {
        $user = $this->makeUser(['name' => 'Rina', 'phone' => '081200001111']);

        $this->getJson('/api/v1/me', $this->bearer($this->tokenFor($user)))
            ->assertOk()
            ->assertJsonPath('data.name', 'Rina')
            ->assertJsonPath('data.phone', '081200001111')
            ->assertJsonPath('data.roles', ['customer']);
    }

    public function test_update_profile(): void
    {
        $user = $this->makeUser();

        $this->putJson('/api/v1/me', ['name' => '  Rina Wati ', 'phone' => '+6281299998888'], $this->bearer($this->tokenFor($user)))
            ->assertOk()
            ->assertJsonPath('data.name', 'Rina Wati')
            ->assertJsonPath('data.phone', '+6281299998888');
    }

    public function test_update_profile_rejects_phone_of_another_user(): void
    {
        $this->makeUser(['phone' => '081211112222']);
        $user = $this->makeUser();

        $this->putJson('/api/v1/me', ['name' => 'Rina', 'phone' => '081211112222'], $this->bearer($this->tokenFor($user)))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_change_password_revokes_other_tokens(): void
    {
        $user = $this->makeUser(['password' => Hash::make('lama12345')]);
        $current = $this->tokenFor($user, 'hp');
        $other = $this->tokenFor($user, 'tablet');

        $this->putJson('/api/v1/me/password', [
            'current_password' => 'lama12345',
            'password' => 'baru12345',
            'password_confirmation' => 'baru12345',
        ], $this->bearer($current))->assertOk()->assertJsonStructure(['message']);

        $this->assertTrue(Hash::check('baru12345', $user->fresh()->password));

        $this->freshAuth();
        $this->getJson('/api/v1/me', $this->bearer($other))->assertUnauthorized();

        $this->freshAuth();
        $this->getJson('/api/v1/me', $this->bearer($current))->assertOk();
    }

    public function test_change_password_requires_correct_current_password(): void
    {
        $user = $this->makeUser(['password' => Hash::make('lama12345')]);

        $this->putJson('/api/v1/me/password', [
            'current_password' => 'salah999',
            'password' => 'baru12345',
            'password_confirmation' => 'baru12345',
        ], $this->bearer($this->tokenFor($user)))
            ->assertUnprocessable()
            ->assertJsonPath('errors.current_password.0', 'Kata sandi saat ini salah.');
    }
}
