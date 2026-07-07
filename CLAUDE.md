# CLAUDE.md — نظام إدارة العطاءات | جمعية الموسى الخيرية

## نظرة عامة
نظام داخلي (ليس SaaS) لإدارة المستفيدين والإعانات النقدية والعينية بسير موافقات متعدد المراحل. المرجع الكامل للمتطلبات: `PROJECT_BRIEF.md` (برومبت المشروع الأصلي في المحادثة).

## Stack (إصدارات فعلية مثبتة)
Laravel 13.19 · Livewire 4.3 · Tailwind 4 (Vite plugin) · PHP 8.4 · Pest 4.7 · spatie/permission 8.3 · spatie/activitylog 5.0 · spatie/medialibrary 11.23 · ApexCharts (npm)

## أوامر التشغيل
- `composer dev` — التشغيل المحلي (server + queue + vite)
- `./vendor/bin/pest` أو `php artisan test` — الاختبارات
- `npm run build` — الأصول
- `./vendor/bin/pint` — التنسيق (Laravel Pint)

## قرارات معمارية (حدّثها عند كل قرار)
- **قاعدة البيانات**: MySQL 8 للإنتاج؛ SQLite للتطوير المحلي والاختبارات (لا MySQL في بيئة التطوير الحالية). بيئة الاستضافة لم تُحدد → لا اعتماد على Redis؛ queues بـ database driver.
- **الألوان (معتمدة من المستخدم)**: Primary بترولي `#1C545E`، Secondary أخضر `#85BF40`، Accent رملي `#EAB977` — مستخرجة من موقع الجمعية (متغيرات CSS للثيم + شعار SVG). معرفة في `resources/css/app.css` تحت `@theme`.
- **الخط**: IBM Plex Sans Arabic (معتمد من المستخدم رغم أن الموقع يستخدم Almarai) — self-hosted عبر `@fontsource`، أوزان 300–700، مع `tnum` للأرقام الجدولية.
- **Dark mode**: مطلوب من الإصدار الأول — عبر `.dark` class على `<html>` (`@custom-variant dark`).
- **الرسوم البيانية**: ApexCharts.
- **سير الموافقات الافتراضي**: مرحلتان (باحث اجتماعي: دراسة وتوصية ← مدير: اعتماد نهائي) — والمحرك قابل للتهيئة بأي عدد مراحل.
- **رابط تأكيد الاستلام**: يُرسل عند حالة "مُسلَّمة"، توثيقي (لا يمنع إغلاق الإعانة)، صلاحية 7 أيام، تذكير تلقائي واحد بعد 3 أيام.
- **الاستبيانات**: قابلة للربط ببرنامج إعانة معيّن أو عامة؛ اختيارية تظهر بعد ضغطة التأكيد.
- **بيانات المزودات (Taqnyat/Okta)**: اسم المرسل والقوالب تُدار من شاشة إعدادات داخل النظام؛ المفاتيح السرية في `.env` فقط.
- **إشعارات المدير**: داخل النظام (جرس) + بريد إلكتروني عند وصول طلب لمرحلته.
- Pest بدل PHPUnit (أزيل phpunit/phpunit من require-dev، والاختبارات بصيغة Pest functions).
- **spatie/laravel-activitylog 5.0**: النيمسبيس الفعلي للـ trait هو `Spatie\Activitylog\Models\Concerns\LogsActivity` (لا `Spatie\Activitylog\Traits\LogsActivity` كما في نسخ أقدم)، و`LogOptions` في `Spatie\Activitylog\Support\LogOptions`. الميثود الصحيحة لتخطي السجلات الفارغة هي `dontLogEmptyChanges()` (لا `dontSubmitEmptyLogs()`). تغييرات الحقول تُخزَّن في عمود `attribute_changes` وليس `properties`.
- **spatie/laravel-permission 8.3**: يجب استدعاء `PermissionRegistrar::forgetCachedPermissions()` بين كل seeder (صلاحيات ← أدوار ← مستخدمين) لأن الكاش لا يُنعش تلقائيًا عند إنشاء صلاحيات/أدوار جديدة داخل نفس الطلب.
- **Gate::before لـ system-admin**: يستثني قدرات `delete` و`suspend` عندما يكون الهدف (subject) هو الفاعل نفسه — منع الحذف/الإيقاف الذاتي. أي قدرة مستقبلية بهذا النمط يجب إضافتها للاستثناء.
- **إسناد الأدوار**: يتطلب صلاحية `roles.assign`، وإسناد `system-admin` محصور بمن يحمله فقط. مصدر الحقيقة في `app/Actions/Users/AuthorizeRoleAssignment.php` + تحقق ودي في الفورم.
- **تغيير status**: عبر `UpdateUser` يتطلب Gate `suspend`.
- **ميدلوير active**: `EnsureUserIsActive` على مجموعة `auth` يطرد المستخدم الموقوف من جلسته الحية.
- **Password::defaults()**: معرفة في `AppServiceProvider` (min 8 + أحرف وأرقام، بلا `uncompromised` لبيئات بلا إنترنت).
- **نمط Livewire 4**: فئات في `app/Livewire` + عروض في `resources/views/livewire` منفصلة (لا SFC) — layouts بصيغة `layouts::app` / `layouts::guest`.
- **forgot-password**: رسالة نجاح عامة دائمًا (منع enumeration) + rate limit 3/دقيقة.

