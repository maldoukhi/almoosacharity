<?php

use App\Enums\RoleName;
use App\Livewire\Settings\BeneficiaryFlows\Form as BeneficiaryFlowForm;
use App\Livewire\Settings\BeneficiaryFlows\Index as BeneficiaryFlowIndex;
use App\Models\BeneficiaryFlow;
use Livewire\Livewire;

it('lets a system-admin build a configurable beneficiary flow with ordered stages', function () {
    asAdmin();

    Livewire::test(BeneficiaryFlowForm::class)
        ->set('name', 'مسار مخصص')
        ->set('is_active', true)
        ->set('is_default', false)
        ->set('stages', [
            [
                'name' => 'مراجعة أولية',
                'order' => 1,
                'role' => RoleName::SocialResearcher->value,
                'assignee_user_ids' => [],
                'allowed_actions' => ['approve', 'reject', 'return'],
            ],
            [
                'name' => 'اعتماد',
                'order' => 2,
                'role' => RoleName::Manager->value,
                'assignee_user_ids' => [],
                'allowed_actions' => ['approve', 'reject'],
            ],
        ])
        ->call('save');

    $flow = BeneficiaryFlow::query()->where('name', 'مسار مخصص')->firstOrFail();

    expect($flow->stages()->count())->toBe(2);
    expect($flow->stages()->where('order', 1)->first()->role)->toBe(RoleName::SocialResearcher->value);
    expect($flow->stages()->where('order', 2)->first()->allowed_actions)->toBe(['approve', 'reject']);
});

it('rejects a stage that targets neither a role nor a specific user', function () {
    asAdmin();

    Livewire::test(BeneficiaryFlowForm::class)
        ->set('name', 'مسار ناقص')
        ->set('stages', [
            [
                'name' => 'مرحلة بلا مخوّل',
                'order' => 1,
                'role' => '',
                'assignee_user_ids' => [],
                'allowed_actions' => ['approve'],
            ],
        ])
        ->call('save')
        ->assertHasErrors('stages.0.role');

    expect(BeneficiaryFlow::query()->where('name', 'مسار ناقص')->exists())->toBeFalse();
});

it('forbids a user without beneficiaries.flows.configure from the flows builder', function () {
    asResearcher();

    Livewire::test(BeneficiaryFlowIndex::class)->assertForbidden();
    Livewire::test(BeneficiaryFlowForm::class)->assertForbidden();
});
