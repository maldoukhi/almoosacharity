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
}
