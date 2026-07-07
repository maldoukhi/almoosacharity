<?php

namespace App\Reports;

use App\Enums\AidType;
use App\Models\Aid;
use App\Reports\Contracts\Report;
use App\Reports\Filters\AidsFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * Detailed aids report: one row per aid, with its beneficiary/program and
 * a cash/in-kind value breakdown. Soft-deleted aids are excluded by
 * Eloquent's default global scope.
 */
final class AidsReport implements Report
{
    public function __construct(private readonly AidsFilter $filter) {}

    public function authorize(): bool
    {
        return Gate::allows('reports.view');
    }

    /**
     * @return Builder<Aid>
     */
    public function query(): Builder
    {
        return Aid::query()
            ->with([
                'beneficiary:id,first_name,second_name,third_name,last_name',
                'program:id,name',
                'items:id,aid_id,quantity,estimated_value',
            ])
            ->when($this->filter->from, fn (Builder $q) => $q->whereDate('created_at', '>=', $this->filter->from))
            ->when($this->filter->to, fn (Builder $q) => $q->whereDate('created_at', '<=', $this->filter->to))
            ->when($this->filter->status, fn (Builder $q) => $q->where('status', $this->filter->status))
            ->when($this->filter->programId, fn (Builder $q) => $q->where('aid_program_id', $this->filter->programId))
            ->when($this->filter->type, fn (Builder $q) => $q->where('type', $this->filter->type))
            // NOTE: deliveryMethod is intentionally not applied yet — the
            // aids table has no delivery_method column until the
            // disbursement/delivery domain (built in parallel) lands.
            ->latest();
    }

    public function headings(): array
    {
        return [
            __('reports.aids.column_reference'),
            __('reports.aids.column_beneficiary'),
            __('reports.aids.column_program'),
            __('reports.aids.column_type'),
            __('reports.aids.column_status'),
            __('reports.aids.column_amount'),
            __('reports.aids.column_items_value'),
            __('reports.aids.column_submitted_at'),
            __('reports.aids.column_decided_at'),
        ];
    }

    /**
     * @param  Aid  $row
     */
    public function map($row): array
    {
        return [
            $row->reference,
            $row->beneficiary?->full_name,
            $row->program?->name,
            $row->type->label(),
            $row->status->label(),
            $row->type === AidType::Cash ? (float) $row->amount : null,
            $row->type === AidType::InKind ? $this->itemsValue($row) : null,
            optional($row->submitted_at)->format('Y-m-d'),
            optional($row->decided_at)->format('Y-m-d'),
        ];
    }

    public function totals(): array
    {
        $rows = $this->query()->with('items')->get();

        return [
            'count' => $rows->count(),
            'cash_total' => (float) $rows->where('type', AidType::Cash)->sum(fn (Aid $aid): float => (float) $aid->amount),
            'in_kind_total' => (float) $rows->where('type', AidType::InKind)->sum(fn (Aid $aid): float => $this->itemsValue($aid)),
        ];
    }

    public function title(): string
    {
        return __('reports.aids.title');
    }

    public function filename(string $ext): string
    {
        return 'aids-report-'.now()->format('Y-m-d').'.'.$ext;
    }

    public function pdfView(): string
    {
        return 'reports.pdf.aids';
    }

    /**
     * Σ quantity × estimated_value across an in-kind aid's items, computed
     * in PHP over the already-loaded `items` relation (no MySQL-specific
     * SQL aggregation).
     */
    private function itemsValue(Aid $aid): float
    {
        return (float) $aid->items->sum(fn ($item): float => $item->quantity * (float) $item->estimated_value);
    }
}
