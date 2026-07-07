<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Only the custom messages needed by App\Rules are defined here; this
    | file is merged on top of the framework's own default validation.php,
    | so none of Laravel's built-in rule messages need to be repeated.
    |
    */

    'custom' => [
        'saudi_iban' => [
            'format' => 'The IBAN must start with SA followed by 22 digits.',
            'checksum' => 'The IBAN is invalid, please check the digits entered.',
        ],
        'saudi_national_id' => 'The national ID/iqama number must be 10 digits and start with 1 or 2.',
        'saudi_mobile' => 'The mobile number must start with 05 followed by 8 digits.',
    ],

];
