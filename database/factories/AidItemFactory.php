<?php

namespace Database\Factories;

use App\Models\Aid;
use App\Models\AidItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AidItem>
 */
class AidItemFactory extends Factory
{
    protected $model = AidItem::class;

    private const ITEMS = [
        'سلة غذائية' => ['quantity' => [1, 5], 'value' => 500],
        'أرز' => ['quantity' => [1, 10], 'value' => 50],
        'زيت' => ['quantity' => [1, 5], 'value' => 80],
        'سكر' => ['quantity' => [1, 10], 'value' => 40],
        'دقيق' => ['quantity' => [1, 10], 'value' => 30],
        'لحم' => ['quantity' => [1, 5], 'value' => 150],
        'تمر' => ['quantity' => [1, 10], 'value' => 100],
        'حليب' => ['quantity' => [1, 12], 'value' => 30],
        'بطانية' => ['quantity' => [1, 5], 'value' => 200],
        'ملابس شتوية' => ['quantity' => [1, 5], 'value' => 300],
        'ملابس صيفية' => ['quantity' => [1, 5], 'value' => 250],
        'أحذية' => ['quantity' => [1, 5], 'value' => 150],
        'ثلاجة' => ['quantity' => [1, 2], 'value' => 2000],
        'مكيف هواء' => ['quantity' => [1, 2], 'value' => 2500],
        'غسالة' => ['quantity' => [1, 2], 'value' => 1500],
        'تلفاز' => ['quantity' => [1, 2], 'value' => 1200],
        'طباخ' => ['quantity' => [1, 2], 'value' => 800],
        'مروحة' => ['quantity' => [1, 3], 'value' => 300],
        'سرير' => ['quantity' => [1, 3], 'value' => 600],
        'خزانة' => ['quantity' => [1, 2], 'value' => 500],
        'طاولة' => ['quantity' => [1, 2], 'value' => 400],
        'مصابيح' => ['quantity' => [1, 5], 'value' => 100],
        'كتب دراسية' => ['quantity' => [1, 5], 'value' => 150],
        'أدوات مدرسية' => ['quantity' => [1, 10], 'value' => 50],
        'عصير' => ['quantity' => [1, 12], 'value' => 25],
        'قهوة' => ['quantity' => [1, 5], 'value' => 40],
        'شاي' => ['quantity' => [1, 5], 'value' => 35],
        'عسل' => ['quantity' => [1, 3], 'value' => 200],
    ];

    public function definition(): array
    {
        $items = array_keys(self::ITEMS);
        $itemName = $this->faker->randomElement($items);
        $itemData = self::ITEMS[$itemName];

        $quantity = $this->faker->numberBetween($itemData['quantity'][0], $itemData['quantity'][1]);
        $estimatedValue = $quantity * $itemData['value'];

        return [
            'aid_id' => Aid::factory(),
            'name' => $itemName,
            'quantity' => $quantity,
            'estimated_value' => (float) $estimatedValue,
            'description' => $this->faker->boolean(40) ? $this->faker->sentence(3) : null,
        ];
    }
}
