<?php

namespace Database\Seeders;

use App\Enums\NotificationEvent;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Default copy for every notification event, keyed by channel. SMS
     * ships active by default; WhatsApp ships inactive until an Okta
     * Connect template is approved and turned on from settings. The staff
     * event (aid_awaiting_approval) ships its Email template active so
     * approvers are emailed on arrival, WhatsApp inactive.
     *
     * @var array<string, array<string, string>>
     */
    protected array $bodies = [
        'aid_approved' => [
            'sms' => 'عزيزي/عزيزتي {name}، تمت الموافقة على إعانتكم ({program}) من جمعية الموسى الخيرية. سيتم التواصل معكم قريبًا بخصوص التسليم.',
            'whatsapp' => 'عزيزي/عزيزتي {name}، تمت الموافقة على إعانتكم ({program}) من جمعية الموسى الخيرية. سيتم التواصل معكم قريبًا بخصوص التسليم.',
        ],
        'aid_ready' => [
            'sms' => 'عزيزي/عزيزتي {name}، إعانتكم ({program}) جاهزة للاستلام. للاستفسار يرجى التواصل مع الجمعية.',
            'whatsapp' => 'عزيزي/عزيزتي {name}، إعانتكم ({program}) جاهزة للاستلام. للاستفسار يرجى التواصل مع الجمعية.',
        ],
        'aid_delivered' => [
            'sms' => 'عزيزي/عزيزتي {name}، تم تسليم إعانتكم ({program}) بنجاح. نشكر لكم ثقتكم بجمعية الموسى الخيرية.',
            'whatsapp' => 'عزيزي/عزيزتي {name}، تم تسليم إعانتكم ({program}) بنجاح. نشكر لكم ثقتكم بجمعية الموسى الخيرية.',
        ],
        'aid_awaiting_approval' => [
            'email' => "مرحبًا،\n\nوصلت الإعانة رقم {reference} للمستفيد {beneficiary} ضمن برنامج {program} إلى مرحلة \"{stage}\" وهي بانتظار قراركم.\n\nلمراجعة الطلب واتخاذ القرار: {link}\n\nشكرًا لكم، جمعية الموسى الخيرية.",
            'whatsapp' => 'وصلت الإعانة رقم {reference} للمستفيد {beneficiary} ({program}) إلى مرحلة "{stage}" وهي بانتظار قراركم. للمراجعة: {link}',
        ],
    ];

    /**
     * Channels that ship active by default, per event value.
     *
     * @var array<string, array<int, string>>
     */
    protected array $activeChannels = [
        'aid_approved' => ['sms'],
        'aid_ready' => ['sms'],
        'aid_delivered' => ['sms'],
        'aid_awaiting_approval' => ['email'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (NotificationEvent::cases() as $event) {
            $copy = $this->bodies[$event->value] ?? [];
            $active = $this->activeChannels[$event->value] ?? [];

            foreach ($event->channels() as $channel) {
                NotificationTemplate::query()->updateOrCreate(
                    ['event' => $event->value, 'channel' => $channel->value],
                    [
                        'body' => $copy[$channel->value] ?? '',
                        'is_active' => in_array($channel->value, $active, true),
                    ],
                );
            }
        }

        Setting::query()->updateOrCreate(['key' => 'taqnyat_sender'], ['value' => '']);
        Setting::query()->updateOrCreate(['key' => 'sms_enabled'], ['value' => '1']);
        Setting::query()->updateOrCreate(['key' => 'whatsapp_enabled'], ['value' => '0']);
    }
}
