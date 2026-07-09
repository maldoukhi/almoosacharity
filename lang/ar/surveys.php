<?php

return [
    'index_title' => 'الاستبيانات',
    'index_subtitle' => 'إدارة الاستبيانات المرتبطة بالبرامج أو العامة ونتائجها',
    'create_button' => 'استبيان جديد',
    'create_title' => 'إنشاء استبيان',
    'create_subtitle' => 'أنشئ استبيانًا جديدًا وأضف أسئلته',
    'edit_title' => 'تعديل الاستبيان',
    'edit_subtitle' => 'عدّل بيانات الاستبيان وأسئلته',
    'results_title' => 'نتائج الاستبيان',
    'results_subtitle' => 'ملخص إجابات المستفيدين على هذا الاستبيان',

    'field_title' => 'عنوان الاستبيان',
    'field_description' => 'الوصف',
    'field_scope' => 'النطاق',
    'field_program' => 'برنامج الإعانة',
    'field_is_active' => 'نشط',
    'field_is_required' => 'الاستبيان إجباري',
    'field_is_required_hint' => 'عند التفعيل، لا يمكن للمستفيد تخطي الاستبيان.',
    'field_starts_at' => 'تاريخ البدء (اختياري)',
    'field_ends_at' => 'تاريخ الانتهاء (اختياري)',
    'field_questions_count' => 'عدد الأسئلة',
    'field_responses_count' => 'عدد الردود',
    'field_status' => 'الحالة',

    'select_placeholder' => 'اختر من القائمة',

    'status_active' => 'نشط',
    'status_inactive' => 'غير نشط',

    'confirm_delete' => 'هل أنت متأكد من رغبتك في حذف هذا الاستبيان؟',
    'confirm_toggle_activate' => 'هل تريد تفعيل هذا الاستبيان؟',
    'confirm_toggle_deactivate' => 'هل تريد إيقاف هذا الاستبيان؟',

    'empty_title' => 'لا توجد استبيانات',
    'empty_description' => 'ابدأ بإنشاء استبيان جديد لجمع آراء المستفيدين',
    'results_count' => '{0} لا نتائج|{1} نتيجة واحدة|[2,*] :count نتيجة',

    'question_type' => [
        'short_text' => 'نص قصير',
        'long_text' => 'نص طويل',
        'single_choice' => 'اختيار واحد',
        'multiple_choice' => 'اختيار متعدد',
        'rating' => 'تقييم بالنجوم',
        'yes_no' => 'نعم / لا',
    ],

    'scope' => [
        'general' => 'عام',
        'program' => 'خاص ببرنامج',
    ],

    'builder' => [
        'survey_details' => 'بيانات الاستبيان',
        'questions_title' => 'الأسئلة',
        'add_question_title' => 'إضافة سؤال',
        'field_label' => 'نص السؤال',
        'field_help_text' => 'نص مساعد (اختياري)',
        'field_required' => 'إلزامي',
        'field_options' => 'الخيارات',
        'field_option_value' => 'القيمة',
        'field_option_label' => 'نص الخيار',
        'add_option' => 'إضافة خيار',
        'remove_option' => 'حذف الخيار',
        'field_max_stars' => 'الحد الأقصى للنجوم',
        'move_up' => 'نقل لأعلى',
        'move_down' => 'نقل لأسفل',
        'remove_question' => 'حذف السؤال',
        'no_questions_yet' => 'لم تُضف أسئلة بعد. أضف سؤالًا من الأزرار أعلاه.',
        'min_options' => 'يجب إضافة خيارين على الأقل لهذا السؤال.',
        'save' => 'حفظ الاستبيان',
        'question_label_placeholder' => 'اكتب نص السؤال هنا...',
    ],

    'results' => [
        'total_responses' => 'إجمالي الردود',
        'no_responses_title' => 'لا توجد ردود بعد',
        'no_responses_description' => 'لم يستجب أي مستفيد لهذا الاستبيان حتى الآن',
        'average_rating' => 'متوسط التقييم',
        'yes_percentage' => 'نعم',
        'no_percentage' => 'لا',
        'latest_answers' => 'أحدث الإجابات',
        'export_excel' => 'تصدير Excel',
        'export_hint' => 'متاح مع التقارير',
        'responses_count' => '{0} بدون ردود|{1} رد واحد|[2,*] :count ردود',
        'answers_count' => '{0} بدون إجابات|{1} إجابة واحدة|[2,*] :count إجابات',
    ],

    'aid_detail' => [
        'title' => 'استبيان المستفيد',
        'subtitle' => 'إجابات المستفيد على استبيان «:survey»',
        'submitted_at' => 'أُرسل في :date',
        'no_answer' => 'لم تتم الإجابة على هذا السؤال',
        'yes' => 'نعم',
        'no' => 'لا',
        'empty_title' => 'لا توجد إجابات بعد',
        'empty_description' => 'لم يُكمل المستفيد أي استبيان مرتبط بهذه الإعانة حتى الآن',
    ],

    'messages' => [
        'saved' => 'تم حفظ الاستبيان بنجاح',
        'deleted' => 'تم حذف الاستبيان بنجاح',
        'toggled' => 'تم تحديث حالة الاستبيان بنجاح',
        'cannot_delete_has_responses' => 'لا يمكن حذف استبيان له ردود مسجّلة',
        'question_has_answers' => 'لا يمكن حذف السؤال ":label" لوجود إجابات مسجّلة عليه',
        'scope_requires_program' => 'يجب اختيار برنامج إعانة عند اختيار نطاق "خاص ببرنامج"',
    ],
];
