<?php

namespace App\Listeners;

use App\Enums\NotificationEvent;
use App\Events\Aids\AidApproved;
use App\Events\Aids\AidDelivered;
use App\Events\Aids\AidReadyForCollection;
use App\Services\Notifications\NotifyBeneficiary;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Translates an aid lifecycle event into the matching beneficiary-facing
 * SMS/WhatsApp send, via {@see NotifyBeneficiary}. Queued: it makes an
 * outbound HTTP-bound call chain, which must never block the request
 * that dispatched the underlying event.
 */
class SendBeneficiaryAidNotification implements ShouldQueue
{
    public function __construct(private readonly NotifyBeneficiary $notifyBeneficiary) {}

    public function handle(AidApproved|AidReadyForCollection|AidDelivered $event): void
    {
        $notificationEvent = match ($event::class) {
            AidApproved::class => NotificationEvent::AidApproved,
            AidReadyForCollection::class => NotificationEvent::AidReady,
            AidDelivered::class => NotificationEvent::AidDelivered,
        };

        $this->notifyBeneficiary->send($event->aid, $notificationEvent);
    }
}
