<?php

namespace App\Support;

use App\Livewire\Beneficiaries\Form;

/**
 * Reference catalog of countries/nationalities used by the beneficiary
 * "nationality" picker ({@see Form}).
 *
 * Decision (documented for the coordinator): each entry stores the
 * **country name** (اسم الدولة, e.g. "السعودية") rather than the gendered
 * nationality adjective (سعودي/سعودية). The adjective form would require
 * tracking beneficiary gender to pick the correct variant and doubles the
 * translation surface for no functional gain; Saudi government systems
 * (e.g. Absher) commonly show the country name under "الجنسية" for the
 * same reason. The stored value on the model remains the plain ISO
 * 3166-1 alpha-2 code (e.g. 'SA'); only the picker's *display* label is a
 * country name.
 *
 * This is treated as reference data (a static catalog), not UI copy, so
 * it is kept self-contained here with both `ar`/`en` names rather than
 * split across lang/beneficiaries.php — the picker's UI chrome (search
 * placeholder, empty state, hints) still goes through lang/beneficiaries.php
 * as usual.
 */
final class Countries
{
    /**
     * ISO codes shown first, in this order, before the rest of the
     * (alphabetically sorted) catalog — the nationalities most common
     * among beneficiaries and residents in Saudi Arabia.
     *
     * @var list<string>
     */
    private const PRIORITY = [
        'SA', 'YE', 'EG', 'SY', 'SD', 'PK', 'IN', 'BD', 'PH', 'JO', 'PS',
    ];

