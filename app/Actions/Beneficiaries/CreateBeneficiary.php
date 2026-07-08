<?php

namespace App\Actions\Beneficiaries;

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CreateBeneficiary
{
    /**
     * Bank field names that require the `beneficiaries.bank-data.manage`
     * permission to write.
     *
     * @var array<int, string>
     */
    private const BANK_FIELDS = ['iban', 'bank_name', 'bank_account_holder'];

    /**
     * Nullable date/numeric columns: MySQL strict mode rejects '' for these,
     * so a blank submission must be stored as null. String columns are left
     * alone (they accept '' and some, like mobile, are NOT NULL).
     *
     * @var array<int, string>
     */
    private const NULLABLE_NUMERIC_FIELDS = ['birth_date', 'family_members_count', 'monthly_income', 'rent_amount'];

    /**
     * Create a new beneficiary from the given validated attributes, syncing
     * the given category ids and stamping `created_by` with the actor.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $categoryIds
     *
     * @throws AuthorizationException
     */
    public function handle(array $data, array $categoryIds, User $actor): Beneficiary
    {
        $this->guardBankFields($data, $actor);

        $data = $this->nullifyBlankNumericFields($data);

        return DB::transaction(function () use ($data, $categoryIds, $actor): Beneficiary {
            $beneficiary = Beneficiary::create([
                ...$data,
                'created_by' => $actor->id,
            ]);

            $beneficiary->categories()->sync($categoryIds);

            return $beneficiary->fresh(['categories']);
        });
    }

    /**
     * Bank fields may only be written by an actor holding
     * `beneficiaries.bank-data.manage`. Any attempt to submit them without
     * that permission is treated as a defense-in-depth violation (the
     * Livewire Form already hides these fields for unprivileged actors) and
     * throws rather than silently dropping the data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    /**
     * Convert '' to null for nullable date/numeric columns so MySQL strict
     * mode accepts them (SQLite silently coerces, hiding this in dev).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function nullifyBlankNumericFields(array $data): array
    {
        foreach (self::NULLABLE_NUMERIC_FIELDS as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    private function guardBankFields(array $data, User $actor): void
    {
        if (array_intersect_key($data, array_flip(self::BANK_FIELDS)) === []) {
            return;
        }

        if (! $actor->can('beneficiaries.bank-data.manage')) {
            throw new AuthorizationException(__('beneficiaries.messages.cannot_manage_bank_data'));
        }
    }
}
