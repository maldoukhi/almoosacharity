<?php

return [
    'events' => [
        'aid_approved' => 'اعتماد الإعانة',
        'aid_ready' => 'جاهزية الإعانة للاستلام',
        'aid_delivered' => 'تسليم الإعانة',
    ],

    'channels' => [
        'sms' => 'رسالة نصية',
        'whatsapp' => 'واتساب',
    ],

    'bell' => [
        'title' => 'الإشعارات',
        'empty' => 'لا توجد إشعارات',
        'mark_all_read' => 'تحديد الكل مقروءًا',
        'view_all' => 'كل الإشعارات',
    ],

    'index' => [
        'title' => 'الإشعارات',
        'subtitle' => 'كل الإشعارات الموجهة إليك',
        'filter_all' => 'الكل',
        'filter_unread' => 'غير مقروءة',
        'empty_title' => 'لا توجد إشعارات',
        'empty_description' => 'ستظهر هنا الإشعارات الموجهة إليك فور وصولها',
        'mark_all_read' => 'تحديد الكل مقروءًا',
        'mark_read' => 'تحديد كمقروء',
        'unread_badge' => 'غير مقروءة',
    ],

    'awaiting_review' => [
        'title' => 'إعانة بانتظار مراجعتك',
        'body' => 'الإعانة :reference للمستفيد :beneficiary وصلت إلى مرحلة :stage',
    ],

    'settings' => [
        'title' => 'إعدادات الإشعارات',
        'subtitle' => 'إدارة قوالب الرسائل، قنوات الإرسال، واسم مرسل الرسائل النصية',
        'saved' => 'تم حفظ إعدادات الإشعارات بنجاح',

        'section_templates_title' => 'قوالب الرسائل',
        'section_templates_description' => 'نص كل رسالة يُرسل للمستفيد حسب حدث الإعانة وقناة الإرسال',

        'legend_title' => 'المتغيرات المتاحة',
        'legend_name' => '{name} — اسم المستفيد',
        'legend_amount' => '{amount} — مبلغ الإعانة (للإعانات النقدية)',
        'legend_program' => '{program} — اسم برنامج الإعانة',

        'field_body' => 'نص الرسالة',
        'field_is_active' => 'مفعّلة',

        'section_channels_title' => 'قنوات الإرسال',
        'section_channels_description' => 'تفعيل/تعطيل الإرسال عبر كل قناة لكل الإشعارات',
        'field_sms_enabled' => 'تفعيل الرسائل النصية (SMS)',
        'field_whatsapp_enabled' => 'تفعيل واتساب',

        'section_taqnyat_title' => 'إعدادات Taqnyat',
        'section_taqnyat_description' => 'اسم المرسل الذي يظهر للمستفيد في الرسائل النصية (حتى 11 حرفًا/رقمًا)',
        'field_sender_name' => 'اسم المرسل',
        'field_sender_name_hint' => 'يُترك فارغًا لاستخدام القيمة الافتراضية من إعدادات الخادم',
    ],

    'mail' => [
        'awaiting_review' => [
            'subject' => 'إعانة بانتظار مراجعتك',
            'greeting' => 'مرحبًا :name،',
            'line' => 'الإعانة رقم :reference للمستفيد :beneficiary وصلت إلى مرحلة ":stage" وهي بانتظار قرارك.',
            'action' => 'مراجعة الإعانة',
            'footer' => 'شكرًا لك، جمعية الموسى الخيرية.',
        ],
    ],
];
