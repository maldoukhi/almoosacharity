<?php

namespace App\Livewire\Beneficiaries;

use App\Models\Beneficiary;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Skeleton for the beneficiary create/edit form. Field population,
 * validation and saving are implemented in phase 2b; this class only
 * wires up the public property contract, authorization and the target
 * view.
 */
class Form extends Component
{
    public ?Beneficiary $beneficiary = null;

    public string $first_name = '';

    public string $second_name = '';

    public string $third_name = '';

    public string $last_name = '';

    public string $id_type = 'national_id';

    public string $national_id = '';

    public string $nationality = 'SA';

    public string $birth_date = '';

    public string $gender = '';

    public string $mobile = '';

    public string $marital_status = '';

    public ?int $family_members_count = null;

    public string $occupation = '';

    public string $employer = '';

    public ?float $monthly_income = null;

    public string $health_status = '';

    public string $special_needs = '';

    public string $housing_type = '';

    public ?float $rent_amount = null;

    public string $national_address = '';

    public string $city = '';

    public string $district = '';

    public string $bank_name = '';

    public string $iban = '';

    public string $bank_account_holder = '';

    /** @var array<int, int> */
    public array $selectedCategories = [];

    public string $status = 'under_study';

    public function mount(?Beneficiary $beneficiary = null): void
    {
        $this->beneficiary = $beneficiary;

        Gate::authorize(
            $this->beneficiary?->exists ? 'update' : 'create',
            $this->beneficiary ?? Beneficiary::class,
        );
    }

    public function render()
    {
        return view('livewire.beneficiaries.form');
    }
}
