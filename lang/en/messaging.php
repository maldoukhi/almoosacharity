<?php

return [
    'broadcast' => [
        'title' => 'Bulk Message',
        'subtitle' => 'Send an SMS or WhatsApp message to a group of beneficiaries at once.',
        'channel_label' => 'Channel',
        'template_label' => 'Saved Template',
        'template_placeholder' => 'No template',
        'notification_template_label' => 'Template: :event',
        'apply_template' => 'Apply Template',
        'body_label' => 'Message Body',
        'body_placeholder' => 'Write the message here... use {name} to insert the beneficiary\'s name',
        'hint_variable' => 'Use {name} to automatically insert the beneficiary\'s name',
        'save_as_template' => 'Save this text as a reusable template',
        'template_name_label' => 'Template Name',
        'preview_title' => 'Preview',
        'preview_empty' => 'The message preview will appear here',
        'preview_sample_name' => 'Name',
        'send_button' => 'Send to :count Beneficiaries',
        'confirm_send' => 'This message will be sent to :count beneficiaries. Continue?',
        'confirm_title' => 'Confirm broadcast',
        'confirm_subtitle' => 'Review the details below before sending.',
        'field_channel' => 'Channel',
        'confirm_recipients' => 'Recipients',
        'confirm_breakdown' => 'Breakdown',
        'confirm_message' => 'Message',
        'confirm_send_button' => 'Confirm send to :count',
        'excluded_no_mobile' => ':count beneficiaries matching the filters have no mobile number on file and will be excluded automatically.',
        'filters_title' => 'Beneficiary Filters',
        'select_all_filtered' => 'Select all matching results',
        'selected_count' => ':count selected',
        'empty_title' => 'No matching beneficiaries',
        'empty_description' => 'Try adjusting the filters to find beneficiaries.',
        'no_mobile' => 'No mobile',
        'sent' => 'Message sent to :count beneficiaries.',
        'error_no_recipients' => 'No eligible recipients (a mobile number is required).',
        'error_too_many_recipients' => 'A single broadcast cannot exceed :max recipients.',
        'error_throttled' => 'Too many broadcasts, please try again in :seconds seconds.',
        'manual_numbers_label' => 'Additional Numbers',
        'manual_numbers_placeholder' => "Enter one number per line, e.g.:\n0511111111\n0522222222",
        'manual_numbers_hint' => 'One number per line (or comma-separated). Accepted format: 05 followed by eight digits (e.g. 0511111111); +9665xxxxxxxx is also accepted and converted automatically.',
        'manual_numbers_valid_count' => ':count valid numbers',
        'manual_numbers_invalid_count' => ':count invalid numbers',
        'manual_numbers_invalid' => 'The following numbers are invalid: :numbers',
        'manual_emails_label' => 'Email Addresses',
        'manual_emails_placeholder' => "Enter one email per line, e.g.:\nahmad@example.com\nsara@example.com",
        'manual_emails_hint' => 'One email per line (or comma-separated). Beneficiaries have no email on file, so the email is sent only to the addresses entered here.',
        'manual_emails_valid_count' => ':count valid emails',
        'manual_emails_invalid_count' => ':count invalid emails',
        'recipients_breakdown' => ':beneficiaries beneficiaries + :manual additional recipients = :total recipients',
        'attachment_label' => 'Attachment (optional)',
        'attachment_hint' => 'The file is delivered with the WhatsApp message only. Accepted types: PDF, JPG or PNG, up to 8 MB.',
        'attachment_hint_email' => 'The file is attached to the email. Accepted types: PDF, JPG or PNG, up to 8 MB.',
        'attachment_choose' => 'Choose file',
        'attachment_remove' => 'Remove attachment',
        'attachment_uploading' => 'Uploading file...',
        'attachment_selected' => 'Attached file: :name',
    ],

    'quick_send' => [
        'button' => 'Send Message',
    ],

    /*
    |--------------------------------------------------------------------------
    | Neutral recipient name
    |--------------------------------------------------------------------------
    |
    | Substituted for {name} in a broadcast body when the recipient is a
    | freely-typed manual number rather than a beneficiary (there is no
    | name to use). See App\Jobs\Messaging\SendBroadcastMessages::sendManual().
    |
    */
    'default_recipient_name' => 'Dear valued recipient',

    /*
    |--------------------------------------------------------------------------
    | Default email subject
    |--------------------------------------------------------------------------
    |
    | Used for broadcast emails and for re-sending a failed email from the
    | messages report (the body carries the message; the subject is a fixed
    | branded line).
    |
    */
    'email_subject' => 'A message from Al-Moosa Charity',
];
