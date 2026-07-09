<?php

return [
    'broadcast' => [
        'title' => 'رسالة جماعية',
        'subtitle' => 'أرسل رسالة نصية أو واتساب لمجموعة من المستفيدين دفعة واحدة.',
        'channel_label' => 'القناة',
        'template_label' => 'قالب جاهز',
        'template_placeholder' => 'بدون قالب',
        'notification_template_label' => 'قالب: :event',
        'apply_template' => 'تطبيق القالب',
        'body_label' => 'نص الرسالة',
        'body_placeholder' => 'اكتب نص الرسالة هنا... يمكنك استخدام {name} ليُستبدل باسم المستفيد',
        'hint_variable' => 'استخدم {name} لإدراج اسم المستفيد تلقائيًا',
        'save_as_template' => 'حفظ هذا النص كقالب لإعادة الاستخدام',
        'template_name_label' => 'اسم القالب',
        'preview_title' => 'معاينة',
        'preview_empty' => 'ستظهر معاينة الرسالة هنا',
        'preview_sample_name' => 'الاسم',
        'send_button' => 'إرسال إلى :count مستفيد',
        'confirm_send' => 'سيتم إرسال هذه الرسالة إلى :count مستفيد. هل تريد المتابعة؟',
        'confirm_title' => 'تأكيد إرسال الرسالة الجماعية',
        'confirm_subtitle' => 'راجِع التفاصيل التالية قبل الإرسال.',
        'field_channel' => 'القناة',
        'confirm_recipients' => 'عدد المستلمين',
        'confirm_breakdown' => 'التفصيل',
        'confirm_message' => 'نص الرسالة',
        'confirm_send_button' => 'تأكيد الإرسال إلى :count',
        'excluded_no_mobile' => ':count من المستفيدين المطابقين للفلاتر لا يملكون رقم جوال مسجّلًا وسيُستبعدون تلقائيًا.',
        'filters_title' => 'فلاتر المستفيدين',
        'select_all_filtered' => 'تحديد كل النتائج المطابقة للفلاتر',
        'selected_count' => ':count محدد',
        'empty_title' => 'لا يوجد مستفيدون مطابقون',
        'empty_description' => 'جرّب تعديل الفلاتر للعثور على مستفيدين.',
        'no_mobile' => 'بلا جوال',
        'sent' => 'تم إرسال الرسالة إلى :count مستفيد.',
        'error_no_recipients' => 'لا يوجد مستفيدون مؤهلون للإرسال (يجب أن يكون لديهم رقم جوال).',
        'error_too_many_recipients' => 'لا يمكن تجاوز :max مستلم في الرسالة الواحدة.',
        'error_throttled' => 'لقد أرسلت رسائل كثيرة، حاول بعد :seconds ثانية.',
        'manual_numbers_label' => 'أرقام إضافية',
        'manual_numbers_placeholder' => "أدخل رقمًا واحدًا في كل سطر، مثل:\n0511111111\n0522222222",
        'manual_numbers_hint' => 'رقم واحد لكل سطر (أو مفصول بفاصلة). الصيغة المقبولة: 05 متبوعًا بثمانية أرقام (مثال: 0511111111)، ويمكن كتابته أيضًا بصيغة +9665xxxxxxxx وسيُحوَّل تلقائيًا.',
        'manual_numbers_valid_count' => ':count رقم صالح',
        'manual_numbers_invalid_count' => ':count رقم غير صالح',
        'manual_numbers_invalid' => 'الأرقام التالية غير صحيحة: :numbers',
        'recipients_breakdown' => ':beneficiaries مستفيد + :manual رقم إضافي = :total مستلم',
        'attachment_label' => 'مرفق (اختياري)',
        'attachment_hint' => 'يُرسَل المرفق مع رسالة الواتساب فقط. الصيغ المقبولة: PDF أو JPG أو PNG، بحد أقصى 8 ميجابايت.',
        'attachment_choose' => 'اختيار ملف',
        'attachment_remove' => 'إزالة المرفق',
        'attachment_uploading' => 'جارٍ رفع الملف...',
        'attachment_selected' => 'الملف المرفق: :name',
    ],

    'quick_send' => [
        'button' => 'إرسال رسالة',
    ],

    /*
    |--------------------------------------------------------------------------
    | Neutral recipient name
    |--------------------------------------------------------------------------
    |
    | Substituted for {name} in a broadcast body when the recipient is a
    | freely-typed manual number rather than a beneficiary (there is no
    | name to use). See App\Jobs\Messaging\SendBroadcastMessages::sendManual().
    |
    */
    'default_recipient_name' => 'عزيزنا المكرم',
];
