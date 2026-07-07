<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
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
        //
        // Exception: 'delete'/'suspend' checks against the acting user's own
        // account always fall through to the policy (return null instead of
        // short-circuiting to true), so UserPolicy::delete()/suspend() can
        // deny self-deletion/self-suspension even for a system-admin.
        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if (
                in_array($ability, ['delete', 'suspend'], true)
                && ($arguments[0] ?? null) instanceof User
                && $user->is($arguments[0])
            ) {
                return null;
            }

            return $user->hasRole(RoleName::SystemAdmin->value) ? true : null;
        });

        // Laravel's automatic policy discovery only guesses namespaces under
        // App\Models, so the Spatie package's Role model needs an explicit
        // mapping to our RolePolicy.
        Gate::policy(Role::class, RolePolicy::class);

        // Baseline password strength for every password-confirmed form in
        // the app (user creation/update, password reset). No
        // `uncompromised()` check: the hosting environment for this app may
        // not have outbound internet access at runtime, and that check
        // calls the (k-anonymity) Have I Been Pwned API.
        Password::defaults(function (): Password {
            return Password::min(8)->letters()->numbers();
        });
    }
}
