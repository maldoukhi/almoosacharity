<?php

use App\Livewire\Beneficiaries\Form;
use App\Livewire\Beneficiaries\Show;
use App\Models\Beneficiary;
use Livewire\Livewire;

it('shows a documents tab in the edit form and can render the nested documents component', function () {
    asDataEntry();

    $beneficiary = Beneficiary::factory()->create();

    Livewire::test(Form::class, ['beneficiary' => $beneficiary])
        ->assertSee(__('beneficiaries.tab.documents'))
        ->assertSee(__('beneficiaries.documents.field_type'));
});

it('shows a save-first empty state for the documents tab in the create form', function () {
    asDataEntry();

    Livewire::test(Form::class)
        ->assertSee(__('beneficiaries.tab.documents'))
        ->assertSee(__('beneficiaries.documents.create_first_title'))
        ->assertDontSee(__('beneficiaries.documents.field_type'));
});

it('groups family members into family-tree tiers with computed ages and spread x positions', function () {
    asResearcher();

    $beneficiary = Beneficiary::factory()->create();

    $husband = $beneficiary->familyMembers()->create([
        'name' => 'زوج المستفيدة',
        'relation' => 'husband',
        'birth_date' => now()->subYears(40),
    ]);

    $son = $beneficiary->familyMembers()->create([
        'name' => 'ابن المستفيدة',
        'relation' => 'son',
        'birth_date' => now()->subYears(10),
    ]);

    $brother = $beneficiary->familyMembers()->create([
        'name' => 'أخو المستفيدة',
        'relation' => 'brother',
        'birth_date' => null,
    ]);

    $beneficiary->refresh()->load('familyMembers');

    $component = Livewire::test(Show::class, ['beneficiary' => $beneficiary]);

    $nodes = collect($component->instance()->familyTreeNodes())->keyBy('id');

    expect($nodes[$husband->id]['tier'])->toBe('top');
    expect($nodes[$husband->id]['y'])->toBe(18.0);
    expect($nodes[$husband->id]['age'])->toBe(40);

    expect($nodes[$son->id]['tier'])->toBe('bottom');
    expect($nodes[$son->id]['y'])->toBe(78.0);
    expect($nodes[$son->id]['age'])->toBe(10);

    expect($nodes[$brother->id]['tier'])->toBe('side');
    expect($nodes[$brother->id]['y'])->toBe(50.0);
    expect($nodes[$brother->id]['age'])->toBeNull();

    $component->call('setTab', 'family')
        ->assertSee(__('beneficiaries.family_tree.view_tree'))
        ->assertSee(__('beneficiaries.family_tree.view_table'));
});

it('shows the family-tree empty state when the beneficiary has no family members', function () {
    asResearcher();

    $beneficiary = Beneficiary::factory()->create();

    Livewire::test(Show::class, ['beneficiary' => $beneficiary])
        ->call('setTab', 'family')
        ->assertSee(__('beneficiaries.family_tree.empty_title'));
});
