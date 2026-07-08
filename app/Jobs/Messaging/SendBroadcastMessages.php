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
     */
    public function __construct(
        public readonly int $broadcastId,
        public readonly array $beneficiaryIds,
        public readonly string $channel,
        public readonly string $body,
        public readonly array $manualNumbers = [],
    ) {}

    public function handle(Messenger $messenger): void
    {
        $broadcast = Broadcast::findOrFail($this->broadcastId);
        $channel = MessageChannel::from($this->channel);

        Beneficiary::query()
            ->whereIn('id', $this->beneficiaryIds)
            ->select(['id', 'first_name', 'second_name', 'third_name', 'last_name', 'mobile'])
            ->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function ($chunk) use ($broadcast, $channel, $messenger): void {
                foreach ($chunk as $beneficiary) {
                    $this->sendOne($messenger, $broadcast, $beneficiary, $channel);
                }
            });

        foreach (array_chunk($this->manualNumbers, self::CHUNK_SIZE) as $chunk) {
            foreach ($chunk as $number) {
                $this->sendManual($messenger, $broadcast, $number, $channel);
            }
        }

        $broadcast->update(['status' => BroadcastStatus::Completed]);
    }

    private function sendOne(Messenger $messenger, Broadcast $broadcast, Beneficiary $beneficiary, MessageChannel $channel): void
    {
        if (blank($beneficiary->mobile)) {
            return;
        }

        $body = strtr($this->body, ['{name}' => $beneficiary->full_name]);

        match ($channel) {
            MessageChannel::Sms => $messenger->sms($beneficiary->mobile, $body, related: $broadcast),
            MessageChannel::WhatsApp => $messenger->whatsappText(
                $beneficiary->mobile,
                $body,
                related: $broadcast,
                idempotencyKey: "broadcast-{$broadcast->id}-beneficiary-{$beneficiary->id}",
            ),
        };
    }

    /**
     * A manually-entered number has no beneficiary record, so {name} is
     * replaced with a neutral greeting (see messaging.default_recipient_name)
     * instead of a real name.
     */
    private function sendManual(Messenger $messenger, Broadcast $broadcast, string $number, MessageChannel $channel): void
    {
        $body = strtr($this->body, ['{name}' => __('messaging.default_recipient_name')]);

        match ($channel) {
            MessageChannel::Sms => $messenger->sms($number, $body, related: $broadcast),
            MessageChannel::WhatsApp => $messenger->whatsappText(
                $number,
                $body,
                related: $broadcast,
                idempotencyKey: "broadcast-{$broadcast->id}-manual-{$number}",
            ),
        };
    }
}
