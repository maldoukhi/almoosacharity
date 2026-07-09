<?php

return [

    // Reports index screen (cards linking to each report)
    'index' => [
        'title' => 'التقارير',
        'subtitle' => 'تقارير مفصّلة قابلة للتصدير Excel وPDF',
        'card_aids_title' => 'تقرير الإعانات المفصل',
        'card_aids_description' => 'كل إعانة مع المستفيد والبرنامج والقيمة والحالة',
        'card_beneficiaries_title' => 'تقرير المستفيدين',
        'card_beneficiaries_description' => 'بيانات المستفيدين مع عدد إعاناتهم وآخر إعانة',
        'card_financial_title' => 'التقرير المالي',
        'card_financial_description' => 'إجماليات شهرية/سنوية لكل برنامج إعانة',
        'card_surveys_title' => 'نتائج الاستبيانات',
        'card_surveys_description' => 'ملخص نتائج استبيانات رضا المستفيدين',
        'card_messages_title' => 'تقرير الرسائل المرسلة',
        'card_messages_description' => 'كل رسالة SMS/واتساب مع المستلم والحالة ومصدرها ومن أرسلها',
        'coming_soon' => 'قريبًا',
        'open' => 'فتح التقرير',
    ],

    // Shared filter labels
    'filters' => [
        'from' => 'من تاريخ',
        'to' => 'إلى تاريخ',
        'status' => 'الحالة',
        'program' => 'البرنامج',
        'type' => 'النوع',
        'category' => 'التصنيف',
        'city' => 'المدينة',
        'delivery_method' => 'طريقة التسليم',
        'delivery_method_hint' => 'قريبًا — بانتظار ربط بيانات التسليم',
    ],

    // Shared export/actions
    'actions' => [
        'export_excel' => 'تصدير Excel',
        'export_pdf' => 'تصدير PDF',
        'exporting' => 'جارٍ التصدير...',
    ],

    // Dashboard-specific keys (owned exclusively by this phase's agent
    // alongside the rest of this file, per CLAUDE.md's phase-7 exception)
    'dashboard' => [
        'stat_approved_this_month' => 'معتمدة هذا الشهر',
        'stat_pending_inbox' => 'بانتظار إجرائي',
        'stat_pending_inbox_link' => 'الانتقال إلى صندوق الموافقات ←',
        'chart_by_status' => 'الإعانات حسب الحالة',
        'chart_by_type' => 'الإعانات حسب النوع',
        'chart_by_month' => 'الإعانات حسب الشهر',
        'chart_by_month_count' => 'عدد الإعانات',
        'chart_by_month_cash' => 'إجمالي النقدي (ر.س)',
        'receipt_issues_title' => 'إعانات لم تُستلم بالكامل',
        'receipt_issues_empty' => 'لا توجد بلاغات باستلام جزئي أو عدم استلام.',
        'receipt_issues_view_all' => 'عرض كل الإعانات ←',
    ],

    // PDF layout strings
    'pdf' => [
        'generated_at' => 'تاريخ الإصدار: :date',
        'filters_applied' => 'الفلاتر المطبقة',
        'no_data' => 'لا توجد بيانات مطابقة',
    ],

    // Aids report
    'aids' => [
        'title' => 'تقرير الإعانات المفصل',
        'subtitle' => 'كل إعانة مع المستفيد والبرنامج والقيمة والحالة',
        'column_reference' => 'الرقم المرجعي',
        'column_beneficiary' => 'المستفيد',
        'column_program' => 'البرنامج',
        'column_type' => 'النوع',
        'column_status' => 'الحالة',
        'column_amount' => 'المبلغ (ر.س)',
        'column_items_value' => 'قيمة العناصر (ر.س)',
        'column_submitted_at' => 'تاريخ التقديم',
        'column_decided_at' => 'تاريخ القرار',
        'total_count' => 'العدد: :count',
        'total_cash' => 'إجمالي النقدي: :amount ر.س',
        'total_in_kind' => 'إجمالي العيني: :amount ر.س',
    ],

    // Beneficiaries report
    'beneficiaries' => [
        'title' => 'تقرير المستفيدين',
        'subtitle' => 'بيانات المستفيدين مع عدد إعاناتهم وآخر إعانة',
        'column_full_name' => 'الاسم الكامل',
        'column_national_id' => 'رقم الهوية',
        'column_mobile' => 'الجوال',
        'column_city' => 'المدينة',
        'column_categories' => 'التصنيفات',
        'column_status' => 'الحالة',
        'column_aids_count' => 'عدد الإعانات',
        'column_last_aid_at' => 'تاريخ آخر إعانة',
        'total_count' => 'إجمالي المستفيدين: :count',
    ],

    // Financial report
    'financial' => [
        'title' => 'التقرير المالي',
        'subtitle' => 'إجماليات شهرية لكل برنامج، حسب تاريخ القرار',
        'column_program' => 'البرنامج',
        'column_month' => 'الشهر',
        'column_count' => 'عدد الإعانات',
        'column_cash_total' => 'إجمالي النقدي (ر.س)',
        'column_in_kind_total' => 'إجمالي العيني (ر.س)',
        'column_grand_total' => 'الإجمالي (ر.س)',
        'total_count' => 'العدد: :count',
        'total_cash' => 'إجمالي النقدي: :amount ر.س',
        'total_in_kind' => 'إجمالي العيني: :amount ر.س',
        'total_grand' => 'الإجمالي الكلي: :amount ر.س',
        'no_program' => 'بدون برنامج',
    ],

    // Surveys report — stub until the surveys domain lands
    'surveys' => [
        'title' => 'نتائج الاستبيانات',
        'subtitle' => 'ملخص نتائج استبيانات رضا المستفيدين',
        'column_survey' => 'الاستبيان',
        'column_question' => 'السؤال',
        'column_response' => 'الإجابة',
        'column_submitted_at' => 'تاريخ الإرسال',
        'stub_notice' => 'هذا التقرير متاح بعد ربط الاستبيانات',
        'coming_soon_badge' => 'قريبًا',
    ],

    // Messages report (sent SMS/WhatsApp log)
    'messages' => [
        'title' => 'تقرير الرسائل المرسلة',
        'subtitle' => 'كل رسالة SMS/واتساب مع المستلم والحالة ومصدرها ومن أرسلها',
        'column_date' => 'التاريخ',
        'column_recipient' => 'المستلم',
        'column_name' => 'الاسم',
        'column_channel' => 'القناة',
        'column_status' => 'الحالة',
        'column_reason' => 'سبب الفشل',
        'view_reason' => 'اضغط لعرض سبب الفشل',
        'view_message' => 'اضغط لعرض نص الرسالة كاملًا',
        'reason_provider_label' => 'رسالة المزوّد',
        'reason_hint_label' => 'ماذا يعني ذلك؟',
        // Friendly explanations for recognized provider error messages.
        'hint_invalid_credentials' => 'رفض المزوّد الطلب لأن مفتاح Taqnyat API لم يكن صحيحًا وقت إرسال هذه الرسالة. هذا سجل تاريخي للحظة الإرسال ولا يُحدَّث تلقائيًا. إذا كان "تحقق من الاتصال" ناجحًا الآن فالمفتاح الحالي صحيح — يكفي الضغط على "إعادة الإرسال" وستُرسل بالمفتاح الصحيح. أما إن استمر فشل رسالة جديدة بالسبب نفسه، فافتح إعدادات الإشعارات ← إعدادات الاتصال وأدخل مفتاح API صحيحًا من لوحة تحكم Taqnyat.',
        'hint_insufficient_balance' => 'رصيد حساب Taqnyat لا يكفي لإرسال الرسالة. اشحن رصيد الحساب من لوحة تحكم Taqnyat ثم أعد الإرسال.',
        'hint_invalid_sender' => 'اسم المرسل غير معتمد لدى Taqnyat. تأكد من اعتماد اسم المرسل في حسابك، ثم اختره من "سحب الأسماء المتاحة" في إعدادات الاتصال.',
        'hint_rate_limited' => 'تم تجاوز حد عدد الرسائل المسموح في المزوّد. انتظر قليلًا ثم أعد الإرسال.',
        'hint_invalid_recipient' => 'رقم جوال المستلم غير صالح أو غير مقبول لدى المزوّد. تأكد من صحة الرقم (صيغة دولية 9665XXXXXXXX).',
        'message_title' => 'نص الرسالة',
        'resend' => 'إعادة الإرسال',
        'confirm_resend' => 'هل تريد إعادة إرسال هذه الرسالة؟',
        'resend_queued' => 'تمت إعادة جدولة الرسالة للإرسال.',
        'column_source' => 'المصدر',
        'column_sender' => 'من أرسلها',
        'column_excerpt' => 'مقتطف النص',
        'filter_channel' => 'القناة',
        'filter_status' => 'الحالة',
        'filter_source' => 'المصدر',
        'source_broadcast' => 'بث جماعي',
        'source_notification' => 'إشعار تلقائي',
        'system_sender' => 'النظام',
        'total_count' => 'الإجمالي: :count',
        'total_sent' => 'مُرسلة: :count',
        'total_failed' => 'فاشلة: :count',
        'total_pending' => 'قيد الإرسال: :count',
        'total_sms' => 'رسائل نصية: :count',
        'total_whatsapp' => 'واتساب: :count',
    ],

];
