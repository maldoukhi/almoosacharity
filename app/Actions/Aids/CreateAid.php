<?php

namespace App\Actions\Aids;

use App\Enums\AidType;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAid
{
    public function __construct(
        private readonly GenerateAidReference $generateAidReference,
        private readonly AssertAidTypeMatchesProgram $assertAidTypeMatchesProgram,
        private readonly SyncAidItems $syncAidItems,
    ) {}

    /**
     * @param  array{beneficiary_id: int, aid_program_id: int, type: string, amount?: ?float, purpose?: ?string, notes?: ?string, items?: array<int, array{name: string, quantity: int, estimated_value?: ?float, description?: ?string}>}  $data
     */
    public function handle(array $data, User $actor): Aid
    {
        $program = AidProgram::findOrFail($data['aid_program_id']);

        $this->assertAidTypeMatchesProgram->handle(AidType::from($data['type']), $program);

        return DB::transaction(function () use ($data, $actor): Aid {
            $aid = Aid::create([
                'reference' => $this->generateAidReference->handle(),
                'beneficiary_id' => $data['beneficiary_id'],
                'aid_program_id' => $data['aid_program_id'],
                'type' => $data['type'],
                'amount' => $data['amount'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->syncAidItems->handle($aid, $data['items'] ?? []);

            return $aid->fresh('items');
        });
    }
}
