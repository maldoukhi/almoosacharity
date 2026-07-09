<?php

return [
    'inbox_title' => 'صندوق الموافقات',
    'inbox_subtitle' => 'الإعانات المنتظرة قرارك',

    'empty_title' => 'لا توجد إعانات بانتظار الموافقة',
    'empty_description' => 'جميع الإعانات معالجة أو لا توجد بانتظار قرارك حاليًا',

    'filter_program' => 'التصفية حسب البرنامج',

    'field_waiting_since' => 'بانتظر منذ',

    'overdue_badge' => 'متأخرة منذ :days يوم',

    'escalation' => [
        'bell' => 'إعانة :reference متأخرة في مرحلة :stage منذ :days يوم',
    ],

    'review_button' => 'المراجعة والقرار',

    'tab_pending' => 'بانتظار قراري',
    'tab_history' => 'الموافقات السابقة',

    'history_empty' => 'لا توجد قرارات سابقة',
    'history_empty_description' => 'لم تتخذ أي قرار على الإعانات بعد',

    'col_action' => 'القرار',
    'col_note' => 'الملاحظة',
    'col_decided_at' => 'تاريخ القرار',

    'action' => [
        'approve' => 'الموافقة',
        'reject' => 'الرفض',
        'return' => 'الإرجاع للتعديل',
    ],

    'messages' => [
        'approved' => 'تمت الموافقة على الإعانة بنجاح',
        'rejected' => 'تم رفض الإعانة بنجاح',
        'returned' => 'تم إرجاع الإعانة للتعديل بنجاح',
    ],

    'stage_type' => [
        'approval' => 'موافقة',
        'beneficiary_response' => 'رد المستفيد',
    ],

    'beneficiary_response' => [
        'awaiting_title' => 'بانتظار رد المستفيد',
        'awaiting_description' => 'أُرسل رابط خاص للمستفيد لتقديم رده في هذه المرحلة.',
        'sent_at' => 'أُرسل الرابط في :at',
        'not_sent' => 'لم يُرسل الرابط بعد.',
        'resend_button' => 'إعادة إرسال الرابط',
        'confirm_resend' => 'هل تريد إعادة إرسال رابط الرد للمستفيد؟ سيتوقف الرابط السابق عن العمل.',
        'link_resent' => 'تم إرسال رابط الرد للمستفيد بنجاح',
    ],

    'decision' => [
        'documents_label' => 'المستندات الداعمة',
        'documents_required' => 'يجب إرفاق مستند واحد على الأقل قبل اتخاذ القرار',
        'documents_hint_required' => 'مطلوب رفع مستند واحد أو أكثر (PDF أو JPG أو PNG، بحد أقصى 5 ميجابايت لكل ملف).',
        'documents_hint_optional' => 'يمكنك إرفاق مستندات داعمة اختياريًا (PDF أو JPG أو PNG، بحد أقصى 5 ميجابايت لكل ملف).',
        'typed_documents_hint' => 'أرفق كل مستند في خانته (PDF أو JPG أو PNG، بحد أقصى 5 ميجابايت لكل ملف). المستندات المعلَّمة بعلامة * إجبارية.',
        'document_slot_required' => 'مستند «:label» إجباري ويجب إرفاقه',
        'mandatory' => 'إجباري',
        'optional' => 'اختياري',
        'uploading' => 'جارٍ رفع الملفات…',
    ],

    'flows' => [
        'builder' => [
            'field_stage_type' => 'نوع المرحلة',
            'field_stage_type_hint' => 'طبيعة العمل المطلوب في هذه المرحلة.',
            'field_notify_channels' => 'قنوات الإشعار',
            'notify_channel' => [
                'in_app' => 'داخل النظام',
                'email' => 'البريد الإلكتروني',
                'whatsapp' => 'واتساب',
            ],
            'field_max_days' => 'المدة القصوى (أيام)',
            'field_max_days_hint' => 'اختياري: أقصى عدد أيام يبقى فيه الطلب في هذه المرحلة قبل اعتباره متأخرًا وتصعيده لأصحاب القرار يوميًا. اتركه فارغًا لتعطيل التصعيد.',
            'max_days_placeholder' => 'بدون حد',
            'field_documents_required' => 'طلب مستندات في هذه المرحلة',
            'required_documents_hint' => 'حدد أنواع المستندات المطلوبة من صاحب القرار في هذه المرحلة، وهل كل مستند إجباري أم اختياري.',
            'document_type_placeholder' => 'مثال: صورة إثبات التسليم',
            'document_required_toggle' => 'إجباري',
            'add_document_type' => 'إضافة نوع مستند',
        ],
        'messages' => [
            'at_least_one_stage' => 'يجب أن يحتوي مسار الموافقات على مرحلة واحدة على الأقل',
            'saved' => 'تم حفظ مسار الموافقات بنجاح',
            'must_be_active' => 'يجب تفعيل مسار الموافقات قبل تعيينه كافتراضي',
            'default_set' => 'تم تعيين مسار الموافقات كافتراضي بنجاح',
            'cannot_delete_default' => 'لا يمكن حذف مسار الموافقات الافتراضي',
            'cannot_delete_in_use' => 'لا يمكن حذف مسار موافقات قيد الاستخدام',
            'deleted' => 'تم حذف مسار الموافقات بنجاح',
        ],
    ],
];
