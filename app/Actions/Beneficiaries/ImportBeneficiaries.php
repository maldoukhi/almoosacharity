<?php

namespace App\Actions\Beneficiaries;

use App\Enums\Gender;
use App\Enums\HousingType;
use App\Enums\MaritalStatus;
use App\Models\Beneficiary;
use App\Models\BeneficiaryCategory;
use App\Models\User;
use App\Rules\SaudiMobile;
use App\Rules\SaudiNationalId;
use App\Support\BeneficiaryImportResult;
use App\Support\MobileNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Flexible Excel/CSV importer for beneficiaries. The admin maps each column
 * of the uploaded sheet to a beneficiary field (or skips it), reviews every
 * row inline — fixing bad cells, excluding people/groups, and seeing which
 * national ids are already in the system — and then imports.
 *
 * The heavy lifting lives in pure, side-effect-light methods ({@see mapRow},
 * {@see validateData}, {@see analyze}) so the live review screen and the
 * final {@see handleRows} import share one source of truth: what the admin
 * previews is exactly what gets created.
 */
class ImportBeneficiaries
{
    /**
     * Every beneficiary field the importer can populate, in display order.
     * `categories` is a relation (matched by name), everything else is a
     * plain column on the beneficiaries table. Bank fields are deliberately
     * excluded: they are sensitive and require a dedicated permission, so
     * they are never bulk-imported.
     *
     * @var array<int, string>
     */
    public const FIELDS = [
        'national_id', 'first_name', 'second_name', 'third_name', 'last_name',
        'mobile', 'nationality', 'birth_date', 'gender', 'marital_status',
        'occupation', 'employer', 'monthly_income', 'health_status',
        'special_needs', 'housing_type', 'city', 'district',
        'national_address', 'categories', 'notes',
    ];

    /**
     * Fields the admin must map before an import can run: without them a row
     * can never satisfy the beneficiary validation rules.
     *
     * @var array<int, string>
     */
    public const REQUIRED_FIELDS = [
        'national_id', 'first_name', 'last_name', 'mobile',
        'gender', 'marital_status', 'housing_type',
    ];

    /**
     * Import the given in-memory rows honouring the admin's mapping,
     * per-row exclusions, and both flavours of duplicate detection.
     *
     * Nothing is ever fatal: each valid row is created in its own
     * transaction and any failure is captured against that row only.
     *
     * @param  array<int, array{line: int, cells: array<int, string>}>  $rows
     * @param  array<string, int|string|null>  $mapping  field => column index (or '' to skip)
     * @param  array<int, bool>  $excluded  row index => excluded?
     */
    public function handleRows(array $rows, array $mapping, array $excluded, User $actor): BeneficiaryImportResult
    {
        $result = new BeneficiaryImportResult;

        $existingIds = $this->existingNationalIds($this->candidateNationalIds($rows, $mapping));
        $analysis = $this->analyze($rows, $mapping, $excluded, $existingIds);
        $categoryLookup = $this->categoryLookup();

        foreach ($rows as $index => $row) {
            $state = $analysis[$index] ?? null;

            if ($state === null) {
                continue;
            }

            switch ($state['status']) {
                case 'excluded':
                    $result->recordExcluded();
                    break;

                case 'duplicate_system':
                    $result->recordDuplicate();
                    break;

                case 'duplicate_file':
                    $result->recordFileDuplicate();
                    break;

                case 'error':
                    $result->recordError($row['line'], (string) $state['error']);
                    break;

                case 'valid':
                    $this->createRow($row['cells'], $mapping, $categoryLookup, $actor, $result, $row['line']);
                    break;
            }
        }

        return $result;
    }

    /**
     * Classify every row for both the live review table and the import run.
     *
     * Precedence per row: an admin exclusion wins first, then a validation
     * error, then an already-in-system national id, then an in-file repeat,
     * otherwise the row is valid. In-file duplicates are decided in document
     * order — the first occurrence of a national id imports, later ones are
     * flagged.
     *
     * @param  array<int, array{line: int, cells: array<int, string>}>  $rows
     * @param  array<string, int|string|null>  $mapping
     * @param  array<int, bool>  $excluded
     * @param  array<int, string>  $existingIds  national ids already in the system
     * @return array<int, array{status: string, error: string|null, national_id: string, line: int}>
     */
    public function analyze(array $rows, array $mapping, array $excluded, array $existingIds): array
    {
        $existing = array_fill_keys($existingIds, true);
        $claimed = [];
        $states = [];

        foreach ($rows as $index => $row) {
            $cells = $row['cells'];
            $data = $this->mapRow($cells, $mapping);
            $nationalId = $data['national_id'];
            $error = $this->validateData($data);

            if ($excluded[$index] ?? false) {
                $status = 'excluded';
            } elseif ($error !== null) {
                $status = 'error';
            } elseif ($nationalId !== '' && isset($existing[$nationalId])) {
                $status = 'duplicate_system';
            } elseif ($nationalId !== '' && isset($claimed[$nationalId])) {
                $status = 'duplicate_file';
            } else {
                $status = 'valid';

                if ($nationalId !== '') {
                    $claimed[$nationalId] = true;
                }
            }

            $states[$index] = [
                'status' => $status,
                'error' => $error,
                'national_id' => $nationalId,
                'line' => $row['line'],
            ];
        }

        return $states;
    }