    /**
     * @var array<string, array{ar: string, en: string}>
     */
    private const CATALOG = [
        'SA' => ['ar' => 'السعودية', 'en' => 'Saudi Arabia'],
        'YE' => ['ar' => 'اليمن', 'en' => 'Yemen'],
        'EG' => ['ar' => 'مصر', 'en' => 'Egypt'],
        'SY' => ['ar' => 'سوريا', 'en' => 'Syria'],
        'SD' => ['ar' => 'السودان', 'en' => 'Sudan'],
        'PK' => ['ar' => 'باكستان', 'en' => 'Pakistan'],
        'IN' => ['ar' => 'الهند', 'en' => 'India'],
        'BD' => ['ar' => 'بنغلاديش', 'en' => 'Bangladesh'],
        'PH' => ['ar' => 'الفلبين', 'en' => 'Philippines'],
        'JO' => ['ar' => 'الأردن', 'en' => 'Jordan'],
        'PS' => ['ar' => 'فلسطين', 'en' => 'Palestine'],

        'AE' => ['ar' => 'الإمارات العربية المتحدة', 'en' => 'United Arab Emirates'],
        'BH' => ['ar' => 'البحرين', 'en' => 'Bahrain'],
        'KW' => ['ar' => 'الكويت', 'en' => 'Kuwait'],
        'OM' => ['ar' => 'عُمان', 'en' => 'Oman'],
        'QA' => ['ar' => 'قطر', 'en' => 'Qatar'],
        'IQ' => ['ar' => 'العراق', 'en' => 'Iraq'],
        'LB' => ['ar' => 'لبنان', 'en' => 'Lebanon'],
        'LY' => ['ar' => 'ليبيا', 'en' => 'Libya'],
        'MA' => ['ar' => 'المغرب', 'en' => 'Morocco'],
        'TN' => ['ar' => 'تونس', 'en' => 'Tunisia'],
        'DZ' => ['ar' => 'الجزائر', 'en' => 'Algeria'],
        'MR' => ['ar' => 'موريتانيا', 'en' => 'Mauritania'],
        'SO' => ['ar' => 'الصومال', 'en' => 'Somalia'],
        'DJ' => ['ar' => 'جيبوتي', 'en' => 'Djibouti'],
        'KM' => ['ar' => 'جزر القمر', 'en' => 'Comoros'],
        'ER' => ['ar' => 'إريتريا', 'en' => 'Eritrea'],

        'AF' => ['ar' => 'أفغانستان', 'en' => 'Afghanistan'],
        'AL' => ['ar' => 'ألبانيا', 'en' => 'Albania'],
        'AM' => ['ar' => 'أرمينيا', 'en' => 'Armenia'],
        'AO' => ['ar' => 'أنغولا', 'en' => 'Angola'],
        'AR' => ['ar' => 'الأرجنتين', 'en' => 'Argentina'],
        'AT' => ['ar' => 'النمسا', 'en' => 'Austria'],
        'AU' => ['ar' => 'أستراليا', 'en' => 'Australia'],
        'AZ' => ['ar' => 'أذربيجان', 'en' => 'Azerbaijan'],
        'BA' => ['ar' => 'البوسنة والهرسك', 'en' => 'Bosnia and Herzegovina'],
        'BB' => ['ar' => 'بربادوس', 'en' => 'Barbados'],
        'BE' => ['ar' => 'بلجيكا', 'en' => 'Belgium'],
        'BF' => ['ar' => 'بوركينا فاسو', 'en' => 'Burkina Faso'],
        'BG' => ['ar' => 'بلغاريا', 'en' => 'Bulgaria'],
        'BI' => ['ar' => 'بوروندي', 'en' => 'Burundi'],
        'BJ' => ['ar' => 'بنين', 'en' => 'Benin'],
        'BN' => ['ar' => 'بروناي', 'en' => 'Brunei'],
        'BO' => ['ar' => 'بوليفيا', 'en' => 'Bolivia'],
        'BR' => ['ar' => 'البرازيل', 'en' => 'Brazil'],
        'BS' => ['ar' => 'الباهاماس', 'en' => 'Bahamas'],
        'BT' => ['ar' => 'بوتان', 'en' => 'Bhutan'],
        'BW' => ['ar' => 'بوتسوانا', 'en' => 'Botswana'],
        'BY' => ['ar' => 'بيلاروسيا', 'en' => 'Belarus'],
        'BZ' => ['ar' => 'بليز', 'en' => 'Belize'],
        'CA' => ['ar' => 'كندا', 'en' => 'Canada'],
        'CD' => ['ar' => 'جمهورية الكونغو الديمقراطية', 'en' => 'DR Congo'],
        'CF' => ['ar' => 'أفريقيا الوسطى', 'en' => 'Central African Republic'],
        'CG' => ['ar' => 'الكونغو', 'en' => 'Congo'],
        'CH' => ['ar' => 'سويسرا', 'en' => 'Switzerland'],
        'CI' => ['ar' => 'ساحل العاج', 'en' => "Côte d'Ivoire"],
        'CL' => ['ar' => 'تشيلي', 'en' => 'Chile'],
        'CM' => ['ar' => 'الكاميرون', 'en' => 'Cameroon'],
        'CN' => ['ar' => 'الصين', 'en' => 'China'],
        'CO' => ['ar' => 'كولومبيا', 'en' => 'Colombia'],
        'CR' => ['ar' => 'كوستاريكا', 'en' => 'Costa Rica'],
        'CU' => ['ar' => 'كوبا', 'en' => 'Cuba'],
        'CV' => ['ar' => 'الرأس الأخضر', 'en' => 'Cabo Verde'],
        'CY' => ['ar' => 'قبرص', 'en' => 'Cyprus'],
        'CZ' => ['ar' => 'التشيك', 'en' => 'Czechia'],
        'DE' => ['ar' => 'ألمانيا', 'en' => 'Germany'],
        'DK' => ['ar' => 'الدنمارك', 'en' => 'Denmark'],
        'DM' => ['ar' => 'دومينيكا', 'en' => 'Dominica'],
        'DO' => ['ar' => 'جمهورية الدومينيكان', 'en' => 'Dominican Republic'],
        'EC' => ['ar' => 'الإكوادور', 'en' => 'Ecuador'],
        'EE' => ['ar' => 'إستونيا', 'en' => 'Estonia'],
        'ET' => ['ar' => 'إثيوبيا', 'en' => 'Ethiopia'],
        'FI' => ['ar' => 'فنلندا', 'en' => 'Finland'],
        'FJ' => ['ar' => 'فيجي', 'en' => 'Fiji'],
        'FM' => ['ar' => 'ميكرونيسيا', 'en' => 'Micronesia'],
        'FR' => ['ar' => 'فرنسا', 'en' => 'France'],
        'GA' => ['ar' => 'الغابون', 'en' => 'Gabon'],
        'GB' => ['ar' => 'المملكة المتحدة', 'en' => 'United Kingdom'],
        'GD' => ['ar' => 'غرينادا', 'en' => 'Grenada'],
        'GE' => ['ar' => 'جورجيا', 'en' => 'Georgia'],
        'GH' => ['ar' => 'غانا', 'en' => 'Ghana'],
        'GM' => ['ar' => 'غامبيا', 'en' => 'Gambia'],
        'GN' => ['ar' => 'غينيا', 'en' => 'Guinea'],
        'GQ' => ['ar' => 'غينيا الاستوائية', 'en' => 'Equatorial Guinea'],
        'GR' => ['ar' => 'اليونان', 'en' => 'Greece'],
        'GT' => ['ar' => 'غواتيمالا', 'en' => 'Guatemala'],
        'GW' => ['ar' => 'غينيا بيساو', 'en' => 'Guinea-Bissau'],
        'GY' => ['ar' => 'غيانا', 'en' => 'Guyana'],
        'HN' => ['ar' => 'هندوراس', 'en' => 'Honduras'],
        'HR' => ['ar' => 'كرواتيا', 'en' => 'Croatia'],
        'HT' => ['ar' => 'هايتي', 'en' => 'Haiti'],
        'HU' => ['ar' => 'المجر', 'en' => 'Hungary'],
        'ID' => ['ar' => 'إندونيسيا', 'en' => 'Indonesia'],
        'IE' => ['ar' => 'أيرلندا', 'en' => 'Ireland'],
        'IR' => ['ar' => 'إيران', 'en' => 'Iran'],
        'IS' => ['ar' => 'آيسلندا', 'en' => 'Iceland'],
        'IT' => ['ar' => 'إيطاليا', 'en' => 'Italy'],
        'JM' => ['ar' => 'جامايكا', 'en' => 'Jamaica'],
        'JP' => ['ar' => 'اليابان', 'en' => 'Japan'],
        'KE' => ['ar' => 'كينيا', 'en' => 'Kenya'],
        'KG' => ['ar' => 'قيرغيزستان', 'en' => 'Kyrgyzstan'],
        'KH' => ['ar' => 'كمبوديا', 'en' => 'Cambodia'],
        'KI' => ['ar' => 'كيريباتي', 'en' => 'Kiribati'],
        'KN' => ['ar' => 'سانت كيتس ونيفيس', 'en' => 'Saint Kitts and Nevis'],
        'KP' => ['ar' => 'كوريا الشمالية', 'en' => 'North Korea'],
        'KR' => ['ar' => 'كوريا الجنوبية', 'en' => 'South Korea'],
        'KZ' => ['ar' => 'كازاخستان', 'en' => 'Kazakhstan'],
        'LA' => ['ar' => 'لاوس', 'en' => 'Laos'],
        'LC' => ['ar' => 'سانت لوسيا', 'en' => 'Saint Lucia'],
        'LI' => ['ar' => 'ليختنشتاين', 'en' => 'Liechtenstein'],
        'LK' => ['ar' => 'سريلانكا', 'en' => 'Sri Lanka'],
        'LR' => ['ar' => 'ليبيريا', 'en' => 'Liberia'],
        'LS' => ['ar' => 'ليسوتو', 'en' => 'Lesotho'],
        'LT' => ['ar' => 'ليتوانيا', 'en' => 'Lithuania'],
        'LU' => ['ar' => 'لوكسمبورغ', 'en' => 'Luxembourg'],
        'LV' => ['ar' => 'لاتفيا', 'en' => 'Latvia'],
        'MC' => ['ar' => 'موناكو', 'en' => 'Monaco'],
        'MD' => ['ar' => 'مولدوفا', 'en' => 'Moldova'],
        'ME' => ['ar' => 'الجبل الأسود', 'en' => 'Montenegro'],
        'MG' => ['ar' => 'مدغشقر', 'en' => 'Madagascar'],
        'MH' => ['ar' => 'جزر مارشال', 'en' => 'Marshall Islands'],
        'MK' => ['ar' => 'مقدونيا الشمالية', 'en' => 'North Macedonia'],
        'ML' => ['ar' => 'مالي', 'en' => 'Mali'],
        'MM' => ['ar' => 'ميانمار', 'en' => 'Myanmar'],
        'MN' => ['ar' => 'منغوليا', 'en' => 'Mongolia'],
        'MT' => ['ar' => 'مالطا', 'en' => 'Malta'],
        'MU' => ['ar' => 'موريشيوس', 'en' => 'Mauritius'],
        'MV' => ['ar' => 'المالديف', 'en' => 'Maldives'],
        'MW' => ['ar' => 'مالاوي', 'en' => 'Malawi'],
        'MX' => ['ar' => 'المكسيك', 'en' => 'Mexico'],
        'MY' => ['ar' => 'ماليزيا', 'en' => 'Malaysia'],
        'MZ' => ['ar' => 'موزمبيق', 'en' => 'Mozambique'],
        'NA' => ['ar' => 'ناميبيا', 'en' => 'Namibia'],
        'NE' => ['ar' => 'النيجر', 'en' => 'Niger'],
        'NG' => ['ar' => 'نيجيريا', 'en' => 'Nigeria'],
        'NI' => ['ar' => 'نيكاراغوا', 'en' => 'Nicaragua'],
        'NL' => ['ar' => 'هولندا', 'en' => 'Netherlands'],
        'NO' => ['ar' => 'النرويج', 'en' => 'Norway'],
        'NP' => ['ar' => 'نيبال', 'en' => 'Nepal'],
        'NR' => ['ar' => 'ناورو', 'en' => 'Nauru'],
        'NZ' => ['ar' => 'نيوزيلندا', 'en' => 'New Zealand'],
        'PA' => ['ar' => 'بنما', 'en' => 'Panama'],
        'PE' => ['ar' => 'بيرو', 'en' => 'Peru'],
        'PG' => ['ar' => 'بابوا غينيا الجديدة', 'en' => 'Papua New Guinea'],
        'PL' => ['ar' => 'بولندا', 'en' => 'Poland'],
        'PT' => ['ar' => 'البرتغال', 'en' => 'Portugal'],
        'PW' => ['ar' => 'بالاو', 'en' => 'Palau'],
        'PY' => ['ar' => 'باراغواي', 'en' => 'Paraguay'],
        'RO' => ['ar' => 'رومانيا', 'en' => 'Romania'],
        'RS' => ['ar' => 'صربيا', 'en' => 'Serbia'],
        'RU' => ['ar' => 'روسيا', 'en' => 'Russia'],
        'RW' => ['ar' => 'رواندا', 'en' => 'Rwanda'],
        'SB' => ['ar' => 'جزر سليمان', 'en' => 'Solomon Islands'],
        'SC' => ['ar' => 'سيشل', 'en' => 'Seychelles'],
        'SE' => ['ar' => 'السويد', 'en' => 'Sweden'],
        'SG' => ['ar' => 'سنغافورة', 'en' => 'Singapore'],
        'SI' => ['ar' => 'سلوفينيا', 'en' => 'Slovenia'],
        'SK' => ['ar' => 'سلوفاكيا', 'en' => 'Slovakia'],
        'SL' => ['ar' => 'سيراليون', 'en' => 'Sierra Leone'],
        'SM' => ['ar' => 'سان مارينو', 'en' => 'San Marino'],
        'SN' => ['ar' => 'السنغال', 'en' => 'Senegal'],
        'SR' => ['ar' => 'سورينام', 'en' => 'Suriname'],
        'SS' => ['ar' => 'جنوب السودان', 'en' => 'South Sudan'],
        'ST' => ['ar' => 'ساو تومي وبرينسيبي', 'en' => 'São Tomé and Príncipe'],
        'SV' => ['ar' => 'السلفادور', 'en' => 'El Salvador'],
        'SZ' => ['ar' => 'إسواتيني', 'en' => 'Eswatini'],
        'TD' => ['ar' => 'تشاد', 'en' => 'Chad'],
        'TG' => ['ar' => 'توغو', 'en' => 'Togo'],
        'TH' => ['ar' => 'تايلاند', 'en' => 'Thailand'],
        'TJ' => ['ar' => 'طاجيكستان', 'en' => 'Tajikistan'],
        'TL' => ['ar' => 'تيمور الشرقية', 'en' => 'Timor-Leste'],
        'TM' => ['ar' => 'تركمانستان', 'en' => 'Turkmenistan'],
        'TO' => ['ar' => 'تونغا', 'en' => 'Tonga'],
        'TR' => ['ar' => 'تركيا', 'en' => 'Turkey'],
        'TT' => ['ar' => 'ترينيداد وتوباغو', 'en' => 'Trinidad and Tobago'],
        'TV' => ['ar' => 'توفالو', 'en' => 'Tuvalu'],
        'TW' => ['ar' => 'تايوان', 'en' => 'Taiwan'],
        'TZ' => ['ar' => 'تنزانيا', 'en' => 'Tanzania'],
        'UA' => ['ar' => 'أوكرانيا', 'en' => 'Ukraine'],
        'UG' => ['ar' => 'أوغندا', 'en' => 'Uganda'],
        'US' => ['ar' => 'الولايات المتحدة', 'en' => 'United States'],
        'UY' => ['ar' => 'أوروغواي', 'en' => 'Uruguay'],
        'UZ' => ['ar' => 'أوزبكستان', 'en' => 'Uzbekistan'],
        'VA' => ['ar' => 'الفاتيكان', 'en' => 'Vatican City'],
        'VC' => ['ar' => 'سانت فينسنت والغرينادين', 'en' => 'Saint Vincent and the Grenadines'],
        'VE' => ['ar' => 'فنزويلا', 'en' => 'Venezuela'],
        'VN' => ['ar' => 'فيتنام', 'en' => 'Vietnam'],
        'VU' => ['ar' => 'فانواتو', 'en' => 'Vanuatu'],
        'WS' => ['ar' => 'ساموا', 'en' => 'Samoa'],
        'ZA' => ['ar' => 'جنوب أفريقيا', 'en' => 'South Africa'],
        'ZM' => ['ar' => 'زامبيا', 'en' => 'Zambia'],
        'ZW' => ['ar' => 'زيمبابوي', 'en' => 'Zimbabwe'],
        'AG' => ['ar' => 'أنتيغوا وبربودا', 'en' => 'Antigua and Barbuda'],
        'AD' => ['ar' => 'أندورا', 'en' => 'Andorra'],
    ];

