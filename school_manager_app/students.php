<?php
require_once 'config.php';
require_once 'functions.php';
requireLogin();
requirePermission($pdo, 'students.view');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$editing = $id !== false && $id !== null;
$student = null;

if ($editing) {
    $statement = $pdo->prepare('SELECT * FROM students WHERE id = :id');
    $statement->execute(['id' => $id]);
    $student = $statement->fetch();
    if (!$student) { flash('error', 'الطالب غير موجود.'); redirect('students.php'); }
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $statement = $pdo->prepare('SELECT s.*, c.name AS class_name FROM students s LEFT JOIN classes c ON c.id = s.class_id WHERE s.full_name LIKE :search OR s.student_code LIKE :search ORDER BY s.id DESC');
    $statement->execute(['search' => "%{$search}%"]);
    $students = $statement->fetchAll();
} else {
    $students = $pdo->query('SELECT s.*, c.name AS class_name FROM students s LEFT JOIN classes c ON c.id = s.class_id ORDER BY s.id DESC')->fetchAll();
}
$classes = $pdo->query('SELECT id, name FROM classes ORDER BY name')->fetchAll();
$pageTitle = 'إدارة الطلاب';
require 'partials/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">البيانات الأكاديمية</p><h1>إدارة الطلاب</h1><p>أضف بيانات الطلاب وتابع حالتهم وفصولهم.</p></div><?php if(hasPermission($pdo,'students.manage')): ?><div class="actions"><a class="btn btn-light" href="students_export.php" title="تصدير قائمة الطلاب إلى ملف CSV">↓ تصدير CSV</a><a class="btn btn-primary" href="students.php?action=create" title="فتح نموذج إضافة طالب">+ طالب جديد</a></div><?php endif; ?></div>
<?php if (hasPermission($pdo,'students.manage') && ($editing || ($_GET['action'] ?? '') === 'create')): ?>
<section class="panel"><div class="panel-header"><h2><?= $editing ? 'تعديل بيانات الطالب' : 'إضافة طالب جديد' ?></h2><a href="students.php">إلغاء</a></div>
<form method="post" action="student_save.php"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int) ($student['id'] ?? 0) ?>"><div class="form-grid">
<div class="form-group"><label>رمز الطالب *</label><input class="form-control" name="student_code" required value="<?= e($student['student_code'] ?? '') ?>"></div>
<div class="form-group"><label>اسم الطالب *</label><input class="form-control" name="full_name" required value="<?= e($student['full_name'] ?? '') ?>"></div>
<div class="form-group"><label>الجنس *</label><select class="form-control" name="gender"><option value="male" <?= ($student['gender'] ?? '') === 'male' ? 'selected' : '' ?>>ذكر</option><option value="female" <?= ($student['gender'] ?? '') === 'female' ? 'selected' : '' ?>>أنثى</option></select></div>
<div class="form-group"><label>تاريخ الميلاد</label><input class="form-control" type="date" name="birth_date" value="<?= e($student['birth_date'] ?? '') ?>"></div>
<div class="form-group"><label>الفصل الدراسي</label><select class="form-control" name="class_id"><option value="">غير محدد</option><?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>" <?= (string) ($student['class_id'] ?? '') === (string) $class['id'] ? 'selected' : '' ?>><?= e($class['name']) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label>حالة الطالب</label><select class="form-control" name="status"><option value="active" <?= ($student['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>نشط</option><option value="inactive" <?= ($student['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>غير نشط</option></select></div>
<div class="form-group"><label>اسم ولي الأمر</label><input class="form-control" name="guardian_name" value="<?= e($student['guardian_name'] ?? '') ?>"></div>
<div class="form-group"><label>هاتف ولي الأمر</label><input class="form-control" name="guardian_phone" value="<?= e($student['guardian_phone'] ?? '') ?>"></div>
<div class="form-group"><label>هاتف الطالب</label><input class="form-control" name="phone" value="<?= e($student['phone'] ?? '') ?>"></div>
</div><div class="form-actions"><button class="btn btn-primary" type="submit">حفظ البيانات</button><a class="btn btn-light" href="students.php">إلغاء</a></div></form></section>
<?php endif; ?>
<section class="panel"><div class="panel-header"><h2>سجل الطلاب <span class="badge badge-blue"><?= count($students) ?></span></h2><form class="search-row" method="get"><input class="form-control" name="search" placeholder="ابحث بالاسم أو الرمز..." value="<?= e($search) ?>"><button class="btn btn-light" type="submit">بحث</button></form></div><div class="table-wrap"><table class="data-table"><thead><tr><th>الطالب</th><th>الجنس</th><th>الفصل</th><th>ولي الأمر</th><th>الحالة</th><th>إجراءات</th></tr></thead><tbody><?php foreach ($students as $item): ?><tr><td><span class="student-name"><?= e($item['full_name']) ?></span><span class="student-code"><?= e($item['student_code']) ?></span></td><td><?= $item['gender'] === 'male' ? 'ذكر' : 'أنثى' ?></td><td><?= e($item['class_name'] ?? 'غير محدد') ?></td><td><?= e($item['guardian_name'] ?? '-') ?><br><small><?= e($item['guardian_phone'] ?? '') ?></small></td><td><span class="badge <?= $item['status'] === 'active' ? 'badge-success' : 'badge-muted' ?>"><?= $item['status'] === 'active' ? 'نشط' : 'غير نشط' ?></span></td><td class="actions-cell"><?php if(hasPermission($pdo,'students.manage')): ?><a class="btn btn-light btn-sm" href="students.php?id=<?= (int) $item['id'] ?>" title="تعديل بيانات الطالب">تعديل</a><form method="post" action="student_delete.php" class="inline-form" data-confirm-delete><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="btn btn-danger btn-sm" type="submit" title="حذف سجل الطالب نهائيًا">حذف</button></form><?php else: ?><span class="badge badge-muted">عرض فقط</span><?php endif; ?></td></tr><?php endforeach; ?><?php if (!$students): ?><tr><td class="empty" colspan="6">لا توجد نتائج مطابقة.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require 'partials/footer.php'; ?>
