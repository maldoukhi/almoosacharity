<?php

use App\Livewire\Beneficiaries\Profile\FamilyMemberDetailModal;
use App\Models\Beneficiary;
use Livewire\Livewire;

it('shows a family member\'s details in the modal', function () {
    asDataEntry();

    $beneficiary = Beneficiary::factory()->create();
    $member = $beneficiary->familyMembers()->create([
        'name' => 'سارة محمد',
        'relation' => 'daughter',
        'birth_date' => '2012-03-01',
        'health_status' => 'سليمة',
        'education_status' => 'ابتدائي',
    ]);

    Livewire::test(FamilyMemberDetailModal::class, ['beneficiary' => $beneficiary, 'memberId' => $member->id])
        ->assertOk()
        ->assertSee('سارة محمد')
        ->assertSee('ابتدائي');
});

it('404s when the member does not belong to the beneficiary', function () {
    asDataEntry();

    $beneficiary = Beneficiary::factory()->create();
    $other = Beneficiary::factory()->create();
    $foreign = $other->familyMembers()->create([
        'name' => 'دخيل',
        'relation' => 'son',
    ]);

    Livewire::test(FamilyMemberDetailModal::class, ['beneficiary' => $beneficiary, 'memberId' => $foreign->id])
        ->assertStatus(404);
});
