<?php

namespace App\Actions\Aids;

use App\Enums\AidType;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Support\FiscalLock;
use Illuminate\Support\Facades\DB;

class UpdateAid
{
    public function __construct(
        private readonly AssertAidTypeMatchesProgram $assertAidTypeMatchesProgram,
        private readonly SyncAidItems $syncAidItems,
    ) {}

    /**
     * @param  array{beneficiary_id?: int, aid_program_id?: int, type?: string, amount?: ?float, purpose?: ?string, notes?: ?string, items?: array<int, array{name: string, quantity: int, estimated_value?: ?float, description?: ?string}>}  $data
     *
     * @throws InvalidAidTransitionException
     */
    public function handle(Aid $aid, array $data): Aid
    {
        FiscalLock::assertMutable($aid);

        if (! $aid->status->isEditable()) {
            throw InvalidAidTransitionException::notEditable();
        }

        $programId = $data['aid_program_id'] ?? $aid->aid_program_id;
        $type = AidType::from($data['type'] ?? $aid->type->value);

        $this->assertAidTypeMatchesProgram->handle($type, AidProgram::findOrFail($programId));

        return DB::transaction(function () use ($aid, $data, $programId, $type): Aid {
            $aid->update([
                'beneficiary_id' => $data['beneficiary_id'] ?? $aid->beneficiary_id,
                'aid_program_id' => $programId,
                'type' => $type,
                'amount' => $data['amount'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncAidItems->handle($aid, $data['items'] ?? []);

            return $aid->fresh('items');
        });
    }
}
