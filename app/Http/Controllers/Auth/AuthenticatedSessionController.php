<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\ActivityRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * LoginRequest::authenticate() owns credential checking plus the staged
     * lockout; this method deals with what happens once that succeeds.
     */
    public function store(LoginRequest $request): JsonResponse|RedirectResponse
    {
        $request->authenticate();

        // New session id on privilege change — blocks session fixation.
        $request->session()->regenerate();

        $user = Auth::user();

        if ($this->isLockedOut($user)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $user->banned_at
                ? 'Akun Anda telah dibekukan. Silakan hubungi administrator.'
                : 'Akun Anda tidak aktif. Silakan hubungi administrator.';

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $message], 403);
            }

            throw ValidationException::withMessages(['email' => $message]);
        }

        $user->forceFill([
            'last_ip' => $request->ip(),
            'last_login' => now(),
        ])->save();

        ActivityRecorder::loggedIn();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil, mengalihkan...',
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        // Recorded before the guard forgets who was acting.
        ActivityRecorder::loggedOut();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Banned or deactivated accounts must not hold a session. */
    private function isLockedOut($user): bool
    {
        return (bool) $user->banned_at || $user->is_active === false;
    }
}
