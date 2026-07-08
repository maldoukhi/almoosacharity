<?php

namespace App\Models;

use App\Enums\MessageChannel;
use App\Enums\MessageStatus;
use Database\Factories\MessageLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A record of one outbound SMS/WhatsApp send attempt, kept for auditing
 * and for the queued job to update as the gateway responds.
 */
class MessageLog extends Model
{
    /** @use HasFactory<MessageLogFactory> */
    use HasFactory;

    protected $fillable = [
        'channel',
        'provider',
        'recipient',
        'body',
        'template_name',
        'status',
        'provider_message_id',
        'error',
        'attempts',
        'messageable_type',
        'messageable_id',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => MessageChannel::class,
            'status' => MessageStatus::class,
            'attempts' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * The model this message relates to (e.g. an Aid/beneficiary record),
     * wired up in later phases.
     */
    public function messageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * A human-friendly, actionable explanation for a recognized provider
     * error, or null when the raw {@see $error} is not one we can map. The
     * raw provider message is always kept and shown as-is; this only *adds*
     * context (what it means, how to fix it) when we recognize the pattern.
     *
     * Matching is done on a lowercased needle so it is resilient to the
     * provider's casing/wording drift around the key phrase.
     */
    public function errorHint(): ?string
    {
        if (blank($this->error)) {
            return null;
        }

        $needle = strtolower($this->error);

        return match (true) {
            str_contains($needle, 'credential'),
            str_contains($needle, 'unauthorized'),
            str_contains($needle, 'invalid token'),
            str_contains($needle, 'authentication') => __('reports.messages.hint_invalid_credentials'),

            str_contains($needle, 'balance'),
            str_contains($needle, 'insufficient'),
            str_contains($needle, 'not enough') => __('reports.messages.hint_insufficient_balance'),

            str_contains($needle, 'sender') => __('reports.messages.hint_invalid_sender'),

            str_contains($needle, 'rate limit'),
            str_contains($needle, 'too many') => __('reports.messages.hint_rate_limited'),

            str_contains($needle, 'recipient'),
            str_contains($needle, 'mobile'),
            str_contains($needle, 'number') => __('reports.messages.hint_invalid_recipient'),

            default => null,
        };
    }
}
