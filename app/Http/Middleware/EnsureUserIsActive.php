<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards every authenticated request against a session that outlived the
 * user's account being suspended after login (e.g. an admin suspends a
 * currently logged-in user). Without this, Login::login() only checks
 * status at the moment of authentication, and the session otherwise stays
 * valid until it expires.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('auth.suspended'),
            ]);
        }

        return $next($request);
    }
}
