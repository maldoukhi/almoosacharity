<?php

return [
    'panel_title' => 'Disbursement & Delivery',

    'select_method_hint' => 'Choose how this aid will be disbursed to the beneficiary',
    'no_methods_title' => 'No delivery method available',
    'no_methods_description' => 'Add an IBAN for the beneficiary to enable bank transfer, or choose another delivery method.',

    'field_method' => 'Disbursement Method',
    'field_transfer_reference' => 'Bank Transfer Reference',
    'field_receipt_number' => 'Receipt Number',
    'field_courier_name' => 'Courier Name',
    'field_delivered_at' => 'Delivered At',
    'field_delivered_by' => 'Delivered By',
    'field_notes' => 'Notes',
    'field_proof' => 'Proof of Delivery',
    'field_bank_account_masked' => 'Bank Account',
    'field_confirmed_by' => 'Confirmed By',
    'field_confirmed_at' => 'Confirmed At',

    'delivered_at_hint' => 'Leave empty to use today\'s date',

    'start_button' => 'Start Disbursement',
    'record_button' => 'Record Delivery',
    'confirm_button' => 'Confirm Review',

    'confirm_start' => 'Are you sure you want to start disbursing this aid using the selected method?',
    'confirm_record' => 'Are you sure you want to record this aid as delivered?',

    'start_confirm' => [
        'title' => 'Confirm start of disbursement',
        'subtitle' => 'Review the deliverer and recipient before starting.',
        'deliverer' => 'Deliverer',
        'recipient' => 'Recipient',
        'signature' => 'Signature (optional)',
        'signature_hint' => 'Sign inside the box with a finger or mouse; it is saved with the disbursement record.',
        'clear_signature' => 'Clear signature',
    ],
    'confirm_confirm' => 'Are you sure you want to confirm the review of this disbursement?',

    'proof_recommended_notice' => 'Attaching proof of delivery (a photo or PDF) is recommended before recording delivery.',
    'proof_upload_hint' => 'Drag a file here or click to choose (PDF or image, up to 5MB)',
    'proof_download' => 'Download Proof',
    'proof_download_pending' => 'The proof download link is not available yet',
    'no_proof' => 'No proof of delivery was attached',

    'audit_pending_badge' => 'Awaiting Review',
    'audit_confirmed_badge' => 'Reviewed',
    'audit_confirmed_by' => 'Reviewed by :name on :date',

    'empty_title' => 'No disbursement yet',
    'empty_description' => 'Disbursement starts automatically once the aid is approved.',

    'method' => [
        'bank_transfer' => 'Bank Transfer',
        'office_pickup' => 'Office Pickup',
        'courier' => 'Courier',
        'field_handover' => 'Field Handover',
    ],

    'status' => [
        'pending' => 'In Disbursement',
        'delivered' => 'Delivered',
    ],

    'messages' => [
        'started' => 'Disbursement started successfully',
        'delivered' => 'Delivery recorded successfully',
        'confirmed' => 'Disbursement review confirmed successfully',
    ],
];
