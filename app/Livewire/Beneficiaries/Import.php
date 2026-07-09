<?php

namespace App\Livewire\Beneficiaries;

use App\Actions\Beneficiaries\ImportBeneficiaries;
use App\Imports\SpreadsheetReader;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Smart beneficiary importer. The admin uploads an .xlsx/.xls/.csv, then
 * works a single review table: each column header carries an inline "map to
 * field" picker, every data row shows live below it, and per row the screen
 * flags validation errors (editable inline), national ids already in the
 * system, and in-file duplicate ids. Rows and whole groups can be excluded.
 * Only clean, included, non-duplicate rows are created. Every step is gated
 * on beneficiaries.import.
 */
class Import extends Component
{
    use WithFileUploads;

    /**
     * Disk the uploaded workbook is parked on while it is being reviewed.
     * Private (never the public disk): uploaded lists carry national ids and
     * mobiles.
     */
    private const DISK = 'local';

    public mixed $file = null;

    /** upload | review | done */
    public string $step = 'upload';

    /** Path of the stored workbook on {@see self::DISK}. */
    public ?string $storedPath = null;

    /**
     * Header labels, one per column index.
     *
     * @var array<int, string>
     */
    public array $headers = [];

    /**
     * Every non-blank data row, in document order. Each entry keeps the
     * 1-based spreadsheet line (header = 1) for error reporting and the
     * editable cell values keyed by column index.
     *
     * @var array<int, array{line: int, cells: array<int, string>}>
     */
    public array $rows = [];

    /**
     * The admin's per-column choice: column index => beneficiary field (or
     * '' to skip). This is the UI source of truth; {@see mapping()} inverts
     * it into the field => column shape the importer consumes.
     *
     * @var array<int, string>
     */
    public array $columnMapping = [];

    /**
     * Whether each row is included in the import, keyed by row index. Rows
     * default to included; duplicates are skipped regardless of this flag.
     *
     * @var array<int, bool>
     */
    public array $included = [];

    /**
     * Column index the review table is grouped by, or '' for a flat list.
     * Grouping powers the "exclude this whole group" affordance.
     */
    public string $groupBy = '';

    /**
     * Result summary after a run.
     *
     * @var array<string, mixed>|null
     */
    public ?array $summary = null;

    public function mount(): void
    {
        Gate::authorize('beneficiaries.import');
    }

    /**
     * @return array<int, string>
     */
    public function fields(): array
    {
        return ImportBeneficiaries::FIELDS;
    }

    /**
     * @return array<int, string>
     */
    public function requiredFields(): array
    {
        return ImportBeneficiaries::REQUIRED_FIELDS;
    }

    /**
     * Column-picker options: field => localized label (required fields get a
     * trailing marker so the admin sees what must be mapped).
     *
     * @return array<string, string>
     */
    public function fieldOptions(): array
    {
        $options = [];

        foreach ($this->fields() as $field) {
            $label = __('beneficiaries.field_'.$field);

            if (in_array($field, $this->requiredFields(), true)) {
                $label .= ' *';
            }

            $options[$field] = $label;
        }

        return $options;
    }

    /**
     * Invert {@see $columnMapping} into field => column index. When the same
     * field is picked for two columns the later column wins (the updated hook
     * keeps this from happening, but we stay defensive here).
     *
     * @return array<string, string>
     */
    public function mapping(): array
    {
        $mapping = [];

        foreach ($this->columnMapping as $column => $field) {
            if ($field !== '' && $field !== null) {
                $mapping[$field] = (string) $column;
            }
        }

        return $mapping;
    }

    /**
     * Are all required fields mapped to a column? Until then the review table
     * cannot meaningfully validate rows and the import is blocked.
     */
    public function requiredMapped(): bool
    {
        return array_diff($this->requiredFields(), array_keys($this->mapping())) === [];
    }

    /**
     * As soon as a file is selected, validate it, store it privately, read
     * its header + every data row, auto-guess the mapping and open the review
     * table.
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

        $header = $rows[0];

        $this->headers = array_values(array_map(
            fn (int $index, string $label): string => $label !== ''
                ? $label
                : __('beneficiaries.import.unnamed_column', ['number' => $index + 1]),
            array_keys($header),
            $header,
        ));

        $columnCount = count($this->headers);

        // Keep every non-blank data row, preserving its real spreadsheet line
        // number and normalizing each row to the full column width so inline
        // edits always have a cell to bind to.
        $this->rows = [];
        $this->included = [];

        foreach (array_slice($rows, 1, null, true) as $index => $row) {
            if ($this->isBlank($row)) {
                continue;
            }

            $cells = [];
            for ($column = 0; $column < $columnCount; $column++) {
                $cells[$column] = isset($row[$column]) ? trim((string) $row[$column]) : '';
            }

            $position = count($this->rows);
            $this->rows[$position] = ['line' => $index + 1, 'cells' => $cells];
            $this->included[$position] = true;
        }

        $this->columnMapping = array_fill(0, $columnCount, '');
        $this->groupBy = '';
        $this->autoGuessMapping();

        $this->step = 'review';
    }

    /**
     * Keep a field mapped to a single column: when it is picked for one
     * column, drop it from any other column that still held it.
     */
    public function updatedColumnMapping(mixed $value, ?string $key): void
    {
        if ($value === '' || $value === null || $key === null) {
            return;
        }

        foreach ($this->columnMapping as $column => $field) {
            if ((string) $column !== $key && $field === $value) {
                $this->columnMapping[$column] = '';
            }
        }
    }