## قرارات منتج (من المستخدم — المرحلة 0)
- طرق التسليم الأربع كلها: تحويل بنكي، استلام من المقر، مندوب توصيل، تسليم يدوي ميداني.
- حقول إضافية للمستفيد: الدخل الشهري ومصادره، جهة العمل والمهنة، الحالة الصحية والاحتياجات.
- استيراد بيانات حالية من Excel مطلوب (المستخدم سيرسل عينة الملف لاحقًا لمطابقة الأعمدة).
- التصنيفات الأولية: أرملة، أيتام، مطلّقة، أسرة محتاجة، كبير سن، ذوو إعاقة، سجين/أسرة سجين. البرامج: سلة غذائية، كسوة، سداد إيجار، إعانة نقدية عامة، أجهزة كهربائية، ترميم منزل، فرحة عيد.
- التقارير الأربعة كلها: الإعانات المفصل، المستفيدين، المالي الشهري/السنوي، نتائج الاستبيانات (Excel + PDF).

## بنية المجالات (Domains)
Beneficiaries / Aids / Approvals / Deliveries / Notifications / Confirmations / Surveys / Users — لم تُبنَ بعد (تبدأ من المرحلة 1).

## حالة المراحل
- [x] المرحلة 0 — التأسيس: الوكلاء التسعة في `.claude/agents/`، أسئلة القرارات أُجيبت كلها، الألوان استُخرجت واعتُمدت، مشروع Laravel 13.19 + Livewire 4.3 + Tailwind 4 + Pest جاهز، توكنز الهوية في `@theme`، الشعار في `public/images/brand/`، اللغة الافتراضية عربية.
- [x] **المرحلة 1 — المصادقة والأدوار + نظام التصميم** — مكتملة بالكامل.
  - [x] 1أ — الأساس: هجرة users، Enums (`UserStatus`/`Locale`/`RoleName`)، تحديث User model (HasRoles/SoftDeletes/LogsActivity/HasMedia)، `SetLocale` middleware، aliases ميدلوير Spatie، `Gate::before` لـ system-admin، Seeders (Permission/Role/AdminUser).
  - [x] 1ب — شاشات المصادقة (login/forgot/reset يدوي + rate limiting + فحص الموقوف)، إدارة مستخدمين/أدوار بصلاحيات granular، مكتبة x-ui (12 مكون) + layouts RTL ثنائية مع dark mode، ترجمة كاملة ar/en (119+ مفتاح)، 35 اختبار Pest أخضر، مراجعة أمنية أُغلقت ملاحظاتها الحرجة الثلاث.
