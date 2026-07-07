<?php

namespace App\Listeners;

use App\Actions\Confirmations\CreateAidConfirmation;
use App\Actions\Confirmations\SendConfirmationLink;
use App\Events\Aids\AidDelivered;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Issues and sends the beneficiary-facing "confirm receipt" link as soon
 * as an aid's disbursement is recorded as delivered. Queued: creating the
 * confirmation row is cheap, but sending goes through {@see SendConfirmationLink}
 * which dispatches the actual outbound SMS/WhatsApp job.
 */
class CreateConfirmationOnDelivery implements ShouldQueue
{
    public function __construct(
        private readonly CreateAidConfirmation $create,
        private readonly SendConfirmationLink $send,
    ) {}

    public function handle(AidDelivered $event): void
    {
        ['confirmation' => $confirmation, 'rawToken' => $rawToken] = $this->create->handle($event->aid);

        $this->send->handle($confirmation, $rawToken);
    }
}
