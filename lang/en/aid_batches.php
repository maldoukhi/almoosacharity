<?php

return [
    'title' => 'Batch aid creation',
    'subtitle' => 'Raise cash or in-kind aids for a group of beneficiaries at once',
    'create_button' => 'Batch create',
    'submit' => 'Create aids',

    'step_select' => 'Select beneficiaries',
    'step_details' => 'Aid details',
    'step_beneficiaries' => 'Selected beneficiaries',

    'categories' => 'Beneficiary categories',
    'no_categories' => 'No active categories',
    'program' => 'Program',
    'program_hint' => 'The program is applied to every aid created in this batch',

    'excel_upload' => 'Or upload an Excel of national ids',
    'excel_hint' => 'National ids are matched to active beneficiaries and added to the selection',
    'excel_reading' => 'Reading file...',
    'excel_no_ids' => 'No valid national ids were found in the file',
    'excel_matched' => 'Matched :matched beneficiaries, :unmatched national ids unmatched',
    'unmatched_title' => 'National ids that matched no active beneficiary (:count)',

    'mode' => 'Aid type',
    'mode_cash' => 'Cash',
    'mode_in_kind' => 'In-kind',
    'mode_both' => 'Cash and in-kind',

    'note' => 'Batch note',
    'note_placeholder' => 'Optional note added to every aid',

    'default_amount' => 'Default amount',
    'default_amount_hint' => 'Can be overridden per beneficiary in the table below',
    'default_purpose' => 'Purpose',

    'default_items' => 'Default items',
    'add_item' => 'Add item',

    'amount_override' => 'Custom amount',
    'default' => 'Default',

    'empty_title' => 'No beneficiaries',
    'empty_description' => 'Pick a category or upload a national-id file to list beneficiaries',

    'selection_capped' => 'A single batch can target at most :count beneficiaries',

    'summary_title' => 'Done',
    'summary_body' => 'Created :cash cash and :in_kind in-kind aids for :beneficiaries beneficiaries.',
    'created' => 'Aids created for :beneficiaries beneficiaries',
    'no_eligible_selected' => 'None of the selected beneficiaries are eligible (suspended/deactivated/rejected are excluded).',
];