- [ ] المرحلة 2 — ملف المستفيد
- [ ] المرحلة 3 — الإعانات وسير الموافقات
- [ ] المرحلة 4 — الصرف والتسليم
- [ ] المرحلة 5 — الإشعارات (Taqnyat + Okta Connect)
- [ ] المرحلة 6 — تأكيد الاستلام والاستبيانات
- [ ] المرحلة 7 — اللوحة والتقارير
- [ ] المرحلة 8 — الجودة

## طريقة العمل
- منسّق + وكلاء في `.claude/agents/`: architect وsecurity-reviewer (opus)، backend-builder وui-builder وintegrations وtest-writer (sonnet)، translator وdocs-keeper وseeder (haiku).
- بوابة الجودة قبل أي commit لمرحلة: اختبارات خضراء (test-writer) ثم مراجعة أمنية بلا ملاحظات حرجة (security-reviewer).
- كل قرار مفتوح يُعرض على المستخدم عبر AskUserQuestion؛ المخرجات النهائية للمستخدم مقتضبة (3–6 أسطر).

## اتفاقيات
- كل النصوص عبر `lang/ar` و`lang/en` — لا نصوص صلبة في الواجهات. العربية الافتراضية (`APP_LOCALE=ar`).
- المنطق في Actions/Services، الحالات PHP Enums، الإرسال الخارجي عبر Queued Jobs فقط.
- الحقول البنكية (آيبان SA + 22 رقم، اسم صاحب الحساب) encrypted casts + صلاحية خاصة لعرضها.
- الألوان فقط عبر توكنز `@theme` (`bg-primary`, `text-secondary-600`...) — ممنوع ألوان Tailwind الافتراضية في المكونات.
- ألوان حالات الإعانة الدلالية (معرفة كتوكنز `--color-status-*`): مسودة رمادي `#6B7280`، قيد المراجعة كهرماني `#D97706`، معتمدة أخضر `#16A34A`، مرفوضة أحمر `#DC2626`، مُسلَّمة/مؤكَّدة أخضر داكن `#44631F`.
- Soft deletes للمستفيدين والإعانات، Form Requests لكل إدخال، Policies لكل موديل حساس.
- الصفحة العامة: rate limiting، Signed URL + توكن يستخدم مرة واحدة، لا بيانات حساسة.
- `prefers-reduced-motion` محترم عالميًا (في app.css).

## التكاملات
- Taqnyat SMS: `TAQNYAT_API_KEY`, `TAQNYAT_SENDER` — خلف `SmsGatewayInterface` (المرحلة 5).
- Okta Connect WhatsApp: `OKTA_CONNECT_BASE_URL` / `TOKEN` / `CHANNEL_ID` — خلف `WhatsAppGatewayInterface`، مع `idempotencyKey` واحترام `RateLimitException::retryAfter()` (المرحلة 5).
- لاحقًا: `maatwebsite/excel` (استيراد/تصدير) و`barryvdh/laravel-dompdf` (PDF) — تثبت عند مرحلتها.

## ملاحظات للجلسة القادمة
- **المرحلة التالية: المرحلة 2 — ملف المستفيد** — ابدأ بوكيل architect لخطة النموذج والعلاقات والحقول، اطلب من المستخدم عينة Excel للاستيراد لمطابقة الأعمدة، ثم وزّع على backend-builder بالتوازي.
- مستخدم التجربة: `admin@almoosacharity.org` / `password` (متاح للاختبار E2E بالمتصفح).
- فحص E2E بالمتصفح: متاح عبر Playwright على `/opt/pw-browsers/chromium`.
- بيئة التطوير هذه بلا MySQL — أبقِ الاختبارات والتشغيل المحلي على SQLite، وتجنب SQL خاص بـ MySQL في migrations.
