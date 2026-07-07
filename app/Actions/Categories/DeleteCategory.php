<?php

namespace App\Actions\Categories;

use App\Models\BeneficiaryCategory;
use RuntimeException;

class DeleteCategory
{
    /**
     * Delete the given beneficiary category, refusing when it is still
     * linked to at least one beneficiary.
     *
     * @throws RuntimeException
     */
    public function handle(BeneficiaryCategory $category): void
    {
        if ($category->beneficiaries()->exists()) {
            throw new RuntimeException(__('categories.messages.in_use'));
        }

        $category->delete();
    }
}
