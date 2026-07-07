<?php

namespace App\Jobs\Messaging;

use App\Enums\MessageStatus;
use App\Models\MessageLog;
use App\Services\Messaging\Contracts\WhatsAppGatewayInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

/**
 * Sends one queued WhatsApp message (free text or an approved template)
 * through the bound {@see WhatsAppGatewayInterface} driver. See
 * {@see SendSmsMessage} for the retry/backoff design notes, which are
 * mirrored here.
 */
class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 600];

    /**
     * @param  array<string, mixed>  $variables
     */
    public function __construct(
        public readonly int $messageLogId,
        public readonly string $to,
        public readonly ?string $body = null,
        public readonly ?string $templateName = null,
        public readonly array $variables = [],
        public readonly string $language = 'ar',
        public readonly ?string $idempotencyKey = null,
    ) {}

    public function handle(WhatsAppGatewayInterface $gateway): void
    {
        $log = MessageLog::findOrFail($this->messageLogId);
        $log->increment('attempts');
        $log->refresh();

        $response = $this->templateName !== null
            ? $gateway->sendTemplate($this->to, $this->templateName, $this->variables, $this->language, $this->idempotencyKey)
            : $gateway->sendText($this->to, (string) $this->body, $this->idempotencyKey);

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

        if ($response->retryAfter !== null) {
            $this->release($response->retryAfter);

            return;
        }

        if ($log->attempts >= $this->tries) {
            $log->update(['status' => MessageStatus::Failed]);

            return;
        }

        throw new RuntimeException($response->error ?? 'WhatsApp send failed.');
    }

    public function failed(?\Throwable $exception): void
    {
        $log = MessageLog::find($this->messageLogId);

        $log?->update([
            'status' => MessageStatus::Failed,
            'error' => $exception?->getMessage() ?? $log->error,
        ]);
    }
}
