<?php

use App\Enums\UserStatus;
use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

it('logs an active user in, redirects to the dashboard, and stamps last_login_at', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
        'password' => bcrypt('correct-password'),
        'last_login_at' => null,
    ]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('login')
        ->assertRedirect(route('dashboard'))
        ->assertHasNoErrors();

    expect(Auth::check())->toBeTrue();
    expect(Auth::id())->toBe($user->id);

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull();
});

it('fails to log in with a wrong password and does not authenticate the user', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
        'password' => bcrypt('correct-password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email'])
        ->assertNoRedirect();

    expect(Auth::check())->toBeFalse();
});

it('rejects a suspended user with a message and logs them back out', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Suspended,
        'password' => bcrypt('correct-password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('login')
        ->assertHasErrors(['email'])
        ->assertNoRedirect();

    expect(Auth::check())->toBeFalse();
});

it('throttles the login form after repeated failed attempts', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
        'password' => bcrypt('correct-password'),
    ]);

    // The rate limiter allows 5 recorded failures before blocking the 6th
    // submission outright (see Login::login()).
    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    $throttled = Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login');

    $throttled->assertHasErrors(['email']);

    // Match the translated throttle message with any number of seconds,
    // instead of depending on the exact remaining decay window.
    $pattern = '/^'.str_replace(
        preg_quote(':seconds', '/'),
        '\d+',
        preg_quote(__('auth.throttle', ['seconds' => ':seconds']), '/'),
    ).'$/u';

    expect($throttled->errors()->first('email'))->toMatch($pattern);

    expect(Auth::check())->toBeFalse();
});

it('redirects a guest away from the dashboard to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('redirects an authenticated user from the home page straight to the dashboard', function () {
    $user = User::factory()->create(['status' => UserStatus::Active]);

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect(route('dashboard'));
});
