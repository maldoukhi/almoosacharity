<?php

namespace App\Livewire\Beneficiaries;

use App\Actions\BeneficiaryFlows\DeactivateBeneficiary;
use App\Actions\BeneficiaryFlows\SubmitBeneficiary;
use App\Enums\ApprovalAction;
use App\Enums\BeneficiaryStatus;
use App\Enums\RelationKind;
use App\Enums\RoleName;
use App\Exceptions\InvalidBeneficiaryTransitionException;
use App\Models\Beneficiary;
use App\Models\BeneficiaryDecision;
use App\Models\BeneficiaryFamilyMember;
use App\Models\BeneficiaryFlow;
use App\Models\BeneficiaryFlowStage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Beneficiary profile screen: tabbed detail view delegating each tab's
 * content to a nested component under Beneficiaries\Profile.
 */
class Show extends Component
{
    /**
     * Tabs allowed for #[Url]-bound navigation; anything else is ignored.
     *
     * @var array<int, string>
     */
    private const TABS = ['basic', 'family', 'housing-income', 'bank', 'documents', 'activity'];

    public Beneficiary $beneficiary;

    #[Url]
    public string $activeTab = 'basic';

    public function mount(Beneficiary $beneficiary): void
    {
        $this->beneficiary = $beneficiary;

        Gate::authorize('view', $this->beneficiary);

        $this->eagerLoad();

        if (! in_array($this->activeTab, self::TABS, true)) {
            $this->activeTab = 'basic';
        }
    }

