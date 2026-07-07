<?php

return [
    'index_title' => 'الإعانات',
    'index_subtitle' => 'عرض وإدارة جميع الإعانات',
    'create_title' => 'إنشاء إعانة جديدة',
    'create_subtitle' => 'أنشئ إعانة جديدة لمستفيد',
    'edit_title' => 'تعديل الإعانة',
    'edit_subtitle' => 'عدّل تفاصيل الإعانة',
    'create_button' => 'إعانة جديدة',

    'field_beneficiary' => 'المستفيد',
    'field_program' => 'البرنامج',
    'field_type' => 'نوع الإعانة',
    'field_amount' => 'المبلغ',
    'field_purpose' => 'الغرض',
    'field_items' => 'العناصر',
    'field_item_name' => 'اسم العنصر',
    'field_item_quantity' => 'الكمية',
    'field_item_estimated_value' => 'القيمة المقدرة',
    'field_item_description' => 'الوصف',
    'field_notes' => 'ملاحظات',
    'field_reference' => 'الرقم المرجعي',
    'field_status' => 'الحالة',
    'field_current_stage' => 'المرحلة الحالية',
    'field_created_at' => 'تاريخ الإنشاء',
    'field_created_by' => 'أنشأه',
    'field_decision_note' => 'ملاحظة القرار',
    'decision_note_hint' => 'أضف ملاحظة توضح السبب (مطلوب للرفض والإرجاع)',

    'select_placeholder' => 'اختر من القائمة',
    'search_placeholder' => 'ابحث بالمرجع أو اسم المستفيد...',

    'type_cash' => 'إعانة نقدية',
    'type_cash_hint' => 'دفع مبلغ محدد بالريال السعودي',
    'type_in_kind' => 'إعانة عينية',
    'type_in_kind_hint' => 'توفير سلع أو خدمات محددة',

    'currency_sar' => 'ر.س',

    'add_item' => 'إضافة عنصر',
    'no_items_yet' => 'لم تُضف عناصر بعد',

    'save_draft' => 'حفظ كمسودة',
    'save_and_submit' => 'حفظ وتقديم للموافقة',
    'submit_button' => 'تقديم للموافقة',

    'confirm_submit' => 'هل أنت متأكد من رغبتك في تقديم هذه الإعانة للموافقة؟',
    'confirm_cancel' => 'هل أنت متأكد من رغبتك في إلغاء هذه الإعانة؟',
    'confirm_delete' => 'هل أنت متأكد من رغبتك في حذف هذه الإعانة؟',
    'confirm_decision' => 'هل أنت متأكد من هذا القرار؟',

    'no_actions_available' => 'لا توجد إجراءات متاحة حاليًا',

    'returned_notice_title' => 'تم إرجاع هذه الإعانة للتعديل',

    'details_title' => 'التفاصيل',
    'progress_title' => 'التقدم',
    'decisions_title' => 'القرارات',
    'actions_title' => 'الإجراءات',

    'items_total' => 'المجموع',
    'items_count' => '{0} بدون عناصر|{1} عنصر واحد|[2,*] :count عناصر',

    'filter_status' => 'التصفية حسب الحالة',
    'filter_program' => 'التصفية حسب البرنامج',
    'filter_type' => 'التصفية حسب النوع',

    'empty_title' => 'لا توجد إعانات',
    'empty_description' => 'ابدأ بإنشاء إعانة جديدة للمستفيدين',

    'status' => [
        'draft' => 'مسودة',
        'submitted' => 'مُقدَّمة',
        'under_review' => 'قيد المراجعة',
        'approved' => 'معتمدة',
        'in_disbursement' => 'قيد الصرف',
        'delivered' => 'مُسلَّمة',
        'rejected' => 'مرفوضة',
        'cancelled' => 'ملغاة',
    ],

    'type' => [
        'cash' => 'نقدية',
        'in_kind' => 'عينية',
    ],

    'program_type' => [
        'cash' => 'نقدية فقط',
        'in_kind' => 'عينية فقط',
        'both' => 'نقدية وعينية',
    ],

    'messages' => [
        'submitted' => 'تم تقديم الإعانة بنجاح للموافقة',
        'saved' => 'تم حفظ الإعانة بنجاح',
        'deleted' => 'تم حذف الإعانة بنجاح',
        'cancelled' => 'تم إلغاء الإعانة بنجاح',
    ],

    'programs' => [
        'messages' => [
            'saved' => 'تم حفظ برنامج الإعانة بنجاح',
            'cannot_delete_in_use' => 'لا يمكن حذف برنامج إعانة قيد الاستخدام',
            'deleted' => 'تم حذف برنامج الإعانة بنجاح',
        ],
    ],
];
