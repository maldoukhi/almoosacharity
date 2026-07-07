<?php

namespace Database\Seeders;

use App\Models\BeneficiaryCategory;
use Illuminate\Database\Seeder;

class BeneficiaryCategorySeeder extends Seeder
{
    /**
     * The seven initial beneficiary categories from CLAUDE.md.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'أرملة',
                'description' => 'نساء فقدن أزواجهن والمعيلات للأسرة',
                'sort_order' => 1,
            ],
            [
                'name' => 'أيتام',
                'description' => 'أطفال فقدوا آباءهم والمحتاجون للرعاية',
                'sort_order' => 2,
            ],
            [
                'name' => 'مطلّقة',
                'description' => 'نساء مطلقات يعانين من ظروف اقتصادية صعبة',
                'sort_order' => 3,
            ],
            [
                'name' => 'أسرة محتاجة',
                'description' => 'أسر تحتاج إلى دعم مالي واجتماعي',
                'sort_order' => 4,
            ],
            [
                'name' => 'كبير سن',
                'description' => 'كبار السن والمسنون بلا معيل',
                'sort_order' => 5,
            ],
            [
                'name' => 'ذوو إعاقة',
                'description' => 'أشخاص ذوو احتياجات خاصة وإعاقات',
                'sort_order' => 6,
            ],
            [
                'name' => 'سجين/أسرة سجين',
                'description' => 'السجناء وأسرهم المحتاجة للدعم',
                'sort_order' => 7,
            ],
        ];

        foreach ($categories as $category) {
            BeneficiaryCategory::query()->firstOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                ],
            );
        }
    }
}
