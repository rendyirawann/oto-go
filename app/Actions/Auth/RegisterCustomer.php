<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Creates a customer account from the mobile app and issues its first token.
 */
class RegisterCustomer
{
    public function __construct(private readonly IssueApiToken $issueToken)
    {
    }

    /**
     * @param  array{name:string,email:string,phone?:string|null,password:string,device_name?:string|null}  $data
     * @return array{user:User,token:string}
     */
    public function handle(array $data): array
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => trim($data['name']),
                'username' => $this->uniqueUsername($data['email']),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'is_active' => true,
            ]);

            $role = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
            $user->assignRole($role);

            return $user;
        });

        event(new Registered($user));

        return [
            'user' => $user,
            'token' => $this->issueToken->handle($user, $data['device_name'] ?? null),
        ];
    }

    /** "Budi.Santoso+x@mail.com" -> "budisantosox", then "budisantosox2" ... */
    private function uniqueUsername(string $email): string
    {
        $base = Str::lower(preg_replace('/[^A-Za-z0-9]/', '', Str::before($email, '@')) ?? '');
        $base = Str::limit($base !== '' ? $base : 'pelanggan', 24, '');

        $candidate = $base;
        $suffix = 1;

        while (User::where('username', $candidate)->exists()) {
            $suffix++;
            $candidate = $base . $suffix;
        }

        return $candidate;
    }
}
