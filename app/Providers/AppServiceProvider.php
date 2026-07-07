<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // System admins bypass all authorization checks. Must return null
        // (not false) for other users so their own ability/policy checks
        // still run normally.
        Gate::before(function (User $user): ?bool {
            return $user->hasRole(RoleName::SystemAdmin->value) ? true : null;
        });
    }
}
