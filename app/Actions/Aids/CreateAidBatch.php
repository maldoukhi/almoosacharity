<?php

namespace App\Actions\Aids;

use App\Enums\AidType;
use App\Models\AidBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Raise many aids in one go for a set of beneficiaries, grouping them under
 * a single {@see AidBatch}. Each beneficiary receives a cash aid, an in-kind
 * aid, or both (per the project decision that every Aid stays single-type,
 * so "both" produces two separate aids). Every aid is created through the
 * ordinary {@see CreateAid} action, so batch-raised aids are indistinguishable
 * from hand-entered ones (Draft status, reference, type/program check, items).
 */
class CreateAidBatch
{
    public function __construct(
        private readonly CreateAid $createAid,
    ) {}

    /**
     * @param  array{
     *     aid_program_id: int,
     *     mode: string,
     *     note?: ?string,
     *     default_amount?: ?float,
     *     default_purpose?: ?string,
     *     default_items?: array<int, array{name: string, quantity: int, estimated_value?: ?float, description?: ?string}>,
     *     beneficiaries: array<int, array{id: int, amount?: ?float, purpose?: ?string, items?: ?array<int, array{name: string, quantity: int, estimated_value?: ?float, description?: ?string}>}>
     * }  $data
     * @return array{batch: AidBatch, cash: int, in_kind: int, beneficiaries: int}
     */
    public function handle(array $data, User $actor): array
    {
        $mode = $data['mode'];
        $wantsCash = in_array($mode, [AidType::Cash->value, 'both'], true);
        $wantsInKind = in_array($mode, [AidType::InKind->value, 'both'], true);

        return DB::transaction(function () use ($data, $actor, $wantsCash, $wantsInKind): array {
            $batch = AidBatch::create([
                'created_by' => $actor->id,
                'program_id' => $data['aid_program_id'],
                'note' => $data['note'] ?? null,
            ]);

            $cashCount = 0;
            $inKindCount = 0;

            foreach ($data['beneficiaries'] as $beneficiary) {
                $beneficiaryId = $beneficiary['id'];

                if ($wantsCash) {
                    $aid = $this->createAid->handle([
                        'beneficiary_id' => $beneficiaryId,
                        'aid_program_id' => $data['aid_program_id'],
                        'type' => AidType::Cash->value,
                        'amount' => $beneficiary['amount'] ?? $data['default_amount'] ?? null,
                        'purpose' => $beneficiary['purpose'] ?? $data['default_purpose'] ?? null,
                        'notes' => $data['note'] ?? null,
                    ], $actor);

                    $batch->aids()->attach($aid->id);
                    $cashCount++;
                }

                if ($wantsInKind) {
                    $aid = $this->createAid->handle([
                        'beneficiary_id' => $beneficiaryId,
                        'aid_program_id' => $data['aid_program_id'],
                        'type' => AidType::InKind->value,
                        'notes' => $data['note'] ?? null,
                        'items' => $beneficiary['items'] ?? $data['default_items'] ?? [],
                    ], $actor);

                    $batch->aids()->attach($aid->id);
                    $inKindCount++;
                }
            }

            return [
                'batch' => $batch,
                'cash' => $cashCount,
                'in_kind' => $inKindCount,
                'beneficiaries' => count($data['beneficiaries']),
            ];
        });
    }
}