    /**
     * Include or exclude every row at once.
     */
    public function setAllIncluded(bool $include): void
    {
        foreach (array_keys($this->rows) as $index) {
            $this->included[$index] = $include;
        }
    }

    /**
     * Include or exclude every row of a single group (rows sharing the same
     * value in the {@see $groupBy} column).
     */
    public function setGroupIncluded(string $value, bool $include): void
    {
        if ($this->groupBy === '') {
            return;
        }

        foreach ($this->rows as $index => $row) {
            if ($this->groupValue($row['cells']) === $value) {
                $this->included[$index] = $include;
            }
        }
    }

    /**
     * Run the import from the reviewed, in-memory rows.
     */
    public function import(): void
    {
        Gate::authorize('beneficiaries.import');

        if ($this->rows === []) {
            $this->step = 'upload';

            return;
        }

        $mapping = $this->mapping();

        $missing = array_diff($this->requiredFields(), array_keys($mapping));

        if ($missing !== []) {
            foreach ($missing as $field) {
                $this->addError('mapping.'.$field, __('beneficiaries.import.field_required', [
                    'field' => __('beneficiaries.field_'.$field),
                ]));
            }

            return;
        }

        $result = app(ImportBeneficiaries::class)->handleRows(
            $this->rows,
            $mapping,
            $this->excludedMap(),
            Auth::user(),
        );

        $this->summary = [
            'created' => $result->created,
            'duplicates' => $result->duplicates,
            'file_duplicates' => $result->fileDuplicates,
            'excluded' => $result->excluded,
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

        $this->reset(['file', 'storedPath', 'headers', 'rows', 'columnMapping', 'included', 'groupBy', 'summary']);
        $this->step = 'upload';
    }

    public function render()
    {
        $ready = $this->step === 'review' && $this->requiredMapped();
        $states = [];
        $stats = [
            'total' => count($this->rows),
            'valid' => 0,
            'error' => 0,
            'duplicate_system' => 0,
            'duplicate_file' => 0,
            'excluded' => 0,
        ];

        if ($ready) {
            $action = app(ImportBeneficiaries::class);
            $states = $action->analyze(
                $this->rows,
                $this->mapping(),
                $this->excludedMap(),
                $action->existingNationalIds($this->nationalIdColumnValues()),
            );

            foreach ($states as $state) {
                $stats[$state['status']] = ($stats[$state['status']] ?? 0) + 1;
            }
        }

        return view('livewire.beneficiaries.import', [
            'ready' => $ready,
            'states' => $states,
            'stats' => $stats,
            'groups' => $this->groups(),
            'grouped' => $this->groupBy !== '',
        ]);
    }

    /**
     * Rows organized for rendering: a map of group value => ordered row
     * indexes. Ungrouped mode returns a single anonymous group.
     *
     * @return array<string, array<int, int>>
     */
    private function groups(): array
    {
        if ($this->groupBy === '') {
            return ['' => array_keys($this->rows)];
        }

        $groups = [];

        foreach ($this->rows as $index => $row) {
            $groups[$this->groupValue($row['cells'])][] = $index;
        }

        return $groups;
    }

    /**
     * The (display-safe) value of the grouping column for a row.
     *
     * @param  array<int, string>  $cells
     */
    private function groupValue(array $cells): string
    {
        $value = trim((string) ($cells[(int) $this->groupBy] ?? ''));

        return $value === '' ? __('beneficiaries.import.group_blank') : $value;
    }

    /**
     * Row index => excluded?, derived from the include flags.
     *
     * @return array<int, bool>
     */
    private function excludedMap(): array
    {
        $excluded = [];

        foreach (array_keys($this->rows) as $index) {
            $excluded[$index] = ! ($this->included[$index] ?? true);
        }

        return $excluded;
    }

    /**
     * The raw national-id cell of every row (blank when unmapped), used to
     * probe which ids already exist in the system.
     *
     * @return array<int, string>
     */
    private function nationalIdColumnValues(): array
    {
        $column = $this->mapping()['national_id'] ?? null;

        if ($column === null || $column === '') {
            return [];
        }

        return array_map(
            fn (array $row): string => trim((string) ($row['cells'][(int) $column] ?? '')),
            $this->rows,
        );
    }

    /**
     * Pre-select a column for each field when its header label loosely
     * matches the field's Arabic/English name. The admin can always override.
     */
    private function autoGuessMapping(): void
    {
        foreach ($this->fields() as $field) {
            $candidates = [
                mb_strtolower(str_replace('_', ' ', $field)),
                mb_strtolower((string) __('beneficiaries.field_'.$field, [], 'ar')),
                mb_strtolower((string) __('beneficiaries.field_'.$field, [], 'en')),
            ];

            foreach ($this->headers as $index => $label) {
                $normalized = mb_strtolower(trim($label));

                if ($normalized === '' || ! in_array($normalized, $candidates, true)) {
                    continue;
                }

                // Do not overwrite a column already claimed by another field.
                if (($this->columnMapping[$index] ?? '') === '') {
                    $this->columnMapping[$index] = $field;
                }

                break;
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
}
