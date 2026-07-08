<?php

namespace App\Listeners;

use App\Enums\NotificationEvent;
use App\Events\Aids\AidApproved;
use App\Events\Aids\AidDelivered;
use App\Events\Aids\AidReadyForCollection;
use App\Services\Notifications\NotifyBeneficiary;
use App\Support\Settings;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Translates an aid lifecycle event into the matching beneficiary-facing
 * SMS/WhatsApp send, via {@see NotifyBeneficiary}. Queued: it makes an
 * outbound HTTP-bound call chain, which must never block the request
 * that dispatched the underlying event.
 */
class SendBeneficiaryAidNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotifyBeneficiary $notifyBeneficiary,
        private readonly Settings $settings,
    ) {}

    public function handle(AidApproved|AidReadyForCollection|AidDelivered $event): void
    {
        // When the "combined delivery message" toggle is on, the delivery
        // notice is folded into the single confirmation-link message sent by
        // SendConfirmationLink, so suppress the standalone delivery
        // notification here to avoid double-messaging the beneficiary.
        if ($event instanceof AidDelivered && $this->settings->get('combined_delivery_message') === '1') {
            return;
        }

        $notificationEvent = match ($event::class) {
            AidApproved::class => NotificationEvent::AidApproved,
            AidReadyForCollection::class => NotificationEvent::AidReady,
            AidDelivered::class => NotificationEvent::AidDelivered,
        };

        $this->notifyBeneficiary->send($event->aid, $notificationEvent);
    }
}
