<?php

namespace App\Jobs\Messaging;

use App\Enums\MessageStatus;
use App\Models\MessageLog;
use App\Services\Messaging\Contracts\SmsGatewayInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

/**
 * Sends one queued SMS through the bound {@see SmsGatewayInterface} driver
 * and keeps its {@see MessageLog} row in sync with the outcome.
 *
 * Retry/backoff is intentionally driven by two independent counters:
 *  - Laravel's own job attempt counter ($tries / $backoff), which retries
 *    the job automatically when handle() throws.
 *  - The MessageLog::$attempts column, which this job increments itself
 *    and uses to decide when to give up — this keeps the "exhausted all
 *    retries → mark failed" decision testable in isolation (by calling
 *    handle() directly) without depending on the queue worker's internal
 *    job/attempts bookkeeping.
 */
class SendSmsMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the queue worker will attempt this job.
     */
    public int $tries = 3;

    /**
     * Backoff (seconds) between automatic queue retries.
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 600];

    public function __construct(public readonly int $messageLogId) {}

    public function handle(SmsGatewayInterface $gateway): void
    {
        $log = MessageLog::findOrFail($this->messageLogId);
        $log->increment('attempts');
        $log->refresh();

        $response = $gateway->send($log->recipient, $log->body);

        if ($response->success) {
            $log->update([
                'status' => MessageStatus::Sent,
                'provider_message_id' => $response->providerMessageId,
                'error' => null,
                'sent_at' => now(),
            ]);

            return;
        }

        $log->update(['error' => $response->error]);

        // The gateway told us explicitly how long to wait (e.g. a 429):
        // release back onto the queue for that long instead of burning
        // through the retry budget.
        if ($response->retryAfter !== null) {
            $this->release($response->retryAfter);

            return;
        }

        if ($log->attempts >= $this->tries) {
            $log->update(['status' => MessageStatus::Failed]);

            return;
        }

        // Still have retries left: throw so the queue worker retries this
        // job after the next $backoff step.
        throw new RuntimeException($response->error ?? 'SMS send failed.');
    }

    /**
     * If the job exhausts all of Laravel's own queue attempts (edge case:
     * that budget runs out before our own MessageLog::$attempts counter
     * does, e.g. because of manual dispatch), make sure the log still
     * ends up marked as failed rather than stuck at "pending".
     */
    public function failed(?\Throwable $exception): void
    {
        $log = MessageLog::find($this->messageLogId);

        $log?->update([
            'status' => MessageStatus::Failed,
            'error' => $exception?->getMessage() ?? $log->error,
        ]);
    }
}
