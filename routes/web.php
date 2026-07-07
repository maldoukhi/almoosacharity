<?php

use App\Enums\Locale;
use App\Livewire\Admin\Roles\Form as RoleForm;
use App\Livewire\Admin\Roles\Index as RoleIndex;
use App\Livewire\Admin\Users\Form as UserForm;
use App\Livewire\Admin\Users\Index as UserIndex;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Dashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', function (Request $request) {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::post('/locale/{locale}', function (Request $request, string $locale) {
        if (! in_array($locale, array_column(Locale::cases(), 'value'), true)) {
            abort(404);
        }

        $request->session()->put('locale', $locale);

        if ($user = $request->user()) {
            $user->update(['preferred_locale' => $locale]);
        }

        return back();
    })->name('locale.switch');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::prefix('users')->name('users.')->group(function (): void {
            Route::get('/', UserIndex::class)->name('index')->middleware('permission:users.view');
            Route::get('/create', UserForm::class)->name('create')->middleware('permission:users.create');
            Route::get('/{user}/edit', UserForm::class)->name('edit')->middleware('permission:users.update');
        });

        Route::prefix('roles')->name('roles.')->group(function (): void {
            Route::get('/', RoleIndex::class)->name('index')->middleware('permission:roles.view');
            Route::get('/create', RoleForm::class)->name('create')->middleware('permission:roles.create');
            Route::get('/{role}/edit', RoleForm::class)->name('edit')->middleware('permission:roles.update');
        });
    });
});
