<?php

namespace App\Imports;

use Maatwebsite\Excel\Facades\Excel;

/**
 * Thin wrapper around maatwebsite/excel's toArray() reader used by the
 * flexible beneficiary importer. It returns the first sheet as a plain
 * array of numerically-indexed rows (row 0 being the header row), so the
 * import flow can present the raw columns to the admin for manual mapping
 * without assuming any particular header names.
 *
 * A bare, concern-less object is passed to Excel::toArray() on purpose: we
 * want the untouched cell grid, not a heading-keyed or model-bound import.
 */
final class SpreadsheetReader
{
    /**
     * Read every row of the first sheet of the given stored file.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function rows(string $path, string $disk): array
    {
        $sheets = Excel::toArray(new self, $path, $disk);

        $rows = $sheets[0] ?? [];

        // Normalize each cell to a trimmed string (or '' for null/blank),
        // so downstream mapping/validation never has to juggle mixed types
        // coming out of the spreadsheet reader.
        return array_map(
            static fn (array $row): array => array_map(
                static fn ($cell): string => trim((string) $cell),
                $row,
            ),
            $rows,
        );
    }
}
