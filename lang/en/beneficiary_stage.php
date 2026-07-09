<?php

return [
    // Default outbound message (SMS/WhatsApp). Must contain {link}.
    'default_body' => 'Hello {short_name}, we need your response on your aid request. Please open the link and submit your response: {link}',

    'greeting' => 'Hello :name',
    'intro' => 'We need your response on your aid request at this stage.',

    'field_stage' => 'Stage',
    'field_program' => 'Program',
    'field_type' => 'Aid type',
    'field_items' => 'Items',

    'note_label' => 'Your note',
    'note_placeholder' => 'Write your response or any note you would like to add…',

    'document_label' => 'Attach a document (optional)',
    'document_hint' => 'You may attach a single file (PDF, JPG or PNG, up to 5 MB).',
    'uploading' => 'Uploading file…',

    'response_required' => 'Please write a note or attach a document before submitting.',
    'submit_button' => 'Submit response',

    'success_title' => 'Your response was received',
    'success_description' => 'Thank you, your response has been recorded and your request will continue processing.',

    'already_title' => 'Response already received',
    'already_description' => 'You have already submitted your response for this stage. No further action is needed.',

    'expired_title' => 'Link expired',
    'expired_description' => 'This link has expired. Please contact the charity for a new link.',

    'not_found_title' => 'Invalid link',
    'not_found_description' => 'This link is invalid or no longer available.',
];
