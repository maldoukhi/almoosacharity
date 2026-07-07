<?php

namespace App\Livewire\Settings\Notifications;

use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Models\NotificationTemplate;
use App\Support\Settings;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Notifications settings screen: per-event/per-channel message
 * templates, the SMS/WhatsApp channel toggles, and the Taqnyat sender
 * name — all admin-editable at runtime (no secrets here, those stay in
 * .env).
 */
class Manage extends Component
{
    /** @var array<string, array<string, array{body: string, is_active: bool}>> */
    public array $templates = [];

    public ?string $senderName = null;

    public bool $smsEnabled = true;

    public bool $whatsappEnabled = false;

    public function mount(): void
    {
        Gate::authorize('notifications.settings.manage');

        foreach (NotificationEvent::cases() as $event) {
            foreach (MessageChannel::cases() as $channel) {
                $template = NotificationTemplate::query()
                    ->where('event', $event->value)
                    ->where('channel', $channel->value)
                    ->first();

                $this->templates[$event->value][$channel->value] = [
                    'body' => $template?->body ?? '',
                    'is_active' => $template?->is_active ?? false,
                ];
            }
        }

        $settings = app(Settings::class);

        $this->senderName = $settings->get('taqnyat_sender') ?: null;
        $this->smsEnabled = $settings->get('sms_enabled', '1') === '1';
        $this->whatsappEnabled = $settings->get('whatsapp_enabled', '0') === '1';
    }

    public function save(): void
    {
        Gate::authorize('notifications.settings.manage');

        $this->validate([
            'templates.*.*.body' => ['required', 'string', 'max:480'],
            'templates.*.*.is_active' => ['boolean'],
            'senderName' => ['nullable', 'string', 'max:11'],
            'smsEnabled' => ['boolean'],
            'whatsappEnabled' => ['boolean'],
        ]);

        foreach ($this->templates as $eventValue => $channels) {
            foreach ($channels as $channelValue => $data) {
                NotificationTemplate::query()->updateOrCreate(
                    ['event' => $eventValue, 'channel' => $channelValue],
                    ['body' => $data['body'], 'is_active' => (bool) ($data['is_active'] ?? false)],
                );
            }
        }

        $settings = app(Settings::class);

        $settings->set('taqnyat_sender', $this->senderName !== null ? trim($this->senderName) : null);
        $settings->set('sms_enabled', $this->smsEnabled ? '1' : '0');
        $settings->set('whatsapp_enabled', $this->whatsappEnabled ? '1' : '0');

        $this->dispatch('toast', type: 'success', message: __('notifications.settings.saved'));
    }

    public function render()
    {
        return view('livewire.settings.notifications.manage');
    }
}
