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
- **الخط**: IBM Plex Sans Arabic (معتمد من المستخدم رغم أن الموقع يستخدم Almarai) — self-hosted عبر `@fontsource`، أوزان 300–700، مع `tnum` للأرقام الجدولية. **dompdf يتطلب TTF في `storage/fonts`** (IBM Plex عربي مضمن بدون تراخيص مشاع).
- **Dark mode**: مطلوب من الإصدار الأول — عبر `.dark` class على `<html>` (`@custom-variant dark`).
- **الرسوم البيانية**: ApexCharts.
- **سير الموافقات الافتراضي**: مرحلتان (باحث اجتماعي: دراسة وتوصية ← مدير: اعتماد نهائي) — والمحرك قابل للتهيئة بأي عدد مراحل (flows/stages بلقطة مثبتة على الإعانة).
- **رابط تأكيد الاستلام**: يُرسل عند حالة "مُسلَّمة"، توثيقي (لا يمنع إغلاق الإعانة)، صلاحية 7 أيام، تذكير تلقائي واحد بعد 3 أيام (يدوّر التوكن).
- **الاستبيانات**: قابلة للربط ببرنامج إعانة معيّن أو عامة؛ اختيارية تظهر بعد ضغطة التأكيد؛ ستة أنواع أسئلة (نص/رقم/اختيار واحد/متعدد/تقييم/تاريخ).
- **بيانات المزودات (Taqnyat/Okta)**: اسم المرسل والقوالب تُدار من شاشة إعدادات داخل النظام؛ المفاتيح السرية في `.env` فقط.
- **إشعارات المدير**: داخل النظام (جرس في `notifications` table) + بريد إلكتروني عند وصول طلب لمرحلته؛ `withEvents(discover:false)` منع إرسال مزدوج.
- Pest بدل PHPUnit (أزيل phpunit/phpunit من require-dev، والاختبارات بصيغة Pest functions).
- **spatie/laravel-activitylog 5.0**: النيمسبيس الفعلي للـ trait هو `Spatie\Activitylog\Models\Concerns\LogsActivity`، و`LogOptions` في `Spatie\Activitylog\Support\LogOptions`. الميثود الصحيحة: `dontLogEmptyChanges()`، تغييرات الحقول في عمود `attribute_changes`.
- **spatie/laravel-permission 8.3**: استدعاء `PermissionRegistrar::forgetCachedPermissions()` بين كل seeder (صلاحيات ← أدوار ← مستخدمين).
- **Gate::before لـ system-admin**: يستثني قدرات `delete` و`suspend` عندما الهدف = الفاعل نفسه (منع الحذف/الإيقاف الذاتي).
- **إسناد الأدوار**: صلاحية `roles.assign`؛ `system-admin` محصور بمن يحمله فقط (مصدر الحقيقة: `app/Actions/Users/AuthorizeRoleAssignment.php`).
- **تغيير status**: عبر `UpdateUser` يتطلب Gate `suspend`.
- **ميدلوير active**: `EnsureUserIsActive` يطرد الموقوف من جلسته الحية.
- **Password::defaults()**: min 8 + أحرف وأرقام، بلا `uncompromised` (بيئات بلا إنترنت).
- **نمط Livewire 4**: فئات في `app/Livewire` + عروض في `resources/views/livewire` منفصلة — layouts: `layouts::app` / `layouts::guest` / **`layouts::public`** (صفحة عامة).
- **forgot-password**: رسالة نجاح عامة دائمًا (منع enumeration) + rate limit 3/دقيقة.
- **Seeders**: `AdminUserSeeder` يتطلب `ADMIN_INITIAL_PASSWORD` من `.env` (ليس local-only)؛ `DemoUsersSeeder`/`DemoDataSeeder` محصوران بـ `local` فقط.
- **جداول جديدة**: `beneficiaries` (CRUD + soft delete/restore)، `family_members`، `income_sources` (تجمع تلقائيًا → `monthly_income`)، `aids` (نقدية/عينية بأصناف + soft delete)، `approval_flows/stages` (قابل التهيئة)، `aid_decisions`/`decision_approvers`، `disbursements` (1:1 مع Aid، 4 طرق)، `aid_confirmations` (توكن sha256 hash + Signed URL)، `surveys`/`survey_questions`/`survey_responses`، `message_logs`، `notification_templates`، `notifications` (جرس)، `event_listeners` (discovery=false).
- **Encrypted/Masked**: آيبان (encrypted cast) + قناع جزئي (كشف آخر 4 أرقام)؛ معرف الهوية (masked جزئيًا في التقارير)؛ كشف عبر gate `bank-data-reveal` مع audit logging.
- **Media Library**: مرفقات على media disk خاص، روابط محمية (policy-based)، صيغ مقبولة (PDF/JPG/PNG).
- **tabErrors (Livewire)**: تجنب تظليل ViewErrorBag؛ استخدام `tabErrors` بدل `errors` في السياق المتعدد الألسنة.
- **Livewire components**: منع @if مباشرة داخل وسوم Blade (استخدام wire:key عند التكرار).
- **Approval Lock**: row-level lock عند اتخاذ قرار (منع سباق الدرجة الثانية).

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
- [x] **المرحلة 2 — ملف المستفيد** — CRUD كامل (إنشاء/قراءة/تحديث/حذف + استرجاع)، أفراد أسرة (family_members)، مصادر دخل (income_sources) تجمع تلقائيًا → monthly_income، آيبان + اسم الحساب (encrypted + قناع)، كشف بيانات بنكية عبر gate `bank-data-reveal` مع audit logging، مرفقات على media disk خاص بروابط محمية (policy-based)، سجل نشاط (LogsActivity)، تصنيفات، soft delete/استرجاع.
- [x] **المرحلة 3 — الإعانات وسير الموافقات** — نقدية وعينية بأصناف، محرك موافقات قابل التهيئة (flows/stages بلقطة snapshot مثبتة على كل Aid، return→draft، قرارات بrow-level lock لمنع السباق)، شاشة في الانتظار (Pending Inbox)، دور manager أُضيف، Form Requests + Policies.
- [x] **المرحلة 4 — الصرف والتسليم** — disbursements 1:1 مع الإعانة (4 طرق: تحويل بنكي/استلام مقر/مندوب/ميداني)، لقطة بيانات بنكية مقنعة (masked) + snapshot مشفر (encrypted)، إثبات تسليم على media disk خاص، تدقيق ثانٍ (confirmed_by)، أحداث تسليم مع timestamps.
- [x] **المرحلة 5 — الإشعارات** — Taqnyat SMS عبر Http مباشر (الحزمة الرسمية تعطل TLS)، Okta SDK (retries=0 + exponential backoff في Job)، خلف واجهات (SmsGatewayInterface/WhatsAppGatewayInterface) + fakes للاختبار، message_logs (قناة×حدث)، قوالب حسب حدث×قناة + settings من شاشة إعدادات (اسم المرسل)، أحداث→listeners queued، جرس (notifications) + /notifications + شاشة settings، withEvents(discover:false) منع مزدوج.
- [x] **المرحلة 6 — تأكيد الاستلام والاستبيانات** — استبيانات ببانٍ (ستة أنواع أسئلة: نص/رقم/اختيار/متعدد/تقييم/تاريخ)، ربط ببرنامج أو عام، نتائج بنسب؛ تأكيد استلام عام (aid_confirmations بتوكن sha256 hash + Signed URL صلاحية 7 أيام)، تذكير يومي بعد 3 أيام يدوّر التوكن، صفحة عامة جوال-أولًا (layouts::public) بلا بيانات حساسة ولا مبالغ نقدية، حالة Confirmed جديدة، routes/public.php، إعادة إرسال من صفحة Aid.
- [x] **المرحلة 7 — اللوحة والتقارير** — Dashboard بـ ApexCharts (x-ui.chart داعم dark/RTL) + 4 تقارير (aids مفصل/beneficiaries/financial شهري/سنوي/surveys-stub) بفلاتر + Excel RTL + PDF عربي بخط IBM Plex TTF مضمن (dompdf + storage/fonts)، تجميع شهري بـ PHP (حياد SQLite/MySQL)، معرف هوية مقنع جزئيًا في التقارير.
- [x] **المرحلة 8 — الجودة** — 178 اختبار Pest أخضر (506 توكيدات)، مراجعتان أمنيتان (1 شاملة + 1 مراحل 4-7) أُغلقت كل الملاحظات الحرجة (آيبان مكشوفة عبر Livewire manipulation، مستندات على قرص عام، seeders بكلمات معروفة في prod، سباق قرار مزدوج)، E2E متعدد الأدوار بلا أخطاء (admin/researcher/manager/data-entry)، تدقيق ترجمة: صفر مفاتيح ناقصة (33 ملف/لغة).

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

