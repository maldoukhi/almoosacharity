<?php

namespace App\Actions\Categories;

use App\Models\BeneficiaryCategory;

class CreateCategory
{
    /**
     * Create a new beneficiary category.
     *
     * @param  array{name: string, description?: ?string, is_active?: bool, sort_order?: int}  $data
     */
    public function handle(array $data): BeneficiaryCategory
    {
        return BeneficiaryCategory::create($data);
    }
}
