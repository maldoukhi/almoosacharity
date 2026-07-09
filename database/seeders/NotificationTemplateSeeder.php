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
     * This array is the single source of truth for the shipped default
     * bodies: the seeder writes them on a fresh install, and the settings
     * screen reads them back through {@see defaultBody()} so an empty
     * template row always shows real default content in the editor (the
     * same fall-back-to-a-shipped-default pattern the confirmation body
     * uses).
     *
     * @var array<string, array<string, string>>
     */
    protected static array $bodies = [
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
            'email' => "السلام عليكم ورحمة الله وبركاته،\n\nنفيدكم بوصول طلب إعانة جديد بانتظار قراركم ضمن سير الموافقات في نظام جمعية الموسى الخيرية:\n\n• رقم الإعانة: {reference}\n• المستفيد: {beneficiary}\n• البرنامج: {program}\n• المرحلة الحالية: {stage}\n\nنأمل مراجعة الطلب واتخاذ القرار المناسب من خلال الرابط التالي:\n{link}\n\nهذه رسالة آلية من نظام إدارة العطاءات، فلا حاجة للرد عليها.\nمع خالص الشكر والتقدير، جمعية الموسى الخيرية.",
            'whatsapp' => "وصل طلب إعانة بانتظار قراركم في نظام جمعية الموسى الخيرية.\nرقم الإعانة: {reference}\nالمستفيد: {beneficiary}\nالبرنامج: {program}\nالمرحلة: {stage}\nلمراجعة الطلب واتخاذ القرار: {link}",
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
     * The shipped default body for a given event/channel, or an empty
     * string when none is defined. Used both by this seeder and by the
     * notifications settings screen (so an empty template row falls back to
     * this default in the editor rather than showing a blank textarea).
     */
    public static function defaultBody(string $event, string $channel): string
    {
        return static::$bodies[$event][$channel] ?? '';
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (NotificationEvent::cases() as $event) {
            $active = $this->activeChannels[$event->value] ?? [];

            foreach ($event->channels() as $channel) {
                NotificationTemplate::query()->updateOrCreate(
                    ['event' => $event->value, 'channel' => $channel->value],
                    [
                        'body' => static::defaultBody($event->value, $channel->value),
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
