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
        sizeObserver: null,
        themeObserver: null,
        lastDark: false,
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
        build() {
            if (this.chart || typeof window.ApexCharts === 'undefined' || ! this.$refs.canvas) {
                return;
            }

            // Only build once the container actually has a width. Arriving
            // via wire:navigate, the flex layout / collapsible sidebar can
            // leave it at zero width for a beat; the ResizeObserver below
            // calls build() again the moment it gains a real width, so we
            // never draw into an unsized box (which renders an empty chart).
            if (this.$refs.canvas.offsetWidth === 0) {
                return;
            }

            try {
                this.chart = new window.ApexCharts(this.$refs.canvas, this.buildOptions());
                this.chart.render();
            } catch (e) {
                this.chart = null;
            }
        },
        destroyChart() {
            if (this.chart) {
                // ApexCharts can throw from its own teardown when destroyed
                // mid-animation (e.g. a wire:navigate fires while the line
                // chart is still animating in) — swallow it so a leaving page
                // never aborts cleanup and strands a half-dead instance.
                try { this.chart.destroy(); } catch (e) {}
                this.chart = null;
            }
        },
        cleanup() {
            if (this.sizeObserver) { this.sizeObserver.disconnect(); this.sizeObserver = null; }
            if (this.themeObserver) { this.themeObserver.disconnect(); this.themeObserver = null; }
            this.destroyChart();
            document.removeEventListener('livewire:navigating', this._onNavigating);
        },
        init() {
            // A ResizeObserver fires an initial callback on observe AND on
            // every subsequent size change, so it renders the chart the
            // instant the canvas has a real width — on first load, after a
            // wire:navigate visit (fresh element, 0 -> real width), and when
            // the sidebar collapses. This replaces the previous rAF-polling +
            // global livewire:navigated wiring, whose timing/order fragility
            // was leaving charts blank after sidebar navigation.
            this.sizeObserver = new ResizeObserver(() => {
                if (! this.chart && this.$refs.canvas && this.$refs.canvas.offsetWidth > 0) {
                    this.build();
                }
            });
            this.sizeObserver.observe(this.$refs.canvas);

            // Dark mode is toggled by adding/removing the `.dark` class on
            // <html> (see resources/views/components/layouts/topbar.blade.php).
            // Only react to an actual light<->dark change: the class attribute
            // also gets touched during a wire:navigate morph, and reacting to
            // those no-op mutations was firing updateOptions() at the wrong
            // time — that call empties a donut's SVG and throws on a line
            // chart, which is what left charts blank after sidebar navigation.
            // On a real theme change, rebuild fresh (updateOptions with
            // redrawPaths corrupts donuts) rather than updating in place.
            this.lastDark = this.isDark();
            this.themeObserver = new MutationObserver(() => {
                const dark = this.isDark();

                if (dark === this.lastDark) {
                    return;
                }

                this.lastDark = dark;

                if (this.chart) {
                    this.destroyChart();
                    this.build();
                }
            });
            this.themeObserver.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class'],
            });

            // Destroy cleanly *before* a wire:navigate swaps the DOM, so the
            // instance is never torn down on a detached node mid-animation.
            this._onNavigating = () => this.cleanup();
            document.addEventListener('livewire:navigating', this._onNavigating);
        },
        destroy() {
            this.cleanup();
        },
    }"
    x-init="init()"
    {{ $attributes }}
>
    <div x-ref="canvas"></div>
</div>
