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
        'section_taqnyat_description' => 'The sender name and API key for the Taqnyat SMS account',
        'field_sender_name' => 'Sender name',
        'field_sender_name_hint' => 'Leave empty to use the server-configured default',
        'field_api_key' => 'API key',
        'field_api_key_placeholder' => 'Enter the Taqnyat API key',
        'field_api_key_hint' => 'Leave empty when saving to keep the current key',

        'action_fetch_senders' => 'Fetch available senders',
        'senders_fetch_success' => 'Available sender names fetched successfully',
        'senders_fetch_failed' => 'Could not fetch sender names: :message',
        'senders_fetch_empty' => 'No accepted sender names were found on this account',
        'field_sender_select_label' => 'Pick from the fetched names',
        'field_sender_manual_option' => 'Manual entry',
        'sender_status_accepted' => 'Accepted',
        'sender_status_pending' => 'Pending review',

        'action_clear' => 'Clear',
        'confirm_clear_secret' => 'Delete this saved key? Sending through this provider will stop working until a new key is entered.',
        'action_verify' => 'Verify connection',
        'verify_success' => 'Connection successful',
        'verify_success_with_balance' => 'Connection successful — balance: :balance',
        'verify_failed' => 'Connection failed: :message',
        'taqnyat_verify_missing_config' => 'No API key has been entered yet',
        'taqnyat_key_cleared' => 'The saved Taqnyat key was deleted',

        'section_okta_title' => 'Okta Connect settings (WhatsApp)',
        'section_okta_description' => 'Connection details for the WhatsApp provider (Okta Connect) and channel pairing',
        'field_okta_base_url' => 'Base URL',
        'field_okta_base_url_hint' => 'Leave empty to use the value from the .env file',
        'field_okta_channel_id' => 'Channel ID',
        'field_okta_channel_id_hint' => 'Leave empty to use the value from the .env file; filled in automatically after a successful QR pairing',
        'field_okta_token' => 'Access token',
        'field_okta_token_placeholder' => 'Enter the access token',
        'field_okta_token_hint' => 'Leave empty when saving to keep the current token',
        'okta_verify_missing_config' => 'Enter the base URL, token, and channel id first',
        'okta_verify_status' => 'Status: :status',
        'okta_token_cleared' => 'The saved Okta token was deleted',

        'section_okta_qr_title' => 'Pair a WhatsApp channel via QR code',
        'qr_scanning_hint' => 'Click "Connect WhatsApp" then scan the code that appears using the WhatsApp app on the device you want to pair',
        'action_connect_whatsapp' => 'Connect WhatsApp via QR',
        'qr_status_pending' => 'Waiting for scan',
        'qr_status_connected' => 'Connected',
        'qr_status_disconnected' => 'Disconnected',
        'qr_status_failed' => 'Pairing failed',
        'qr_waiting' => 'Checking automatically every 5 seconds until pairing completes',
        'okta_qr_missing_credentials' => 'Enter the base URL and token first to start pairing',
        'okta_qr_connected' => 'WhatsApp channel paired successfully',
        'okta_qr_failed' => 'Could not pair the channel, please try again',
        'qr_not_supported' => 'The current WhatsApp provider does not support pairing via QR code from within the system; use the Okta Connect dashboard directly to complete channel pairing.',
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
