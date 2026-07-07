<?php

use App\Rules\SaudiNationalId;
use Illuminate\Support\Facades\Validator;

function validateNationalId(string $value): bool
{
    return Validator::make(
        ['national_id' => $value],
        ['national_id' => [new SaudiNationalId]],
    )->passes();
}

it('accepts a citizen national id starting with 1', function () {
    expect(validateNationalId('1234567890'))->toBeTrue();
});

it('accepts a resident iqama number starting with 2', function () {
    expect(validateNationalId('2234567890'))->toBeTrue();
});

it('rejects a 10-digit number not starting with 1 or 2', function () {
    expect(validateNationalId('3234567890'))->toBeFalse();
    expect(validateNationalId('0234567890'))->toBeFalse();
});

it('rejects a national id with the wrong number of digits', function () {
    expect(validateNationalId('123456789'))->toBeFalse();
    expect(validateNationalId('12345678901'))->toBeFalse();
});

it('rejects a national id containing non-digit characters', function () {
    expect(validateNationalId('123456789a'))->toBeFalse();
});
