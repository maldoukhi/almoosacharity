<?php

namespace App\Reports;

use App\Enums\MessageChannel;
use App\Enums\MessageStatus;
use App\Models\Beneficiary;
use App\Models\Broadcast;
use App\Models\MessageLog;
use App\Reports\Contracts\Report;
use App\Reports\Filters\MessagesReportFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Sent-messages report: one row per {@see MessageLog} (SMS/WhatsApp),
 * across both bulk broadcasts and automated aid/confirmation
 * notifications, with who sent it, who received it, and its outcome.
 *
 * "Who sent it" resolution: message_logs.messageable_type is either
 * App\Models\Broadcast (a manually triggered bulk send — sender is the
 * broadcast's `sent_by` user) or App\Models\Aid (an automated lifecycle
 * notification/confirmation link dispatched by the system, with no human
 * actor) or null. Only the Broadcast case needs a human name, so we never
 * eager-load the polymorphic `messageable` relation itself: we batch-fetch
 * just the distinct broadcast ids referenced by the filtered result set
 * and their `sent_by` user names in one extra query (see senderNames()),
 * instead of eager-loading messageable (which would pull two different
 * relations conditionally per row and still not resolve non-broadcast rows
 * any faster) or querying per row (N+1).
 *
 * "Recipient name" resolution: recipient is a raw phone number with no FK
 * to beneficiaries. Per the phase-9 decision, we match it to a beneficiary
 * by mobile number: all distinct recipient numbers in the filtered result
 * set are collected and looked up against beneficiaries.mobile in a single
 * query (see recipientNames()), same batching strategy as senderNames().
 * Both lookups are memoized per Report instance, so map() never re-queries
 * once built — consistent with AidsReport::totals()/BeneficiariesReport,
 * which already run one extra unpaginated query over query() to aggregate
 * across the whole filtered set rather than just the visible page.
 */
final class MessagesReport implements Report
{
    private ?array $recipientNamesCache = null;

    private ?array $senderNamesCache = null;

    public function __construct(private readonly MessagesReportFilter $filter) {}

    public function authorize(): bool
    {
        return Gate::allows('reports.view');
    }

    /**
     * @return Builder<MessageLog>
     */
    public function query(): Builder
    {
        return MessageLog::query()
            // COALESCE/DATE are portable across SQLite and MySQL (no
            // MySQL-only date functions), per CLAUDE.md's SQLite constraint.
            ->when($this->filter->from, fn (Builder $q) => $q->whereRaw('DATE(COALESCE(sent_at, created_at)) >= ?', [$this->filter->from]))
            ->when($this->filter->to, fn (Builder $q) => $q->whereRaw('DATE(COALESCE(sent_at, created_at)) <= ?', [$this->filter->to]))
            ->when($this->filter->channel, fn (Builder $q) => $q->where('channel', $this->filter->channel))
            ->when($this->filter->status, fn (Builder $q) => $q->where('status', $this->filter->status))
            ->when($this->filter->source === 'broadcast', fn (Builder $q) => $q->where('messageable_type', Broadcast::class))
            ->when($this->filter->source === 'notification', function (Builder $q): void {
                $q->where(function (Builder $q): void {
                    $q->whereNull('messageable_type')->orWhere('messageable_type', '!=', Broadcast::class);
                });
            })
            ->orderByRaw('COALESCE(sent_at, created_at) DESC');
    }

    public function headings(): array
    {
        return [
            __('reports.messages.column_date'),
            __('reports.messages.column_recipient'),
            __('reports.messages.column_name'),
            __('reports.messages.column_channel'),
            __('reports.messages.column_status'),
            __('reports.messages.column_reason'),
            __('reports.messages.column_source'),
            __('reports.messages.column_sender'),
            __('reports.messages.column_excerpt'),
        ];
    }