    /**
     * Parse one row's raw cells into the beneficiary field shape using the
     * current mapping. Pure: no validation, no persistence.
     *
     * @param  array<int, string>  $cells
     * @param  array<string, int|string|null>  $mapping
     * @return array<string, mixed>
     */
    public function mapRow(array $cells, array $mapping): array
    {
        $get = function (string $field) use ($cells, $mapping): string {
            $column = $mapping[$field] ?? null;

            if ($column === null || $column === '') {
                return '';
            }

            return isset($cells[(int) $column]) ? trim((string) $cells[(int) $column]) : '';
        };

        return [
            'first_name' => $get('first_name'),
            'second_name' => $get('second_name'),
            'third_name' => $get('third_name'),
            'last_name' => $get('last_name'),
            'national_id' => $get('national_id'),
            'nationality' => $get('nationality') !== '' ? $get('nationality') : 'SA',
            'mobile' => MobileNumber::normalize($get('mobile')),
            'gender' => $this->resolveEnum(Gender::class, $get('gender')),
            'marital_status' => $this->resolveEnum(MaritalStatus::class, $get('marital_status')),
            'housing_type' => $this->resolveEnum(HousingType::class, $get('housing_type')),
            'occupation' => $get('occupation'),
            'employer' => $get('employer'),
            'monthly_income' => $this->parseNumber($get('monthly_income')),
            'health_status' => $get('health_status'),
            'special_needs' => $get('special_needs'),
            'city' => $get('city'),
            'district' => $get('district'),
            'national_address' => $get('national_address'),
            'notes' => $get('notes'),
            'birth_date' => $this->parseDate($get('birth_date')),
        ];
    }

