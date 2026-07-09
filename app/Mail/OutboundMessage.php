<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A single outbound email carrying a plain-text body rendered from a
 * notification/broadcast template, with an optional file attachment. The
 * body is produced upstream (template placeholders already substituted)
 * so this Mailable only presents it — it never builds copy itself.
 */
class OutboundMessage extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{disk: string, path: string, name?: string}|null  $attachment
     *                                                                             An optional file (already stored on a disk) to attach.
     */
    public function __construct(
        public readonly string $subjectLine,
        public readonly string $bodyText,
        public readonly ?array $attachment = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.message',
            with: ['bodyText' => $this->bodyText],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->attachment === null) {
            return [];
        }

        $attachment = Attachment::fromStorageDisk(
            $this->attachment['disk'],
            $this->attachment['path'],
        );

        if (! empty($this->attachment['name'])) {
            $attachment = $attachment->as($this->attachment['name']);
        }

        return [$attachment];
    }
}
