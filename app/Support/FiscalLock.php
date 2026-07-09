<?php

namespace App\Support;

use App\Actions\Aids\CancelAid;
use App\Actions\Aids\UpdateAid;
use App\Models\Aid;
use App\Policies\AidPolicy;
use RuntimeException;

/**
 * Fiscal-year lock: once an accounting year has been closed, aids that
 * belong to it (by their `created_at` year) become immutable — they can no
 * longer be updated, cancelled or deleted, only viewed and exported.
 *
 * The closed-through year is stored as a single `fiscal_locked_until_year`
 * setting (an int year, or unset for "no lock") managed from the
 * {@see \App\Livewire\Settings\FiscalLock} screen. An aid is locked when its
 * creation year is at or before that year — draft aids included, so a stray
 * draft left over from a closed year cannot be quietly edited either.
 *
 * Enforcement is centralised in {@see AidPolicy} (so every UI
 * path inherits it via the gate) and reinforced on the direct write actions
 * ({@see UpdateAid}, {@see CancelAid})
 * via {@see assertMutable()}.
 */
class FiscalLock
{
    /**
     * The year through which the books are closed, or null when no fiscal
     * lock is set.
     */
    public static function lockedUntilYear(): ?int
    {
        $value = app(Settings::class)->get('fiscal_locked_until_year');

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * Whether the given aid falls inside a locked (closed) fiscal year and
     * is therefore immutable.
     */
    public static function isLocked(Aid $aid): bool
    {
        $lockedYear = self::lockedUntilYear();

        if ($lockedYear === null) {
            return false;
        }

        $createdYear = $aid->created_at?->year;

        if ($createdYear === null) {
            return false;
        }

        return $createdYear <= $lockedYear;
    }

    /**
     * Guard a write path: throw with a translated denial message when the
     * aid belongs to a locked fiscal year. A no-op otherwise.
     *
     * @throws RuntimeException
     */
    public static function assertMutable(Aid $aid): void
    {
        if (self::isLocked($aid)) {
            throw new RuntimeException(__('reports.fiscal.locked_error'));
        }
    }
}
