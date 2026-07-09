<?php

return [
    'index_title' => 'الإعانات الدورية',
    'index_subtitle' => 'إدارة خطط الإعانات المتكررة: الإيقاف المؤقت، التعديل، الحذف، وعرض السلسلة المُولّدة.',
    'nav_button' => 'الإعانات الدورية',

    'section_title' => 'الإعانات الدورية',
    'section_subtitle' => 'خطط الإعانات المتكررة الخاصة بهذا المستفيد.',

    'empty_title' => 'لا توجد خطط دورية',
    'empty_description' => 'لم تُنشأ أي خطة إعانة متكررة بعد.',

    'col_beneficiary' => 'المستفيد',
    'col_program' => 'البرنامج',
    'col_frequency' => 'التكرار',
    'col_next_run' => 'موعد التوليد القادم',
    'col_lead_days' => 'أيام التهيئة',
    'col_status' => 'الحالة',
    'col_generated' => 'المُولّدة',
    'col_reference' => 'المرجع',
    'col_amount' => 'المبلغ/العناصر',
    'col_created_at' => 'تاريخ الإنشاء',

    'status_active' => 'مفعّلة',
    'status_paused' => 'موقوفة',

    'interval_every_months' => '{1} كل شهر|[2,*] كل :count أشهر',
    'lead_days_value' => '{0} في الموعد|{1} يوم واحد|[2,*] :count أيام',
    'generated_count' => '{0} لا شيء|{1} إعانة واحدة|[2,*] :count إعانات',

    'action_pause' => 'إيقاف مؤقت',
    'action_resume' => 'استئناف',
    'action_edit' => 'تعديل',
    'action_delete' => 'حذف',
    'action_view_series' => 'عرض السلسلة',

    'confirm_delete' => 'هل أنت متأكد من حذف هذه الخطة الدورية؟ لن تُحذف الإعانات المُولّدة منها.',
    'confirm_pause' => 'إيقاف توليد الإعانات المستقبلية لهذه الخطة؟',

    'edit_modal' => [
        'title' => 'تعديل الخطة الدورية',
        'subtitle' => 'عدّل جدولة توليد الإعانات المتكررة.',
        'save' => 'حفظ التعديلات',
    ],

    'series_modal' => [
        'title' => 'سلسلة الإعانات الدورية',
        'subtitle' => 'الإعانات المُولّدة من هذه الخطة.',
        'pause_button' => 'إيقاف الخطة',
        'empty' => 'لم تُولّد أي إعانة من هذه الخطة بعد.',
    ],

    'messages' => [
        'paused' => 'تم إيقاف الخطة الدورية مؤقتًا',
        'resumed' => 'تم استئناف الخطة الدورية',
        'deleted' => 'تم حذف الخطة الدورية',
        'saved' => 'تم حفظ التعديلات بنجاح',
    ],
];
