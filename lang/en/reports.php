<?php

return [

    // Reports index screen (cards linking to each report)
    'index' => [
        'title' => 'Reports',
        'subtitle' => 'Detailed reports, exportable to Excel and PDF',
        'card_aids_title' => 'Detailed Aids Report',
        'card_aids_description' => 'Every aid with its beneficiary, program, value and status',
        'card_beneficiaries_title' => 'Beneficiaries Report',
        'card_beneficiaries_description' => 'Beneficiary data with their aid count and last aid',
        'card_financial_title' => 'Financial Report',
        'card_financial_description' => 'Monthly/yearly totals per aid program',
        'card_surveys_title' => 'Survey Results',
        'card_surveys_description' => 'Summary of beneficiary satisfaction survey results',
        'card_messages_title' => 'Sent Messages Report',
        'card_messages_description' => 'Every SMS/WhatsApp message with recipient, status, source and sender',
        'coming_soon' => 'Coming soon',
        'open' => 'Open report',
    ],

    // Shared filter labels
    'filters' => [
        'from' => 'From date',
        'to' => 'To date',
        'status' => 'Status',
        'program' => 'Program',
        'type' => 'Type',
        'category' => 'Category',
        'city' => 'City',
        'delivery_method' => 'Delivery method',
        'delivery_method_hint' => 'Coming soon — pending delivery data integration',
    ],

    // Shared export/actions
    'actions' => [
        'export_excel' => 'Export Excel',
        'export_pdf' => 'Export PDF',
        'exporting' => 'Exporting...',
    ],

    // Dashboard-specific keys (owned exclusively by this phase's agent
    // alongside the rest of this file, per CLAUDE.md's phase-7 exception)
    'dashboard' => [
        'stat_approved_this_month' => 'Approved this month',
        'stat_pending_inbox' => 'Awaiting my action',
        'stat_pending_inbox_link' => 'Go to approvals inbox ←',
        'chart_by_status' => 'Aids by status',
        'chart_by_type' => 'Aids by type',
        'chart_by_month' => 'Aids by month',
        'chart_by_month_count' => 'Aid count',
        'chart_by_month_cash' => 'Cash total (SAR)',
    ],

    // PDF layout strings
    'pdf' => [
        'generated_at' => 'Generated: :date',
        'filters_applied' => 'Applied filters',
        'no_data' => 'No matching data',
    ],

    // Aids report
    'aids' => [
        'title' => 'Detailed Aids Report',
        'subtitle' => 'Every aid with its beneficiary, program, value and status',
        'column_reference' => 'Reference',
        'column_beneficiary' => 'Beneficiary',
        'column_program' => 'Program',
        'column_type' => 'Type',
        'column_status' => 'Status',
        'column_amount' => 'Amount (SAR)',
        'column_items_value' => 'Items value (SAR)',
        'column_submitted_at' => 'Submitted at',
        'column_decided_at' => 'Decided at',
        'total_count' => 'Count: :count',
        'total_cash' => 'Cash total: :amount SAR',
        'total_in_kind' => 'In-kind total: :amount SAR',
    ],

    // Beneficiaries report
    'beneficiaries' => [
        'title' => 'Beneficiaries Report',
        'subtitle' => 'Beneficiary data with their aid count and last aid',
        'column_full_name' => 'Full name',
        'column_national_id' => 'National ID',
        'column_mobile' => 'Mobile',
        'column_city' => 'City',
        'column_categories' => 'Categories',
        'column_status' => 'Status',
        'column_aids_count' => 'Aid count',
        'column_last_aid_at' => 'Last aid date',
        'total_count' => 'Total beneficiaries: :count',
    ],

    // Financial report
    'financial' => [
        'title' => 'Financial Report',
        'subtitle' => 'Monthly totals per program, by decision date',
        'column_program' => 'Program',
        'column_month' => 'Month',
        'column_count' => 'Aid count',
        'column_cash_total' => 'Cash total (SAR)',
        'column_in_kind_total' => 'In-kind total (SAR)',
        'column_grand_total' => 'Grand total (SAR)',
        'total_count' => 'Count: :count',
        'total_cash' => 'Cash total: :amount SAR',
        'total_in_kind' => 'In-kind total: :amount SAR',
        'total_grand' => 'Grand total: :amount SAR',
        'no_program' => 'No program',
    ],

    // Surveys report — stub until the surveys domain lands
    'surveys' => [
        'title' => 'Survey Results',
        'subtitle' => 'Summary of beneficiary satisfaction survey results',
        'column_survey' => 'Survey',
        'column_question' => 'Question',
        'column_response' => 'Response',
        'column_submitted_at' => 'Submitted at',
        'stub_notice' => 'This report will be available once surveys are connected',
        'coming_soon_badge' => 'Coming soon',
    ],

    // Messages report (sent SMS/WhatsApp log)
    'messages' => [
        'title' => 'Sent Messages Report',
        'subtitle' => 'Every SMS/WhatsApp message with recipient, status, source and sender',
        'column_date' => 'Date',
        'column_recipient' => 'Recipient',
        'column_name' => 'Name',
        'column_channel' => 'Channel',
        'column_status' => 'Status',
        'column_reason' => 'Failure reason',
        'view_reason' => 'Click to view the failure reason',
        'view_message' => 'Click to view the full message',
        'reason_provider_label' => 'Provider message',
        'reason_hint_label' => 'What does this mean?',
        // Friendly explanations for recognized provider error messages.
        'hint_invalid_credentials' => 'The provider rejected the request because the Taqnyat API key was not valid at the time this message was sent. This is a historical record of that moment and is not updated automatically. If "Verify connection" now succeeds, your current key is valid — just press "Resend" and it will go out with the correct key. If a brand-new message keeps failing with the same reason, open Notification settings → Connection settings and enter a valid API key from your Taqnyat dashboard.',
        'hint_insufficient_balance' => 'The Taqnyat account balance is not enough to send the message. Top up the account from your Taqnyat dashboard, then resend.',
        'hint_invalid_sender' => 'The sender name is not approved by Taqnyat. Make sure the sender name is approved on your account, then pick it from "Fetch available names" in the connection settings.',
        'hint_rate_limited' => 'The provider rate limit was exceeded. Wait a moment, then resend.',
        'hint_invalid_recipient' => 'The recipient mobile number is invalid or not accepted by the provider. Verify the number (international format 9665XXXXXXXX).',
        'message_title' => 'Message',
        'resend' => 'Resend',
        'confirm_resend' => 'Do you want to resend this message?',
        'resend_queued' => 'The message has been re-queued for sending.',
        'column_source' => 'Source',
        'column_sender' => 'Sent by',
        'column_excerpt' => 'Excerpt',
        'filter_channel' => 'Channel',
        'filter_status' => 'Status',
        'filter_source' => 'Source',
        'source_broadcast' => 'Bulk broadcast',
        'source_notification' => 'Automated notification',
        'system_sender' => 'System',
        'total_count' => 'Total: :count',
        'total_sent' => 'Sent: :count',
        'total_failed' => 'Failed: :count',
        'total_pending' => 'Pending: :count',
        'total_sms' => 'SMS: :count',
        'total_whatsapp' => 'WhatsApp: :count',
    ],

];
