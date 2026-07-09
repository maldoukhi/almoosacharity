<?php

return [
    // Default outbound message (SMS/WhatsApp). Must contain {link}.
    'default_body' => 'مرحبًا {short_name}، نحتاج ردك على طلب الإعانة الخاص بك. يرجى فتح الرابط وتقديم ردك: {link}',

    'greeting' => 'مرحبًا :name',
    'intro' => 'نحتاج ردك على طلب الإعانة الخاص بك في هذه المرحلة.',

    'field_stage' => 'المرحلة',
    'field_program' => 'البرنامج',
    'field_type' => 'نوع الإعانة',
    'field_items' => 'الأصناف',

    'note_label' => 'ملاحظتك',
    'note_placeholder' => 'اكتب ردك أو أي ملاحظة تود إضافتها…',

    'document_label' => 'إرفاق مستند (اختياري)',
    'document_hint' => 'يمكنك إرفاق ملف واحد (PDF أو JPG أو PNG، بحد أقصى 5 ميجابايت).',
    'uploading' => 'جارٍ رفع الملف…',

    'response_required' => 'يرجى كتابة ملاحظة أو إرفاق مستند قبل الإرسال.',
    'submit_button' => 'إرسال الرد',

    'success_title' => 'تم استلام ردك',
    'success_description' => 'شكرًا لك، تم تسجيل ردك بنجاح وسيتم استكمال معالجة طلبك.',

    'already_title' => 'تم استلام ردك مسبقًا',
    'already_description' => 'لقد قدمت ردك على هذه المرحلة من قبل، ولا حاجة لإجراء آخر.',

    'expired_title' => 'انتهت صلاحية الرابط',
    'expired_description' => 'انتهت صلاحية هذا الرابط. يرجى التواصل مع الجمعية للحصول على رابط جديد.',

    'not_found_title' => 'الرابط غير صالح',
    'not_found_description' => 'هذا الرابط غير صحيح أو لم يعد متاحًا.',
];
