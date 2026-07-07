<?php

namespace App\Livewire\Beneficiaries;

use App\Actions\Beneficiaries\CreateBeneficiary;
use App\Actions\Beneficiaries\UpdateBeneficiary;
use App\Enums\BeneficiaryStatus;
use App\Enums\Gender;
use App\Enums\HousingType;
use App\Enums\IdType;
use App\Enums\MaritalStatus;
use App\Models\Beneficiary;
use App\Models\BeneficiaryCategory;
use App\Rules\SaudiIban;
use App\Rules\SaudiMobile;
use App\Rules\SaudiNationalId;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Beneficiary create/edit form.
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

        if (! $this->beneficiary?->exists) {
            return;
        }

        $this->first_name = $this->beneficiary->first_name;
        $this->second_name = (string) $this->beneficiary->second_name;
        $this->third_name = (string) $this->beneficiary->third_name;
        $this->last_name = $this->beneficiary->last_name;
        $this->id_type = $this->beneficiary->id_type->value;
        $this->national_id = $this->beneficiary->national_id;
        $this->nationality = $this->beneficiary->nationality;
        $this->birth_date = $this->beneficiary->birth_date?->format('Y-m-d') ?? '';
        $this->gender = $this->beneficiary->gender->value;
        $this->mobile = $this->beneficiary->mobile;
        $this->marital_status = $this->beneficiary->marital_status->value;
        $this->family_members_count = $this->beneficiary->family_members_count;
        $this->occupation = (string) $this->beneficiary->occupation;
        $this->employer = (string) $this->beneficiary->employer;
        $this->monthly_income = $this->beneficiary->monthly_income !== null
            ? (float) $this->beneficiary->monthly_income
            : null;
        $this->health_status = (string) $this->beneficiary->health_status;
        $this->special_needs = (string) $this->beneficiary->special_needs;
        $this->housing_type = $this->beneficiary->housing_type->value;
        $this->rent_amount = $this->beneficiary->rent_amount !== null
            ? (float) $this->beneficiary->rent_amount
            : null;
        $this->national_address = (string) $this->beneficiary->national_address;
        $this->city = $this->beneficiary->city;
        $this->district = (string) $this->beneficiary->district;
        // Bank fields (bank_name/iban/bank_account_holder) are deliberately
        // left blank: they are sensitive, never round-tripped into the
        // form, and an empty submission is treated by UpdateBeneficiary as
        // "keep the current value" rather than "clear it".
        $this->status = $this->beneficiary->status->value;
        $this->selectedCategories = $this->beneficiary->categories()
            ->pluck('beneficiary_categories.id')
            ->all();
    }

    /**
     * @return Collection<int, BeneficiaryCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return BeneficiaryCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Whether the current actor may write the bank fields on this form.
     * Checked as a raw permission (rather than the viewBankData/
     * manageBankData Policy abilities, which require a real Beneficiary
     * instance) so it also works safely in create mode.
     */
    #[Computed]
    public function canManageBank(): bool
    {
        return Gate::allows('beneficiaries.bank-data.manage');
    }

    public function save(): void
    {
        $isUpdate = $this->beneficiary?->exists ?? false;

        Gate::authorize($isUpdate ? 'update' : 'create', $this->beneficiary ?? Beneficiary::class);

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'second_name' => ['nullable', 'string', 'max:255'],
            'third_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'id_type' => ['required', Rule::enum(IdType::class)],
            'national_id' => [
                'required',
                'string',
                new SaudiNationalId,
                Rule::unique('beneficiaries', 'national_id')->ignore($this->beneficiary?->id),
            ],
            'nationality' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'mobile' => ['required', 'string', new SaudiMobile],
            'marital_status' => ['required', Rule::enum(MaritalStatus::class)],
            'family_members_count' => ['nullable', 'integer', 'min:0'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'employer' => ['nullable', 'string', 'max:255'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
            'health_status' => ['nullable', 'string'],
            'special_needs' => ['nullable', 'string'],
            'housing_type' => ['required', Rule::enum(HousingType::class)],
            'rent_amount' => [
                Rule::requiredIf(fn (): bool => $this->housing_type === HousingType::Rented->value),
                'nullable',
                'numeric',
                'min:0',
            ],
            'national_address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(BeneficiaryStatus::class)],
            'selectedCategories' => ['array'],
            'selectedCategories.*' => ['integer', 'exists:beneficiary_categories,id'],
        ];

        if ($this->canManageBank) {
            $rules['iban'] = ['nullable', new SaudiIban];
            $rules['bank_name'] = ['nullable', 'string', 'max:255'];
            $rules['bank_account_holder'] = ['nullable', 'string', 'max:255'];
        }

        $validated = $this->validate($rules);

        $categoryIds = $validated['selectedCategories'] ?? [];
        unset($validated['selectedCategories']);

        $actor = Auth::user();

        $beneficiary = $isUpdate
            ? app(UpdateBeneficiary::class)->handle($this->beneficiary, $validated, $categoryIds, $actor)
            : app(CreateBeneficiary::class)->handle($validated, $categoryIds, $actor);

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.messages.saved'));

        $this->redirectRoute('admin.beneficiaries.show', ['beneficiary' => $beneficiary->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.beneficiaries.form');
    }
}
