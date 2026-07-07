<?php

namespace Database\Factories;

use App\Models\BeneficiaryCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BeneficiaryCategory>
 */
class BeneficiaryCategoryFactory extends Factory
{
    protected $model = BeneficiaryCategory::class;

    public function definition(): array
    {
        $categories = [
            'أرملة' => 'نساء فقدن أزواجهن',
            'أيتام' => 'أطفال فقدوا آباءهم',
            'مطلّقة' => 'نساء مطلقات',
            'أسرة محتاجة' => 'أسر تحتاج إلى دعم مالي',
            'كبير سن' => 'كبار السن والمسنون',
            'ذوو إعاقة' => 'أشخاص ذوو احتياجات خاصة',
            'سجين/أسرة سجين' => 'السجناء وأسرهم',
        ];

        $name = $this->faker->randomElement(array_keys($categories));
        $description = $categories[$name];

        return [
            'name' => $name,
            'description' => $description,
            'is_active' => true,
            'sort_order' => $this->faker->randomNumber(1),
        ];
    }
}
