<?php

return [

    'title' => 'صحة النظام',
    'subtitle' => 'نظرة سريعة على قائمة الانتظار والرسائل والنسخ الاحتياطية ومساحة القرص',

    'unavailable' => 'غير متاح',

    'queue' => [
        'label' => 'قائمة الانتظار',
        'pending' => 'وظائف قيد الانتظار',
        'failed' => 'وظائف فاشلة',
        'unavailable_hint' => 'جداول قائمة الانتظار غير متاحة',
    ],

    'messages' => [
        'label' => 'الرسائل الفاشلة',
        'description' => 'خلال آخر 24 ساعة',
        'view_report' => 'عرض تقرير الرسائل',
    ],

    'recurring' => [
        'label' => 'آخر توليد للإعانات الدورية',
        'never_run' => 'لم يعمل بعد',
        'last_generated_at' => 'آخر توليد: :date',
        'overdue' => 'خطط متأخرة: :count',
        'no_overdue' => 'لا توجد خطط متأخرة',
    ],

    'backup' => [
        'label' => 'آخر نسخة احتياطية',
        'empty' => 'لا توجد نسخ بعد',
        'newest_at' => 'بتاريخ: :date',
        'size' => 'الحجم: :size',
        'stale_hint' => 'أقدم من 48 ساعة',
    ],

    'disk' => [
        'label' => 'مساحة القرص',
        'used_of_total' => ':used جيجابايت من :total جيجابايت',
        'used_percent' => ':percent% مستخدَم',
    ],

    'schedule' => [
        'label' => 'الجدولة',
        'description' => 'الأوقات المتوقعة للمهام المجدولة (معلوماتي)',
        'backup_row' => 'النسخ الاحتياطي',
        'backup_time' => 'يوميًا الساعة 2:00 صباحًا',
        'recurring_row' => 'توليد الإعانات الدورية',
        'reminders_row' => 'تذكيرات تأكيد الاستلام',
        'daily' => 'يوميًا',
    ],

];
