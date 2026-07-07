<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve the locale to apply for the current request, in order of
     * priority: authenticated user's preferred_locale, session('locale'),
     * then the application's configured default locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        $user = $request->user();

        if ($user && $user->preferred_locale instanceof Locale) {
            return $user->preferred_locale->value;
        }

        if ($sessionLocale = $request->session()->get('locale')) {
            return $sessionLocale;
        }

        return config('app.locale');
    }
}
