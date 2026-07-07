<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Delivery Confirmation Link
    |--------------------------------------------------------------------------
    |
    | The beneficiary-facing "confirm receipt" link (sent once an aid's
    | disbursement is recorded as delivered) is a signed, single-purpose
    | URL valid for `ttl_days` days. If it hasn't been confirmed by
    | `reminder_after_days` days after sending, exactly one automatic
    | reminder is sent through the same channel(s) as the original link.
    | Per CLAUDE.md's phase 6 decision: 7 days / 3 days.
    |
    */

    'ttl_days' => (int) env('AID_CONFIRMATION_TTL_DAYS', 7),

    'reminder_after_days' => (int) env('AID_CONFIRMATION_REMINDER_AFTER_DAYS', 3),

];
