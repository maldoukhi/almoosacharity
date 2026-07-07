<?php

use App\Rules\SaudiMobile;
use Illuminate\Support\Facades\Validator;

function validateMobile(string $value): bool
{
    return Validator::make(
        ['mobile' => $value],
        ['mobile' => [new SaudiMobile]],
    )->passes();
}

it('accepts a local Saudi mobile number (05 + 8 digits)', function () {
    expect(validateMobile('0512345678'))->toBeTrue();
});

it('rejects a mobile number not starting with 05', function () {
    expect(validateMobile('0612345678'))->toBeFalse();
    expect(validateMobile('512345678'))->toBeFalse();
});

it('rejects a mobile number with the wrong length', function () {
    expect(validateMobile('051234567'))->toBeFalse();
    expect(validateMobile('05123456789'))->toBeFalse();
});

it('rejects an international-format mobile number', function () {
    expect(validateMobile('+966512345678'))->toBeFalse();
});
