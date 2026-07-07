<?php

namespace Database\Factories;

use App\Enums\RelationKind;
use App\Models\Beneficiary;
use App\Models\BeneficiaryFamilyMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BeneficiaryFamilyMember>
 */
class BeneficiaryFamilyMemberFactory extends Factory
{
    protected $model = BeneficiaryFamilyMember::class;

    private const MALE_NAMES = [
        'محمد', 'أحمد', 'سعود', 'فهد', 'خالد', 'علي', 'عبدالله', 'سالم',
        'عمر', 'عبدالعزيز', 'إبراهيم', 'يوسف', 'صالح', 'منصور', 'ناصر',
    ];

    private const FEMALE_NAMES = [
        'فاطمة', 'عائشة', 'علا', 'منى', 'حنان', 'نور', 'ريم', 'لينا',
        'إسراء', 'جنان', 'سها', 'بشرى', 'لما', 'زينب', 'رقية',
    ];

    private const EDUCATION_LEVELS = [
        'ابتدائي', 'متوسط', 'ثانوي', 'جامعي', 'دراسات عليا', 'أمي',
    ];

    private const HEALTH_CONDITIONS = [
        'سليم', 'إعاقة حركية', 'إعاقة بصرية', 'إعاقة سمعية', 'أمراض مزمنة', null,
    ];

    public function definition(): array
    {
        $relation = $this->faker->randomElement(RelationKind::cases());
        $isMale = in_array($relation, [
            RelationKind::Son,
            RelationKind::Husband,
            RelationKind::Father,
            RelationKind::Brother,
        ]);

        $names = $isMale ? self::MALE_NAMES : self::FEMALE_NAMES;

        return [
            'beneficiary_id' => Beneficiary::factory(),
            'name' => $this->faker->randomElement($names) . ' ' . $this->faker->randomElement($names),
            'relation' => $relation,
            'birth_date' => $this->faker->dateTimeBetween('-80 years', 'now'),
            'health_status' => $this->faker->randomElement(self::HEALTH_CONDITIONS),
            'education_status' => $this->faker->randomElement(self::EDUCATION_LEVELS),
        ];
    }
}
