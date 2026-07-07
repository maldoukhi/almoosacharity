<?php

namespace App\Models;

use App\Enums\RelationKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['beneficiary_id', 'name', 'relation', 'birth_date', 'health_status', 'education_status'])]
class BeneficiaryFamilyMember extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'relation' => RelationKind::class,
            'birth_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Beneficiary, $this>
     */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
