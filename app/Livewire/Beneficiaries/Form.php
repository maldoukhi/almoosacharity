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
use App\Support\Countries;
use App\Support\MobileNumber;
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

    /**
     * The workflow status is no longer chosen freely on this form: a new
     * beneficiary always starts at BeneficiaryStatus::New and then progresses
     * only through the controlled review actions (submit / approve / reject /
     * return) and the off-sequence suspend/deactivate/reactivate actions. The
     * property is kept (defaulting to New) purely so an existing status is
     * preserved on edit and never rewritten by this form.
     */
    public string $status = 'new';

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
        $this->housing_type = $this->beneficiary->housing_type?->value ?? '';
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
     * Nationality picker options: ISO code => localized country name,
     * Saudi Arabia and its neighboring/most common nationalities first.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function countryOptions(): array
    {
        return Countries::all();
    }

    /**
     * Normalizes a mobile number typed in any of the flexible formats the
     * field accepts (05XXXXXXXX, 5XXXXXXXX, +9665XXXXXXXX, 009665XXXXXXXX)
     * down to the canonical 05XXXXXXXX storage format expected by
     * {@see SaudiMobile}. Anything else is returned unchanged so it still
     * fails validation with a clear error instead of being silently
     * mangled.
     */
    private function normalizeMobile(string $raw): string
    {
        $value = MobileNumber::normalize($raw);

        // MobileNumber::normalize() only rewrites the internationally
        // prefixed variants (+966/00966/966). Also accept the bare local
        // number typed without its leading 0, e.g. "512345678".
        if (preg_match('/^5\d{8}$/', $value) === 1) {
            $value = '0'.$value;
        }

        return $value;
    }

    /**
     * Livewire hook: re-normalizes the mobile field to 05XXXXXXXX as soon
     * as the user finishes typing/leaves the field, so what they see
     * before submitting already matches the stored format.
     */
    public function updatedMobile(string $value): void
    {
        $this->mobile = $this->normalizeMobile($value);
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

        // Defensive re-normalization: updatedMobile() already normalizes on
        // every change, but this guarantees 05XXXXXXXX is what gets
        // validated/stored even if the property was set some other way.
        $this->mobile = $this->normalizeMobile($this->mobile);

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
            // No workflow-status rule: status is never set from this form's
            // free input. It is forced to New on create (below) and left
            // untouched on update so the review workflow stays the sole owner
            // of status progression.
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

        if ($isUpdate) {
            // Status is workflow-owned: it is never included in an update from
            // this form, so an in-flight or approved beneficiary keeps its
            // lifecycle state.
            $beneficiary = app(UpdateBeneficiary::class)->handle($this->beneficiary, $validated, $categoryIds, $actor);
        } else {
            // Every new registration starts at New and enters the review
            // workflow only via the explicit "submit for review" action.
            $validated['status'] = BeneficiaryStatus::New->value;
            $beneficiary = app(CreateBeneficiary::class)->handle($validated, $categoryIds, $actor);
        }

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.messages.saved'));

        $this->redirectRoute('admin.beneficiaries.show', $beneficiary, navigate: true);
    }

    public function render()
    {
        return view('livewire.beneficiaries.form');
    }
}
