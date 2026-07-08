<?php

use App\Livewire\Beneficiaries\Profile\FamilyMemberModal;
use App\Livewire\Beneficiaries\Profile\FamilyMembers;
use App\Livewire\Beneficiaries\Show;
use App\Models\Beneficiary;
use Livewire\Livewire;

it('creates a family member via the modal and dispatches family-member-saved', function () {
    asDataEntry();

    $beneficiary = Beneficiary::factory()->create();

    Livewire::test(FamilyMemberModal::class, ['beneficiary' => $beneficiary])
        ->set('name', 'أحمد محمد')
        ->set('relation', 'son')
        ->set('birth_date', '')
        ->set('health_status', '')
        ->set('education_status', '')
        ->call('save')
        ->assertDispatched('family-member-saved')
        ->assertDispatched('toast');

    $beneficiary->refresh();

    expect($beneficiary->familyMembers)->toHaveCount(1);
    expect($beneficiary->familyMembers->first()->name)->toBe('أحمد محمد');
    // A blank birth_date must be normalized to null (regression: MySQL
    // strict mode rejects '' for a date column).
    expect($beneficiary->familyMembers->first()->birth_date)->toBeNull();
});

it('edits an existing family member via the modal, pre-filling its current data', function () {
    asDataEntry();

    $beneficiary = Beneficiary::factory()->create();

    $member = $beneficiary->familyMembers()->create([
        'name' => 'سارة محمد',
        'relation' => 'daughter',
        'birth_date' => '2010-05-01',
        'health_status' => 'سليمة',
        'education_status' => 'ابتدائي',
    ]);

    Livewire::test(FamilyMemberModal::class, ['beneficiary' => $beneficiary, 'memberId' => $member->id])
        ->assertSet('name', 'سارة محمد')
        ->assertSet('relation', 'daughter')
        ->assertSet('birth_date', '2010-05-01')
        ->set('name', 'سارة محمد المحدثة')
        ->set('birth_date', '')
        ->call('save')
        ->assertDispatched('family-member-saved');

    $member->refresh();

    expect($member->name)->toBe('سارة محمد المحدثة');
    expect($member->birth_date)->toBeNull();
    expect($beneficiary->familyMembers()->count())->toBe(1);
});

it('requires beneficiaries.update to open or save the family member modal', function () {
    asManager(); // has beneficiaries.view but not beneficiaries.update

    $beneficiary = Beneficiary::factory()->create();

    Livewire::test(FamilyMemberModal::class, ['beneficiary' => $beneficiary])
        ->assertForbidden();
});

it('refreshes the family members list when the modal dispatches family-member-saved', function () {
    asDataEntry();

    $beneficiary = Beneficiary::factory()->create();

    $list = Livewire::test(FamilyMembers::class, ['beneficiary' => $beneficiary]);

    expect($list->instance()->members())->toHaveCount(0);

    $beneficiary->familyMembers()->create([
        'name' => 'فرد جديد',
        'relation' => 'brother',
    ]);

    $list->dispatch('family-member-saved');

    expect($list->instance()->members())->toHaveCount(1);
});

it('refreshes the family-tree computed on Show when family-member-saved is dispatched', function () {
    asResearcher();

    $beneficiary = Beneficiary::factory()->create();

    $component = Livewire::test(Show::class, ['beneficiary' => $beneficiary]);

    expect($component->instance()->familyTreeNodes())->toHaveCount(0);

    $beneficiary->familyMembers()->create([
        'name' => 'أخ المستفيد',
        'relation' => 'brother',
    ]);

    $component->dispatch('family-member-saved');

    expect($component->instance()->familyTreeNodes())->toHaveCount(1);
});
