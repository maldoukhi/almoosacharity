<?php

namespace Database\Factories;

use App\Enums\BeneficiaryStatus;
use App\Enums\Gender;
use App\Enums\HousingType;
use App\Enums\IdType;
use App\Enums\MaritalStatus;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Beneficiary>
 */
class BeneficiaryFactory extends Factory
{
    protected $model = Beneficiary::class;

    private const MALE_NAMES = [
        'محمد', 'أحمد', 'سعود', 'فهد', 'خالد', 'علي', 'عبدالله', 'سالم',
        'عمر', 'عبدالعزيز', 'إبراهيم', 'يوسف', 'صالح', 'منصور', 'ناصر',
        'حسن', 'حسين', 'مصطفى', 'محمود', 'رشيد',
    ];

    private const FEMALE_NAMES = [
        'فاطمة', 'عائشة', 'علا', 'منى', 'حنان', 'نور', 'ريم', 'لينا',
        'إسراء', 'جنان', 'سها', 'بشرى', 'لما', 'زينب', 'رقية', 'أسماء',
        'سارة', 'ليلى', 'هند', 'شيخة',
    ];

    private const SECOND_NAMES = [
        'محمد', 'عبدالله', 'أحمد', 'سعود', 'خالد', 'علي', 'صالح', 'عمر',
        'عبدالعزيز', 'إبراهيم', 'فهد', 'ناصر', 'حسن', 'مصطفى', 'سالم',
    ];

    private const LAST_NAMES = [
        'العتيبي', 'الشراري', 'الرويلي', 'الدوسري', 'الغامدي', 'الزهراني',
        'القحطاني', 'الحربي', 'المطيري', 'الخثعمي', 'الثقفي', 'البكري',
        'الشمراني', 'الأنصاري', 'الجهني', 'الملحاوي', 'المزروعي', 'الكناني',
        'الرشيدي', 'البتيري', 'السهياني', 'الجابري', 'الشريف', 'الهاشمي',
    ];

    private const SAUDI_CITIES = [
        'الرياض' => ['قصر الحكم', 'الملز', 'العليا', 'المعيزيلية', 'البطيحا', 'الحمراء', 'الفاتح', 'الأمل'],
        'جدة' => ['الشاطئ', 'المنصورية', 'الكورنيش', 'الروضة', 'الصفاة', 'الحمراء', 'أم الساحل', 'الرحاب'],
        'المدينة' => ['قباء', 'الجرف', 'الخندمة', 'العنبرية', 'البقيع', 'المسجد النبوي', 'أحد', 'العريض'],
        'مكة' => ['الحرم', 'الشبيكة', 'أجياد', 'الصفا', 'المروة', 'العتيبية', 'كدي', 'الشفاء'],
        'الدمام' => ['الخليج', 'الروضة', 'السلام', 'الهفوف', 'الشاطئ', 'أم السماق', 'النزهة', 'القسيم'],
        'الإحساء' => ['الهفوف', 'المبرز', 'الجفر', 'العيينة', 'البراحة', 'الكوت', 'الجفر', 'القاره'],
        'القصيم' => ['بريدة', 'عنيزة', 'الرس', 'البكيرية', 'النبهانية', 'ضرماء', 'الأسياح', 'البدائع'],
        'جازان' => ['جيزان', 'صبيا', 'أبو عريش', 'الدرب', 'العارضة', 'بيش', 'الريث', 'فيفاء'],
        'عسير' => ['أبها', 'خميس مشيط', 'النماص', 'محايل عسير', 'رجال ألمع', 'تثليث', 'بارق', 'الأفلاج'],
        'تبوك' => ['تبوك', 'الوجه', 'ضباء', 'تيماء', 'حقل', 'الدويخلة', 'البدع', 'النقا'],
        'حائل' => ['حائل', 'الجلاميد', 'الشنان', 'الرياض', 'سميرة', 'موقق', 'القصع', 'الأطاولة'],
        'الحدود الشمالية' => ['عرعر', 'رفحاء', 'طريف', 'العويقيلة', 'الرويضة', 'الدفينة', 'سدير', 'البصرة'],
    ];

    private const OCCUPATIONS = [
        'عامل', 'موظف', 'معلم', 'صحي', 'سائق', 'عامل بناء', 'تاجر', 'حرفي',
        'حداد', 'نجار', 'مهندس', 'طبيب', 'ممرضة', 'كهربائي', 'سباك', 'بائع',
    ];

