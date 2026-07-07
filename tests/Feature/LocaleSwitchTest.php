<?php

use App\Enums\Locale;
use App\Enums\UserStatus;
use App\Models\User;

it('switches the session locale and the user preferred_locale to english', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
        'preferred_locale' => Locale::Ar,
    ]);

    $this->actingAs($user)
        ->post('/locale/en')
        ->assertRedirect();

    expect(session('locale'))->toBe('en');
    expect($user->fresh()->preferred_locale)->toBe(Locale::En);
});

it('rejects an invalid locale value', function () {
    $user = User::factory()->create(['status' => UserStatus::Active]);

    $this->actingAs($user)
        ->post('/locale/fr')
        ->assertNotFound();

    expect($user->fresh()->preferred_locale)->not->toBe(Locale::En);
});
