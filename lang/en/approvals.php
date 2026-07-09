<?php

return [
    'inbox_title' => 'Approvals Inbox',
    'inbox_subtitle' => 'Aids awaiting your decision',

    'empty_title' => 'No Aids Awaiting Approval',
    'empty_description' => 'All aids are processed or none are awaiting your decision at this time',

    'filter_program' => 'Filter by Program',

    'field_waiting_since' => 'Waiting Since',

    'review_button' => 'Review & Decide',

    'tab_pending' => 'Awaiting My Decision',
    'tab_history' => 'Previous Approvals',

    'history_empty' => 'No Previous Decisions',
    'history_empty_description' => 'You have not made any decisions on aids yet',

    'col_action' => 'Decision',
    'col_note' => 'Note',
    'col_decided_at' => 'Decided On',

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

    'stage_type' => [
        'approval' => 'Approval',
        'beneficiary_response' => 'Beneficiary Response',
    ],

    'beneficiary_response' => [
        'awaiting_title' => 'Awaiting the beneficiary',
        'awaiting_description' => 'A private link was sent to the beneficiary to submit their response at this stage.',
        'sent_at' => 'Link sent on :at',
        'not_sent' => 'The link has not been sent yet.',
        'resend_button' => 'Resend link',
        'confirm_resend' => 'Resend the response link to the beneficiary? The previous link will stop working.',
        'link_resent' => 'Response link sent to the beneficiary successfully',
    ],

    'decision' => [
        'documents_label' => 'Supporting Documents',
        'documents_required' => 'At least one document must be attached before the decision',
        'documents_hint_required' => 'One or more documents are required (PDF, JPG or PNG, up to 5 MB each).',
        'documents_hint_optional' => 'You may optionally attach supporting documents (PDF, JPG or PNG, up to 5 MB each).',
        'typed_documents_hint' => 'Attach each document in its own slot (PDF, JPG or PNG, up to 5 MB each). Documents marked with * are mandatory.',
        'document_slot_required' => 'The ":label" document is mandatory and must be attached',
        'mandatory' => 'Required',
        'optional' => 'Optional',
        'uploading' => 'Uploading files…',
    ],

    'flows' => [
        'builder' => [
            'field_stage_type' => 'Stage Type',
            'field_stage_type_hint' => 'The kind of work required at this stage.',
            'field_notify_channels' => 'Notification Channels',
            'notify_channel' => [
                'in_app' => 'In-app',
                'email' => 'Email',
                'whatsapp' => 'WhatsApp',
            ],
            'field_documents_required' => 'Require documents at this stage',
            'required_documents_hint' => 'Define the document types the decision-maker must provide at this stage, and whether each one is mandatory or optional.',
            'document_type_placeholder' => 'e.g. Proof of delivery photo',
            'document_required_toggle' => 'Mandatory',
            'add_document_type' => 'Add document type',
        ],
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
