<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A batch aid-creation run: a single admin action that raised many aids at
 * once for a set of beneficiaries. The aids themselves are ordinary,
 * fully-independent {@see Aid} records; this model just groups the ones a
 * given run produced (through the aid_batch_items pivot) for traceability.
 */
#[Fillable(['created_by', 'program_id', 'note'])]
class AidBatch extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<AidProgram, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(AidProgram::class, 'program_id');
    }

    /**
     * Every aid raised by this batch, linked through the aid_batch_items
     * pivot (the aids table itself is never touched).
     *
     * @return BelongsToMany<Aid, $this>
     */
    public function aids(): BelongsToMany
    {
        return $this->belongsToMany(Aid::class, 'aid_batch_items')
            ->withTimestamps();
    }
}
