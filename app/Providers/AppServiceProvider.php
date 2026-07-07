<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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

        // Laravel's automatic policy discovery only guesses namespaces under
        // App\Models, so the Spatie package's Role model needs an explicit
        // mapping to our RolePolicy.
        Gate::policy(Role::class, RolePolicy::class);
    }
}
