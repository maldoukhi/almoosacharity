<?php

return [
    // Default outbound SMS/WhatsApp body sent with the signed confirmation
    // link (admin-editable from the settings screen). Placeholders: {name},
    // {link}.
    'default_body' => 'مرحبًا {name}، نأمل تأكيد استلامك للإعانة عبر الرابط التالي: {link}',

    'errors' => [
        'requires_delivered' => 'لا يمكن إصدار رابط تأكيد الاستلام إلا بعد تسليم الإعانة فعليًا',
    ],

    // Public confirmation page (resources/views/livewire/public/confirm-receipt.blade.php).
    'page_title' => 'تأكيد استلام الإعانة',

    'greeting' => 'مرحبًا :name',
    'intro' => 'يسعدنا تأكيد استلامك للإعانة التالية من جمعية الموسى الخيرية',

    'field_program' => 'البرنامج',
    'field_type' => 'نوع الإعانة',
    'field_items' => 'الأصناف المشمولة',
    'field_delivered_at' => 'تاريخ التسليم',
    'field_delivery_method' => 'طريقة التسليم',

    'confirm_button' => 'أؤكد الاستلام',
    'confirming' => 'جارٍ التأكيد...',

    'success_title' => 'شكرًا لتأكيدك',
    'success_description' => 'تم تسجيل تأكيد استلامك للإعانة بنجاح.',
    'share_feedback_button' => 'شارك رأيك',
    'finish_button' => 'إنهاء',

    'already_title' => 'تم التأكيد مسبقًا',
    'already_description' => 'سبق أن أكّدتَ استلام هذه الإعانة، شكرًا لك.',

    'expired_title' => 'انتهت صلاحية الرابط',
    'expired_description' => 'انتهت صلاحية رابط تأكيد الاستلام. إذا لم تكن قد أكّدتَ استلام الإعانة بعد، يرجى التواصل مع الجمعية.',

    'done_title' => 'شكرًا لمشاركتك',
    'done_description' => 'نقدّر وقتك، نتمنى لك ولأسرتك كل خير.',

    'survey_intro' => 'رأيك يهمنا',
    'survey_intro_description' => 'أسئلة قصيرة اختيارية لتحسين خدماتنا لك مستقبلًا',
    'question_of' => 'سؤال :current من :total',
    'next_button' => 'التالي',
    'previous_button' => 'السابق',
    'submit_button' => 'إرسال',
    'skip_button' => 'تخطٍّ',

    'yes' => 'نعم',
    'no' => 'لا',

    'not_found_title' => 'الرابط غير صالح',
    'not_found_description' => 'تعذّر العثور على رابط تأكيد صالح. يرجى التأكد من الرابط أو التواصل مع الجمعية.',

    // Admin-facing tracking card on the aid detail screen
    // (resources/views/livewire/aids/show.blade.php).
    'tracking_title' => 'تأكيد الاستلام',
    'tracking_sent_at' => 'أُرسل الرابط',
    'tracking_opened_at' => 'فُتح الرابط',
    'tracking_confirmed_at' => 'أكَّد المستفيد الاستلام',
    'tracking_reminder_sent_at' => 'أُرسل تذكير',
    'tracking_not_sent' => 'لم يُرسل بعد',
    'tracking_not_opened' => 'لم يُفتح بعد',
    'tracking_not_confirmed' => 'لم يُؤكَّد بعد',
    'tracking_no_reminder' => 'لم يُرسل تذكير',
    'tracking_expired_badge' => 'منتهي الصلاحية',
    'tracking_confirmed_ip' => 'من عنوان :ip',
    'tracking_no_confirmation_yet' => 'لم يُصدَر رابط تأكيد استلام لهذه الإعانة بعد',

    'resend_button' => 'إعادة إرسال الرابط',
    'confirm_resend' => 'هل تريد إعادة إرسال رابط تأكيد الاستلام؟ سيُلغى الرابط السابق فور الإرسال.',

    'messages' => [
        'resent' => 'تم إعادة إرسال رابط التأكيد بنجاح',
    ],
];
