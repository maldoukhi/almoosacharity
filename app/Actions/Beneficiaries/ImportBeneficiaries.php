<?php

namespace App\Actions\Beneficiaries;

use App\Enums\Gender;
use App\Enums\HousingType;
use App\Enums\MaritalStatus;
use App\Imports\SpreadsheetReader;
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
 * Flexible Excel/CSV importer for beneficiaries. The admin maps each
 * beneficiary field to whichever spreadsheet column it lives in (or skips
 * it); this action then parses every data row against that mapping,
 * validates it, and creates a Beneficiary — collecting per-row failures
 * instead of ever aborting the whole batch on a single bad cell.
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
     * Run the import.
     *
     * @param  array<string, int|string|null>  $mapping  field => column index (or null/'' to skip)
     */
    public function handle(string $path, string $disk, array $mapping, User $actor): BeneficiaryImportResult
    {
        $result = new BeneficiaryImportResult;

        $rows = SpreadsheetReader::rows($path, $disk);

        // Row 0 is the header; data starts at row 1. Cache the active
        // categories once (lower-cased name => id) for name matching.
        $categoryLookup = $this->categoryLookup();

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            // 1-based line number as the admin sees it in their spreadsheet.
            $lineNumber = $index + 1;

            if ($this->isBlankRow($row)) {
                continue;
            }

            $this->importRow($row, $lineNumber, $mapping, $categoryLookup, $actor, $result);
        }

        return $result;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int|string|null>  $mapping
     * @param  array<string, int>  $categoryLookup
     */
    private function importRow(
        array $row,
        int $lineNumber,
        array $mapping,
        array $categoryLookup,
        User $actor,
        BeneficiaryImportResult $result,
    ): void {
        $get = function (string $field) use ($row, $mapping): string {
            $column = $mapping[$field] ?? null;

            if ($column === null || $column === '') {
                return '';
            }

            return isset($row[(int) $column]) ? trim((string) $row[(int) $column]) : '';
        };

        $data = [
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

        if ($validator->fails()) {
            $result->recordError($lineNumber, $validator->errors()->first());

            return;
        }

        // De-dupe on national id (including soft-deleted rows so we never
        // hit the unique constraint): skip existing by default and report.
        if (Beneficiary::withTrashed()->where('national_id', $data['national_id'])->exists()) {
            $result->recordDuplicate();

            return;
        }

        $categoryIds = $this->resolveCategoryIds($this->rawCell($row, $mapping, 'categories'), $categoryLookup);

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
            $result->recordError($lineNumber, $e->getMessage());
        }
    }

    /**
     * The raw (untrimmed-then-trimmed) cell value for a mapped field, or ''
     * when the field is skipped / out of range.
     *
     * @param  array<int, mixed>  $row
     * @param  array<string, int|string|null>  $mapping
     */
    private function rawCell(array $row, array $mapping, string $field): string
    {
        $column = $mapping[$field] ?? null;

        if ($column === null || $column === '') {
            return '';
        }

        return isset($row[(int) $column]) ? trim((string) $row[(int) $column]) : '';
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
     * and its localized Arabic/English labels. Returns '' when nothing
     * matches so the row fails enum validation with a clear message.
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
            // and the row is skipped, exactly as before.
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
     * @param  array<int, mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
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
