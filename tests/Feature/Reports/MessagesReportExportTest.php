<?php

use App\Livewire\Reports\MessagesReport;
use Livewire\Livewire;

it('lets a user with reports.export download the messages report as an excel file', function () {
    asAdmin();

    Livewire::test(MessagesReport::class)
        ->call('exportExcel')
        ->assertFileDownloaded('messages-report-'.now()->format('Y-m-d').'.xlsx');
});

it('forbids a manager (reports.view but not reports.export) from exporting the messages report', function () {
    asManager();

    Livewire::test(MessagesReport::class)
        ->call('exportExcel')
        ->assertForbidden();
});

it('lets a user with reports.view but not reports.export open the messages report screen without exporting', function () {
    asManager();

    Livewire::test(MessagesReport::class)
        ->assertOk();
});
