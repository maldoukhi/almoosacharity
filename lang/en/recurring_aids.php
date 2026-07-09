<?php

return [
    'index_title' => 'Recurring aids',
    'index_subtitle' => 'Manage recurring aid plans: pause, edit, delete, and view the generated series.',
    'nav_button' => 'Recurring aids',

    'section_title' => 'Recurring aids',
    'section_subtitle' => 'This beneficiary\'s recurring aid plans.',

    'empty_title' => 'No recurring plans',
    'empty_description' => 'No recurring aid plan has been created yet.',

    'col_beneficiary' => 'Beneficiary',
    'col_program' => 'Program',
    'col_frequency' => 'Frequency',
    'col_next_run' => 'Next run',
    'col_lead_days' => 'Lead days',
    'col_status' => 'Status',
    'col_generated' => 'Generated',
    'col_reference' => 'Reference',
    'col_amount' => 'Amount/Items',
    'col_created_at' => 'Created',

    'status_active' => 'Active',
    'status_paused' => 'Paused',

    'interval_every_months' => '{1} Every month|[2,*] Every :count months',
    'lead_days_value' => '{0} On the due date|{1} 1 day|[2,*] :count days',
    'generated_count' => '{0} None|{1} 1 aid|[2,*] :count aids',

    'action_pause' => 'Pause',
    'action_resume' => 'Resume',
    'action_edit' => 'Edit',
    'action_delete' => 'Delete',
    'action_view_series' => 'View series',

    'confirm_delete' => 'Delete this recurring plan? The aids it already generated will be kept.',
    'confirm_pause' => 'Pause future aid generation for this plan?',

    'edit_modal' => [
        'title' => 'Edit recurring plan',
        'subtitle' => 'Adjust the recurring aid generation schedule.',
        'save' => 'Save changes',
    ],

    'series_modal' => [
        'title' => 'Recurring aid series',
        'subtitle' => 'Aids generated from this plan.',
        'pause_button' => 'Pause plan',
        'empty' => 'No aid has been generated from this plan yet.',
        'add_button' => 'Add aid manually',
        'add_hint' => 'For special cases: add an extra aid to this series now, without waiting for the schedule.',
        'confirm_add' => 'Add a new aid to this series now? It will be created as a draft, off the regular schedule.',
    ],

    'col_title' => 'Title',
    'col_due' => 'Due date',

    'messages' => [
        'paused' => 'Recurring plan paused',
        'resumed' => 'Recurring plan resumed',
        'deleted' => 'Recurring plan deleted',
        'saved' => 'Changes saved successfully',
        'manual_added' => 'A new aid was added to the series',
    ],
];