    private function eagerLoad(): void
    {
        $this->beneficiary->load([
            'categories', 'familyMembers', 'incomeSources', 'creator',
            'beneficiaryFlow.stages', 'currentStage', 'decisions.user',
        ]);
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->activeTab = $tab;
        }
    }

    /**
     * Keep the family-tree visual in sync with FamilyMemberModal (add/
     * edit): reload the relation and drop the cached tree-node computed
     * so it is rebuilt from the fresh data on the next render.
     */
    #[On('family-member-saved')]
    public function refreshFamily(): void
    {
        $this->beneficiary->load('familyMembers');
        unset($this->familyTreeNodes);
    }

    /**
     * Family members positioned for the interactive family-tree visual.
     * Each entry carries x/y percentages (0-100, matching an
     * `viewBox="0 0 100 100"` SVG plus absolutely-positioned node cards)
     * and a computed age from birth_date. Members are grouped by
     * RelationKind into four tiers: spouses/parents above the
     * beneficiary, siblings to the sides, children below, and anything
     * else (Other) near the bottom.
     *
     * @return array<int, array{id: int, name: string, relation: ?string, age: ?int, x: float, y: float, tier: string}>
     */
    #[Computed]
    public function familyTreeNodes(): array
    {
        $tierKinds = [
            'top' => [RelationKind::Wife, RelationKind::Husband, RelationKind::Mother, RelationKind::Father],
            'side' => [RelationKind::Brother, RelationKind::Sister],
            'bottom' => [RelationKind::Son, RelationKind::Daughter],
            'other' => [RelationKind::Other],
        ];

        $byTier = ['top' => [], 'side' => [], 'bottom' => [], 'other' => []];

        foreach ($this->beneficiary->familyMembers as $member) {
            $tier = 'other';

            foreach ($tierKinds as $candidateTier => $kinds) {
                if (in_array($member->relation, $kinds, true)) {
                    $tier = $candidateTier;
                    break;
                }
            }

            $byTier[$tier][] = $member;
        }

        $nodes = [];

        foreach ($byTier['top'] as $index => $member) {
            $nodes[] = $this->spreadNode($member, 'top', $index, count($byTier['top']), y: 18.0);
        }

        foreach ($byTier['side'] as $index => $member) {
            $nodes[] = $this->sideNode($member, $index);
        }

        foreach ($byTier['bottom'] as $index => $member) {
            $nodes[] = $this->spreadNode($member, 'bottom', $index, count($byTier['bottom']), y: 78.0);
        }

        foreach ($byTier['other'] as $index => $member) {
            $nodes[] = $this->spreadNode($member, 'other', $index, count($byTier['other']), y: 90.0);
        }

        return $nodes;
    }

    /**
     * Evenly spreads a tier's members left-to-right across the canvas at a
     * fixed vertical position ($y).
     *
     * @return array{id: int, name: string, relation: ?string, age: ?int, x: float, y: float, tier: string}
     */
    private function spreadNode(BeneficiaryFamilyMember $member, string $tier, int $index, int $count, float $y): array
    {
        $x = $count === 1 ? 50.0 : 12.0 + ($index * (76.0 / max($count - 1, 1)));

        return $this->nodePayload($member, $tier, $x, $y);
    }

    /**
     * Places siblings alternately on either side of the beneficiary, at
     * the same vertical center, spreading further out as more are added.
     *
     * @return array{id: int, name: string, relation: ?string, age: ?int, x: float, y: float, tier: string}
     */
    private function sideNode(BeneficiaryFamilyMember $member, int $index): array
    {
        $direction = $index % 2 === 0 ? 1 : -1;
        $slot = intdiv($index, 2);
        $x = 50.0 + $direction * (34.0 + $slot * 14.0);

        return $this->nodePayload($member, 'side', max(4.0, min(96.0, $x)), 50.0);
    }

    /**
     * @return array{id: int, name: string, relation: ?string, age: ?int, x: float, y: float, tier: string}
     */
    private function nodePayload(BeneficiaryFamilyMember $member, string $tier, float $x, float $y): array
    {
        return [
            'id' => $member->id,
            'name' => $member->name,
            'relation' => $member->relation?->label(),
            'age' => $member->birth_date?->age,
            'x' => $x,
            'y' => $y,
            'tier' => $tier,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Review lifecycle (mirrors the aid approval-flow subsystem)
    |--------------------------------------------------------------------------
    */

    /**
     * The ordered stages of the flow that applies to this beneficiary — its
     * own submitted-flow snapshot once it exists, otherwise the default
     * active flow so the stepper can be previewed before submission — shaped
     * for x-ui.stepper (label/meta pairs).
     *
     * @return Collection<int, array{label: string, meta: ?string}>
     */
    #[Computed]
    public function stages(): Collection
    {
        return $this->resolveStages()->map(fn (BeneficiaryFlowStage $stage): array => [
            'label' => $stage->name,
            'meta' => RoleName::tryFrom((string) $stage->role)?->label() ?? $stage->role,
        ]);
    }

    /**
     * Zero-based position of the beneficiary's current stage within
     * {@see stages}, or null if there is no current stage.
     */
    #[Computed]
    public function currentStageIndex(): ?int
    {
        if (! $this->beneficiary->current_stage_id) {
            return null;
        }

        $index = $this->resolveStages()->search(
            fn (BeneficiaryFlowStage $stage): bool => $stage->id === $this->beneficiary->current_stage_id,
        );

        return $index === false ? null : $index;
    }

    /**
     * Terminal outcome coloring for the stepper: 'approved' once Active,
     * 'rejected' once Rejected, otherwise null.
     */
    #[Computed]
    public function finalStatus(): ?string
    {
        return match ($this->beneficiary->status) {
            BeneficiaryStatus::Active => 'approved',
            BeneficiaryStatus::Rejected => 'rejected',
            default => null,
        };
    }

    /**
     * The recorded decisions, newest first, shaped for x-ui.timeline.
     *
     * @return Collection<int, BeneficiaryDecision>
     */
    #[Computed]
    public function timeline(): Collection
    {
        return $this->beneficiary->decisions;
    }

    /**
     * @return Collection<int, BeneficiaryFlowStage>
     */
    private function resolveStages(): Collection
    {
        $flow = $this->beneficiary->beneficiaryFlow
            ?? BeneficiaryFlow::query()->default()->where('is_active', true)->first();

        return $flow?->stages ?? collect();
    }

    #[Computed]
    public function canSubmit(): bool
    {
        return $this->beneficiary->status->isSubmittable()
            && Gate::allows('submit', $this->beneficiary);
    }

    #[Computed]
    public function canReview(): bool
    {
        return $this->beneficiary->status === BeneficiaryStatus::UnderReview
            && $this->beneficiary->current_stage_id !== null
            && Gate::allows('review', $this->beneficiary);
    }

    /**
     * The review actions allowed at the beneficiary's current stage.
     *
     * @return array<int, ApprovalAction>
     */
    #[Computed]
    public function allowedActions(): array
    {
        $stage = $this->beneficiary->currentStage;

        if (! $stage) {
            return [];
        }

        return collect(ApprovalAction::cases())
            ->filter(fn (ApprovalAction $action): bool => $stage->allows($action))
            ->values()
            ->all();
    }

    #[Computed]
    public function canDeactivate(): bool
    {
        return Gate::allows('deactivate', $this->beneficiary);
    }

    #[Computed]
    public function canSuspend(): bool
    {
        return Gate::allows('suspend', $this->beneficiary);
    }

    #[Computed]
    public function canReactivate(): bool
    {
        return $this->beneficiary->status !== BeneficiaryStatus::Active
            && Gate::allows('reactivate', $this->beneficiary);
    }

    /**
     * The note from the most recent "return" decision, surfaced as a heads-up
     * while the beneficiary is back at New awaiting rework.
     */
    #[Computed]
    public function latestReturnNote(): ?string
    {
        if (! $this->beneficiary->status->isSubmittable()) {
            return null;
        }

        return $this->beneficiary->decisions
            ->firstWhere('action', ApprovalAction::Return)
            ?->note;
    }

    public function submitForReview(): void
    {
        Gate::authorize('submit', $this->beneficiary);

        try {
            app(SubmitBeneficiary::class)->handle($this->beneficiary, Auth::user());
        } catch (InvalidBeneficiaryTransitionException|AuthorizationException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.flow.messages.submitted'));

        $this->refreshLifecycle();
    }

    public function deactivate(): void
    {
        Gate::authorize('deactivate', $this->beneficiary);

        $this->applyOffSequence(fn (DeactivateBeneficiary $action) => $action->handle($this->beneficiary, Auth::user()), 'deactivated');
    }

    public function suspend(): void
    {
        Gate::authorize('suspend', $this->beneficiary);

        $this->applyOffSequence(fn (DeactivateBeneficiary $action) => $action->suspend($this->beneficiary, Auth::user()), 'suspended');
    }

    public function reactivate(): void
    {
        Gate::authorize('reactivate', $this->beneficiary);

        $this->applyOffSequence(fn (DeactivateBeneficiary $action) => $action->reactivate($this->beneficiary, Auth::user()), 'reactivated');
    }

    private function applyOffSequence(callable $callback, string $messageKey): void
    {
        try {
            $callback(app(DeactivateBeneficiary::class));
        } catch (InvalidBeneficiaryTransitionException|AuthorizationException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.flow.messages.'.$messageKey));

        $this->refreshLifecycle();
    }

    #[On('beneficiary-reviewed')]
    public function refreshLifecycle(): void
    {
        $this->beneficiary->refresh();

        $this->eagerLoad();

        unset(
            $this->stages,
            $this->currentStageIndex,
            $this->finalStatus,
            $this->timeline,
            $this->canSubmit,
            $this->canReview,
            $this->allowedActions,
            $this->canDeactivate,
            $this->canSuspend,
            $this->canReactivate,
            $this->latestReturnNote,
        );
    }

    public function render()
    {
        return view('livewire.beneficiaries.show');
    }
}
