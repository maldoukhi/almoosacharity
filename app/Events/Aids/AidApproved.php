<?php

namespace App\Events\Aids;

use App\Enums\AidStatus;
use App\Models\Aid;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired once an aid clears its final approval stage and becomes
 * {@see AidStatus::Approved}.
 */
class AidApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Aid $aid) {}
}
