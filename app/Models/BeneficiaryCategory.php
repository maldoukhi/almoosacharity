<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'description', 'is_active', 'sort_order'])]
class BeneficiaryCategory extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<Beneficiary, $this>
     */
    public function beneficiaries(): BelongsToMany
    {
        return $this->belongsToMany(
            Beneficiary::class,
            'beneficiary_beneficiary_category',
        );
    }
}
