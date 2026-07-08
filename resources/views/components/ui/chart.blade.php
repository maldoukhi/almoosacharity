@props([
    'type' => 'donut',
    'series' => [],
    'labels' => [],
    'colors' => [],
    'height' => 300,
])

{{-- wire:ignore keeps this subtree out of Livewire's re-render/morph
    pipeline entirely, so the ApexCharts instance created below survives
    any parent component re-render untouched. --}}
<div
    wire:ignore
    x-data="{
        chart: null,
        observer: null,
        raf: null,
        isPie: {{ in_array($type, ['donut', 'pie'], true) ? 'true' : 'false' }},
        isDark() {
            return document.documentElement.classList.contains('dark');
        },
        prefersReducedMotion() {
            return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        },
        buildOptions() {
            const dark = this.isDark();
            const axisColor = dark ? '#9ca3af' : '#6b7280';

            return {
                chart: {
                    type: @js($type),
                    height: @js($height),
                    fontFamily: 'inherit',
                    foreColor: dark ? '#e5e7eb' : '#374151',
                    background: 'transparent',
                    toolbar: { show: false },
                    animations: { enabled: ! this.prefersReducedMotion() },
                },
                series: @js($series),
                colors: @js($colors),
                labels: this.isPie ? @js($labels) : undefined,
                xaxis: this.isPie ? undefined : {
                    categories: @js($labels),
                    labels: { style: { colors: axisColor } },
                },
                yaxis: this.isPie ? undefined : {
                    labels: { style: { colors: axisColor } },
                },
                legend: {
                    position: 'bottom',
                    labels: { colors: dark ? '#e5e7eb' : '#374151' },
                },
                dataLabels: { enabled: this.isPie },
                stroke: { width: this.isPie ? 0 : 2, curve: 'smooth' },
                grid: { borderColor: dark ? 'rgba(255,255,255,0.08)' : '#e5e7eb' },
                tooltip: { theme: dark ? 'dark' : 'light' },
            };
        },
        render() {
            if (this.chart || typeof window.ApexCharts === 'undefined' || ! this.$refs.canvas) {
                return;
            }

            // Arriving via wire:navigate, the flex layout / collapsible
            // sidebar can leave this container at zero width for a frame or
            // two. Rendering then yields a zero-size 'sliver' that collapses
            // to nothing — so keep deferring until the element has a real
            // width instead of drawing into an unsized box.
            if (this.$refs.canvas.offsetWidth === 0) {
                this.raf = requestAnimationFrame(() => this.render());

                return;
            }

            this.chart = new window.ApexCharts(this.$refs.canvas, this.buildOptions());
            this.chart.render();

            // Dark mode is toggled by adding/removing the `.dark` class on
            // <html> (see resources/views/components/layouts/topbar.blade.php);
            // rather than editing that button, watch for the class change
            // directly and re-theme the chart in place.
            this.observer = new MutationObserver(() => {
                this.chart && this.chart.updateOptions(this.buildOptions(), false, true);
            });
            this.observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class'],
            });
        },
        teardown() {
            if (this.raf) { cancelAnimationFrame(this.raf); this.raf = null; }
            if (this.observer) { this.observer.disconnect(); this.observer = null; }
            if (this.chart) { this.chart.destroy(); this.chart = null; }
        },
        init() {
            this._onNavigated = () => this.render();
            this._onNavigating = () => this.teardown();

            // Render on the first (non-SPA) page load...
            this.raf = requestAnimationFrame(() => this.render());

            // ...and re-render after every wire:navigate visit completes.
            // livewire:navigated fires once the destination DOM is fully
            // committed (container properly sized): if the chart was torn
            // down on the way out this rebuilds it, and if it is still alive
            // render() is a no-op.
            document.addEventListener('livewire:navigated', this._onNavigated);

            // Livewire's SPA navigation swaps the page without unmounting
            // Alpine cleanly, so tear the chart down before leaving to avoid
            // a leaked instance/observer that leaves the canvas blank.
            document.addEventListener('livewire:navigating', this._onNavigating);
        },
        destroy() {
            this.teardown();
            document.removeEventListener('livewire:navigated', this._onNavigated);
            document.removeEventListener('livewire:navigating', this._onNavigating);
        },
    }"
    x-init="init()"
    {{ $attributes }}
>
    <div x-ref="canvas"></div>
</div>
