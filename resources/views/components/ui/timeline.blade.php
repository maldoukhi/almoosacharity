@props([
    // Iterable of entries. Each entry may be:
    //  - an App\Models\ApprovalDecision (action/note/user/decided_at read automatically), or
    //  - a plain array: ['action' => 'approve'|'reject'|'return'|null, 'title' => ?string,
    //                     'description' => ?string, 'actor' => ?string, 'date' => ?string,
    //                     'color' => ?string, 'icon' => ?string]
    // Leave empty and pass a default slot of custom markup instead when full
    // control over each row is needed.
    'items' => [],
])

@php
    $colorMap = [
        'approve' => 'approved',
        'reject' => 'rejected',
        'return' => 'review',
    ];

    $iconMap = [
        'approve' => 'check',
        'reject' => 'x',
        'return' => 'undo',
    ];

    $entries = collect($items)->map(function ($item) use ($colorMap, $iconMap) {
        $action = data_get($item, 'action');

        $actionEnum = match (true) {
            $action instanceof \App\Enums\ApprovalAction => $action,
            is_string($action) => \App\Enums\ApprovalAction::tryFrom($action),
            default => null,
        };

        $key = $actionEnum?->value ?? data_get($item, 'icon');
        $date = data_get($item, 'date') ?? data_get($item, 'decided_at');

        return (object) [
            'title' => data_get($item, 'title') ?? $actionEnum?->label(),
            'description' => data_get($item, 'description') ?? data_get($item, 'note'),
            'actor' => data_get($item, 'actor') ?? data_get($item, 'user.name'),
            'date' => $date instanceof \Illuminate\Support\Carbon ? $date->translatedFormat('Y/m/d H:i') : $date,
            'color' => $colorMap[$key] ?? data_get($item, 'color', 'primary'),
            'icon' => $iconMap[$key] ?? data_get($item, 'icon', 'dot'),
        ];
    });
@endphp

<ol {{ $attributes->class(['flex flex-col']) }}>
    @if ($slot->isNotEmpty())
        {{ $slot }}
    @elseif ($entries->isEmpty())
        <li class="text-sm text-gray-500 dark:text-gray-400">{{ __('common.dash') }}</li>
    @else
        @foreach ($entries as $entry)
            <li class="flex items-start gap-3">
                <div class="flex h-full flex-col items-center self-stretch">
                    <span
                        @class([
                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
                            'bg-status-approved/10 text-status-approved' => $entry->color === 'approved',
                            'bg-status-rejected/10 text-status-rejected' => $entry->color === 'rejected',
                            'bg-status-review/10 text-status-review' => $entry->color === 'review',
                            'bg-primary-50 text-primary-700 dark:bg-primary-900/40 dark:text-primary-200' => $entry->color === 'primary',
                            'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400' => ! in_array($entry->color, ['approved', 'rejected', 'review', 'primary'], true),
                        ])
                        aria-hidden="true"
                    >
                        @if ($entry->icon === 'check')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        @elseif ($entry->icon === 'x')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        @elseif ($entry->icon === 'undo')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                        @else
                            <span class="h-2 w-2 rounded-full bg-current"></span>
                        @endif
                    </span>

                    @if (! $loop->last)
                        <span class="my-1 w-px flex-1 bg-gray-200 dark:bg-white/10" aria-hidden="true"></span>
                    @endif
                </div>

                <div @class(['min-w-0 flex-1', 'pb-6' => ! $loop->last])>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $entry->title }}</p>

                    @if ($entry->description)
                        <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">{{ $entry->description }}</p>
                    @endif

                    @if ($entry->actor || $entry->date)
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $entry->actor }}
                            @if ($entry->actor && $entry->date)
                                &middot;
                            @endif
                            <span class="tabular-nums">{{ $entry->date }}</span>
                        </p>
                    @endif
                </div>
            </li>
        @endforeach
    @endif
</ol>
