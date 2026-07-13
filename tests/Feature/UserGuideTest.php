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
