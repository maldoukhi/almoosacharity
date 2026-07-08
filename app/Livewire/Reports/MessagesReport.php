<?php

namespace App\Livewire\Reports;

use App\Enums\MessageChannel;
use App\Enums\MessageStatus;
use App\Exports\MessagesExport;
use App\Jobs\Messaging\SendSmsMessage;
use App\Jobs\Messaging\SendWhatsAppMessage;
use App\Models\MessageLog;
use App\Reports\Filters\MessagesReportFilter;
use App\Reports\MessagesReport as MessagesReportData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sent-messages report screen: filter bar, totals cards, a preview table,
 * and Excel/PDF export.
 */
class MessagesReport extends Component
{
    use WithPagination;

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $channel = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $source = '';

    public function mount(): void
    {
        Gate::authorize('reports.view');
    }

    /**
     * Re-queue a failed message for another send attempt (e.g. after the
     * provider credentials were fixed). Requires the broadcast permission
     * since it triggers an outbound send.
     */
    public function resend(int $id): void
    {
        Gate::authorize('messages.broadcast');

        $log = MessageLog::query()
            ->whereKey($id)
            ->where('status', MessageStatus::Failed)
            ->first();

        if (! $log) {
            abort(404);
        }

        $log->update(['status' => MessageStatus::Pending, 'error' => null]);

        match ($log->channel) {
            MessageChannel::Sms => SendSmsMessage::dispatch($log->id),
            MessageChannel::WhatsApp => SendWhatsAppMessage::dispatch(
                $log->id,
                $log->recipient,
                (string) $log->body,
                idempotencyKey: 'resend-'.$log->id.'-'.now()->timestamp,
            ),
        };

        $this->dispatch('toast', type: 'success', message: __('reports.messages.resend_queued'));
    }

    public function updatingFrom(): void
    {
        $this->resetPage();
    }

    public function updatingTo(): void
    {
        $this->resetPage();
    }

    public function updatingChannel(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSource(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function report(): MessagesReportData
    {
        return new MessagesReportData(new MessagesReportFilter(
            from: $this->from !== '' ? $this->from : null,
            to: $this->to !== '' ? $this->to : null,
            channel: $this->channel !== '' ? $this->channel : null,
            status: $this->status !== '' ? $this->status : null,
            source: $this->source !== '' ? $this->source : null,
        ));
    }

    /**
     * @return LengthAwarePaginator<int, MessageLog>
     */
    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return $this->report->query()->paginate(25);
    }

    #[Computed]
    public function totals(): array
    {
        return $this->report->totals();
    }

    /**
     * @return array<int, MessageChannel>
     */
    #[Computed]
    public function channels(): array
    {
        return MessageChannel::cases();
    }

    /**
     * @return array<int, MessageStatus>
     */
    #[Computed]
    public function statuses(): array
    {
        return MessageStatus::cases();
    }

    public function exportExcel(): BinaryFileResponse
    {
        Gate::authorize('reports.export');

        return Excel::download(new MessagesExport($this->report), $this->report->filename('xlsx'));
    }

    public function exportPdf(): StreamedResponse
    {
        Gate::authorize('reports.export');

        $report = $this->report;

        $rows = $report->query()->get()->map(fn (Model $row): array => $report->map($row));

        $pdf = Pdf::loadView($report->pdfView(), [
            'title' => $report->title(),
            'headings' => $report->headings(),
            'rows' => $rows,
            'totals' => $report->totals(),
            'filtersDescription' => $this->filtersDescription(),
        ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $report->filename('pdf'),
        );
    }

    private function filtersDescription(): string
    {
        $parts = [];

        if ($this->from !== '') {
            $parts[] = __('reports.filters.from').': '.$this->from;
        }

        if ($this->to !== '') {
            $parts[] = __('reports.filters.to').': '.$this->to;
        }

        if ($this->channel !== '') {
            $parts[] = __('reports.messages.filter_channel').': '.MessageChannel::from($this->channel)->label();
        }

        if ($this->status !== '') {
            $parts[] = __('reports.filters.status').': '.MessageStatus::from($this->status)->label();
        }

        if ($this->source !== '') {
            $parts[] = __('reports.messages.filter_source').': '.__('reports.messages.source_'.$this->source);
        }

        return implode(' | ', $parts);
    }

    public function render()
    {
        return view('livewire.reports.messages-report');
    }
}
