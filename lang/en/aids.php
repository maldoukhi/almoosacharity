<?php

return [
    'index_title' => 'Aids',
    'index_subtitle' => 'View and manage all aids',
    'create_title' => 'Create New Aid',
    'create_subtitle' => 'Create a new aid for a beneficiary',
    'edit_title' => 'Edit Aid',
    'edit_subtitle' => 'Update aid details',
    'create_button' => 'New Aid',

    'field_beneficiary' => 'Beneficiary',
    'field_program' => 'Program',
    'field_type' => 'Aid Type',
    'field_amount' => 'Amount',
    'field_purpose' => 'Purpose',
    'field_items' => 'Items',
    'field_item_name' => 'Item Name',
    'field_item_quantity' => 'Quantity',
    'field_item_estimated_value' => 'Estimated Value',
    'field_item_description' => 'Description',
    'field_notes' => 'Notes',
    'field_reference' => 'Reference',
    'field_status' => 'Status',
    'field_current_stage' => 'Current Stage',
    'field_created_at' => 'Created At',
    'field_created_by' => 'Created By',
    'field_decision_note' => 'Decision Note',
    'decision_note_hint' => 'Add a note explaining the reason (required for rejection and return)',

    'select_placeholder' => 'Select from the list',
    'search_placeholder' => 'Search by reference or beneficiary name...',

    'type_cash' => 'Cash Aid',
    'type_cash_hint' => 'Provide a specific amount in Saudi Riyals',
    'type_in_kind' => 'In-Kind Aid',
    'type_in_kind_hint' => 'Provide specific goods or services',

    'currency_sar' => 'SAR',

    'add_item' => 'Add Item',
    'no_items_yet' => 'No items added yet',

    'save_draft' => 'Save as Draft',
    'save_and_submit' => 'Save and Submit for Approval',
    'submit_button' => 'Submit for Approval',

    'confirm_submit' => 'Are you sure you want to submit this aid for approval?',
    'confirm_cancel' => 'Are you sure you want to cancel this aid?',
    'confirm_delete' => 'Are you sure you want to delete this aid?',
    'confirm_decision' => 'Are you sure about this decision?',

    'no_actions_available' => 'No actions available at this time',

    'returned_notice_title' => 'This aid was returned for revision',

    'details_title' => 'Details',
    'progress_title' => 'Progress',
    'decisions_title' => 'Decisions',
    'actions_title' => 'Actions',

    'items_total' => 'Total',
    'items_count' => '{0} no items|{1} one item|[2,*] :count items',

    'filter_status' => 'Filter by Status',
    'filter_program' => 'Filter by Program',
    'filter_type' => 'Filter by Type',

    'empty_title' => 'No Aids',
    'empty_description' => 'Start by creating a new aid for your beneficiaries',

    'status' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'in_disbursement' => 'In Disbursement',
        'delivered' => 'Delivered',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ],

    'type' => [
        'cash' => 'Cash',
        'in_kind' => 'In-Kind',
    ],

    'program_type' => [
        'cash' => 'Cash Only',
        'in_kind' => 'In-Kind Only',
        'both' => 'Cash and In-Kind',
    ],

    'messages' => [
        'submitted' => 'Aid submitted successfully for approval',
        'saved' => 'Aid saved successfully',
        'deleted' => 'Aid deleted successfully',
        'cancelled' => 'Aid cancelled successfully',
    ],

    'programs' => [
        'messages' => [
            'saved' => 'Aid program saved successfully',
            'cannot_delete_in_use' => 'Cannot delete an aid program that is in use',
            'deleted' => 'Aid program deleted successfully',
        ],
    ],
];
