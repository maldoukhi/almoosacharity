<?php

namespace App\Actions\Settings;

use App\Models\AidProgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaveAidProgram
{
    /**
     * Create or update an aid program. The slug is derived from the name
     * once, at creation, and never changes afterwards.
     *
     * @param  array{name: string, type: string, approval_flow_id?: ?int, is_active?: bool, sort_order?: int, description?: ?string}  $data
     */
    public function handle(array $data, ?AidProgram $program = null): AidProgram
    {
        return DB::transaction(function () use ($data, $program): AidProgram {
            $attributes = [
                'name' => $data['name'],
                'type' => $data['type'],
                'approval_flow_id' => $data['approval_flow_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
                'description' => $data['description'] ?? null,
            ];

            if ($program) {
                $program->update($attributes);

                return $program->fresh();
            }

            $attributes['slug'] = $this->generateUniqueSlug($data['name']);

            return AidProgram::create($attributes);
        });
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $attempt = 1;

        while (AidProgram::withTrashed()->where('slug', $slug)->exists()) {
            $attempt++;
            $slug = $base.'-'.$attempt;
        }

        return $slug;
    }
}
