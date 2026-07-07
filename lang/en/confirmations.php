<?php

return [
    // Outbound SMS/WhatsApp body sent with the signed confirmation link.
    'sms_body' => 'Hello :name, please confirm you received your aid via this link: :link',

    'errors' => [
        'requires_delivered' => 'A confirmation link can only be issued once the aid has actually been delivered',
    ],

    // Public confirmation page (resources/views/livewire/public/confirm-receipt.blade.php).
    'page_title' => 'Confirm Aid Receipt',

    'greeting' => 'Hello :name',
    'intro' => 'We would like you to confirm you received the following aid from Al-Moosa Charity',

    'field_program' => 'Program',
    'field_type' => 'Aid Type',
    'field_items' => 'Items Included',
    'field_delivered_at' => 'Delivery Date',
    'field_delivery_method' => 'Delivery Method',

    'confirm_button' => 'Confirm Receipt',
    'confirming' => 'Confirming...',

    'success_title' => 'Thank You',
    'success_description' => 'Your confirmation has been recorded successfully.',
    'share_feedback_button' => 'Share Your Feedback',
    'finish_button' => 'Finish',

    'already_title' => 'Already Confirmed',
    'already_description' => 'You already confirmed receipt of this aid. Thank you.',

    'expired_title' => 'Link Expired',
    'expired_description' => 'This confirmation link has expired. If you have not confirmed receipt of the aid yet, please contact the charity.',

    'done_title' => 'Thank You For Sharing',
    'done_description' => 'We appreciate your time. We wish you and your family all the best.',

    'survey_intro' => 'We Value Your Feedback',
    'survey_intro_description' => 'A few optional short questions to help us serve you better',
    'question_of' => 'Question :current of :total',
    'next_button' => 'Next',
    'previous_button' => 'Previous',
    'submit_button' => 'Submit',
    'skip_button' => 'Skip',

    'yes' => 'Yes',
    'no' => 'No',

    'not_found_title' => 'Invalid Link',
    'not_found_description' => 'We could not find a valid confirmation link. Please double-check the link or contact the charity.',

    // Admin-facing tracking card on the aid detail screen
    // (resources/views/livewire/aids/show.blade.php).
    'tracking_title' => 'Delivery Confirmation',
    'tracking_sent_at' => 'Link Sent',
    'tracking_opened_at' => 'Link Opened',
    'tracking_confirmed_at' => 'Beneficiary Confirmed',
    'tracking_reminder_sent_at' => 'Reminder Sent',
    'tracking_not_sent' => 'Not sent yet',
    'tracking_not_opened' => 'Not opened yet',
    'tracking_not_confirmed' => 'Not confirmed yet',
    'tracking_no_reminder' => 'No reminder sent',
    'tracking_expired_badge' => 'Expired',
    'tracking_confirmed_ip' => 'from :ip',
    'tracking_no_confirmation_yet' => 'No confirmation link has been issued for this aid yet',

    'resend_button' => 'Resend Link',
    'confirm_resend' => 'Resend the confirmation link? The previous link will stop working immediately.',

    'messages' => [
        'resent' => 'Confirmation link resent successfully',
    ],
];