    /**
     * @param  MessageLog  $row
     */
    public function map($row): array
    {
        return [
            optional($row->sent_at ?? $row->created_at)->format('Y-m-d H:i'),
            $row->recipient,
            $this->recipientName($row),
            $row->channel->label(),
            $row->status->label(),
            $row->status === MessageStatus::Failed ? (string) $row->error : '',
            $this->sourceLabel($row),
            $this->senderName($row),
            Str::limit((string) $row->body, 60),
        ];
    }

    public function totals(): array
    {
        return [
            'count' => $this->query()->count(),
            'sent' => $this->query()->where('status', MessageStatus::Sent)->count(),
            'failed' => $this->query()->where('status', MessageStatus::Failed)->count(),
            'pending' => $this->query()->where('status', MessageStatus::Pending)->count(),
            'sms' => $this->query()->where('channel', MessageChannel::Sms)->count(),
            'whatsapp' => $this->query()->where('channel', MessageChannel::WhatsApp)->count(),
        ];
    }

    public function title(): string
    {
        return __('reports.messages.title');
    }

    public function filename(string $ext): string
    {
        return 'messages-report-'.now()->format('Y-m-d').'.'.$ext;
    }

    public function pdfView(): string
    {
        return 'reports.pdf.messages';
    }

    /**
     * The beneficiary name matching this message's recipient number, if
     * any. Public so the Livewire preview can render it next to the raw
     * number without duplicating the lookup.
     */
    public function recipientName(MessageLog $row): ?string
    {
        return $this->recipientNames()[$row->recipient] ?? null;
    }

    /**
     * Human name of who triggered this send, or the system sender label
     * for automated aid/confirmation notifications.
     */
    public function senderName(MessageLog $row): string
    {
        if ($row->messageable_type === Broadcast::class) {
            return $this->senderNames()[$row->messageable_id] ?? __('reports.messages.system_sender');
        }

        return __('reports.messages.system_sender');
    }

    /**
     * Translated label for this message's origin (bulk broadcast vs an
     * automated lifecycle notification).
     */
    public function sourceLabel(MessageLog $row): string
    {
        return $row->messageable_type === Broadcast::class
            ? __('reports.messages.source_broadcast')
            : __('reports.messages.source_notification');
    }

    /**
     * Map of recipient mobile number => matching beneficiary full name,
     * built in one query over every distinct recipient in the filtered
     * result set and memoized for the lifetime of this Report instance.
     *
     * @return array<string, string>
     */
    private function recipientNames(): array
    {
        if ($this->recipientNamesCache !== null) {
            return $this->recipientNamesCache;
        }

        $mobiles = $this->query()->pluck('recipient')->filter()->unique()->values();

        if ($mobiles->isEmpty()) {
            return $this->recipientNamesCache = [];
        }

        return $this->recipientNamesCache = Beneficiary::query()
            ->whereIn('mobile', $mobiles)
            ->get(['mobile', 'first_name', 'second_name', 'third_name', 'last_name'])
            ->mapWithKeys(fn (Beneficiary $beneficiary): array => [$beneficiary->mobile => $beneficiary->full_name])
            ->all();
    }

    /**
     * Map of broadcast id => sender's user name, built in one query over
     * every distinct broadcast referenced by the filtered result set and
     * memoized for the lifetime of this Report instance.
     *
     * @return array<int, string|null>
     */
    private function senderNames(): array
    {
        if ($this->senderNamesCache !== null) {
            return $this->senderNamesCache;
        }

        $broadcastIds = $this->query()
            ->where('messageable_type', Broadcast::class)
            ->pluck('messageable_id')
            ->filter()
            ->unique()
            ->values();

        if ($broadcastIds->isEmpty()) {
            return $this->senderNamesCache = [];
        }

        return $this->senderNamesCache = Broadcast::query()
            ->whereIn('id', $broadcastIds)
            ->with('sender:id,name')
            ->get(['id', 'sent_by'])
            ->mapWithKeys(fn (Broadcast $broadcast): array => [$broadcast->id => $broadcast->sender?->name])
            ->all();
    }
}
