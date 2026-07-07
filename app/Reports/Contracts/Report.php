<?php

namespace App\Reports\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract every report in App\Reports must implement, so the Excel exports
 * (App\Exports) and the Livewire report screens can consume any report
 * uniformly: a report only knows how to build its query, describe its
 * columns, and total itself — it never renders Excel/PDF markup directly.
 */
interface Report
{
    /**
     * Whether the current user is allowed to view this report. Screens and
     * exports must check this before doing any work.
     */
    public function authorize(): bool;

    /**
     * The base query for this report, with filters already applied and
     * soft-deleted records excluded by default.
     *
     * @return Builder<Model>
     */
    public function query(): Builder;

    /**
     * Column headings, in the same order as map().
     *
     * @return array<int, string>
     */
    public function headings(): array;

    /**
     * Map a single row into a flat array of display values, in the same
     * order as headings(). For most reports $row is an Eloquent model
     * yielded by query(); FinancialReport instead groups its rows in PHP
     * (see FinancialReport::rows()) and passes plain arrays here.
     *
     * @param  Model|array<string, mixed>  $row
     * @return array<int, mixed>
     */
    public function map($row): array;

    /**
     * Aggregate totals for this report (counts/sums), keyed by a
     * translation-friendly label.
     *
     * @return array<string, mixed>
     */
    public function totals(): array;

    /**
     * Translated, human readable report title.
     */
    public function title(): string;

    /**
     * A filesystem-safe filename (without extension) for the given
     * extension, e.g. filename('xlsx') => 'aids-report-2026-07-07.xlsx'.
     */
    public function filename(string $ext): string;

    /**
     * The Blade view name (dot notation) used to render this report as PDF.
     */
    public function pdfView(): string;
}
