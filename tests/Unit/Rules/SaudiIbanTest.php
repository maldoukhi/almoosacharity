<?php

use App\Rules\SaudiIban;
use Illuminate\Support\Facades\Validator;

function validateIban(string $iban): bool
{
    return Validator::make(['iban' => $iban], ['iban' => [new SaudiIban]])->passes();
}

it('accepts a Saudi IBAN with a correct checksum', function () {
    expect(validateIban(validSaudiIban()))->toBeTrue();
});

it('rejects a Saudi IBAN with the right shape but a wrong checksum', function () {
    // Flip the last digit of the account number: still "SA" + 22 digits,
    // but the MOD-97 checksum no longer holds.
    $valid = validSaudiIban();
    $tampered = substr($valid, 0, -1).(((int) substr($valid, -1) + 1) % 10);

    expect($tampered)->not->toBe($valid);
    expect(validateIban($tampered))->toBeFalse();
});

it('rejects an IBAN that is not Saudi (wrong country code)', function () {
    $notSaudi = 'AE070331234567890123456';

    expect(validateIban($notSaudi))->toBeFalse();
});

it('rejects an IBAN with the wrong number of digits', function () {
    expect(validateIban('SA0380000000608010'))->toBeFalse();
    expect(validateIban('SA038000000060801016751900'))->toBeFalse();
});

it('rejects a non-numeric suffix after SA', function () {
    expect(validateIban('SAABCDEFGHIJKLMNOPQRSTUV'))->toBeFalse();
});