## التكاملات (مكتملة)
- **Taqnyat SMS**: `TAQNYAT_API_KEY`, `TAQNYAT_SENDER` — Http مباشر خلف `SmsGatewayInterface`، fakes للاختبار.
- **Okta Connect WhatsApp**: `OKTA_CONNECT_BASE_URL` / `TOKEN` / `CHANNEL_ID` — SDK خلف `WhatsAppGatewayInterface`، retries=0 + exponential backoff في Job، idempotencyKey، احترام RateLimitException.
- **maatwebsite/excel**: استيراد/تصدير RTL (المرحلة 7).
- **barryvdh/laravel-dompdf**: PDF عربي بـ IBM Plex TTF مضمن (storage/fonts) — المرحلة 7.
- **إعدادات المزودات**: داخل النظام (شاشة settings) — اسم المرسل والقوالب قابلة للتعديل.

## ملاحظات للجلسة القادمة
- **اختياري (بانتظار عينة Excel من المستخدم)**: استيراد بيانات حالية من Excel (maatwebsite/excel mapper) — حقول: رقم الهوية، الاسم، رقم الجوال، العنوان، مصادر الدخل (كائنات منفصلة)، التصنيفات.
- **اختياري**: توصيل تقرير الاستبيانات ببيانات survey_responses الفعلية (stub متاح، جاهز للربط).
- **اختياري**: اختبارات UI إضافية (Panel/Inbox flows، exportPdf عبر Livewire)، تفعيل WhatsApp عند اعتماد قوالب Meta من Okta.
- **فرع prod**: أنشئ للدمج النهائي عند الحد من التعديلات.
- **حسابات التجربة** (4 أدوار):
  - `admin@almoosacharity.org` / `password` (system-admin)
  - `researcher@almoosacharity.org` / `password` (باحث اجتماعي)
  - `manager@almoosacharity.org` / `password` (مدير)
  - `dataentry@almoosacharity.org` / `password` (موظف إدخال بيانات)
- **فحص E2E**: Playwright على `/opt/pw-browsers/chromium`، متعدد الأدوار بلا أخطاء.
- **بيئة التطوير**: SQLite فقط — تجنب SQL خاص بـ MySQL في migrations.
