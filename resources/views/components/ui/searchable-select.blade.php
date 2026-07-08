@props([
    'options' => [],
    'model' => null,
    'label' => null,
    'placeholder' => null,
    'searchPlaceholder' => null,
    'noResultsText' => null,
    'name' => null,
    'hint' => null,
])

{{--
    Reusable searchable single-select (Alpine + Livewire entangle).

    Usage:
      <x-ui.searchable-select
          :label="__('beneficiaries.field_nationality')"
          model="nationality"
          :options="$countryOptions"
          :placeholder="__('beneficiaries.select_placeholder')"
      />

    - `options` is a value => label array (order is preserved).
    - `model` is the Livewire component property name to two-way bind
      (kept in sync via @entangle, so it works exactly like wire:model).
--}}

@php
    $name = $name ?? $model;
    $hasError = $name && $errors->has($name);
    $id = $attributes->get('id', $name);
@endphp

<div
    x-data="{
        open: false,
        query: '',
        highlighted: 0,
        value: @entangle($model),
        options: @js($options),
        get filteredEntries() {
            const q = this.query.trim().toLowerCase();
            const entries = Object.entries(this.options);
            if (! q) return entries;
            return entries.filter(([code, label]) => label.toLowerCase().includes(q));
        },
        get selectedLabel() {
            if (! this.value) return '';

            // Falls back to the raw stored value itself (rather than a
            // blank field) for legacy/unmapped values that don't match any
            // option in the catalog, e.g. old free-text nationality data.
            return this.options[this.value] ?? this.value;
        },
        select(code) {
            this.value = code;
            this.open = false;
            this.query = '';
            this.highlighted = 0;
        },
        openList() {
            this.open = true;
            const idx = this.filteredEntries.findIndex(([code]) => code === this.value);
            this.highlighted = idx >= 0 ? idx : 0;
            this.$nextTick(() => this.$refs.search?.focus());
        },
        move(delta) {
            const max = this.filteredEntries.length - 1;
            if (max < 0) return;
            this.highlighted = Math.min(Math.max(this.highlighted + delta, 0), max);
        },
        chooseHighlighted() {
            const entry = this.filteredEntries[this.highlighted];
            if (entry) this.select(entry[0]);
        },
    }"
    x-on:click.outside="open = false"
    class="relative"
>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ $label }}
        </label>
    @endif

    <button
        type="button"
        id="{{ $id }}"
        x-on:click="open ? (open = false) : openList()"
        x-on:keydown.down.prevent="open ? move(1) : openList()"
        x-on:keydown.up.prevent="open ? move(-1) : openList()"
        x-on:keydown.enter.prevent="open ? chooseHighlighted() : openList()"
        x-on:keydown.escape="open = false"
        :aria-expanded="open.toString()"
        aria-haspopup="listbox"
        {{ $attributes->except('id')->class([
            'flex w-full items-center justify-between gap-2 rounded-(--radius-brand) border bg-white ps-3.5 pe-3 py-2.5 text-start text-sm shadow-sm transition duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-offset-0 dark:bg-primary-950/30',
            'border-status-rejected focus:border-status-rejected focus:ring-status-rejected/30' => $hasError,
            'border-gray-300 focus:border-primary-500 focus:ring-primary-500/30 dark:border-white/10' => ! $hasError,
        ]) }}
    >
        <span
            x-text="selectedLabel || @js((string) $placeholder)"
            :class="selectedLabel ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500'"
            class="truncate"
        ></span>

        <svg
            class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200 ease-out"
            :class="{ 'rotate-180': open }"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
        >
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        style="display: none"
        role="listbox"
        class="absolute z-30 mt-1.5 w-full rounded-(--radius-brand) bg-white p-1.5 shadow-(--shadow-card) ring-1 ring-gray-100 dark:bg-primary-950 dark:ring-white/10"
    >
        <input
            type="text"
            x-ref="search"
            x-model="query"
            x-on:input="highlighted = 0"
            x-on:keydown.down.prevent="move(1)"
            x-on:keydown.up.prevent="move(-1)"
            x-on:keydown.enter.prevent="chooseHighlighted()"
            x-on:keydown.escape="open = false"
            placeholder="{{ $searchPlaceholder ?? __('common.search') }}"
            class="mb-1.5 block w-full rounded-(--radius-brand) border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-white/5 dark:text-gray-100"
        />

        <ul class="max-h-56 space-y-0.5 overflow-y-auto">
            <template x-for="([code, label], index) in filteredEntries" :key="code">
                <li
                    x-on:click="select(code)"
                    x-on:mouseenter="highlighted = index"
                    :class="{
                        'bg-primary-50 dark:bg-primary-900/30': highlighted === index,
                        'font-medium text-primary-700 dark:text-primary-200': code === value,
                    }"
                    class="cursor-pointer rounded-(--radius-brand) px-3 py-2 text-sm text-gray-700 dark:text-gray-200"
                    role="option"
                    x-text="label"
                ></li>
            </template>
        </ul>

        @if ($noResultsText)
            <p x-show="filteredEntries.length === 0" class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500">
                {{ $noResultsText }}
            </p>
        @endif
    </div>

    @if ($hint && ! $hasError)
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif

    @if ($name)
        @error($name)
            <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
        @enderror
    @endif
</div>