    private const HEALTH_CONDITIONS = [
        'سليم', 'إعاقة حركية', 'إعاقة بصرية', 'إعاقة سمعية', 'أمراض مزمنة',
        'ارتفاع ضغط الدم', 'السكري', 'أمراض القلب', 'الربو', 'أمراض النفسية',
    ];

    private const BANKS = [
        'البنك الأهلي السعودي', 'بنك الراجحي', 'بنك الرياض', 'بنك سامبا',
        'بنك STC', 'البنك السعودي الفرنسي', 'البنك البريطاني', 'بنك الإمارات',
    ];

    /**
     * Generate a valid Saudi IBAN with MOD-97 checksum.
     * Format: SA + 2 check digits + 20 digits
     */
    private function generateValidIban(): string
    {
        // Generate 20 random digits using numerify (safer than randomNumber)
        $accountNumber = $this->faker->numerify('####################');

        // Build: account number + SA + "00" placeholder for the check
        // digits (ISO 13616 requires the placeholder before computing).
        $rearranged = $accountNumber.'281000'; // SA = 28 10, then 00

        // Calculate mod 97
        $mod = 0;
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $mod = ($mod * 10 + (int) $rearranged[$i]) % 97;
        }

        // Check digits = 98 - mod
        $checkDigits = str_pad((string) (98 - $mod), 2, '0', STR_PAD_LEFT);

        return 'SA'.$checkDigits.$accountNumber;
    }

    /**
     * Generate a unique national ID in format 1xxxxxxxxx
     */
    private function generateUniqueNationalId(): string
    {
        $lastDigits = $this->faker->unique()->numerify('#########');

        return '1'.$lastDigits;
    }

    public function definition(): array
    {
        $gender = $this->faker->randomElement([Gender::Male, Gender::Female]);
        $firstNames = $gender === Gender::Male ? self::MALE_NAMES : self::FEMALE_NAMES;

        $maritalStatus = $this->faker->randomElement(MaritalStatus::cases());
        $birthDate = $this->faker->dateTimeBetween('-80 years', '-18 years');

        $cities = array_keys(self::SAUDI_CITIES);
        $city = $this->faker->randomElement($cities);
        $districts = self::SAUDI_CITIES[$city];
        $district = $this->faker->randomElement($districts);

        $monthlyIncome = $this->faker->numberBetween(0, 8000);
        $hasIban = $this->faker->boolean(60); // 60% chance to have IBAN

        return [
            'first_name' => $this->faker->randomElement($firstNames),
            'second_name' => $this->faker->randomElement(self::SECOND_NAMES),
            'third_name' => $this->faker->randomElement(self::SECOND_NAMES),
            'last_name' => $this->faker->randomElement(self::LAST_NAMES),
            'id_type' => IdType::NationalId,
            'national_id' => $this->generateUniqueNationalId(),
            'nationality' => 'SA',
            'birth_date' => $birthDate,
            'gender' => $gender,
            'mobile' => $this->faker->unique()->numerify('05########'),
            'marital_status' => $maritalStatus,
            'family_members_count' => $this->faker->numberBetween(0, 10),
            'occupation' => $this->faker->randomElement(self::OCCUPATIONS),
            'employer' => $this->faker->randomElement(['عمل حر', 'القطاع الحكومي', 'القطاع الخاص', 'متقاعد']),
            'monthly_income' => (float) $monthlyIncome,
            'health_status' => $this->faker->randomElement(self::HEALTH_CONDITIONS),
            'special_needs' => $this->faker->boolean(30) ? $this->faker->sentence(3) : null,
            'housing_type' => $this->faker->randomElement(HousingType::cases()),
            'rent_amount' => $this->faker->boolean(50) ? $this->faker->randomFloat(2, 500, 3000) : null,
            'national_address' => $this->faker->address(),
            'city' => $city,
            'district' => $district,
            'bank_name' => $hasIban ? $this->faker->randomElement(self::BANKS) : null,
            'iban' => $hasIban ? $this->generateValidIban() : null,
            'bank_account_holder' => $hasIban ? $this->faker->name(gender: $gender === Gender::Male ? 'male' : 'female') : null,
            // Default to an eligible (non-blocked) status: a random deactivated/
            // suspended/rejected default would make every beneficiary randomly
            // ineligible to receive aids. Tests that need a blocked status set
            // it explicitly.
            'status' => $this->faker->randomElement([
                BeneficiaryStatus::New,
                BeneficiaryStatus::UnderStudy,
                BeneficiaryStatus::UnderReview,
                BeneficiaryStatus::Active,
            ]),
            'notes' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
            'created_by' => User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['system-admin', 'data-entry']))->inRandomOrder()->first()?->id ?? 1,
        ];
    }
}
