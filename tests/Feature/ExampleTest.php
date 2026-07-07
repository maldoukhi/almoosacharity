<?php

it('redirects guests from the home page to the login screen', function () {
    // The root route no longer renders a static welcome page: it redirects
    // based on auth state (Phase 1b introduces the login route).
    $this->get('/')->assertRedirect(route('login'));
});
