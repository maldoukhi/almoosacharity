<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="utf-8">
<title>{{ __('aids.receipt.title') }} — {{ $aid->reference }}</title>
<style>
    {{-- Rendered by mPDF (see App\Support\AidReceiptPdf): fonts are
         registered in the renderer's fontdata, RTL mirroring is native, and
         the page footer is injected via SetHTMLFooter — so this template is
         plain document flow. --}}
    body {
        font-size: 12px;
        color: #1f2937;
    }

    table.header-table {
        width: 100%;
        border-collapse: collapse;
        border-bottom: 2px solid #1C545E;
        margin-bottom: 14px;
    }

    table.header-table td {
        border: none;
        vertical-align: middle;
        padding: 0 0 8px 0;
    }

    table.header-table .logo-cell {
        width: 70px;
    }

    .brand-name {
        font-size: 13px;
        font-weight: bold;
        color: #1C545E;
    }

    .report-title {
        font-size: 18px;
        font-weight: bold;
        color: #1C545E;
        margin-top: 4px;
    }

    .meta {
        font-size: 9px;
        color: #6b7280;
        margin-top: 2px;
    }

    .reference-bar {
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
        padding: 2px 8px;
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

    .label {
        font-size: 9px;
        color: #6b7280;
        margin-bottom: 3px;
    }

    .value {
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

    .amount-box {
        margin-top: 4px;
        margin-bottom: 16px;
        padding: 12px;
        border: 1px solid #85BF40;
        background-color: #f4f9ee;
        text-align: center;
    }

    .amount-value {
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
        border: none;
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
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('images/brand/logo-print.png') }}" alt="{{ config('app.name') }}" width="60">
            </td>
            <td>
                <div class="brand-name">{{ config('app.name') }}</div>
                <div class="report-title">{{ __('aids.receipt.title') }}</div>
                <div class="meta">{{ __('reports.pdf.generated_at', ['date' => now()->format('Y-m-d')]) }}</div>
            </td>
        </tr>
    </table>

    <div class="reference-bar">{{ $aid->reference }}</div>
    <div class="aid-title">{{ $aid->display_title ?? __('aids.receipt.no_title') }}</div>

    <table class="details-table">
        <tr>
            <td>
                <div class="label">{{ __('aids.receipt.field_beneficiary') }}</div>
                <div class="value">{{ $aid->beneficiary?->full_name }}</div>
            </td>
            <td>
                <div class="label">{{ __('aids.receipt.field_national_id') }}</div>
                <div class="value">{{ $maskedNationalId ?? __('common.dash') }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ __('aids.receipt.field_program') }}</div>
                <div class="value">{{ $aid->program?->name ?? __('common.dash') }}</div>
            </td>
            <td>
                <div class="label">{{ __('aids.receipt.field_type') }}</div>
                <div class="value">{{ $aid->type->label() }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ __('aids.receipt.field_date') }}</div>
                <div class="value">{{ now()->translatedFormat('Y/m/d') }}</div>
            </td>
            <td>
                <div class="label">{{ __('aids.receipt.field_status') }}</div>
                <div class="value"><span class="status-badge">{{ $aid->status->label() }}</span></div>
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
