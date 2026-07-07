<?php

namespace App\Actions\Categories;

use App\Models\BeneficiaryCategory;

class UpdateCategory
{
    /**
     * Update the given beneficiary category.
     *
     * @param  array{name: string, description?: ?string, is_active?: bool, sort_order?: int}  $data
     */
    public function handle(BeneficiaryCategory $category, array $data): BeneficiaryCategory
    {
        $category->update($data);

        return $category->fresh();
    }
}
