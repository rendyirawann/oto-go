<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Self-service registration is opt-in. On an internal admin panel the
     * default is that only an administrator creates accounts, so both the
     * form and the handler 404 unless it has been switched on in Settings.
     */
    private function guardRegistrationEnabled(): void
    {
        abort_unless(Setting::get('allow_registration', '0') === '1', 404);
    }

    public function create(): View
    {
        $this->guardRegistrationEnabled();

        return view('auth.register');
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->guardRegistrationEnabled();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 'password' is hashed by the model cast; passing the plain value here
        // avoids double hashing.
        $user = User::create($validated);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
