<?php

use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Support\Facades\Crypt;

it('encrypts a secret at rest so the raw value never touches the database', function () {
    $settings = app(Settings::class);

    $settings->setSecret('taqnyat_api_key', 'super-secret-key-1234');

    $stored = Setting::query()->where('key', 'taqnyat_api_key')->value('value');

    expect($stored)->not->toBeNull()
        ->and($stored)->not->toBe('super-secret-key-1234')
        ->and($stored)->not->toContain('super-secret-key-1234');

    expect(Crypt::decryptString($stored))->toBe('super-secret-key-1234');
    expect($settings->getSecret('taqnyat_api_key'))->toBe('super-secret-key-1234');
});

it('returns null from getSecret when the key was never set', function () {
    expect(app(Settings::class)->getSecret('okta_token'))->toBeNull();
});

it('treats an empty/null value passed to setSecret as deleting the key', function () {
    $settings = app(Settings::class);

    $settings->setSecret('okta_token', 'some-token');
    expect($settings->getSecret('okta_token'))->toBe('some-token');

    $settings->setSecret('okta_token', '');
    expect($settings->getSecret('okta_token'))->toBeNull();
    expect(Setting::query()->where('key', 'okta_token')->value('value'))->toBeNull();

    $settings->setSecret('okta_token', 'another-token');
    $settings->setSecret('okta_token', null);
    expect($settings->getSecret('okta_token'))->toBeNull();
});

it('masks a secret down to the last 4 characters', function () {
    $settings = app(Settings::class);

    $settings->setSecret('taqnyat_api_key', 'ABCDEFGH1234');

    expect($settings->maskedSecret('taqnyat_api_key'))->toBe('•••• 1234');
    expect(app(Settings::class)->maskedSecret('taqnyat_api_key'))->not->toContain('ABCDEFGH');
});

it('returns null from maskedSecret when unset', function () {
    expect(app(Settings::class)->maskedSecret('taqnyat_api_key'))->toBeNull();
});

it('treats an undecryptable stored value as unset rather than throwing', function () {
    Setting::query()->create(['key' => 'okta_token', 'value' => 'not-valid-ciphertext']);

    expect(app(Settings::class)->getSecret('okta_token'))->toBeNull();
});
