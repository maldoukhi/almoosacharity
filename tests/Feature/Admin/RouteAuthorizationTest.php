<?php

it('denies a social researcher access to the users list and the roles list', function () {
    asResearcher();

    $this->get('/admin/users')->assertForbidden();
    $this->get('/admin/roles')->assertForbidden();
});

it('denies a data entry user access to the users list and the roles list', function () {
    asDataEntry();

    $this->get('/admin/users')->assertForbidden();
    $this->get('/admin/roles')->assertForbidden();
});

it('allows a system admin to access the users list and the roles list via Gate::before', function () {
    asAdmin();

    $this->get('/admin/users')->assertOk();
    $this->get('/admin/roles')->assertOk();
});
