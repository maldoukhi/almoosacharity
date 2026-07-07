<?php

namespace Database\Factories;

use App\Enums\MessageChannel;
use App\Enums\MessageStatus;
use App\Models\MessageLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageLog>
 */
class MessageLogFactory extends Factory
{
    protected $model = MessageLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel' => MessageChannel::Sms,
            'provider' => 'fake',
            'recipient' => fake()->e164PhoneNumber(),
            'body' => fake()->sentence(),
            'template_name' => null,
            'status' => MessageStatus::Pending,
            'provider_message_id' => null,
            'error' => null,
            'attempts' => 0,
            'sent_at' => null,
        ];
    }
}
