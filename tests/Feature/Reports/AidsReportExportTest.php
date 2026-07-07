<?php

use App\Livewire\Reports\AidsReport;
use Livewire\Livewire;

it('lets a user with reports.export download the aids report as an excel file', function () {
    asAdmin();

    Livewire::test(AidsReport::class)
        ->call('exportExcel')
        ->assertFileDownloaded('aids-report-'.now()->format('Y-m-d').'.xlsx');
});

it('forbids a manager (reports.view but not reports.export) from exporting the aids report', function () {
    asManager();

    Livewire::test(AidsReport::class)
        ->call('exportExcel')
        ->assertForbidden();
});
