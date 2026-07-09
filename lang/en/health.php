<?php

return [

    'title' => 'System Health',
    'subtitle' => 'A quick overview of the queue, messages, backups, and disk space',

    'unavailable' => 'Unavailable',

    'queue' => [
        'label' => 'Queue',
        'pending' => 'Pending jobs',
        'failed' => 'Failed jobs',
        'unavailable_hint' => 'Queue tables are unavailable',
    ],

    'messages' => [
        'label' => 'Failed Messages',
        'description' => 'In the last 24 hours',
        'view_report' => 'View messages report',
    ],

    'recurring' => [
        'label' => 'Last Recurring Aid Generation',
        'never_run' => 'Not run yet',
        'last_generated_at' => 'Last generated: :date',
        'overdue' => 'Overdue plans: :count',
        'no_overdue' => 'No overdue plans',
    ],

    'backup' => [
        'label' => 'Last Backup',
        'empty' => 'No backups yet',
        'newest_at' => 'On: :date',
        'size' => 'Size: :size',
        'stale_hint' => 'Older than 48 hours',
    ],

    'disk' => [
        'label' => 'Disk Space',
        'used_of_total' => ':used GB of :total GB',
        'used_percent' => ':percent% used',
    ],

    'schedule' => [
        'label' => 'Schedule',
        'description' => 'Expected times for scheduled jobs (informational)',
        'backup_row' => 'Backup',
        'backup_time' => 'Daily at 2:00 AM',
        'recurring_row' => 'Recurring aid generation',
        'reminders_row' => 'Receipt confirmation reminders',
        'daily' => 'Daily',
    ],

];
