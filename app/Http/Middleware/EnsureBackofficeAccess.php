<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the admin portal for staff only.
 *
 * Customers register through the mobile app / API and get the "customer"
 * role, which carries no back-office permission. If such an account signs in
 * on the web, it is signed out and sent back to the login page with a clear
 * message instead of being shown a bare 403.
 */
class EnsureBackofficeAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->can('view_dashboard')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Akun ini tidak memiliki akses ke portal admin.',
            ], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('error', 'Akun pelanggan hanya bisa masuk lewat aplikasi OtoGo. Portal ini khusus pengelola.');
    }
}
