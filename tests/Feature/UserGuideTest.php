<?php

it('serves the user guide to a signed-in employee', function () {
    asDataEntry();

    $response = test()->get(route('help.user-guide'));

    $response->assertOk();
    expect($response->baseResponse->headers->get('Content-Type'))->toContain('text/html');
});

it('redirects guests to the login page', function () {
    test()->get(route('help.user-guide'))->assertRedirect(route('login'));
});

it('serves a single guide section for the contextual help modal', function () {
    asDataEntry();

    $response = test()->get(route('help.user-guide.section', 'beneficiaries'));

    $response->assertOk();
    expect($response->baseResponse->getContent())
        ->toContain('id="beneficiaries"')
        ->not->toContain('id="reports"');
});

it('404s on an unknown guide section', function () {
    asDataEntry();

    test()->get('/help/user-guide/nope')->assertNotFound();
});
