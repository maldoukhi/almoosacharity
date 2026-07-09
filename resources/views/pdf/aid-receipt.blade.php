<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="utf-8">
<title>{{ __('aids.receipt.title') }} — {{ $aid->reference }}</title>
<style>
    {{-- Self-hosted, local files only (no remote fetch — dompdf's
    enable_remote stays false per CLAUDE.md's data-safety rules). Same
    IBM Plex Sans Arabic embedding pattern as resources/views/reports/pdf. --}}
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
        margin: 130px 28px 60px 28px;
    }

    body {
        direction: rtl;
        font-size: 12px;
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
        bottom: -50px;
        right: 0;
        left: 0;
        height: 40px;
        border-top: 1px solid #e5e7eb;
        padding-top: 6px;
        font-size: 9px;
        color: #6b7280;
        text-align: center;
    }

    #footer .page-number:after {
        content: counter(page) " / " counter(pages);
    }

    .reference-bar {
        font-family: monospace, 'ibm-plex-sans-arabic';
        font-size: 14px;
        font-weight: bold;
        color: #1C545E;
        margin-bottom: 4px;
    }

    .aid-title {
        font-size: 13px;
        color: #374151;
        margin-bottom: 14px;
    }

    .status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: bold;
        background-color: #eef2f1;
        color: #1C545E;
    }

    table.details-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
        margin-bottom: 16px;
    }

    table.details-table td {
        padding: 8px 10px;
        border: 1px solid #e5e7eb;
        vertical-align: top;
        width: 50%;
    }

    table.details-table .label {
        display: block;
        font-size: 9px;
        color: #6b7280;
        margin-bottom: 3px;
    }

    table.details-table .value {
        font-size: 12px;
        color: #111827;
        font-weight: bold;
    }

    .section-title {
        font-size: 12px;
        font-weight: bold;
        color: #1C545E;
        margin-top: 10px;
        margin-bottom: 6px;
        border-bottom: 1px dashed #d1d5db;
        padding-bottom: 4px;
    }

    table.items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
    }

    table.items-table thead th {
        background-color: #1C545E;
        color: #ffffff;
        padding: 6px 8px;
        text-align: right;
        font-size: 10px;
    }

    table.items-table tbody td {
        padding: 5px 8px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 10px;
    }

    table.items-table tbody tr:nth-child(even) {
        background-color: #f9fafb;
    }

    .amount-box {
        margin-top: 4px;
        margin-bottom: 16px;
        padding: 12px;
        border: 1px solid #85BF40;
        background-color: #f4f9ee;
        text-align: center;
    }

    .amount-box .amount-value {
        font-size: 20px;
        font-weight: bold;
        color: #1C545E;
    }

    table.signatures-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 50px;
    }

    table.signatures-table td {
        width: 50%;
        text-align: center;
        vertical-align: top;
        padding: 0 20px;
    }

    .signature-line {
        margin-top: 55px;
        border-top: 1px solid #6b7280;
        padding-top: 6px;
        font-size: 11px;
        color: #374151;
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
                    <div class="report-title">{{ __('aids.receipt.title') }}</div>
                    <div class="meta">{{ __('reports.pdf.generated_at', ['date' => now()->format('Y-m-d H:i')]) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div id="footer">
        <span class="page-number"></span> — {{ __('aids.receipt.footer_note') }}
    </div>

    <div class="reference-bar">{{ $aid->reference }}</div>
    <div class="aid-title">{{ $aid->display_title ?? __('aids.receipt.no_title') }}</div>

    <table class="details-table">
        <tr>
            <td>
                <span class="label">{{ __('aids.receipt.field_beneficiary') }}</span>
                <span class="value">{{ $aid->beneficiary?->full_name }}</span>
            </td>
            <td>
                <span class="label">{{ __('aids.receipt.field_national_id') }}</span>
                <span class="value">{{ $maskedNationalId ?? __('common.dash') }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">{{ __('aids.receipt.field_program') }}</span>
                <span class="value">{{ $aid->program?->name ?? __('common.dash') }}</span>
            </td>
            <td>
                <span class="label">{{ __('aids.receipt.field_type') }}</span>
                <span class="value">{{ $aid->type->label() }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">{{ __('aids.receipt.field_date') }}</span>
                <span class="value">{{ now()->translatedFormat('Y/m/d') }}</span>
            </td>
            <td>
                <span class="label">{{ __('aids.receipt.field_status') }}</span>
                <span class="value"><span class="status-badge">{{ $aid->status->label() }}</span></span>
            </td>
        </tr>
    </table>

    @if ($aid->type === \App\Enums\AidType::Cash)
        <div class="amount-box">
            <div class="amount-value">{{ number_format((float) $aid->amount, 2) }} {{ __('aids.currency_sar') }}</div>
        </div>
    @else
        <div class="section-title">{{ __('aids.receipt.items_title') }}</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>{{ __('aids.field_item_name') }}</th>
                    <th>{{ __('aids.field_item_quantity') }}</th>
                    <th>{{ __('aids.field_item_estimated_value') }}</th>
                    <th>{{ __('aids.field_item_description') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($aid->items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format((float) $item->estimated_value, 2) }} {{ __('aids.currency_sar') }}</td>
                        <td>{{ $item->description ?: __('common.dash') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line">{{ __('aids.receipt.signature_recipient') }}</div>
            </td>
            <td>
                <div class="signature-line">{{ __('aids.receipt.signature_official') }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
