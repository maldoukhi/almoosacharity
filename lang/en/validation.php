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
        'aid' => [
            'type_program_mismatch' => 'The aid type is not compatible with the selected program type.',
            'no_items' => 'At least one item must be added for in-kind aids before submitting.',
            'amount_required' => 'An amount greater than zero must be set for cash aids before submitting.',
            'not_editable' => 'This aid can only be edited while it is in draft status.',
            'not_deletable' => 'This aid can only be deleted while it is in draft status.',
            'not_cancellable' => 'This aid cannot be cancelled in its current status.',
            'not_under_review' => 'This aid is not currently under review.',
            'no_active_flow' => 'There is no active approval flow with stages for this program.',
        ],
        'approval' => [
            'note_required' => 'A note is required when rejecting or returning.',
            'action_not_allowed' => 'This action is not allowed at this stage.',
        ],
        'approval_flow' => [
            'at_least_one_stage' => 'The approval flow must have at least one stage.',
            'stage_role_required' => 'Each stage must have a valid role.',
            'stage_assignee_required' => 'Each stage must have a role or at least one assigned person.',
            'stage_action_required' => 'Each stage must allow at least one action.',
            'stages_in_use' => 'This flow\'s stages cannot be edited while aids are currently under review within it.',
        ],
        'disbursement' => [
            'not_approved' => 'Disbursement can only be started for an approved aid.',
            'missing_bank_account' => 'Bank transfer cannot be selected because this beneficiary has no IBAN on file.',
            'not_in_disbursement' => 'Delivery can only be recorded for an aid that is in disbursement.',
            'not_delivered' => 'Review can only be confirmed after the aid has been delivered.',
            'already_confirmed' => 'This disbursement\'s review has already been confirmed.',
            'not_pending_update' => 'Disbursement details can only be edited before delivery is recorded.',
            'reference_required' => 'A reference/receipt number is required before recording delivery.',
            'courier_name_required' => 'A courier name is required for the courier delivery method.',
        ],
    ],

];
