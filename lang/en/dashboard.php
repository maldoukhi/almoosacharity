<?php

return [

    // Operational "pending indicators" row at the top of the main dashboard.
    'ops' => [
        'title' => 'Operational Indicators',
        'subtitle' => 'Items that need attention now',

        'overdue_approvals_label' => 'Overdue Approvals',
        'overdue_approvals_description' => 'Aids under review for more than 7 days',

        'expired_confirmations_label' => 'Expired Receipt Confirmations',
        'expired_confirmations_description' => 'Confirmation links that expired without a beneficiary response',

        'upcoming_recurring_label' => 'Recurring Due Soon',
        'upcoming_recurring_description' => 'Recurring aid plans due to run within 7 days',

        'overdue_beneficiary_reviews_label' => 'Overdue Beneficiary Reviews',
        'overdue_beneficiary_reviews_description' => 'Beneficiary files under review for more than 7 days without an update',
    ],

];
