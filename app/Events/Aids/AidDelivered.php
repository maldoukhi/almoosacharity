<?php

namespace App\Events\Aids;

use App\Models\Aid;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an aid's disbursement has been delivered to the
 * beneficiary. Dispatched from the disbursements domain (a later
 * phase); this task only defines the event and its listeners.
 */
class AidDelivered
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Aid $aid) {}
}
