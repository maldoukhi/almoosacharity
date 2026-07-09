<?php

namespace App\Livewire\Aids\RecurringPlans;

use App\Actions\Aids\GenerateRecurringAids;
use App\Enums\RecurrenceFrequency;
use App\Models\Aid;
use App\Models\RecurringAidPlan;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Central management surface for recurring aid plans (phase 10). Lists every
 * plan — or, when {@see $beneficiaryId} is set, only one beneficiary's plans,
 * so the same component powers the beneficiary profile section — with
 * pause/resume, edit, delete and "view series" actions.
 *
 * Every read is scoped through the RecurringAidPlan → Aid relationship
 * ({@see plans()}, {@see seriesAids()}); every mutation is gated on the
 * matching aids.* permission and logged via activity().
 */
class Index extends Component
{
    use WithPagination;

    /**
     * When set, the list is scoped to this beneficiary's plans only (used by
     * the beneficiary profile embed). Null lists every plan (central page).
     */
    public ?int $beneficiaryId = null;

    /**
     * Renders without the full-page header/back chrome when embedded inside
     * another screen (the beneficiary profile).
     */
    public bool $embedded = false;

    /** The plan currently open in the edit modal, or null. */
    public ?int $editingPlanId = null;

    public string $editFrequency = 'monthly';

    public ?int $editIntervalMonths = null;

    public ?string $editStartsOn = null;

    public ?string $editDueOn = null;

    public ?string $editTitleTemplate = null;

    public ?string $editEndsOn = null;

    public int $editLeadDays = 0;

    public bool $editActive = true;

