<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Only the custom messages needed by App\Rules are defined here. This
    | file does not attempt to translate Laravel's built-in validation
    | rules (required, email, ...); it merges on top of the framework's
    | English defaults, which do not ship an Arabic translation.
    |
    */

    'custom' => [
        'saudi_iban' => [
            'format' => 'رقم الآيبان يجب أن يبدأ بـ SA ويتبعه 22 رقمًا.',
            'checksum' => 'رقم الآيبان غير صحيح، تحقق من الأرقام المدخلة.',
        ],
        'saudi_national_id' => 'رقم الهوية الوطنية/الإقامة يجب أن يتكوّن من 10 أرقام ويبدأ بـ 1 أو 2.',
        'saudi_mobile' => 'رقم الجوال يجب أن يبدأ بـ 05 ويتبعه 8 أرقام.',
        'aid' => [
            'type_program_mismatch' => 'نوع الإعانة غير متوافق مع نوع البرنامج المحدد.',
            'no_items' => 'يجب إضافة عنصر واحد على الأقل للإعانات العينية قبل التقديم.',
            'amount_required' => 'يجب تحديد مبلغ أكبر من صفر للإعانات النقدية قبل التقديم.',
            'not_editable' => 'لا يمكن تعديل هذه الإعانة إلا وهي في حالة المسودة.',
            'not_deletable' => 'لا يمكن حذف هذه الإعانة إلا وهي في حالة المسودة.',
            'not_cancellable' => 'لا يمكن إلغاء هذه الإعانة في حالتها الحالية.',
            'not_under_review' => 'هذه الإعانة ليست قيد المراجعة حاليًا.',
            'no_active_flow' => 'لا يوجد مسار موافقات نشط له مراحل لهذا البرنامج.',
        ],
        'approval' => [
            'note_required' => 'يجب إدخال ملاحظة عند الرفض أو الإرجاع.',
            'action_not_allowed' => 'هذا الإجراء غير مسموح به في هذه المرحلة.',
        ],
        'approval_flow' => [
            'at_least_one_stage' => 'يجب أن يحتوي مسار الموافقات على مرحلة واحدة على الأقل.',
            'stage_role_required' => 'يجب تحديد دور صحيح لكل مرحلة.',
            'stage_action_required' => 'يجب تحديد إجراء واحد على الأقل مسموح به لكل مرحلة.',
            'stages_in_use' => 'لا يمكن تعديل مراحل هذا المسار لوجود إعانات قيد المراجعة ضمنه حاليًا.',
        ],
    ],

];
