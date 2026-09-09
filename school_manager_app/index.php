<?php
require_once 'config.php';
require_once 'functions.php';
requireLogin();
requirePermission($pdo, 'dashboard.view');

$pageTitle = 'لوحة التحكم';
$studentCount = (int) $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'active'")->fetchColumn();
$teacherCount = (int) $pdo->query("SELECT COUNT(*) FROM teachers WHERE status = 'active'")->fetchColumn();
$classCount = (int) $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();
$today = date('Y-m-d');
// ننفذ الاستعلام ثم نقرأ العدد بـ fetchColumn بدل اعتبار execute نتيجة العدد.
$attendanceStatement = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE attendance_date = :day AND state = :state');
$attendanceStatement->execute(['day' => $today, 'state' => 'present']);
$attendanceCount = (int) $attendanceStatement->fetchColumn();
$recentStudents = $pdo->query('SELECT s.*, c.name AS class_name FROM students s LEFT JOIN classes c ON c.id = s.class_id ORDER BY s.id DESC LIMIT 5')->fetchAll();
$attendanceSummary = $pdo->query("SELECT state, COUNT(*) AS total FROM attendance WHERE attendance_date = CURDATE() GROUP BY state")->fetchAll();
require 'partials/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">نظرة عامة اليوم</p><h1>أهلًا بك في لوحة التحكم</h1><p>تابع مؤشرات مدرستك واتخذ قراراتك بثقة.</p></div><div class="actions">
<?php if(hasPermission($pdo,'students.manage')):?><a class="btn btn-primary" href="students.php?action=create" title="إضافة طالب جديد إلى قاعدة البيانات">+ إضافة طالب</a><?php endif;?>
<?php if(hasPermission($pdo,'attendance.manage')):?><a class="btn btn-light" href="attendance.php" title="فتح تسجيل الحضور لليوم">✓ تسجيل الحضور</a><?php endif;?>
<?php if(hasPermission($pdo,'reports.view')):?><a class="btn btn-light" href="reports.php" title="فتح مركز التقارير والطباعة">▤ التقارير</a><?php endif;?>
</div></div>
<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon blue">◉</div><small>الطلاب النشطون</small><strong><?= $studentCount ?></strong><span class="trend">↑ بيانات مباشرة</span></div>
    <div class="stat-card"><div class="stat-icon green">◇</div><small>المعلمون</small><strong><?= $teacherCount ?></strong><span class="trend">↑ ضمن الكادر</span></div>
    <div class="stat-card"><div class="stat-icon orange">▦</div><small>الفصول الدراسية</small><strong><?= $classCount ?></strong><span class="trend">هذا العام</span></div>
    <div class="stat-card"><div class="stat-icon purple">✓</div><small>حضور اليوم</small><strong><?= $attendanceCount ?></strong><span class="trend">طلاب حاضرون</span></div>
</div>
<section class="panel quick-actions"><div class="panel-header"><div><h2>اختصارات العمل</h2><p class="panel-subtitle">أكثر الإجراءات استخدامًا في مكان واحد.</p></div></div><div class="actions">
<?php if(hasPermission($pdo,'academic.view')):?><a class="btn btn-light" href="academic.php">⌘ الهيكل الأكاديمي</a><?php endif;?>
<?php if(hasPermission($pdo,'teachers.manage')):?><a class="btn btn-light" href="assignments.php">≡ توزيع المواد</a><?php endif;?>
<?php if(hasPermission($pdo,'payments.manage')):?><a class="btn btn-light" href="installments.php">! متابعة الأقساط</a><?php endif;?>
<?php if(hasPermission($pdo,'users.manage')):?><a class="btn btn-light" href="users.php?action=create">♙ مستخدم جديد</a><?php endif;?>
</div></section>
<div class="grid-2">
    <section class="panel"><div class="panel-header"><h2>آخر الطلاب المضافين</h2><a href="students.php">عرض الكل ←</a></div><div class="table-wrap"><table class="data-table"><thead><tr><th>الطالب</th><th>الفصل</th><th>الحالة</th></tr></thead><tbody><?php foreach ($recentStudents as $student): ?><tr><td><span class="student-name"><?= e($student['full_name']) ?></span><span class="student-code"><?= e($student['student_code']) ?></span></td><td><?= e($student['class_name'] ?? 'غير محدد') ?></td><td><span class="badge badge-success">نشط</span></td></tr><?php endforeach; ?><?php if (!$recentStudents): ?><tr><td class="empty" colspan="3">لا توجد بيانات بعد.</td></tr><?php endif; ?></tbody></table></div></section>
    <section class="panel"><div class="panel-header"><h2>ملخص حضور اليوم</h2><a href="attendance.php">التفاصيل ←</a></div><?php $labels = ['present' => ['حاضر', 'badge-success'], 'absent' => ['غائب', 'badge-muted'], 'late' => ['متأخر', 'badge-blue'], 'excused' => ['بعذر', 'badge-blue']]; ?><?php foreach ($attendanceSummary as $item): ?><div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--line)"><span><?= e($labels[$item['state']][0] ?? $item['state']) ?></span><strong><?= (int) $item['total'] ?></strong></div><?php endforeach; ?><?php if (!$attendanceSummary): ?><p class="empty">لم يسجل حضور اليوم بعد.</p><?php endif; ?></section>
</div>
<?php require 'partials/footer.php'; ?>
