<?php

return [
    'inbox_title' => 'Approvals Inbox',
    'inbox_subtitle' => 'Aids awaiting your decision',

    'empty_title' => 'No Aids Awaiting Approval',
    'empty_description' => 'All aids are processed or none are awaiting your decision at this time',

    'filter_program' => 'Filter by Program',

    'field_waiting_since' => 'Waiting Since',

    'review_button' => 'Review & Decide',

    'action' => [
        'approve' => 'Approve',
        'reject' => 'Reject',
        'return' => 'Return for Revision',
    ],

    'messages' => [
        'approved' => 'Aid approved successfully',
        'rejected' => 'Aid rejected successfully',
        'returned' => 'Aid returned for revision successfully',
    ],

    'flows' => [
        'messages' => [
            'at_least_one_stage' => 'An approval flow must have at least one stage',
            'saved' => 'Approval flow saved successfully',
            'must_be_active' => 'The approval flow must be active before setting it as default',
            'default_set' => 'Approval flow set as default successfully',
            'cannot_delete_default' => 'Cannot delete the default approval flow',
            'cannot_delete_in_use' => 'Cannot delete an approval flow that is in use',
            'deleted' => 'Approval flow deleted successfully',
        ],
    ],
];
