<?php

namespace App\Actions\Beneficiaries;

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UpdateBeneficiary
{
    /**
     * Bank field names that require the `beneficiaries.bank-data.manage`
     * permission to change.
     *
     * @var array<int, string>
     */
    private const BANK_FIELDS = ['iban', 'bank_name', 'bank_account_holder'];

    /**
     * Nullable date/numeric columns: '' must become null (MySQL strict mode
     * rejects '' for these). String columns accept '' and are left alone.
     *
     * @var array<int, string>
     */
    private const NULLABLE_NUMERIC_FIELDS = ['birth_date', 'family_members_count', 'monthly_income', 'rent_amount'];

    /**
     * Update the given beneficiary's attributes and resync its categories.
     *
     * Bank fields (iban/bank_name/bank_account_holder) follow a "blank
     * means keep the current value" rule: an empty/missing submitted value
     * never clears an existing bank field. An actual change to a bank
     * field requires the actor to hold `beneficiaries.bank-data.manage`;
     * this is re-verified here as the source of truth even though the
     * Livewire Form already hides these fields for unprivileged actors.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $categoryIds
     *
     * @throws AuthorizationException
     */
    public function handle(Beneficiary $beneficiary, array $data, array $categoryIds, User $actor): Beneficiary
    {
        $data = $this->resolveBankFields($data, $actor);

        // A cleared birth_date/amount must be stored as null, not '' (MySQL
        // strict mode rejects '' for date/numeric columns). String columns
        // and the NOT NULL mobile column are left as submitted.
        foreach (self::NULLABLE_NUMERIC_FIELDS as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        return DB::transaction(function () use ($beneficiary, $data, $categoryIds): Beneficiary {
            $beneficiary->update($data);

            $beneficiary->categories()->sync($categoryIds);

            return $beneficiary->fresh(['categories']);
        });
    }

    /**
     * Strip bank fields that were submitted blank (keeping the current
     * stored value untouched) and authorize any bank field that was
     * submitted with an actual new value.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    private function resolveBankFields(array $data, User $actor): array
    {
        foreach (self::BANK_FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            if ($data[$field] === null || $data[$field] === '') {
                unset($data[$field]);

                continue;
            }

            if (! $actor->can('beneficiaries.bank-data.manage')) {
                throw new AuthorizationException(__('beneficiaries.messages.cannot_manage_bank_data'));
            }
        }

        return $data;
    }
}
