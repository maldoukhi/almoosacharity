<?php

namespace App\Livewire\Beneficiaries;

use App\Actions\Beneficiaries\ImportBeneficiaries;
use App\Imports\SpreadsheetReader;
use App\Models\Beneficiary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Flexible beneficiary importer: upload an .xlsx/.xls/.csv, map each
 * beneficiary field to one of the sheet's columns (or skip it), preview a
 * few rows, then create the beneficiaries — reporting created/duplicate/
 * error counts. All steps are gated on beneficiaries.import.
 */
class Import extends Component
{
    use WithFileUploads;

    /**
     * Disk the uploaded workbook is parked on between the map and confirm
     * steps. Private (never the public disk): uploaded lists may carry
     * national ids and mobiles.
     */
    private const DISK = 'local';

    public mixed $file = null;

    /** upload | map | done */
    public string $step = 'upload';

    /** Path of the stored workbook on {@see self::DISK}. */
    public ?string $storedPath = null;

    /**
     * Header row as index => label, used to build the per-field column
     * pickers.
     *
     * @var array<int, string>
     */
    public array $headers = [];

    /**
     * First few data rows for the visual preview table.
     *
     * @var array<int, array<int, string>>
     */
    public array $previewRows = [];

    /**
     * The admin's choice per beneficiary field: field => column index (as a
     * string, since it comes from a <select>) or '' for "skip".
     *
     * @var array<string, string>
     */
    public array $mapping = [];

    /**
     * Result summary after a run: ['created' => int, 'duplicates' => int,
     * 'errors' => array<int, array{row:int, reason:string}>].
     *
     * @var array<string, mixed>|null
     */
    public ?array $summary = null;

    public function mount(): void
    {
        Gate::authorize('beneficiaries.import');

        $this->mapping = array_fill_keys(ImportBeneficiaries::FIELDS, '');
    }

    /**
     * Fields offered in the mapping UI, in display order.
     *
     * @return array<int, string>
     */
    public function fields(): array
    {
        return ImportBeneficiaries::FIELDS;
    }

    /**
     * Fields the admin must map before the import can proceed.
     *
     * @return array<int, string>
     */
    public function requiredFields(): array
    {
        return ImportBeneficiaries::REQUIRED_FIELDS;
    }

    /**
     * As soon as a file is selected, validate it, store it privately, read
     * its header + a short preview, and advance to the mapping step.
     */
    public function updatedFile(): void
    {
        Gate::authorize('beneficiaries.import');

        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $this->storedPath = $this->file->store('imports', self::DISK);

        $rows = SpreadsheetReader::rows($this->storedPath, self::DISK);

        if ($rows === [] || $this->isBlank($rows[0] ?? [])) {
            $this->deleteStored();
            $this->reset(['file', 'storedPath']);
            $this->addError('file', __('beneficiaries.import.empty_file'));

            return;
        }

        $this->headers = array_values(array_map(
            fn (int $index, string $label): string => $label !== ''
                ? $label
                : __('beneficiaries.import.unnamed_column', ['number' => $index + 1]),
            array_keys($rows[0]),
            $rows[0],
        ));

        $this->previewRows = array_slice($rows, 1, 5);

        $this->autoGuessMapping();

        $this->step = 'map';
    }

    /**
     * Run the import against the chosen mapping.
     */
    public function import(): void
    {
        Gate::authorize('beneficiaries.import');

        if ($this->storedPath === null) {
            $this->step = 'upload';

            return;
        }

        // Required fields must be mapped to a column before we run.
        $rules = [];
        foreach ($this->requiredFields() as $field) {
            $rules["mapping.{$field}"] = ['required', 'string'];
        }

        $this->validate($rules, array_reduce(
            $this->requiredFields(),
            function (array $messages, string $field): array {
                $messages["mapping.{$field}.required"] = __('beneficiaries.import.field_required', [
                    'field' => __('beneficiaries.field_'.$field, [], null) ?? $field,
                ]);

                return $messages;
            },
            [],
        ));

        $result = app(ImportBeneficiaries::class)->handle(
            $this->storedPath,
            self::DISK,
            $this->mapping,
            Auth::user(),
        );

        $this->summary = [
            'created' => $result->created,
            'duplicates' => $result->duplicates,
            'errors' => $result->errors,
        ];

        $this->deleteStored();
        $this->storedPath = null;
        $this->step = 'done';

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.import.finished', [
            'count' => $result->created,
        ]));
    }

    /**
     * Discard the current upload and return to the start.
     */
    public function startOver(): void
    {
        $this->deleteStored();

        $this->reset(['file', 'storedPath', 'headers', 'previewRows', 'summary']);
        $this->mapping = array_fill_keys(ImportBeneficiaries::FIELDS, '');
        $this->step = 'upload';
    }

    /**
     * Column options for the mapping <select>s: column index => label.
     *
     * @return array<int, string>
     */
    public function columnOptions(): array
    {
        return $this->headers;
    }

    /**
     * Optional nicety: pre-select a column for each field when its header
     * label loosely matches the field's Arabic/English name. The admin can
     * always override — the mapping UI remains the source of truth.
     */
    private function autoGuessMapping(): void
    {
        foreach ($this->fields() as $field) {
            $candidates = [
                mb_strtolower(str_replace('_', ' ', $field)),
                mb_strtolower((string) __('beneficiaries.field_'.$field, [], 'ar')),
                mb_strtolower((string) __('beneficiaries.field_'.$field, [], 'en')),
            ];

            if ($field === 'categories') {
                $candidates[] = mb_strtolower((string) __('beneficiaries.field_categories', [], 'ar'));
            }

            foreach ($this->headers as $index => $label) {
                $normalized = mb_strtolower(trim($label));

                if ($normalized !== '' && in_array($normalized, $candidates, true)) {
                    $this->mapping[$field] = (string) $index;
                    break;
                }
            }
        }
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isBlank(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function deleteStored(): void
    {
        if ($this->storedPath !== null && Storage::disk(self::DISK)->exists($this->storedPath)) {
            Storage::disk(self::DISK)->delete($this->storedPath);
        }
    }

    public function render()
    {
        return view('livewire.beneficiaries.import');
    }
}
