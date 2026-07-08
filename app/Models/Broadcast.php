<?php

namespace App\Models;

use App\Enums\BroadcastStatus;
use App\Enums\MessageChannel;
use Database\Factories\BroadcastFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A summary row for one bulk SMS/WhatsApp send from the beneficiaries
 * broadcast screen. Each individual recipient's send is a separate
 * {@see MessageLog} row linked back here via the polymorphic
 * `messageable` relation.
 */
#[Fillable(['channel', 'body', 'template_name', 'recipients_count', 'manual_numbers_count', 'sent_by', 'status'])]
class Broadcast extends Model
{
    /** @use HasFactory<BroadcastFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => MessageChannel::class,
            'status' => BroadcastStatus::class,
            'recipients_count' => 'integer',
            'manual_numbers_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * @return MorphMany<MessageLog, $this>
     */
    public function messageLogs(): MorphMany
    {
        return $this->morphMany(MessageLog::class, 'messageable');
    }
}
