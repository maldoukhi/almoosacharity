<?php

namespace App\Actions\Aids;

use App\Models\Aid;

class GenerateAidReference
{
    /**
     * Generate the next 'AID-{year}-{6 digit sequence}' reference.
     *
     * The row lock this issues (`lockForUpdate`) is only meaningful when
     * the caller is already inside its own DB::transaction() that performs
     * the actual insert immediately after calling this — the lock must be
     * held until that insert commits, otherwise a second request could
     * compute the same sequence in the gap. See CreateAid, which is the
     * only caller and wraps everything in a single transaction.
     */
    public function handle(): string
    {
        $lastId = (int) (Aid::withTrashed()->lockForUpdate()->max('id') ?? 0);

        $sequence = str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);

        return 'AID-'.now()->format('Y').'-'.$sequence;
    }
}
