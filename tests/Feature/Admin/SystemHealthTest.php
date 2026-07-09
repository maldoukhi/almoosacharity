<?php

use App\Livewire\Admin\SystemHealth;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('renders the system health page for an authorized admin', function () {
    asAdmin();

    Livewire::test(SystemHealth::class)
        ->assertOk()
        ->assertSee(__('health.title'));
});

it('forbids a user without settings.view from opening the system health page', function () {
    asManager();

    Livewire::test(SystemHealth::class)->assertForbidden();
});

it('shows the empty backups state when no backups exist', function () {
    asAdmin();

    Storage::fake('backups');

    Livewire::test(SystemHealth::class)
        ->assertOk()
        ->assertSee(__('health.backup.empty'));
});
