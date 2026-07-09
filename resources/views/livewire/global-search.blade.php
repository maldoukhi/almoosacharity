@php
    $beneficiaries = $this->beneficiaries;
    $aids = $this->aids;
    $hasResults = $this->hasResults;
    $term = trim($query);
    $flatIndex = 0;
@endphp

<div
    x-data="{
        open: false,
        activeIndex: -1,
        openPalette() {
            this.open = true;
            this.activeIndex = -1;
            this.$nextTick(() => this.$refs.input?.focus());
        },
        closePalette() {
            this.open = false;
            this.activeIndex = -1;
        },
        resultItems() {
            return this.$refs.results ? Array.from(this.$refs.results.querySelectorAll('[data-search-item]')) : [];
        },
        moveActive(delta) {
            const items = this.resultItems();
            if (! items.length) { this.activeIndex = -1; return; }
            this.activeIndex = (this.activeIndex + delta + items.length) % items.length;
            items[this.activeIndex].scrollIntoView({ block: 'nearest' });
        },
        selectActive() {
            const items = this.resultItems();
            if (! items.length) return;
            const index = this.activeIndex === -1 ? 0 : this.activeIndex;
            items[index]?.click();
        },
    }"
    x-on:keydown.window.ctrl.k.prevent="openPalette()"
    x-on:keydown.window.meta.k.prevent="openPalette()"
    x-on:keydown.window.escape="closePalette()"
>
    {{-- Trigger button --}}
    <button
        type="button"
        x-on:click="openPalette()"
        title="{{ __('search.trigger') }}"
        class="inline-flex items-center gap-2 rounded-(--radius-brand) px-3 py-2 text-sm text-gray-500 transition duration-150 hover:bg-gray-100 hover:text-gray-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white"
    >
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <span class="sr-only sm:not-sr-only">{{ __('search.trigger') }}</span>
        <kbd dir="ltr" class="hidden items-center gap-0.5 rounded border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-400 sm:inline-flex dark:border-white/10 dark:bg-white/5 dark:text-gray-500">
            Ctrl K
        </kbd>
    </button>

    {{-- Overlay palette --}}
    <div
        x-show="open"
        x-cloak
        style="display: none"
        class="fixed inset-0 z-[80] overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('search.trigger') }}"
    >
        <div class="absolute inset-0 bg-primary-950/60 backdrop-blur-sm" x-on:click="closePalette()"></div>

        <div class="flex min-h-dvh items-start justify-center p-4 sm:pt-24">
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-on:click.outside="closePalette()"
                class="relative w-full max-w-xl overflow-hidden rounded-(--radius-brand) bg-white shadow-xl dark:bg-primary-950 dark:ring-1 dark:ring-white/10"
            >
                <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-3 dark:border-white/10">
                    <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>

                    <input
                        type="text"
                        x-ref="input"
                        wire:model.live.debounce.250ms="query"
                        x-on:input="activeIndex = -1"
                        x-on:keydown.down.prevent="moveActive(1)"
                        x-on:keydown.up.prevent="moveActive(-1)"
                        x-on:keydown.enter.prevent="selectActive()"
                        placeholder="{{ __('search.placeholder') }}"
                        autocomplete="off"
                        class="w-full border-0 bg-transparent text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                    />

                    <button
                        type="button"
                        x-on:click="closePalette()"
                        title="{{ __('search.close') }}"
                        class="inline-flex shrink-0 items-center justify-center rounded-(--radius-brand) p-1.5 text-gray-400 transition duration-150 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-200"
                    >
                        <span class="sr-only">{{ __('search.close') }}</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div x-ref="results" class="max-h-96 overflow-y-auto p-2">
                    @if ($term === '')
                        <p class="px-3 py-6 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('search.start_typing') }}</p>
                    @elseif (mb_strlen($term) < 2)
                        <p class="px-3 py-6 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('search.min_chars') }}</p>
                    @elseif (! $hasResults)
                        <p class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('search.empty') }}</p>
                    @else
                        @if ($beneficiaries->isNotEmpty())
                            <p class="px-3 pb-1 pt-2 text-xs font-semibold text-gray-400 dark:text-gray-500">{{ __('search.groups.beneficiaries') }}</p>

                            @foreach ($beneficiaries as $beneficiary)
                                <a
                                    href="{{ route('admin.beneficiaries.show', $beneficiary) }}"
                                    wire:navigate
                                    wire:key="search-beneficiary-{{ $beneficiary->id }}"
                                    data-search-item
                                    x-on:click="closePalette()"
                                    :class="activeIndex === {{ $flatIndex }} ? 'bg-primary-50 dark:bg-white/10' : ''"
                                    class="flex items-center justify-between gap-3 rounded-(--radius-brand) px-3 py-2 text-start transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5"
                                >
                                    <span class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">{{ $beneficiary->full_name }}</span>
                                    <span dir="ltr" class="shrink-0 text-xs text-gray-400 dark:text-gray-500">{{ $beneficiary->national_id }}</span>
                                </a>
                                @php $flatIndex++; @endphp
                            @endforeach
                        @endif

                        @if ($aids->isNotEmpty())
                            <p class="px-3 pb-1 pt-3 text-xs font-semibold text-gray-400 dark:text-gray-500">{{ __('search.groups.aids') }}</p>

                            @foreach ($aids as $aid)
                                <a
                                    href="{{ route('aids.show', $aid) }}"
                                    wire:navigate
                                    wire:key="search-aid-{{ $aid->id }}"
                                    data-search-item
                                    x-on:click="closePalette()"
                                    :class="activeIndex === {{ $flatIndex }} ? 'bg-primary-50 dark:bg-white/10' : ''"
                                    class="flex items-center justify-between gap-3 rounded-(--radius-brand) px-3 py-2 text-start transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5"
                                >
                                    <span class="min-w-0 truncate">
                                        <span dir="ltr" class="text-xs text-gray-400 dark:text-gray-500">{{ $aid->reference }}</span>
                                        <span class="ms-2 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $aid->display_title }}</span>
                                    </span>
                                    <x-ui.badge :color="$aid->status->color()" class="shrink-0">{{ $aid->status->label() }}</x-ui.badge>
                                </a>
                                @php $flatIndex++; @endphp
                            @endforeach
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
