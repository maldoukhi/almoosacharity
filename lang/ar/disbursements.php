<?php

return [
    'panel_title' => 'الصرف والتسليم',

    'select_method_hint' => 'اختر طريقة صرف الإعانة للمستفيد',
    'no_methods_title' => 'لا توجد طريقة صرف متاحة',
    'no_methods_description' => 'أضف رقم آيبان للمستفيد لتفعيل التحويل البنكي، أو اختر طريقة تسليم أخرى.',

    'field_method' => 'طريقة الصرف',
    'field_transfer_reference' => 'المرجع البنكي للتحويل',
    'field_receipt_number' => 'رقم إيصال الاستلام',
    'field_courier_name' => 'اسم مندوب التوصيل',
    'field_delivered_at' => 'تاريخ التسليم',
    'field_delivered_by' => 'سلَّمها',
    'field_notes' => 'ملاحظات',
    'field_proof' => 'إثبات الاستلام',
    'field_bank_account_masked' => 'الحساب البنكي',
    'field_confirmed_by' => 'دقَّقها',
    'field_confirmed_at' => 'تاريخ التدقيق',

    'delivered_at_hint' => 'اترك الحقل فارغًا لاستخدام تاريخ اليوم',

    'start_button' => 'بدء الصرف',
    'record_button' => 'تسجيل التسليم',
    'confirm_button' => 'تأكيد التدقيق',

    'confirm_start' => 'هل أنت متأكد من بدء صرف هذه الإعانة بالطريقة المحددة؟',
    'confirm_record' => 'هل أنت متأكد من تسجيل تسليم هذه الإعانة؟',

    'start_confirm' => [
        'title' => 'تأكيد بدء الصرف',
        'subtitle' => 'راجِع بيانات المُسلِّم والمستلم قبل بدء الصرف.',
        'deliverer' => 'المُسلِّم',
        'recipient' => 'المستلم',
        'signature' => 'التوقيع (اختياري)',
        'signature_hint' => 'وقّع بالإصبع أو الفأرة داخل الإطار، وسيُحفظ مع سجل الصرف.',
        'clear_signature' => 'مسح التوقيع',
    ],
    'confirm_confirm' => 'هل أنت متأكد من تأكيد تدقيق هذا الصرف؟',

    'proof_recommended_notice' => 'يُفضَّل إرفاق إثبات استلام (صورة أو ملف PDF) قبل تسجيل التسليم.',
    'proof_upload_hint' => 'اسحب الملف هنا أو اضغط للاختيار (PDF أو صورة، حتى 5 ميجابايت)',
    'proof_download' => 'تحميل الإثبات',
    'proof_download_pending' => 'رابط تحميل الإثبات غير متاح حاليًا',
    'no_proof' => 'لم يُرفق إثبات استلام',

    'audit_pending_badge' => 'بانتظار التدقيق',
    'audit_confirmed_badge' => 'مدقَّقة',
    'audit_confirmed_by' => 'دقَّقها :name بتاريخ :date',

    'empty_title' => 'لا يوجد صرف بعد',
    'empty_description' => 'يبدأ الصرف تلقائيًا بعد اعتماد الإعانة.',

    'method' => [
        'bank_transfer' => 'تحويل بنكي',
        'office_pickup' => 'استلام من المقر',
        'courier' => 'مندوب توصيل',
        'field_handover' => 'تسليم يدوي ميداني',
    ],

    'status' => [
        'pending' => 'قيد الصرف',
        'delivered' => 'مُسلَّمة',
    ],

    'messages' => [
        'started' => 'تم بدء صرف الإعانة بنجاح',
        'delivered' => 'تم تسجيل تسليم الإعانة بنجاح',
        'confirmed' => 'تم تأكيد تدقيق الصرف بنجاح',
    ],
];
