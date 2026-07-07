<?php

namespace App\Actions\Aids;

use App\Models\Aid;

class SyncAidItems
{
    /**
     * Replace an aid's line items wholesale with the given set. Simple
     * delete-then-recreate: aids only carry a handful of items and this
     * task does not need to preserve individual item identity across
     * saves.
     *
     * @param  array<int, array{name: string, quantity: int, estimated_value?: ?float, description?: ?string}>  $items
     */
    public function handle(Aid $aid, array $items): void
    {
        $aid->items()->delete();

        foreach ($items as $item) {
            $aid->items()->create([
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'estimated_value' => $item['estimated_value'] ?? null,
                'description' => $item['description'] ?? null,
            ]);
        }
    }
}
