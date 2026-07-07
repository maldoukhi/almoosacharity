<?php

namespace App\Exports;

use App\Reports\Contracts\Report;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Shared, thin Excel export base for every report: it only renders what a
 * Report already computed (headings/rows/totals) — no query logic lives
 * here. Concrete exports (AidsExport, ...) just bind a specific Report and,
 * where the report's displayable rows are not the same as query()->get()
 * (e.g. FinancialReport, which groups in PHP), override collection().
 */
abstract class ReportExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    public function __construct(protected readonly Report $report) {}

    /**
     * @return Collection<int, mixed>
     */
    public function collection(): Collection
    {
        return $this->report->query()->get();
    }

    public function headings(): array
    {
        return $this->report->headings();
    }

    public function map($row): array
    {
        return $this->report->map($row);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1C545E'],
                ],
            ],
        ];
    }

    /**
     * Renders the sheet right-to-left, matching the app's Arabic-first UI.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $event->sheet->getDelegate()->setRightToLeft(true);
            },
        ];
    }
}
