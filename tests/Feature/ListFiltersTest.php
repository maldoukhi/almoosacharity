<?php

use App\Livewire\Aids\Index as AidsIndex;
use App\Livewire\Beneficiaries\Index as BeneficiariesIndex;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Aids/Index
|--------------------------------------------------------------------------
*/

it('has no active filters by default on the aids index', function () {
    asAdmin();

    Livewire::test(AidsIndex::class)
        ->assertSet('hasActiveFilters', false)
        ->assertSet('activeFiltersCount', 0);
});

it('reports active filters once any aids index filter is set', function () {
    asAdmin();

    Livewire::test(AidsIndex::class)
        ->set('search', 'AID-000001')
        ->assertSet('hasActiveFilters', true)
        ->assertSet('activeFiltersCount', 1);
});

it('counts every deviating filter on the aids index', function () {
    asAdmin();

    Livewire::test(AidsIndex::class)
        ->set('search', 'AID-000001')
        ->set('statusFilter', 'draft')
        ->set('typeFilter', 'cash')
        ->assertSet('activeFiltersCount', 3)
        ->assertSet('hasActiveFilters', true);
});

it('resets every aids index filter and the page back to defaults', function () {
    asAdmin();

    Livewire::test(AidsIndex::class)
        ->set('search', 'AID-000001')
        ->set('statusFilter', 'draft')
        ->set('programFilter', '1')
        ->set('typeFilter', 'cash')
        ->set('beneficiaryFilter', '1')
        ->set('receiptFilter', 'partial')
        ->call('nextPage')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('statusFilter', '')
        ->assertSet('programFilter', '')
        ->assertSet('typeFilter', '')
        ->assertSet('beneficiaryFilter', '')
        ->assertSet('receiptFilter', '')
        ->assertSet('hasActiveFilters', false)
        ->assertSet('activeFiltersCount', 0)
        ->assertSet('paginators.page', 1);
});

/*
|--------------------------------------------------------------------------
| Beneficiaries/Index
|--------------------------------------------------------------------------
*/

it('has no active filters by default on the beneficiaries index', function () {
    asAdmin();

    Livewire::test(BeneficiariesIndex::class)
        ->assertSet('hasActiveFilters', false)
        ->assertSet('activeFiltersCount', 0);
});

it('reports active filters once any beneficiaries index filter is set', function () {
    asAdmin();

    Livewire::test(BeneficiariesIndex::class)
        ->set('search', 'محمد')
        ->assertSet('hasActiveFilters', true)
        ->assertSet('activeFiltersCount', 1);
});

it('counts the trashed toggle as an active filter on the beneficiaries index', function () {
    asAdmin();

    Livewire::test(BeneficiariesIndex::class)
        ->set('trashed', true)
        ->assertSet('hasActiveFilters', true)
        ->assertSet('activeFiltersCount', 1);
});

it('resets every beneficiaries index filter, the selection, and the page back to defaults', function () {
    asAdmin();

    Livewire::test(BeneficiariesIndex::class)
        ->set('search', 'محمد')
        ->set('categoryFilter', '1')
        ->set('statusFilter', 'active')
        ->set('cityFilter', 'الرياض')
        ->set('trashed', true)
        ->set('selected', [1, 2])
        ->call('nextPage')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('categoryFilter', '')
        ->assertSet('statusFilter', '')
        ->assertSet('cityFilter', '')
        ->assertSet('trashed', false)
        ->assertSet('selected', [])
        ->assertSet('hasActiveFilters', false)
        ->assertSet('activeFiltersCount', 0)
        ->assertSet('paginators.page', 1);
});
