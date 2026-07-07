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
