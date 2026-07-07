@props([
    // Ordered list of stages: [['label' => string, 'meta' => ?string], ...].
    'steps' => [],
    // Zero-based index of the stage currently awaiting action.
    // null means the flow has not started yet, or has already ended
    // (see $status below to color a fully finished flow).
    'current' => null,
    // Optional terminal outcome: 'approved' or 'rejected'. When given, it
    // colors the concluding stage (the one at $current, or every stage when
    // $current is null and the whole flow was approved end-to-end).
    'status' => null,
])

@php
    $total = count($steps);

    $states = collect($steps)->values()->map(function ($step, $index) use ($current, $status, $total) {
        $isTerminalHere = $current !== null && $index === $current && in_array($status, ['approved', 'rejected'], true);

        return match (true) {
            $isTerminalHere && $status === 'rejected' => 'rejected',
            $isTerminalHere => 'completed',
            $current !== null && $index < $current => 'completed',
            $current !== null && $index === $current => 'active',
            $current === null && $status === 'approved' => 'completed',
            default => 'upcoming',
        };
    });
@endphp

<ol class="flex flex-col gap-0 sm:flex-row sm:items-start">
    @foreach ($steps as $index => $step)
        @php
            $state = $states[$index];
            $prevState = $index > 0 ? $states[$index - 1] : null;
            $lineBeforeFilled = $prevState === 'completed';
            $lineAfterFilled = $state === 'completed';
        @endphp

        <li class="relative flex flex-1 items-start gap-3 sm:flex-col sm:items-center sm:gap-0 sm:text-center">
            {{-- Desktop: horizontal connector line running through the circle row --}}
            <div class="hidden w-full items-center sm:flex">
                <span
                    aria-hidden="true"
                    @class([
                        'h-0.5 flex-1 transition-colors duration-300 ease-out',
                        'invisible' => $index === 0,
                        'bg-secondary' => $lineBeforeFilled,
                        'bg-gray-200 dark:bg-white/10' => ! $lineBeforeFilled,
                    ])
                ></span>

                <span class="relative shrink-0">
                    @if ($state === 'active')
                        <span
                            class="absolute inset-0 -m-1 rounded-full bg-primary-400/50 motion-safe:animate-ping motion-reduce:hidden"
                            aria-hidden="true"
                        ></span>
                    @endif

                    <span
                        @class([
                            'relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold transition-colors duration-300 ease-out',
                            'bg-secondary text-white' => $state === 'completed',
                            'bg-status-rejected text-white' => $state === 'rejected',
                            'border-2 border-primary bg-white text-primary-700 dark:bg-primary-950' => $state === 'active',
                            'border border-gray-200 bg-gray-50 text-gray-400 dark:border-white/10 dark:bg-white/5 dark:text-gray-500' => $state === 'upcoming',
                        ])
                    >
                        @if ($state === 'completed')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        @elseif ($state === 'rejected')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        @else
                            {{ $index + 1 }}
                        @endif
                    </span>
                </span>

                <span
                    aria-hidden="true"
                    @class([
                        'h-0.5 flex-1 transition-colors duration-300 ease-out',
                        'invisible' => $index === $total - 1,
                        'bg-secondary' => $lineAfterFilled,
                        'bg-gray-200 dark:bg-white/10' => ! $lineAfterFilled,
                    ])
                ></span>
            </div>

            {{-- Mobile: circle without the connector row --}}
            <span class="relative shrink-0 sm:hidden">
                @if ($state === 'active')
                    <span
                        class="absolute inset-0 -m-1 rounded-full bg-primary-400/50 motion-safe:animate-ping motion-reduce:hidden"
                        aria-hidden="true"
                    ></span>
                @endif

                <span
                    @class([
                        'relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                        'bg-secondary text-white' => $state === 'completed',
                        'bg-status-rejected text-white' => $state === 'rejected',
                        'border-2 border-primary bg-white text-primary-700 dark:bg-primary-950' => $state === 'active',
                        'border border-gray-200 bg-gray-50 text-gray-400 dark:border-white/10 dark:bg-white/5 dark:text-gray-500' => $state === 'upcoming',
                    ])
                >
                    @if ($state === 'completed')
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    @elseif ($state === 'rejected')
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    @else
                        {{ $index + 1 }}
                    @endif
                </span>
            </span>

            <div class="min-w-0 pt-0 sm:mt-2 sm:pt-0">
                <p
                    @class([
                        'truncate text-sm font-medium',
                        'text-gray-900 dark:text-white' => in_array($state, ['completed', 'active'], true),
                        'text-status-rejected' => $state === 'rejected',
                        'text-gray-400 dark:text-gray-500' => $state === 'upcoming',
                    ])
                >
                    {{ $step['label'] ?? '' }}
                </p>

                @if (! empty($step['meta']))
                    <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">{{ $step['meta'] }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
