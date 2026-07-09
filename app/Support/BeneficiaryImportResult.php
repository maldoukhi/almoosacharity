<?php

namespace App\Support;

use App\Actions\Beneficiaries\ImportBeneficiaries;

/**
 * Mutable summary of a single beneficiary import run: how many rows were
 * created, how many were skipped as duplicates of an existing national id,
 * and a per-row list of validation/parsing errors. Returned by
 * {@see ImportBeneficiaries} and rendered by the
 * import screen's result step.
 */
final class BeneficiaryImportResult
{
    public int $created = 0;

    public int $duplicates = 0;

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

    /**
     * Record a skipped row. `$row` is the 1-based spreadsheet row number
     * (header row = 1) so the message the admin sees points at the real
     * line in their file.
     */
    public function recordError(int $row, string $reason): void
    {
        $this->errors[] = ['row' => $row, 'reason' => $reason];
    }

    public function skipped(): int
    {
        return $this->duplicates + count($this->errors);
    }
}
