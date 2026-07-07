<?php

namespace Database\Seeders;

use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Default SMS/WhatsApp copy for every aid lifecycle event. SMS ships
     * active by default; WhatsApp ships inactive until an Okta Connect
     * template is approved and the admin turns it on from settings.
     *
     * @var array<string, array{sms: string, whatsapp: string}>
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
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (NotificationEvent::cases() as $event) {
            $copy = $this->bodies[$event->value];

            NotificationTemplate::query()->updateOrCreate(
                ['event' => $event->value, 'channel' => MessageChannel::Sms->value],
                ['body' => $copy['sms'], 'is_active' => true],
            );

            NotificationTemplate::query()->updateOrCreate(
                ['event' => $event->value, 'channel' => MessageChannel::WhatsApp->value],
                ['body' => $copy['whatsapp'], 'is_active' => false],
            );
        }

        Setting::query()->updateOrCreate(['key' => 'taqnyat_sender'], ['value' => '']);
        Setting::query()->updateOrCreate(['key' => 'sms_enabled'], ['value' => '1']);
        Setting::query()->updateOrCreate(['key' => 'whatsapp_enabled'], ['value' => '0']);
    }
}
