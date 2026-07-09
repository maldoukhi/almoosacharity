<?php

namespace App\Enums;

use App\Models\RecurringAidPlan;
use Carbon\CarbonInterface;

/**
 * How often a {@see RecurringAidPlan} clones its source aid.
 * CustomMonths defers to the plan's stored interval_months value; every
 * other case carries a fixed month span.
 */
enum RecurrenceFrequency: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case SemiAnnual = 'semi_annual';
    case Yearly = 'yearly';
    case CustomMonths = 'custom_months';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('aids.recurrence.frequency.'.$this->value);
    }

    public function isCustom(): bool
    {
        return $this === self::CustomMonths;
    }

    /**
     * The number of months one cycle spans. Custom frequencies use the
     * plan's stored interval (falling back to 1 for a missing/invalid
     * value); every other case is fixed and ignores $customMonths.
     */
    public function intervalMonths(?int $customMonths = null): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Quarterly => 3,
            self::SemiAnnual => 6,
            self::Yearly => 12,
            self::CustomMonths => max(1, (int) $customMonths),
        };
    }

    /**
     * The next run date after $base for this frequency. addMonthsNoOverflow
     * keeps a 31st-of-month base from skipping short months.
     */
    public function nextDate(CarbonInterface $base, ?int $customMonths = null): CarbonInterface
    {
        return $base->copy()->addMonthsNoOverflow($this->intervalMonths($customMonths));
    }
}
