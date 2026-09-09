<?php
require_once 'config.php'; require_once 'functions.php'; requireLogin(); requirePermission($pdo, 'settings.manage');

$settings = getSchoolSettings($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['school_name'] ?? '');
    $nameEn = trim($_POST['school_name_en'] ?? '');
    $phone = trim($_POST['school_phone'] ?? '');
    $email = trim($_POST['school_email'] ?? '');
    $website = trim($_POST['school_website'] ?? '');
    $address = trim($_POST['school_address'] ?? '');
    $addressEn = trim($_POST['school_address_en'] ?? '');
    $manager = trim($_POST['school_manager'] ?? '');
    $year = trim($_POST['academic_year'] ?? '');
    $logo = $settings['school_logo'] ?? null;

    if ($name === '') { flash('error', 'اسم المدرسة مطلوب.'); redirect('settings.php'); }

    // نتحقق من مجلد الرفع قبل استلام الملف كي تظهر رسالة مفهومة عند خطأ إعداد XAMPP.
    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        flash('error', 'تعذر إنشاء مجلد الشعار. تأكد من صلاحيات مجلد المشروع.'); redirect('settings.php');
    }
    if (!is_writable($uploadDir)) {
        flash('error', 'مجلد uploads غير قابل للكتابة. امنح XAMPP صلاحية الكتابة ثم أعد المحاولة.'); redirect('settings.php');
    }

    if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = $_FILES['school_logo'];
        if ($upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
            flash('error', 'تعذر رفع الشعار. اختر صورة صحيحة ثم حاول مجددًا.'); redirect('settings.php');
        }
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
        if (@getimagesize($upload['tmp_name']) === false) {
            flash('error', 'الملف ليس صورة صالحة.'); redirect('settings.php');
        }
        if (!isset($allowed[$mime])) {
            flash('error', 'نوع الملف غير مدعوم. ارفع شعار PNG أو JPG أو WEBP فقط.'); redirect('settings.php');
        }
        if ($upload['size'] > 2 * 1024 * 1024) {
            flash('error', 'حجم الشعار كبير. اختر ملفًا بحجم أقل من 2 ميغابايت.'); redirect('settings.php');
        }
        $fileName = 'school-logo-' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
        $target = $uploadDir . '/' . $fileName;
        if (!move_uploaded_file($upload['tmp_name'], $target)) {
            flash('error', 'لم يتم حفظ الشعار. تحقق من صلاحيات مجلد uploads.'); redirect('settings.php');
        }
        // نحذف الشعار القديم إذا كان ملفًا محليًا ضمن uploads ولا نمسح ملفات غير تابعة للنظام.
        if (!empty($logo) && str_starts_with($logo, 'uploads/')) {
            $oldFile = __DIR__ . '/' . $logo;
            if (is_file($oldFile)) @unlink($oldFile);
        }
        $logo = 'uploads/' . $fileName;
    }

    $sql = 'INSERT INTO school_settings (id,school_name,school_name_en,school_phone,school_email,school_website,school_address,school_address_en,school_logo,school_manager,academic_year) VALUES (1,:name,:name_en,:phone,:email,:website,:address,:address_en,:logo,:manager,:year) ON DUPLICATE KEY UPDATE school_name=:name,school_name_en=:name_en,school_phone=:phone,school_email=:email,school_website=:website,school_address=:address,school_address_en=:address_en,school_logo=:logo,school_manager=:manager,academic_year=:year';
    $st = $pdo->prepare($sql);
    $st->execute(['name'=>$name,'name_en'=>$nameEn,'phone'=>$phone,'email'=>$email,'website'=>$website,'address'=>$address,'address_en'=>$addressEn,'logo'=>$logo,'manager'=>$manager,'year'=>$year]);
    flash('success', 'تم حفظ إعدادات المدرسة والشعار بنجاح.'); redirect('settings.php');
}
$logoPreview = schoolLogoUrl($settings['school_logo'] ?? null);
$pageTitle='إعدادات المدرسة'; require 'partials/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">هوية المدرسة والنظام</p><h1>الإعدادات</h1><p>عدّل بيانات المدرسة وشعارها؛ وتظهر التغييرات تلقائيًا في الواجهة والتقارير الرسمية.</p></div></div>
<section class="panel settings-panel"><div class="panel-header"><h2>بيانات المدرسة</h2></div><form method="post" enctype="multipart/form-data" data-validate><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div class="form-grid"><div class="form-group"><label>اسم المدرسة بالعربية *</label><input class="form-control" name="school_name" required value="<?= e($settings['school_name'] ?? '') ?>" data-hint="سيظهر في يمين رأس النظام والتقارير."></div><div class="form-group"><label>اسم المدرسة بالإنجليزية</label><input class="form-control" name="school_name_en" dir="ltr" value="<?= e($settings['school_name_en'] ?? '') ?>" data-hint="سيظهر في يسار رأس التقرير الرسمي."></div><div class="form-group"><label>مدير المدرسة</label><input class="form-control" name="school_manager" value="<?= e($settings['school_manager'] ?? '') ?>" data-hint="يظهر في بيانات التقرير الرسمية."></div><div class="form-group"><label>رقم التواصل</label><input class="form-control" name="school_phone" inputmode="tel" value="<?= e($settings['school_phone'] ?? '') ?>" data-hint="مثال: +967 770 000 000"></div><div class="form-group"><label>البريد الإلكتروني</label><input class="form-control" type="email" name="school_email" value="<?= e($settings['school_email'] ?? '') ?>" data-hint="مثال: info@school.edu"></div><div class="form-group"><label>الموقع الإلكتروني</label><input class="form-control" type="url" name="school_website" dir="ltr" value="<?= e($settings['school_website'] ?? '') ?>" data-hint="مثال: https://school.edu"></div><div class="form-group"><label>العام الدراسي</label><input class="form-control" name="academic_year" placeholder="2026 - 2027" value="<?= e($settings['academic_year'] ?? '') ?>" data-hint="اكتب العام بصيغة 2026 - 2027."></div><div class="form-group"><label>شعار المدرسة</label><input class="form-control" type="file" name="school_logo" accept="image/png,image/jpeg,image/webp" data-hint="PNG أو JPG أو WEBP، بحد أقصى 2MB."></div><div class="form-group"><label>العنوان بالعربية</label><input class="form-control" name="school_address" value="<?= e($settings['school_address'] ?? '') ?>" data-hint="سيظهر أسفل الاسم العربي في التقرير."></div><div class="form-group"><label>العنوان بالإنجليزية</label><input class="form-control" name="school_address_en" dir="ltr" value="<?= e($settings['school_address_en'] ?? '') ?>" data-hint="سيظهر أسفل الاسم الإنجليزي في التقرير."></div></div><div class="form-actions"><button class="btn btn-primary" type="submit" title="حفظ بيانات المدرسة والشعار">حفظ الإعدادات</button></div></form></section>
<section class="panel"><div class="panel-header"><h2>معاينة الهوية</h2></div><div class="school-preview"><?php if($logoPreview): ?><img src="<?= e($logoPreview) ?>" alt="شعار المدرسة المرفوع"><?php else: ?><div class="logo-placeholder">م</div><?php endif; ?><div><strong><?= e($settings['school_name'] ?? '') ?></strong><small><?= e($settings['academic_year'] ?? '') ?> — <?= e($settings['school_address'] ?? '') ?></small></div></div></section>
<?php require 'partials/footer.php'; ?>
