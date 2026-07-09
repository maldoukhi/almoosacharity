<?php

namespace App\Jobs\Messaging;

use App\Actions\Messaging\SendBroadcast;
use App\Enums\BroadcastStatus;
use App\Enums\MessageChannel;
use App\Models\Beneficiary;
use App\Models\Broadcast;
use App\Services\Messaging\Messenger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Sends one {@see Broadcast} to a fixed list of beneficiaries, chunked so
 * a single very large recipient list never loads/iterates in one go.
 *
 * Two ways this runs, both going through the exact same {@see handle()}:
 *  - Queued (via {@see self::dispatch()}) for large recipient lists, so
 *    the web request that triggered the broadcast never blocks on it.
 *  - Called directly (`app(self::class)->handle(...)` from
 *    {@see SendBroadcast}) for small lists, since
 *    the actual provider calls are already deferred to
 *    {@see SendSmsMessage}/{@see SendWhatsAppMessage}
 *    by {@see Messenger} — looping over a small list inline is cheap and
 *    lets the UI report "sent" immediately.
 *
 * Only ever constructed with beneficiary ids already filtered down to
 * "has a mobile number" by the caller, and manual numbers already
 * normalized, validated and de-duplicated against those beneficiaries'
 * numbers (see {@see SendBroadcast}).
 */
class SendBroadcastMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Never retried: a mid-way failure would otherwise re-run handle()
     * from the top and re-queue provider sends for recipients already
     * messaged (the SMS path carries no idempotency key), doubling cost.
     * Each per-recipient provider job ({@see SendSmsMessage}/
     * {@see SendWhatsAppMessage}) still retries on its own.
     */
    public int $tries = 1;

    /**
     * Beneficiaries/manual numbers are processed this many at a time so
     * memory stays bounded regardless of recipient list size.
     */
    private const CHUNK_SIZE = 200;

    /**
     * @param  array<int, int>  $beneficiaryIds
     * @param  array<int, string>  $manualNumbers  Freely-typed numbers (not
     *                                             tied to a beneficiary), already normalized/validated/de-duplicated.
     * @param  array{disk: string, path: string, type: string}|null  $attachment
     *                                                                            An optional file to deliver alongside the message. Only honoured
     *                                                                            on the WhatsApp channel (SMS carries no attachments); `type` is the
     *                                                                            WhatsApp media type (image|document) derived from the upload.
     */
    public function __construct(
        public readonly int $broadcastId,
        public readonly array $beneficiaryIds,
        public readonly string $channel,
        public readonly string $body,
        public readonly array $manualNumbers = [],
        public readonly ?array $attachment = null,
    ) {}

    public function handle(Messenger $messenger): void
    {
        $broadcast = Broadcast::findOrFail($this->broadcastId);
        $channel = MessageChannel::from($this->channel);

        // The attachment file is shared by every recipient, so its public URL
        // is resolved once here rather than per send.
        $mediaUrl = $channel === MessageChannel::WhatsApp ? $this->resolveMediaUrl() : null;

        Beneficiary::query()
            ->whereIn('id', $this->beneficiaryIds)
            ->select(['id', 'first_name', 'second_name', 'third_name', 'last_name', 'mobile'])
            ->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function ($chunk) use ($broadcast, $channel, $messenger, $mediaUrl): void {
                foreach ($chunk as $beneficiary) {
                    $this->sendOne($messenger, $broadcast, $beneficiary, $channel, $mediaUrl);
                }
            });

        foreach (array_chunk($this->manualNumbers, self::CHUNK_SIZE) as $chunk) {
            foreach ($chunk as $number) {
                $this->sendManual($messenger, $broadcast, $number, $channel, $mediaUrl);
            }
        }

        $broadcast->update(['status' => BroadcastStatus::Completed]);
    }

    private function sendOne(Messenger $messenger, Broadcast $broadcast, Beneficiary $beneficiary, MessageChannel $channel, ?string $mediaUrl): void
    {
        if (blank($beneficiary->mobile)) {
            return;
        }

        $body = strtr($this->body, ['{name}' => $beneficiary->full_name]);
        $idempotencyKey = "broadcast-{$broadcast->id}-beneficiary-{$beneficiary->id}";

        match ($channel) {
            MessageChannel::Sms => $messenger->sms($beneficiary->mobile, $body, related: $broadcast),
            MessageChannel::WhatsApp => $this->whatsapp($messenger, $broadcast, $beneficiary->mobile, $body, $mediaUrl, $idempotencyKey),
            // Beneficiaries carry no email on file, so an email broadcast
            // never targets them (only the manual address list) — no-op.
            MessageChannel::Email => null,
        };
    }

    /**
     * A manually-entered number has no beneficiary record, so {name} is
     * replaced with a neutral greeting (see messaging.default_recipient_name)
     * instead of a real name.
     */
    private function sendManual(Messenger $messenger, Broadcast $broadcast, string $number, MessageChannel $channel, ?string $mediaUrl): void
    {
        $body = strtr($this->body, ['{name}' => __('messaging.default_recipient_name')]);
        $idempotencyKey = "broadcast-{$broadcast->id}-manual-{$number}";

        match ($channel) {
            MessageChannel::Sms => $messenger->sms($number, $body, related: $broadcast),
            MessageChannel::WhatsApp => $this->whatsapp($messenger, $broadcast, $number, $body, $mediaUrl, $idempotencyKey),
            // On the email channel the "number" is an email address.
            MessageChannel::Email => $this->email($messenger, $broadcast, $number, $body),
        };
    }

    /**
     * Send one email recipient the body, with the optional broadcast file
     * attached to the email itself (not as a fetchable URL like WhatsApp).
     */
    private function email(Messenger $messenger, Broadcast $broadcast, string $to, string $body): void
    {
        $messenger->email(
            $to,
            __('messaging.email_subject'),
            $body,
            related: $broadcast,
            attachment: $this->emailAttachment(),
        );
    }

    /**
     * The stored broadcast file shaped for an email attachment (disk/path +
     * a display filename), or null when this broadcast carries no file.
     *
     * @return array{disk: string, path: string, name: string}|null
     */
    private function emailAttachment(): ?array
    {
        if ($this->attachment === null) {
            return null;
        }

        return [
            'disk' => $this->attachment['disk'],
            'path' => $this->attachment['path'],
            'name' => basename($this->attachment['path']),
        ];
    }

    /**
     * Route a single WhatsApp recipient through the media send (attachment
     * with the message as its caption) when this broadcast carries a file,
     * or a plain text send otherwise.
     */
    private function whatsapp(Messenger $messenger, Broadcast $broadcast, string $to, string $body, ?string $mediaUrl, string $idempotencyKey): void
    {
        if ($mediaUrl !== null && $this->attachment !== null) {
            $messenger->whatsappMedia(
                $to,
                $this->attachment['type'],
                $mediaUrl,
                caption: $body,
                related: $broadcast,
                idempotencyKey: $idempotencyKey,
            );

            return;
        }

        $messenger->whatsappText($to, $body, related: $broadcast, idempotencyKey: $idempotencyKey);
    }

    /**
     * Resolve a publicly-fetchable URL for the attachment so the WhatsApp
     * provider (Okta Connect) can download it. A temporary/signed URL is
     * preferred so the private file is only exposed for a bounded window;
     * production should use a disk (e.g. S3, or the `serve`-enabled local
     * disk) whose URLs are reachable from the provider's network.
     */
    private function resolveMediaUrl(): ?string
    {
        if ($this->attachment === null) {
            return null;
        }

        $disk = Storage::disk($this->attachment['disk']);

        try {
            return $disk->temporaryUrl($this->attachment['path'], now()->addDays(7));
        } catch (\Throwable) {
            try {
                return $disk->url($this->attachment['path']);
            } catch (\Throwable) {
                // Disks that expose neither temporary nor plain URLs still
                // yield a stable reference rather than dropping the send.
                return $this->attachment['path'];
            }
        }
    }
}
