import ApexCharts from 'apexcharts';

// Exposed globally so resources/views/components/ui/chart.blade.php can
// instantiate charts from an inline Alpine x-data block without an import.
window.ApexCharts = ApexCharts;

// Opens the shared in-app confirmation modal
// (resources/views/components/ui/confirm-dialog) instead of the native
// browser confirm(), used everywhere in place of wire:confirm. Defined as a
// plain global (not an Alpine magic) so it is always available regardless of
// Alpine's init timing. Usage from a Livewire view:
//   x-on:click="uiConfirm(@js(__('...')), () => $wire.delete(id), { danger: true })"
window.uiConfirm = function (message, onConfirm, opts = {}) {
    window.dispatchEvent(
        new CustomEvent('ui-confirm', {
            detail: {
                message,
                onConfirm,
                confirmLabel: opts.confirmLabel || null,
                danger: opts.danger || false,
            },
        }),
    );
};
