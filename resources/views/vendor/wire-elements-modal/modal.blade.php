{{-- Overridden from wire-elements/modal: raised z-index above the app
     sidebar (z-40) and RTL-aware centering. Lives under resources/views
     so Tailwind compiles the modal's utility classes (the vendor path is
     not scanned). --}}
<div>
    @isset($jsPath)
        <script>{!! file_get_contents($jsPath) !!}</script>
    @endisset
    @isset($cssPath)
        <style>{!! file_get_contents($cssPath) !!}</style>
    @endisset

    <div
        x-data="LivewireUIModal()"
        x-on:close.stop="setShowPropertyTo(false)"
        x-on:keydown.escape.window="show && closeModalOnEscape()"
        x-show="show"
        class="fixed inset-0 z-[60] overflow-y-auto"
        style="display: none;"
    >
        <div class="flex min-h-dvh items-center justify-center px-4 py-6 text-center sm:p-0">
            <div
                x-show="show"
                x-on:click="closeModalOnClickAway()"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-all"
            >
                <div class="absolute inset-0 bg-primary-950/60 backdrop-blur-sm"></div>
            </div>

            <div
                x-show="show && showActiveComponent"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-bind:class="modalWidth"
                class="relative my-8 inline-block w-full transform overflow-hidden rounded-(--radius-brand) bg-white text-start align-middle shadow-xl transition-all sm:w-full dark:bg-primary-950"
                id="modal-container"
                x-trap.noscroll.inert="show && showActiveComponent"
                aria-modal="true"
            >
                @forelse($components as $id => $component)
                    <div x-show.immediate="activeComponent == '{{ $id }}'" x-ref="{{ $id }}" wire:key="{{ $id }}">
                        @livewire($component['name'], $component['arguments'], key($id))
                    </div>
                @empty
                @endforelse
            </div>
        </div>
    </div>
</div>
