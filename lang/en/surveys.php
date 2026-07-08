<?php

return [
    'index_title' => 'Surveys',
    'index_subtitle' => 'Manage program-specific or general surveys and their results',
    'create_button' => 'New survey',
    'create_title' => 'Create survey',
    'create_subtitle' => 'Create a new survey and add its questions',
    'edit_title' => 'Edit survey',
    'edit_subtitle' => 'Edit the survey details and its questions',
    'results_title' => 'Survey results',
    'results_subtitle' => "Summary of beneficiaries' answers to this survey",

    'field_title' => 'Survey title',
    'field_description' => 'Description',
    'field_scope' => 'Scope',
    'field_program' => 'Aid program',
    'field_is_active' => 'Active',
    'field_starts_at' => 'Start date (optional)',
    'field_ends_at' => 'End date (optional)',
    'field_questions_count' => 'Questions',
    'field_responses_count' => 'Responses',
    'field_status' => 'Status',

    'select_placeholder' => 'Select from the list',

    'status_active' => 'Active',
    'status_inactive' => 'Inactive',

    'confirm_delete' => 'Are you sure you want to delete this survey?',
    'confirm_toggle_activate' => 'Do you want to activate this survey?',
    'confirm_toggle_deactivate' => 'Do you want to deactivate this survey?',

    'empty_title' => 'No surveys',
    'empty_description' => 'Start by creating a new survey to collect beneficiary feedback',
    'results_count' => '{0} No results|{1} 1 result|[2,*] :count results',

    'question_type' => [
        'short_text' => 'Short text',
        'long_text' => 'Long text',
        'single_choice' => 'Single choice',
        'multiple_choice' => 'Multiple choice',
        'rating' => 'Star rating',
        'yes_no' => 'Yes / No',
    ],

    'scope' => [
        'general' => 'General',
        'program' => 'Program-specific',
    ],

    'builder' => [
        'survey_details' => 'Survey details',
        'questions_title' => 'Questions',
        'add_question_title' => 'Add question',
        'field_label' => 'Question text',
        'field_help_text' => 'Help text (optional)',
        'field_required' => 'Required',
        'field_options' => 'Options',
        'field_option_value' => 'Value',
        'field_option_label' => 'Option text',
        'add_option' => 'Add option',
        'remove_option' => 'Remove option',
        'field_max_stars' => 'Maximum stars',
        'move_up' => 'Move up',
        'move_down' => 'Move down',
        'remove_question' => 'Remove question',
        'no_questions_yet' => 'No questions added yet. Add one from the buttons above.',
        'min_options' => 'At least two options are required for this question.',
        'save' => 'Save survey',
        'question_label_placeholder' => 'Type the question text here...',
    ],

    'results' => [
        'total_responses' => 'Total responses',
        'no_responses_title' => 'No responses yet',
        'no_responses_description' => 'No beneficiary has responded to this survey yet',
        'average_rating' => 'Average rating',
        'yes_percentage' => 'Yes',
        'no_percentage' => 'No',
        'latest_answers' => 'Latest answers',
        'export_excel' => 'Export Excel',
        'export_hint' => 'Available with reports',
        'responses_count' => '{0} no responses|{1} one response|[2,*] :count responses',
        'answers_count' => '{0} no answers|{1} one answer|[2,*] :count answers',
    ],

    'messages' => [
        'saved' => 'Survey saved successfully',
        'deleted' => 'Survey deleted successfully',
        'toggled' => 'Survey status updated successfully',
        'cannot_delete_has_responses' => 'A survey with recorded responses cannot be deleted',
        'question_has_answers' => 'The question ":label" cannot be removed because it has recorded answers',
        'scope_requires_program' => 'An aid program must be selected for the "program-specific" scope',
    ],
];
