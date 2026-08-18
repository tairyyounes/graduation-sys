# توثيق تنفيذ الـMultilingual Support (عربي / إنجليزي)

> ملف يوثّق كل خطوة وكل تغيير في إضافة دعم اللغتين للنظام.
> اللغة الافتراضية: الإنجليزية. اللغة المضافة: العربية (مع RTL).

## التقنيات المستعملة
- **Frontend (Vue 3):** `vue-i18n@11` + ملفات JSON (`en.json`, `ar.json`).
- **Backend (Laravel 11):** ملفات ترجمة JSON (`lang/en.json`, `lang/ar.json`) + Middleware لضبط اللغة.
- **RTL:** تبديل `dir` على `<html>` حسب اللغة.
- **التخزين:** `localStorage` + cookie (`app_locale`) — الكوكي باش الـBackend يقرا اللغة.

---

## سجلّ التغييرات (Changelog)

### المرحلة ١ — البنية التحتية للـFrontend ✅

- **تركيب المكتبة:** `npm install vue-i18n@11` → النسخة `11.4.8`.
- **`resources/js/i18n.js` (جديد):** إعداد `createI18n` (Composition API)، كشف اللغة (localStorage → cookie → html lang → en)، دالة `setLocale()` تحفظ في localStorage + cookie `app_locale` وتقلب `dir`، دالة `applyDirection()`.
- **`resources/js/locales/en.json` + `ar.json` (جديد):** ملفات الترجمة، منظّمة بـnamespaces (`common`, `risk`, `student.overview`…). تتوسّع مع كل component.
- **تسجيل i18n على كل الـapps:**
  - `resources/js/app.js` → `app.use(i18n)` على app + student dashboard.
  - `resources/js/admin-dashboard.js` → `app.use(i18n)`.
  - `resources/js/department-dashboard.js` → `app.use(i18n)`.
  - (`welcome-page.js` فارغ، تجاوزناه.)
- **`resources/js/components/common/LangToggle.vue` (جديد):** زر تبديل English / العربية، ينادي `setLocale()`.
- **إدماج الزر:** `resources/js/components/layouts/AppDashboardLayout.vue` — الزر في الهيدر (admin + department)، + تعريب "Menu" و"Back to Home" و"Logout"، + إصلاح RTL (`ms-auto`, `rtl:-scale-x-100` للسهم).

### المرحلة ٢ — البنية التحتية للـBackend (Laravel) ✅

- **`app/Http/Middleware/SetLocale.php` (جديد):** يقرا كوكي `app_locale` ويضبط `app()->setLocale()` لو `en`/`ar`.
- **`bootstrap/app.php`:** تسجيل `SetLocale` في الـweb middleware group.
- **ملفات ترجمة Laravel — الطريقة المنظّمة (PHP) وليس JSON:**
  - **تصحيح:** في البداية أنشأنا `lang/en.json`/`lang/ar.json`، ثم بدّلناهم للطريقة الأصلية المنظّمة متاع Laravel (أنظف للـvalidation والرسائل المنظّمة).
  - `lang/en/messages.php` + `lang/ar/messages.php` (جديد): رسائل منظّمة، تُستعمل عبر `__('messages.similarity.hidden')`.
  - `validation.php` بالعربي: يتزاد في مرحلة رسائل الـBackend.
  - **القرار:** Vue = JSON (مرتّب للواجهة)، Laravel = PHP منظّم (أنظف للـvalidation). مش فرض JSON على الاثنين.
- **الـBlade layouts (7 ملفات):** إضافة `dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"` على `<html>`:
  `layouts/app.blade.php`, `layouts/guest.blade.php`, `welcome.blade.php`, `admin/vue-dashboard.blade.php`, `student/dashboard.blade.php`, `auth/login.blade.php`, `department/vue-dashboard.blade.php`.

- **التحقّق:** `npm run build` نجح بدون أخطاء — الـi18n bundle مبني.

### المرحلة ٣ — تعريب الـcomponents (جارٍ) 🔄

- [x] **Student dashboard — مكتمل ✅** (14 مكوّن):
  - `StudentDashboard.vue` (الهيكل + nav + كل الـtoasts/confirms + `viewLabel` + RTL sidebar)
  - `StudentOverviewSection`, `StudentWorkspaceSection` (+ `tabLabel`), `StudentTeamSection`,
    `StudentSimilaritySection`, `StudentVersionHistorySection`, `StudentFeedbackSection`,
    `StudentRepoSection`, `StudentCompareSection`, `StudentProposalCard`,
    `StudentNewProposalModal` (+ رسائل validation), `StudentProposalModal`, `StudentInviteMemberModal`, `LangToggle`
  - namespaces مضافة في JSON: `common`, `nav`, `risk`, `fields`, `similarity`,
    `student.{overview,confirm,toast,team,invite,feedback,version,card,compare,repo,workspace,form,modal,simreport}`
  - إصلاحات RTL: `ms/me`, `ps/pe`, `start/end`, `text-start/end`, `rtl:space-x-reverse`, `rtl:-scale-x-100` للأسهم
  - **البناء نظيف بعد كل مكوّن** ✅
- [x] **Department dashboard** — كامل: `DepartmentDashboard` shell + `deptnav`, Overview, Queue, Decisions,
  Students (+`DepartmentStudentModal`), Members, Committees (+`DepartmentCommitteeModal`),
  Proposal, Compare, + common: `DeleteConfirmationModal`, `HistoricalProposalsManager`, `HistoricalProposalsList`
  - namespaces مضافة: `deptnav`, `status`, `hist`, `dept.{review_queue,students,student_modal,members,committees,committee_modal,proposal,compare,...}`
  - `formatStatus` تستعمل `status.*` مع fallback؛ التواريخ تستعمل `ar` locale
- [x] **Admin dashboard** — مكتمل ✅: `AdminDashboard` shell + `adminnav`, Overview (مع محول كروت الإحصاءات), Users (+ `AdminUserModal`), Departments (+ `AdminDepartmentModal`), Department Details, Activity Logs
- [x] **Welcome + auth pages** — مكتمل ✅: `welcomePage.vue` + واجهات المصادقة في Blade (`login`, `register`, `forgot-password`, `reset-password`, `verify-email`, `confirm-password`)
- [x] **`lang/ar/validation.php` بالعربي** — مكتمل ✅: ملف رسائل validation الـBackend وتسميات الحقول بالعربي

