<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
    {{-- Self-hosted, local files only (no remote fetch — dompdf's
    enable_remote stays false per CLAUDE.md's data-safety rules). --}}
    @font-face {
        font-family: 'ibm-plex-sans-arabic';
        font-weight: normal;
        font-style: normal;
        src: url('file://{{ storage_path('fonts/IBMPlexSansArabic-Regular.ttf') }}') format('truetype');
    }
    @font-face {
        font-family: 'ibm-plex-sans-arabic';
        font-weight: bold;
        font-style: normal;
        src: url('file://{{ storage_path('fonts/IBMPlexSansArabic-Bold.ttf') }}') format('truetype');
    }

    * {
        font-family: 'ibm-plex-sans-arabic', sans-serif;
    }

    @page {
        margin: 130px 28px 55px 28px;
    }

    body {
        direction: rtl;
        font-size: 11px;
        color: #1f2937;
    }

    #header {
        position: fixed;
        top: -110px;
        right: 0;
        left: 0;
        height: 100px;
        border-bottom: 2px solid #1C545E;
        padding-bottom: 8px;
    }

    #header table {
        width: 100%;
        border-collapse: collapse;
    }

    #header td {
        border: none;
        vertical-align: middle;
    }

    #header .logo-cell {
        width: 70px;
    }

    #header img {
        height: 48px;
    }

    #header .brand-name {
        font-size: 13px;
        font-weight: bold;
        color: #1C545E;
    }

    #header .report-title {
        font-size: 18px;
        font-weight: bold;
        color: #1C545E;
        margin-top: 4px;
    }

    #header .meta {
        font-size: 9px;
        color: #6b7280;
        margin-top: 2px;
    }

    #footer {
        position: fixed;
        bottom: -45px;
        right: 0;
        left: 0;
        height: 35px;
        border-top: 1px solid #e5e7eb;
        padding-top: 6px;
        font-size: 9px;
        color: #6b7280;
        text-align: center;
    }

    #footer .page-number:after {
        content: counter(page) " / " counter(pages);
    }

    .filters-bar {
        font-size: 9px;
        color: #4b5563;
        margin-bottom: 10px;
        padding-bottom: 6px;
        border-bottom: 1px dashed #d1d5db;
    }

    table.report-table {
        width: 100%;
        border-collapse: collapse;
    }

    table.report-table thead th {
        background-color: #1C545E;
        color: #ffffff;
        padding: 6px 8px;
        text-align: right;
        font-size: 10px;
    }

    table.report-table tbody td {
        padding: 5px 8px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 10px;
    }

    table.report-table tbody tr:nth-child(even) {
        background-color: #f9fafb;
    }

    table.report-table tfoot td {
        padding: 7px 8px;
        font-weight: bold;
        background-color: #eef2f1;
        font-size: 10px;
        color: #1C545E;
    }

    .stub-banner {
        margin-top: 40px;
        padding: 24px;
        border: 1px dashed #EAB977;
        background-color: #fdf8f1;
        color: #7a5a25;
        text-align: center;
        font-size: 12px;
    }
</style>
</head>
<body>
    <div id="header">
        <table>
            <tr>
                <td class="logo-cell">
                    <img src="{{ public_path('images/brand/logo-print.png') }}" alt="{{ config('app.name') }}">
                </td>
                <td>
                    <div class="brand-name">{{ config('app.name') }}</div>
                    <div class="report-title">{{ $title }}</div>
                    <div class="meta">{{ __('reports.pdf.generated_at', ['date' => now()->format('Y-m-d')]) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div id="footer">
        <span class="page-number"></span> — {{ config('app.name') }}
    </div>

    @if (! empty($filtersDescription))
        <div class="filters-bar">{{ __('reports.pdf.filters_applied') }}: {{ $filtersDescription }}</div>
    @endif

    @yield('content')
</body>
</html>
