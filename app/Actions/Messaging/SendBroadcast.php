<?php

namespace App\Actions\Messaging;

use App\Enums\BroadcastStatus;
use App\Enums\MessageChannel;
use App\Jobs\Messaging\SendBroadcastMessages;
use App\Models\Beneficiary;
use App\Models\Broadcast;
use App\Models\User;
use App\Services\Messaging\Messenger;
use App\Support\MobileNumber;
use Illuminate\Support\Facades\Gate;

/**
 * Creates a {@see Broadcast} row for a bulk SMS/WhatsApp send and fans it
 * out to every eligible (has a mobile number) beneficiary in
 * $beneficiaryIds, plus any freely-typed $manualNumbers not tied to a
 * beneficiary. Beneficiaries without a mobile number are silently skipped
 * but still counted (recipients_count only reflects the eligible ones
 * sent to). Manual numbers that normalize to the same number as an
 * eligible beneficiary's mobile are dropped so nobody is messaged twice.
 */
class SendBroadcast
{
    /**
     * Hard ceiling on recipients per broadcast. Bounds provider cost and
     * blast radius of a mistaken/abusive send. Enforced here (source of
     * truth) as well as in the Livewire screen.
     */
    public const MAX_RECIPIENTS = 2000;

    /**
     * Above this many eligible recipients, the actual per-beneficiary
     * loop is queued (see {@see SendBroadcastMessages}) instead of run
     * inline, so a very large broadcast never blocks the web request.
     */
    private const INLINE_THRESHOLD = 200;

    public function __construct(private readonly Messenger $messenger) {}

    /**
     * @param  array<int, int>  $beneficiaryIds
     * @param  array<int, string>  $manualNumbers  Already normalized/valid
     *                                             numbers (see {@see \App\Livewire\Messaging\Broadcast::parsedManualNumbers()});
     *                                             any overlap with the selected beneficiaries' mobiles is removed here.
     */
    public function handle(array $beneficiaryIds, string $channel, string $body, ?string $templateName, User $actor, array $manualNumbers = []): Broadcast
    {
        Gate::forUser($actor)->authorize('messages.broadcast');

        $channelEnum = MessageChannel::from($channel);

        $eligibleIds = Beneficiary::query()
            ->whereIn('id', $beneficiaryIds)
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->pluck('id')
            ->all();

        $beneficiaryNumbers = Beneficiary::query()
            ->whereIn('id', $eligibleIds)
            ->pluck('mobile')
            ->map(fn (string $mobile): string => MobileNumber::normalize($mobile))
            ->all();

        $manualNumbers = collect($manualNumbers)
            ->map(fn (string $number): string => MobileNumber::normalize($number))
            ->unique()
            ->reject(fn (string $number): bool => in_array($number, $beneficiaryNumbers, true))
            ->values()
            ->all();

        $total = count($eligibleIds) + count($manualNumbers);

        if ($total > self::MAX_RECIPIENTS) {
            throw new \RuntimeException(__('messaging.broadcast.error_too_many_recipients', ['max' => self::MAX_RECIPIENTS]));
        }

        $broadcast = Broadcast::create([
            'channel' => $channelEnum,
            'body' => $body,
            'template_name' => $templateName,
            'recipients_count' => $total,
            'manual_numbers_count' => count($manualNumbers),
            'sent_by' => $actor->id,
            'status' => BroadcastStatus::Queued,
        ]);

        if ($total === 0) {
            $broadcast->update(['status' => BroadcastStatus::Completed]);

            return $broadcast;
        }

        if ($total > self::INLINE_THRESHOLD) {
            SendBroadcastMessages::dispatch($broadcast->id, $eligibleIds, $channelEnum->value, $body, $manualNumbers);

            return $broadcast;
        }

        // Small enough to run inline: the actual provider calls are
        // already deferred to queued jobs by Messenger, so this loop
        // itself is cheap and lets the caller report completion right away.
        app(SendBroadcastMessages::class, [
            'broadcastId' => $broadcast->id,
            'beneficiaryIds' => $eligibleIds,
            'channel' => $channelEnum->value,
            'body' => $body,
            'manualNumbers' => $manualNumbers,
        ])->handle($this->messenger);

        return $broadcast->fresh();
    }
}
