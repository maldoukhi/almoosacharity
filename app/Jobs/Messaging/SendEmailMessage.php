<?php

namespace App\Jobs\Messaging;

use App\Enums\MessageStatus;
use App\Mail\OutboundMessage;
use App\Models\MessageLog;
use App\Services\Mail\ApplyMailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends one queued email through Laravel's configured mailer (config/mail,
 * MAIL_* in .env — never hardcoded) and keeps its {@see MessageLog} row in
 * sync with the outcome, mirroring the retry/backoff design of
 * {@see SendSmsMessage}/{@see SendWhatsAppMessage}.
 */
class SendEmailMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 600];

    /**
     * @param  array{disk: string, path: string, name?: string}|null  $attachment
     *                                                                             An optional already-stored file to attach to the email.
     */
    public function __construct(
        public readonly int $messageLogId,
        public readonly string $to,
        public readonly string $subject,
        public readonly string $body,
        public readonly ?array $attachment = null,
    ) {}

    public function handle(): void
    {
        $log = MessageLog::findOrFail($this->messageLogId);
        $log->increment('attempts');
        $log->refresh();

        // Apply the admin-managed SMTP settings (host/port/encryption/auth
        // + from address) over the .env mailer config before sending, then
        // purge any mailer the worker built from the previous config so the
        // new settings take effect on this send.
        app(ApplyMailSettings::class)->apply();
        app('mail.manager')->purge('smtp');

        try {
            Mail::to($this->to)->send(new OutboundMessage(
                subjectLine: $this->subject,
                bodyText: $this->body,
                attachment: $this->attachment,
            ));
        } catch (Throwable $e) {
            $log->update(['error' => $e->getMessage()]);

            if ($log->attempts >= $this->tries) {
                $log->update(['status' => MessageStatus::Failed]);

                return;
            }

            throw $e;
        }

        $log->update([
            'status' => MessageStatus::Sent,
            'error' => null,
            'sent_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $log = MessageLog::find($this->messageLogId);

        $log?->update([
            'status' => MessageStatus::Failed,
            'error' => $exception?->getMessage() ?? $log->error,
        ]);
    }
}
