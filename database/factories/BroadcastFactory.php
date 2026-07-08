<?php

namespace Database\Factories;

use App\Enums\BroadcastStatus;
use App\Enums\MessageChannel;
use App\Models\Broadcast;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Broadcast>
 */
class BroadcastFactory extends Factory
{
    protected $model = Broadcast::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel' => MessageChannel::Sms,
            'body' => 'مرحبًا {name}، رسالة جماعية تجريبية.',
            'template_name' => null,
            'recipients_count' => 0,
            'manual_numbers_count' => 0,
            'sent_by' => User::factory(),
            'status' => BroadcastStatus::Queued,
        ];
    }
}
