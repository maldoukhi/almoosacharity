<?php

namespace App\Exports;

use App\Actions\Beneficiaries\ImportBeneficiaries;
use App\Livewire\Beneficiaries\Import;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The official beneficiary import template: one canonical Arabic header per
 * field the smart importer ({@see Import}) can
 * populate, a single example row of realistic dummy data, and a trailing
 * note row spelling out which columns are required.
 *
 * {@see self::headers()} is the single source of truth for the canonical
 * labels — the Import component reuses it (rather than redeclaring the
 * labels) to auto-detect this exact template on upload and pre-fill the
 * column mapping.
 */
class BeneficiaryImportTemplateExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithStyles
{
    /**
     * Canonical field => Arabic column label, in the same order
     * {@see ImportBeneficiaries::FIELDS} declares them.
     *
     * @return array<string, string>
     */
    public static function headers(): array
    {
        $headers = [];

        foreach (ImportBeneficiaries::FIELDS as $field) {
            $headers[$field] = (string) __('beneficiaries.field_'.$field, [], 'ar');
        }

        return $headers;
    }

    public function headings(): array
    {
        return array_values(self::headers());
    }

    /**
     * One example row of realistic dummy data, followed by a note row
     * explaining the required columns. Both rows share the header's column
     * order.
     *
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        $columns = count(self::headers());

        $note = array_fill(0, $columns, '');
        $note[0] = (string) __('beneficiaries.import.template_note', [
            'fields' => $this->requiredLabels(),
        ]);

        return [
            $this->exampleRow(),
            $note,
        ];
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
     * Renders RTL (matching the app's Arabic-first UI), merges the trailing
     * note row across every column, and drops a tooltip comment on each
     * required field's header cell.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->setRightToLeft(true);

                $columns = count(self::headers());
                $lastColumn = Coordinate::stringFromColumnIndex($columns);
                $noteRow = 3; // 1: headers, 2: example row, 3: note row.

                $sheet->mergeCells("A{$noteRow}:{$lastColumn}{$noteRow}");
                $sheet->getStyle("A{$noteRow}")->getFont()->setItalic(true);
                $sheet->getStyle("A{$noteRow}")->getFont()->getColor()->setRGB('6B7280');
                $sheet->getStyle("A{$noteRow}")->getAlignment()->setWrapText(true);
                $sheet->getRowDimension($noteRow)->setRowHeight(32);

                $required = ImportBeneficiaries::REQUIRED_FIELDS;

                foreach (array_values(array_keys(self::headers())) as $index => $field) {
                    if (! in_array($field, $required, true)) {
                        continue;
                    }

                    $column = Coordinate::stringFromColumnIndex($index + 1);
                    $comment = $sheet->getComment("{$column}1");
                    $comment->getText()->createTextRun((string) __('beneficiaries.import.template_required_hint'));
                    $comment->setWidth('160pt');
                    $comment->setHeight('60pt');
                }
            },
        ];
    }

    /**
     * @return array<int, string>
     */
    private function exampleRow(): array
    {
        return [
            '1234567890', 'محمد', 'عبدالله', 'سعيد', 'الموسى',
            '0512345678', 'SA', '1985-04-12', 'ذكر', 'متزوج/متزوجة',
            'موظف', 'شركة الاتصالات السعودية', '4500', 'جيدة',
            'لا يوجد', 'مستأجر', 'الرياض', 'حي النرجس',
            'الرياض 1234 56789012', 'أسرة محتاجة',
            (string) __('beneficiaries.import.template_example_note'),
        ];
    }

    private function requiredLabels(): string
    {
        $headers = self::headers();

        return collect(ImportBeneficiaries::REQUIRED_FIELDS)
            ->map(fn (string $field): string => $headers[$field] ?? $field)
            ->implode('، ');
    }
}
