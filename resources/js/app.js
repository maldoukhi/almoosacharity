import ApexCharts from 'apexcharts';

// Exposed globally so resources/views/components/ui/chart.blade.php can
// instantiate charts from an inline Alpine x-data block without an import.
window.ApexCharts = ApexCharts;
