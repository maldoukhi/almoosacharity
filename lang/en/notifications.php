<?php

return [
    'events' => [
        'aid_approved' => 'Aid approved',
        'aid_ready' => 'Aid ready for collection',
        'aid_delivered' => 'Aid delivered',
    ],

    'channels' => [
        'sms' => 'SMS',
        'whatsapp' => 'WhatsApp',
    ],

    'bell' => [
        'title' => 'Notifications',
        'empty' => 'No notifications',
        'mark_all_read' => 'Mark all as read',
        'view_all' => 'View all notifications',
    ],

    'index' => [
        'title' => 'Notifications',
        'subtitle' => 'All notifications addressed to you',
        'filter_all' => 'All',
        'filter_unread' => 'Unread',
        'empty_title' => 'No notifications',
        'empty_description' => 'Notifications addressed to you will appear here as soon as they arrive',
        'mark_all_read' => 'Mark all as read',
        'mark_read' => 'Mark as read',
        'unread_badge' => 'Unread',
    ],

    'awaiting_review' => [
        'title' => 'An aid is awaiting your review',
        'body' => 'Aid :reference for :beneficiary has reached the :stage stage',
    ],

    'settings' => [
        'title' => 'Notification settings',
        'subtitle' => 'Manage message templates, delivery channels, and the SMS sender name',
        'saved' => 'Notification settings saved successfully',

        'section_templates_title' => 'Message templates',
        'section_templates_description' => 'The text sent to a beneficiary for each aid event and delivery channel',

        'legend_title' => 'Available placeholders',
        'legend_name' => '{name} — beneficiary name',
        'legend_amount' => '{amount} — aid amount (cash aids only)',
        'legend_program' => '{program} — aid program name',

        'field_body' => 'Message body',
        'field_is_active' => 'Active',

        'section_channels_title' => 'Delivery channels',
        'section_channels_description' => 'Enable/disable sending over each channel for all notifications',
        'field_sms_enabled' => 'Enable SMS',
        'field_whatsapp_enabled' => 'Enable WhatsApp',

        'section_taqnyat_title' => 'Taqnyat settings',
        'section_taqnyat_description' => 'The sender name shown to the beneficiary in SMS messages (up to 11 characters)',
        'field_sender_name' => 'Sender name',
        'field_sender_name_hint' => 'Leave empty to use the server-configured default',
    ],

    'mail' => [
        'awaiting_review' => [
            'subject' => 'An aid is awaiting your review',
            'greeting' => 'Hello :name,',
            'line' => 'Aid #:reference for :beneficiary has reached the ":stage" stage and is awaiting your decision.',
            'action' => 'Review the aid',
            'footer' => 'Thank you, Al-Moosa Charity Association.',
        ],
    ],
    'confirmed' => [
        'body' => 'Beneficiary :beneficiary confirmed receipt of aid :reference.',
    ],
];
