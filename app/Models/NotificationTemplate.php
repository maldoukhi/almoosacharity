<?php

namespace App\Models;

use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Services\Notifications\NotifyBeneficiary;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A per-event, per-channel SMS/WhatsApp message body template, managed
 * from the notifications settings screen. {@see NotifyBeneficiary}
 * renders these with strtr() using {name}/{amount}/{program} placeholders.
 */
#[Fillable(['event', 'channel', 'body', 'is_active'])]
class NotificationTemplate extends Model
{
    use LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => NotificationEvent::class,
            'channel' => MessageChannel::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Activity log options: track all fillable attributes, only log actual
     * changes, and skip empty log entries.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->getFillable())
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * The single active template (if any) for a given event/channel pair.
     *
     * @param  Builder<NotificationTemplate>  $query
     * @return Builder<NotificationTemplate>
     */
    public function scopeActiveFor(Builder $query, NotificationEvent $event, MessageChannel $channel): Builder
    {
        return $query
            ->where('event', $event->value)
            ->where('channel', $channel->value)
            ->where('is_active', true);
    }
}
