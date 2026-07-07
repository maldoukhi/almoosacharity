@props([
    'color' => 'gray',
])

@php
    $map = [
        // Semantic aid/user status colors (fixed across the system).
        'draft' => 'bg-status-draft/10 text-status-draft dark:bg-status-draft dark:text-white',
        'review' => 'bg-status-review/10 text-status-review dark:bg-status-review dark:text-white',
        'approved' => 'bg-status-approved/10 text-status-approved dark:bg-status-approved dark:text-white',
        'rejected' => 'bg-status-rejected/10 text-status-rejected dark:bg-status-rejected dark:text-white',
        'delivered' => 'bg-status-delivered/10 text-status-delivered dark:bg-status-delivered dark:text-white',

        // Brand-token colors for non-status usage.
        'primary' => 'bg-primary-50 text-primary-700 dark:bg-primary-500/20 dark:text-primary-200',
        'secondary' => 'bg-secondary-50 text-secondary-700 dark:bg-secondary-500/20 dark:text-secondary-200',
        'accent' => 'bg-accent-50 text-accent-700 dark:bg-accent-500/20 dark:text-accent-200',
        'gray' => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
    ];

    $classes = $map[$color] ?? $map['gray'];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium',
    $classes,
]) }}>
    {{ $slot }}
</span>
