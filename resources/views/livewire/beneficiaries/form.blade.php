@php
    $isEdit = $beneficiary?->exists ?? false;

    $idTypeOptions = collect(\App\Enums\IdType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]);
    $genderOptions = collect(\App\Enums\Gender::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]);
    $maritalStatusOptions = collect(\App\Enums\MaritalStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]);
    $housingTypeOptions = collect(\App\Enums\HousingType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]);
    $beneficiaryStatusOptions = collect(\App\Enums\BeneficiaryStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]);

    $basicFields = ['first_name', 'second_name', 'third_name', 'last_name', 'id_type', 'national_id', 'nationality', 'birth_date', 'gender', 'marital_status', 'family_members_count'];
    $contactFields = ['mobile', 'occupation', 'employer', 'health_status', 'special_needs'];
    $housingFields = ['housing_type', 'rent_amount', 'national_address', 'city', 'district', 'monthly_income'];
    $bankFields = ['bank_name', 'iban', 'bank_account_holder'];
    $categoryFields = ['selectedCategories'];

    $tabErrors = [
        'basic' => $errors->hasAny($basicFields),
        'contact_work' => $errors->hasAny($contactFields),
        'housing_income' => $errors->hasAny($housingFields),
        'bank' => $errors->hasAny($bankFields),
        'categories' => $errors->hasAny($categoryFields),
    ];

    $tabs = [
        'basic' => __('beneficiaries.tab.basic'),
        'contact_work' => __('beneficiaries.tab.contact_work'),
        'housing_income' => __('beneficiaries.tab.housing_income'),
    ];

    if ($this->canManageBank) {
        $tabs['bank'] = __('beneficiaries.tab.bank');
    }

    $tabs['documents'] = __('beneficiaries.tab.documents');
    $tabs['categories'] = __('beneficiaries.tab.categories');
@endphp

