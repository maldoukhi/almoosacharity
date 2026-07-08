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
                    @foreach ($row as $cell)
                        <td>{{ $cell === null || $cell === '' ? __('common.dash') : $cell }}</td>
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
                    {{ __('reports.messages.total_count', ['count' => $totals['count']]) }}
                    —
                    {{ __('reports.messages.total_sent', ['count' => $totals['sent']]) }}
                    —
                    {{ __('reports.messages.total_failed', ['count' => $totals['failed']]) }}
                    —
                    {{ __('reports.messages.total_pending', ['count' => $totals['pending']]) }}
                </td>
            </tr>
        </tfoot>
    </table>
@endsection
