<?php

namespace App\Models;

use App\Enums\MessageChannel;
use Database\Factories\MessageTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A named, reusable free-text message body staff can save from the
 * beneficiaries broadcast screen ("save as template") and reapply later.
 * Optionally scoped to one channel (sms/whatsapp); a null channel means
 * the template shows up regardless of which channel is selected.
 */
#[Fillable(['name', 'channel', 'body', 'created_by'])]
class MessageTemplate extends Model
{
    /** @use HasFactory<MessageTemplateFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => MessageChannel::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->getFillable())
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Templates usable for the given channel: those scoped to it plus the
     * channel-agnostic ones.
     *
     * @param  Builder<MessageTemplate>  $query
     * @return Builder<MessageTemplate>
     */
    public function scopeForChannel(Builder $query, MessageChannel $channel): Builder
    {
        return $query->where(function (Builder $query) use ($channel): void {
            $query->whereNull('channel')->orWhere('channel', $channel->value);
        });
    }
}
