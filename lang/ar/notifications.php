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
        'section_taqnyat_description' => 'اسم المرسل ومفتاح API الخاص بحساب Taqnyat لإرسال الرسائل النصية',
        'field_sender_name' => 'اسم المرسل',
        'field_sender_name_hint' => 'يُترك فارغًا لاستخدام القيمة الافتراضية من إعدادات الخادم',
        'field_api_key' => 'مفتاح API',
        'field_api_key_placeholder' => 'أدخل مفتاح Taqnyat',
        'field_api_key_hint' => 'يُترك فارغًا عند الحفظ للإبقاء على المفتاح الحالي',

        'action_fetch_senders' => 'سحب الأسماء المتاحة',
        'senders_fetch_success' => 'تم سحب أسماء المرسل المتاحة بنجاح',
        'senders_fetch_failed' => 'تعذّر سحب أسماء المرسل: :message',
        'senders_fetch_empty' => 'لم يتم العثور على أسماء مرسل مقبولة في هذا الحساب',
        'field_sender_select_label' => 'اختيار من الأسماء المسحوبة',
        'field_sender_manual_option' => 'إدخال يدوي',
        'sender_status_accepted' => 'مقبول',
        'sender_status_pending' => 'قيد المراجعة',

        'action_clear' => 'مسح',
        'confirm_clear_secret' => 'هل تريد حذف هذا المفتاح المحفوظ؟ لن يعمل الإرسال عبر هذا المزود حتى إدخال مفتاح جديد.',
        'action_verify' => 'تحقق من الاتصال',
        'verify_success' => 'الاتصال ناجح',
        'verify_success_with_balance' => 'الاتصال ناجح — الرصيد: :balance',
        'verify_failed' => 'فشل الاتصال: :message',
        'taqnyat_verify_missing_config' => 'لم يتم إدخال مفتاح API بعد',
        'taqnyat_key_cleared' => 'تم حذف مفتاح Taqnyat المحفوظ',

        'section_okta_title' => 'إعدادات Okta Connect (واتساب)',
        'section_okta_description' => 'بيانات الاتصال بمزود واتساب (Okta Connect) وربط القناة',
        'field_okta_base_url' => 'رابط الخادم الأساسي (Base URL)',
        'field_okta_base_url_hint' => 'يُترك فارغًا لاستخدام القيمة من ملف .env',
        'field_okta_channel_id' => 'معرّف القناة (Channel ID)',
        'field_okta_channel_id_hint' => 'لا يُدخل يدويًا: يُملأ تلقائيًا بعد ربط واتساب عبر رمز QR بالأعلى (أو يؤخذ من ملف .env).',
        'okta_channel_linked' => 'مربوطة',
        'okta_channel_not_linked' => 'لم تُربط أي قناة بعد. اربط واتساب عبر رمز QR بالأعلى ليظهر معرّف القناة تلقائيًا.',
        'field_okta_token' => 'رمز الوصول (Token)',
        'field_okta_token_placeholder' => 'أدخل رمز الوصول',
        'field_okta_token_hint' => 'يُترك فارغًا عند الحفظ للإبقاء على الرمز الحالي',
        'okta_verify_missing_config' => 'أدخل رابط الخادم والرمز، واربط قناة واتساب عبر رمز QR للحصول على معرّف القناة أولًا',
        'okta_verify_status' => 'الحالة: :status',
        'okta_token_cleared' => 'تم حذف رمز Okta المحفوظ',

        'section_okta_qr_title' => 'ربط قناة واتساب عبر رمز QR',
        'qr_scanning_hint' => 'اضغط على "ربط واتساب" ثم امسح الرمز الظاهر عبر تطبيق واتساب على الجهاز المطلوب ربطه',
        'action_connect_whatsapp' => 'ربط واتساب عبر QR',
        'qr_status_pending' => 'بانتظار المسح',
        'qr_status_connected' => 'متصلة',
        'qr_status_disconnected' => 'منقطعة',
        'qr_status_failed' => 'فشل الربط',
        'qr_waiting' => 'يتم التحقق تلقائيًا كل 5 ثوانٍ حتى يكتمل الربط',
        'okta_qr_missing_credentials' => 'أدخل رابط الخادم والرمز أولًا لبدء الربط',
        'okta_qr_connected' => 'تم ربط قناة واتساب بنجاح',
        'okta_qr_failed' => 'تعذّر ربط القناة، حاول مرة أخرى',
        'qr_not_supported' => 'مزود واتساب الحالي لا يدعم الربط عبر رمز QR من داخل النظام؛ استخدم لوحة تحكم Okta Connect مباشرة لإتمام ربط القناة.',
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
    'confirmed' => [
        'body' => 'أكّد المستفيد :beneficiary استلام الإعانة :reference.',
    ],
];