    /**
     * All countries as `code => localized name`, with the {@see PRIORITY}
     * codes first (in their given order) followed by the rest sorted
     * alphabetically by their localized name.
     *
     * @return array<string, string>
     */
    public static function all(?string $locale = null): array
    {
        $locale = self::resolveLocale($locale);

        $priority = [];
        foreach (self::PRIORITY as $code) {
            $priority[$code] = self::CATALOG[$code][$locale];
        }

        $rest = [];
        foreach (self::CATALOG as $code => $names) {
            if (in_array($code, self::PRIORITY, true)) {
                continue;
            }

            $rest[$code] = $names[$locale];
        }

        asort($rest, SORT_LOCALE_STRING | SORT_FLAG_CASE);

        return $priority + $rest;
    }

    /**
     * Localized display name for a single ISO code. Falls back to the raw
     * code itself (rather than throwing) for unknown/legacy values, so
     * existing data never breaks a view.
     */
    public static function nationalityName(string $code, ?string $locale = null): string
    {
        $code = strtoupper($code);
        $locale = self::resolveLocale($locale);

        return self::CATALOG[$code][$locale] ?? $code;
    }

    private static function resolveLocale(?string $locale): string
    {
        $locale = $locale ?? app()->getLocale();

        return $locale === 'ar' ? 'ar' : 'en';
    }
}
