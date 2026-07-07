@extends('reports.pdf.layout')

@section('content')
    <table class="report-table">
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $index => $cell)
                        <td>
                            @if (is_numeric($cell) && $index >= 3)
                                {{ number_format((float) $cell, 2) }}
                            @else
                                {{ $cell === null || $cell === '' ? __('common.dash') : $cell }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}" style="text-align: center;">{{ __('reports.pdf.no_data') }}</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ count($headings) }}">
                    {{ __('reports.financial.total_count', ['count' => $totals['count']]) }}
                    —
                    {{ __('reports.financial.total_cash', ['amount' => number_format((float) $totals['cash_total'], 2)]) }}
                    —
                    {{ __('reports.financial.total_in_kind', ['amount' => number_format((float) $totals['in_kind_total'], 2)]) }}
                    —
                    {{ __('reports.financial.total_grand', ['amount' => number_format((float) $totals['grand_total'], 2)]) }}
                </td>
            </tr>
        </tfoot>
    </table>
@endsection
