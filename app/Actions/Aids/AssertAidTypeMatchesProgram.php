<?php

namespace App\Actions\Aids;

use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Models\AidProgram;
use InvalidArgumentException;

class AssertAidTypeMatchesProgram
{
    /**
     * A cash aid may only be raised against a program of type Cash/Both,
     * and an in-kind aid only against a program of type InKind/Both.
     *
     * @throws InvalidArgumentException
     */
    public function handle(AidType $type, AidProgram $program): void
    {
        $compatible = match ($program->type) {
            AidProgramType::Both => true,
            AidProgramType::Cash => $type === AidType::Cash,
            AidProgramType::InKind => $type === AidType::InKind,
        };

        if (! $compatible) {
            throw new InvalidArgumentException(__('validation.custom.aid.type_program_mismatch'));
        }
    }
}
