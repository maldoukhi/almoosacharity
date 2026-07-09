<?php

namespace App\Support;

use App\Actions\Beneficiaries\ImportBeneficiaries;

/**
 * Mutable summary of a single beneficiary import run. It tallies every way a
 * row can end up: created, skipped because its national id already exists in
 * the system, skipped as an in-file duplicate national id, excluded by the
 * admin, or failed validation. Returned by {@see ImportBeneficiaries} and
 * rendered by the import screen's result step.
 */
final class BeneficiaryImportResult
{
    public int $created = 0;

    /**
     * Rows skipped because their national id already exists in the
     * beneficiaries table (including soft-deleted rows).
     */
    public int $duplicates = 0;

    /**
     * Rows skipped because their national id repeated an earlier row within
     * the same uploaded file.
     */
    public int $fileDuplicates = 0;

    /**
     * Rows the admin deliberately excluded from the import.
     */
    public int $excluded = 0;

    /**
     * @var array<int, array{row: int, reason: string}>
     */
    public array $errors = [];

    public function recordCreated(): void
    {
        $this->created++;
    }

    public function recordDuplicate(): void
    {
        $this->duplicates++;
    }

    public function recordFileDuplicate(): void
    {
        $this->fileDuplicates++;
    }

    public function recordExcluded(): void
    {
        $this->excluded++;
    }

    /**
     * Record a failed row. `$row` is the 1-based spreadsheet line number
     * (header row = 1) so the message the admin sees points at the real line
     * in their file.
     */
    public function recordError(int $row, string $reason): void
    {
        $this->errors[] = ['row' => $row, 'reason' => $reason];
    }

    /**
     * Total rows that were not created, for a quick "processed" headline.
     */
    public function skipped(): int
    {
        return $this->duplicates + $this->fileDuplicates + $this->excluded + count($this->errors);
    }
}
