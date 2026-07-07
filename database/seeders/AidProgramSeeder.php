<?php

namespace Database\Seeders;

use App\Enums\AidProgramType;
use App\Models\AidProgram;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AidProgramSeeder extends Seeder
{
    /**
     * The seven initial aid programs listed in CLAUDE.md.
     */
    public function run(): void
    {
        $programs = [
            ['name' => 'سلة غذائية', 'type' => AidProgramType::InKind],
            ['name' => 'كسوة', 'type' => AidProgramType::InKind],
            ['name' => 'سداد إيجار', 'type' => AidProgramType::Cash],
            ['name' => 'إعانة نقدية عامة', 'type' => AidProgramType::Cash],
            ['name' => 'أجهزة كهربائية', 'type' => AidProgramType::InKind],
            ['name' => 'ترميم منزل', 'type' => AidProgramType::Both],
            ['name' => 'فرحة عيد', 'type' => AidProgramType::Both],
        ];

        foreach ($programs as $index => $program) {
            $slug = Str::slug($program['name']);

            AidProgram::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $program['name'],
                    'type' => $program['type']->value,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