<div class="space-y-6" x-data="{ activeTab: 'basic', housingType: @entangle('housing_type') }">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ $isEdit ? __('beneficiaries.edit_title') : __('beneficiaries.create_title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $isEdit ? __('beneficiaries.edit_subtitle') : __('beneficiaries.create_subtitle') }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div class="w-48">
                <x-ui.select
                    name="status"
                    wire:model="status"
                    :options="$beneficiaryStatusOptions"
                />
            </div>

            <x-ui.button href="{{ route('admin.beneficiaries.index') }}" variant="ghost">
                {{ __('common.back') }}
            </x-ui.button>
        </div>
    </div>

    <x-ui.card>
        <form wire:submit="save" class="space-y-5">
            <x-ui.tabs :tabs="$tabs" :tab-errors="$tabErrors" />

            {{-- أساسي --}}
            <div x-show="activeTab === 'basic'" x-transition.opacity.duration.200ms class="grid grid-cols-1 gap-5 pt-2 sm:grid-cols-2 lg:grid-cols-3">
                <x-ui.input :label="__('beneficiaries.field_first_name')" name="first_name" wire:model="first_name" autofocus />
                <x-ui.input :label="__('beneficiaries.field_second_name')" name="second_name" wire:model="second_name" />
                <x-ui.input :label="__('beneficiaries.field_third_name')" name="third_name" wire:model="third_name" />
                <x-ui.input :label="__('beneficiaries.field_last_name')" name="last_name" wire:model="last_name" />

                <x-ui.select
                    :label="__('beneficiaries.field_id_type')"
                    name="id_type"
                    wire:model="id_type"
                    :options="$idTypeOptions"
                />

                <x-ui.input :label="__('beneficiaries.field_national_id')" name="national_id" wire:model="national_id" dir="ltr" class="font-mono" />
                <x-ui.input :label="__('beneficiaries.field_nationality')" name="nationality" wire:model="nationality" />
                <x-ui.input :label="__('beneficiaries.field_birth_date')" name="birth_date" type="date" wire:model="birth_date" />

                <x-ui.select
                    :label="__('beneficiaries.field_gender')"
                    name="gender"
                    wire:model="gender"
                    :placeholder="__('beneficiaries.select_placeholder')"
                    :options="$genderOptions"
                />

                <x-ui.select
                    :label="__('beneficiaries.field_marital_status')"
                    name="marital_status"
                    wire:model="marital_status"
                    :placeholder="__('beneficiaries.select_placeholder')"
                    :options="$maritalStatusOptions"
                />

                <x-ui.input :label="__('beneficiaries.field_family_members_count')" name="family_members_count" type="number" min="0" wire:model="family_members_count" />
            </div>

            {{-- التواصل والعمل --}}
            <div x-show="activeTab === 'contact_work'" x-transition.opacity.duration.200ms class="grid grid-cols-1 gap-5 pt-2 sm:grid-cols-2">
                <x-ui.input :label="__('beneficiaries.field_mobile')" name="mobile" wire:model="mobile" dir="ltr" />
                <x-ui.input :label="__('beneficiaries.field_occupation')" name="occupation" wire:model="occupation" />
                <x-ui.input :label="__('beneficiaries.field_employer')" name="employer" wire:model="employer" />

                <div></div>

                <div class="sm:col-span-2">
                    <label for="health_status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ __('beneficiaries.field_health_status') }}
                    </label>
                    <textarea
                        id="health_status"
                        wire:model="health_status"
                        rows="3"
                        class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition duration-200 ease-out focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                    ></textarea>
                    @error('health_status')
                        <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="special_needs" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ __('beneficiaries.field_special_needs') }}
                    </label>
                    <textarea
                        id="special_needs"
                        wire:model="special_needs"
                        rows="3"
                        class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition duration-200 ease-out focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                    ></textarea>
                    @error('special_needs')
                        <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- السكن والدخل --}}
            <div x-show="activeTab === 'housing_income'" x-transition.opacity.duration.200ms class="grid grid-cols-1 gap-5 pt-2 sm:grid-cols-2">
                <x-ui.select
                    :label="__('beneficiaries.field_housing_type')"
                    name="housing_type"
                    wire:model="housing_type"
                    :placeholder="__('beneficiaries.select_placeholder')"
                    :options="$housingTypeOptions"
                />

                <div x-show="housingType === 'rented'" x-transition.opacity.duration.200ms>
                    <x-ui.input :label="__('beneficiaries.field_rent_amount')" name="rent_amount" type="number" step="0.01" min="0" wire:model="rent_amount" />
                </div>

                <x-ui.input :label="__('beneficiaries.field_national_address')" name="national_address" wire:model="national_address" />
                <x-ui.input :label="__('beneficiaries.field_city')" name="city" wire:model="city" />
                <x-ui.input :label="__('beneficiaries.field_district')" name="district" wire:model="district" />
                <x-ui.input :label="__('beneficiaries.field_monthly_income')" name="monthly_income" type="number" step="0.01" min="0" wire:model="monthly_income" />
            </div>

            {{-- البنك --}}
            @if ($this->canManageBank)
                <div x-show="activeTab === 'bank'" x-transition.opacity.duration.200ms class="space-y-5 pt-2">
                    <div class="flex items-start gap-3 rounded-(--radius-brand) bg-accent-50 px-4 py-3 text-sm text-accent-800 dark:bg-accent-500/10 dark:text-accent-200">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <span>{{ __('beneficiaries.bank_hint_keep') }}</span>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-ui.input :label="__('beneficiaries.field_bank_name')" name="bank_name" wire:model="bank_name" />
                        <x-ui.input :label="__('beneficiaries.field_bank_account_holder')" name="bank_account_holder" wire:model="bank_account_holder" />

                        <div class="sm:col-span-2">
                            <x-ui.input :label="__('beneficiaries.field_iban')" name="iban" wire:model="iban" dir="ltr" class="font-mono" />
                        </div>
                    </div>
                </div>
            @endif

            {{-- المستندات --}}
            <div x-show="activeTab === 'documents'" x-transition.opacity.duration.200ms class="pt-2">
                @if ($isEdit)
                    @livewire('beneficiaries.profile.documents', ['beneficiary' => $beneficiary], key('documents-form-'.$beneficiary->id))
                @else
                    <x-ui.empty-state :title="__('beneficiaries.documents.create_first_title')" :description="__('beneficiaries.documents.create_first_description')">
                        <x-slot:icon>
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-1.519-3.769a1 1 0 0 0-.363.363m1.882 3.406L11.7 14.5m0 0-2.2 2.2m2.2-2.2 2.2 2.2" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18.75V4.5a2.25 2.25 0 0 1 2.25-2.25h6.879a1.5 1.5 0 0 1 1.06.44l3.622 3.62a1.5 1.5 0 0 1 .44 1.061V18.75a2.25 2.25 0 0 1-2.25 2.25H8.25a2.25 2.25 0 0 1-2.25-2.25Z" />
                            </svg>
                        </x-slot:icon>
                    </x-ui.empty-state>
                @endif
            </div>

            {{-- التصنيفات --}}
            <div x-show="activeTab === 'categories'" x-transition.opacity.duration.200ms class="pt-2">
                <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('beneficiaries.field_categories') }}</p>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->categories as $category)
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-(--radius-brand) border border-gray-200 px-3.5 py-2.5 text-sm text-gray-700 transition duration-150 ease-out hover:bg-gray-50 has-checked:border-primary-300 has-checked:bg-primary-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5 dark:has-checked:border-primary-700 dark:has-checked:bg-primary-900/30">
                            <input
                                type="checkbox"
                                wire:model="selectedCategories"
                                value="{{ $category->id }}"
                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary-500/30 dark:border-white/20"
                            />
                            {{ $category->name }}
                        </label>
                    @endforeach
                </div>

                @error('selectedCategories')
                    <p class="mt-2 text-xs text-status-rejected">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5 dark:border-white/10">
                <x-ui.button href="{{ route('admin.beneficiaries.index') }}" variant="ghost">
                    {{ __('common.cancel') }}
                </x-ui.button>

                <x-ui.button type="submit" variant="primary" wire:target="save">
                    {{ __('common.save') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
