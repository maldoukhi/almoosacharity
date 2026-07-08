<?php

namespace App\Livewire\Beneficiaries;

use App\Enums\RelationKind;
use App\Models\Beneficiary;
use App\Models\BeneficiaryFamilyMember;
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
        $this->beneficiary = $beneficiary->load(['categories', 'familyMembers', 'incomeSources', 'creator']);

        Gate::authorize('view', $this->beneficiary);

        if (! in_array($this->activeTab, self::TABS, true)) {
            $this->activeTab = 'basic';
        }
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

    public function render()
    {
        return view('livewire.beneficiaries.show');
    }
}
