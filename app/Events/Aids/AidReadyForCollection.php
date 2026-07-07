<?php

namespace App\Events\Aids;

use App\Models\Aid;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an aid's disbursement is ready for the beneficiary to
 * collect (e.g. at the association's premises). Dispatched from the
 * disbursements domain (a later phase); this task only defines the
 * event and its listeners.
 */
class AidReadyForCollection
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Aid $aid) {}
}
