{{--
    Generic preview table shared by every report screen: it only knows how
    to render $headings (array<string>) and $mappedRows (iterable of flat
    arrays already produced by Report::map()) — no report-specific
    formatting lives here, keeping every report's preview, PDF and Excel
    output in sync.
--}}
<x-ui.table>
    <thead>
        <tr>
            @foreach ($headings as $heading)
                <x-ui.table.th>{{ $heading }}</x-ui.table.th>
            @endforeach
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
        @forelse ($mappedRows as $row)
            <tr class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                @foreach ($row as $cell)
                    <x-ui.table.td class="tabular-nums">
                        {{ $cell === null || $cell === '' ? __('common.dash') : $cell }}
                    </x-ui.table.td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($headings) }}" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('reports.pdf.no_data') }}
                </td>
            </tr>
        @endforelse
    </tbody>
</x-ui.table>