    /**
     * Validate a single parsed row. Returns the first human-readable error
     * message, or null when the row is importable.
     *
     * @param  array<string, mixed>  $data
     */
    public function validateData(array $data): ?string
    {
        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'national_id' => ['required', 'string', new SaudiNationalId],
            'mobile' => ['required', 'string', new SaudiMobile],
            'gender' => ['required', Rule::enum(Gender::class)],
            'marital_status' => ['required', Rule::enum(MaritalStatus::class)],
            'housing_type' => ['nullable', Rule::enum(HousingType::class)],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ]);

        return $validator->fails() ? $validator->errors()->first() : null;
    }

    /**
     * Which of the given national ids already exist in the beneficiaries
     * table — soft-deleted included, so we never hit the unique constraint.
     *
     * @param  array<int, string>  $ids
     * @return array<int, string>
     */
    public function existingNationalIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, fn (string $id): bool => $id !== '')));

        if ($ids === []) {
            return [];
        }

        return Beneficiary::withTrashed()
            ->whereIn('national_id', $ids)
            ->pluck('national_id')
            ->map(fn ($id): string => (string) $id)
            ->all();
    }

    /**
     * The mapped national id of every row (blank for rows with no id yet).
     *
     * @param  array<int, array{line: int, cells: array<int, string>}>  $rows
     * @param  array<string, int|string|null>  $mapping
     * @return array<int, string>
     */
    private function candidateNationalIds(array $rows, array $mapping): array
    {
        $column = $mapping['national_id'] ?? null;

        if ($column === null || $column === '') {
            return [];
        }

        return array_map(
            fn (array $row): string => isset($row['cells'][(int) $column])
                ? trim((string) $row['cells'][(int) $column])
                : '',
            $rows,
        );
    }

    /**
     * Create one validated row and its category links, capturing any failure
     * against the row instead of aborting the batch.
     *
     * @param  array<int, string>  $cells
     * @param  array<string, int|string|null>  $mapping
     * @param  array<string, int>  $categoryLookup
     */
    private function createRow(
        array $cells,
        array $mapping,
        array $categoryLookup,
        User $actor,
        BeneficiaryImportResult $result,
        int $line,
    ): void {
        $data = $this->mapRow($cells, $mapping);
        $categoryIds = $this->resolveCategoryIds($this->rawCell($cells, $mapping, 'categories'), $categoryLookup);

        try {
            DB::transaction(function () use ($data, $categoryIds, $actor): void {
                $beneficiary = Beneficiary::create([
                    ...$this->cleanBlanks($data),
                    'status' => 'under_study',
                    'created_by' => $actor->id,
                ]);

                if ($categoryIds !== []) {
                    $beneficiary->categories()->sync($categoryIds);
                }
            });

            $result->recordCreated();
        } catch (Throwable $e) {
            $result->recordError($line, $e->getMessage());
        }
    }

    /**
     * The trimmed cell value for a mapped field, or '' when the field is
     * skipped / out of range.
     *
     * @param  array<int, string>  $cells
     * @param  array<string, int|string|null>  $mapping
     */
    private function rawCell(array $cells, array $mapping, string $field): string
    {
        $column = $mapping[$field] ?? null;

        if ($column === null || $column === '') {
            return '';
        }

        return isset($cells[(int) $column]) ? trim((string) $cells[(int) $column]) : '';
    }

    /**
     * @return array<string, int>
     */
    private function categoryLookup(): array
    {
        return BeneficiaryCategory::query()
            ->where('is_active', true)
            ->pluck('id', 'name')
            ->mapWithKeys(fn (int $id, string $name): array => [$this->normalizeLabel($name) => $id])
            ->all();
    }

    /**
     * Resolve a delimited list of category names to existing category ids.
     * Unknown names are silently ignored (the mapping UI is authoritative;
     * we do not invent categories during an import).
     *
     * @param  array<string, int>  $lookup
     * @return array<int, int>
     */
    private function resolveCategoryIds(string $raw, array $lookup): array
    {
        if ($raw === '') {
            return [];
        }

        return collect(preg_split('/[,،;؛|]/u', $raw) ?: [])
            ->map(fn (string $name): string => $this->normalizeLabel($name))
            ->filter()
            ->map(fn (string $name): ?int => $lookup[$name] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve a free-text spreadsheet value to a backing enum value by
     * matching (case/space-insensitively) against the enum's backing values
     * and its localized Arabic/English labels. Returns the raw value when
     * nothing matches so the row fails enum validation with a clear message.
     *
     * @param  class-string  $enum
     */
    private function resolveEnum(string $enum, string $raw): ?string
    {
        if ($raw === '') {
            // Blank stays null so a nullable enum column (e.g. housing_type)
            // stores null rather than '' — an empty string is not a valid
            // enum backing value and would blow up the model's enum cast on
            // read. Required enum fields fail their 'required' rule on null
            // and the row is flagged, exactly as before.
            return null;
        }

        $needle = $this->normalizeLabel($raw);

        foreach ($enum::cases() as $case) {
            if ($this->normalizeLabel($case->value) === $needle) {
                return $case->value;
            }

            if (method_exists($case, 'label') && $this->normalizeLabel($case->label()) === $needle) {
                return $case->value;
            }
        }

        return $raw;
    }

    /**
     * Parse a possibly-formatted numeric string ("1,200", "1200.50") to a
     * plain numeric string, or null when it is blank/non-numeric.
     */
    private function parseNumber(string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }

        $normalized = str_replace([',', '،', ' '], '', $raw);

        return is_numeric($normalized) ? $normalized : null;
    }

    /**
     * Best-effort date parsing. Returns Y-m-d, or null on a blank/unparsable
     * value so the (nullable) birth_date column simply stays empty rather
     * than failing the whole row.
     */
    private function parseDate(string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($raw)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Lower-case, collapse whitespace and strip surrounding punctuation so
     * label matching is tolerant of spacing/case differences.
     */
    private function normalizeLabel(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? $value;

        return mb_strtolower($value);
    }

    /**
     * Convert '' to null for nullable columns so MySQL strict mode accepts
     * them; leave required string columns (mobile, names) untouched.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function cleanBlanks(array $data): array
    {
        // `city` is intentionally excluded: its column is NOT NULL, so a
        // blank stays as '' rather than becoming null.
        $nullable = [
            'second_name', 'third_name', 'occupation', 'employer',
            'health_status', 'special_needs', 'district',
            'national_address', 'notes',
        ];

        foreach ($nullable as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
