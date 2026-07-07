<?php

it('grants the social researcher role approvals.act but not users.view', function () {
    $user = asResearcher();

    expect($user->can('approvals.act'))->toBeTrue();
    expect($user->can('users.view'))->toBeFalse();
});

it('grants the data entry role beneficiaries.bank-data.manage but not beneficiaries.bank-data.view', function () {
    $user = asDataEntry();

    expect($user->can('beneficiaries.bank-data.manage'))->toBeTrue();
    expect($user->can('beneficiaries.bank-data.view'))->toBeFalse();
});

it('lets a system admin pass can() for any permission via Gate::before, with no direct permissions assigned', function () {
    $user = asAdmin();

    expect($user->getAllPermissions())->toBeEmpty();

    expect($user->can('users.view'))->toBeTrue();
    expect($user->can('roles.delete'))->toBeTrue();
    expect($user->can('beneficiaries.bank-data.view'))->toBeTrue();
    expect($user->can('some.permission.that.does.not.exist'))->toBeTrue();
});
