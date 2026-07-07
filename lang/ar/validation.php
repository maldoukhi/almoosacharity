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
    ],

];