    /** The plan currently open in the "view series" modal, or null. */
    public ?int $viewingSeriesPlanId = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Aid::class);
    }

    /**
     * Recurring plans, newest first, scoped to {@see $beneficiaryId} through
     * the aid relationship when set.
     *
     * @return LengthAwarePaginator<int, RecurringAidPlan>
     */
    #[Computed]
    public function plans(): LengthAwarePaginator
    {
        return RecurringAidPlan::query()
            ->with([
                'aid' => fn ($query) => $query->withTrashed(),
                'aid.beneficiary:id,first_name,second_name,third_name,last_name',
                'aid.program:id,name',
            ])
            ->withCount('aids')
            ->when($this->beneficiaryId, function (Builder $query): void {
                $query->whereHas('aid', function (Builder $aid): void {
                    $aid->withTrashed()->where('beneficiary_id', $this->beneficiaryId);
                });
            })
            ->latest('id')
            ->paginate(10);
    }

    /**
     * The plan whose series is open in the "view series" modal, with its
     * generated aids eager-loaded.
     */
    #[Computed]
    public function seriesPlan(): ?RecurringAidPlan
    {
        if ($this->viewingSeriesPlanId === null) {
            return null;
        }

        return RecurringAidPlan::query()
            ->with(['aid.beneficiary', 'aid.program'])
            ->find($this->viewingSeriesPlanId);
    }

    /**
     * The generated aids that make up the open plan's series, newest first.
     *
     * @return Collection<int, Aid>
     */
    #[Computed]
    public function seriesAids(): Collection
    {
        $plan = $this->seriesPlan();

        if ($plan === null) {
            return collect();
        }

        return $plan->aids()
            ->with('program:id,name')
            ->withCount('items')
            ->latest('id')
            ->get();
    }

    /**
     * Pause or resume a plan (toggle is_active). Requires aids.update.
     */
    public function togglePause(int $planId): void
    {
        $this->authorizeManage();

        $plan = $this->scopedPlan($planId);

        $plan->update(['is_active' => ! $plan->is_active]);

        activity()
            ->performedOn($plan)
            ->causedBy(Auth::user())
            ->event($plan->is_active ? 'resumed' : 'paused')
            ->log('recurring_aid_plan.'.($plan->is_active ? 'resumed' : 'paused'));

        unset($this->plans, $this->seriesPlan);

        $this->dispatch('toast', type: 'success', message: __('recurring_aids.messages.'.($plan->is_active ? 'resumed' : 'paused')));
    }

    /**
     * Open the edit modal, pre-filling it from the plan. Requires aids.update.
     */
    public function openEdit(int $planId): void
    {
        $this->authorizeManage();

        $plan = $this->scopedPlan($planId);

        $this->editingPlanId = $plan->id;
        $this->editFrequency = $plan->frequency->value;
        $this->editIntervalMonths = $plan->interval_months;
        $this->editStartsOn = $plan->starts_on?->toDateString();
        $this->editDueOn = $plan->due_on?->toDateString();
        $this->editTitleTemplate = $plan->title_template;
        $this->editEndsOn = $plan->ends_on?->toDateString();
        $this->editLeadDays = $plan->lead_days;
        $this->editActive = $plan->is_active;
    }

    public function closeEdit(): void
    {
        $this->reset(['editingPlanId', 'editIntervalMonths', 'editStartsOn', 'editDueOn', 'editTitleTemplate', 'editEndsOn']);
        $this->resetValidation();
    }

    /**
     * Persist the edit modal. Requires aids.update.
     */
    public function saveEdit(): void
    {
        $this->authorizeManage();

        if ($this->editingPlanId === null) {
            return;
        }

        $validated = $this->validate([
            'editFrequency' => ['required', Rule::enum(RecurrenceFrequency::class)],
            'editIntervalMonths' => [
                'nullable', 'integer', 'min:1', 'max:60',
                Rule::requiredIf($this->editFrequency === RecurrenceFrequency::CustomMonths->value),
            ],
            'editStartsOn' => ['required', 'date'],
            'editDueOn' => ['nullable', 'date'],
            'editTitleTemplate' => ['nullable', 'string', 'max:255'],
            'editEndsOn' => ['nullable', 'date', 'after_or_equal:editStartsOn'],
            'editLeadDays' => ['required', 'integer', 'min:0', 'max:365'],
        ]);

        $plan = $this->scopedPlan($this->editingPlanId);

        $frequency = RecurrenceFrequency::from($validated['editFrequency']);
        $startsOn = CarbonImmutable::parse($validated['editStartsOn'])->startOfDay();
        $dueOn = ! empty($validated['editDueOn'])
            ? CarbonImmutable::parse($validated['editDueOn'])->startOfDay()
            : null;
        $endsOn = ! empty($validated['editEndsOn'])
            ? CarbonImmutable::parse($validated['editEndsOn'])->startOfDay()
            : null;

        // Keep the already-advanced next_run_on unless the start or due date
        // moved; a moved anchor re-seeds the schedule on due_on ?? starts_on.
        $startMoved = $plan->starts_on?->toDateString() !== $startsOn->toDateString();
        $dueMoved = $plan->due_on?->toDateString() !== $dueOn?->toDateString();
        $nextRunOn = ($startMoved || $dueMoved)
            ? ($dueOn ?? $startsOn)
            : $plan->next_run_on;

        $plan->update([
            'frequency' => $frequency,
            'interval_months' => $frequency->isCustom() ? $validated['editIntervalMonths'] : null,
            'starts_on' => $startsOn,
            'due_on' => $dueOn,
            'title_template' => $validated['editTitleTemplate'] ?: null,
            'ends_on' => $endsOn,
            'next_run_on' => $nextRunOn,
            'lead_days' => (int) $validated['editLeadDays'],
            'is_active' => $this->editActive,
        ]);

        activity()
            ->performedOn($plan)
            ->causedBy(Auth::user())
            ->event('updated')
            ->log('recurring_aid_plan.updated');

        unset($this->plans, $this->seriesPlan);

        $this->closeEdit();

        $this->dispatch('toast', type: 'success', message: __('recurring_aids.messages.saved'));
    }

    /**
     * Delete a plan. The aids it already generated are detached (nullOnDelete)
     * and kept. Requires aids.delete.
     */
    public function delete(int $planId): void
    {
        abort_unless(Auth::user()->can('aids.delete'), 403);

        $plan = $this->scopedPlan($planId);

        activity()
            ->performedOn($plan)
            ->causedBy(Auth::user())
            ->event('deleted')
            ->log('recurring_aid_plan.deleted');

        $plan->delete();

        if ($this->viewingSeriesPlanId === $planId) {
            $this->viewingSeriesPlanId = null;
        }

        unset($this->plans, $this->seriesPlan, $this->seriesAids);

        $this->dispatch('toast', type: 'success', message: __('recurring_aids.messages.deleted'));
    }

    public function viewSeries(int $planId): void
    {
        // Ensure the plan is within scope before revealing its series.
        $this->scopedPlan($planId);

        $this->viewingSeriesPlanId = $planId;

        unset($this->seriesPlan, $this->seriesAids);
    }

    public function closeSeries(): void
    {
        $this->viewingSeriesPlanId = null;
    }

    /**
     * Pause a plan directly from its series modal. Requires aids.update.
     */
    public function pauseFromSeries(int $planId): void
    {
        $this->authorizeManage();

        $plan = $this->scopedPlan($planId);

        if (! $plan->is_active) {
            return;
        }

        $plan->update(['is_active' => false]);

        activity()
            ->performedOn($plan)
            ->causedBy(Auth::user())
            ->event('paused')
            ->log('recurring_aid_plan.paused');

        unset($this->plans, $this->seriesPlan);

        $this->dispatch('toast', type: 'success', message: __('recurring_aids.messages.paused'));
    }

    /**
     * Add an extra, off-cycle aid to a plan's series for special cases —
     * clone the source aid into a fresh draft linked to the series WITHOUT
     * advancing the schedule. Requires aids.update.
     */
    public function addManual(int $planId): void
    {
        $this->authorizeManage();

        $plan = $this->scopedPlan($planId);
        $plan->load(['aid.items', 'aid.program', 'aid.beneficiary']);

        if ($plan->aid === null) {
            $this->dispatch('toast', type: 'error', message: __('recurring_aids.messages.source_missing'));

            return;
        }

        app(GenerateRecurringAids::class)->cloneIntoSeries(
            $plan,
            Auth::user(),
            $plan->next_run_on?->toImmutable() ?? CarbonImmutable::now()->startOfDay(),
        );

        activity()
            ->performedOn($plan)
            ->causedBy(Auth::user())
            ->event('manual_added')
            ->log('recurring_aid_plan.manual_added');

        unset($this->plans, $this->seriesPlan, $this->seriesAids);

        $this->dispatch('toast', type: 'success', message: __('recurring_aids.messages.manual_added'));
    }

    /**
     * Fetch a plan within the component's current scope (beneficiary or
     * global), 404-ing on anything outside it so a forged id can never reach
     * another beneficiary's plan.
     */
    private function scopedPlan(int $planId): RecurringAidPlan
    {
        return RecurringAidPlan::query()
            ->when($this->beneficiaryId, function (Builder $query): void {
                $query->whereHas('aid', function (Builder $aid): void {
                    $aid->withTrashed()->where('beneficiary_id', $this->beneficiaryId);
                });
            })
            ->findOrFail($planId);
    }

    private function authorizeManage(): void
    {
        abort_unless(Auth::user()->can('aids.update'), 403);
    }

    public function render(): View
    {
        return view('livewire.aids.recurring-plans.index');
    }
}
