<?php

it('lets a manager (reports.view) open every report route', function () {
    asManager();

    $this->get(route('reports.index'))->assertOk();
    $this->get(route('reports.aids'))->assertOk();
    $this->get(route('reports.beneficiaries'))->assertOk();
    $this->get(route('reports.financial'))->assertOk();
    $this->get(route('reports.surveys'))->assertOk();
});

it('forbids a data-entry user (no reports.view) from opening any report route', function () {
    asDataEntry();

    $this->get(route('reports.index'))->assertForbidden();
    $this->get(route('reports.aids'))->assertForbidden();
    $this->get(route('reports.financial'))->assertForbidden();
});
