<?php
require_once 'config.php'; require_once 'functions.php';
requireLogin(); requirePermission($pdo,'academic.view');
$stats=[];
$queries=[
 'students'=>'SELECT COUNT(*) FROM students WHERE status="active"',
 'teachers'=>'SELECT COUNT(*) FROM teachers WHERE status="active"',
 'classes'=>'SELECT COUNT(*) FROM classes',
 'subjects'=>'SELECT COUNT(*) FROM subjects'
];
foreach($queries as $k=>$q){try{$stats[$k]=(int)$pdo->query($q)->fetchColumn();}catch(Throwable $e){$stats[$k]=0;}}
$classes=$pdo->query('SELECT c.*,COUNT(s.id) student_count FROM classes c LEFT JOIN students s ON s.class_id=c.id AND s.status="active" GROUP BY c.id ORDER BY FIELD(c.stage,"رياض الأطفال","المرحلة الابتدائية","المرحلة المتوسطة","المرحلة الثانوية"),c.grade_level,c.section')->fetchAll();
$subjects=$pdo->query('SELECT * FROM subjects ORDER BY stage, name')->fetchAll();
$pageTitle='الهيكل الأكاديمي'; require 'partials/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">إدارة البنية التعليمية</p><h1>الهيكل الأكاديمي</h1><p>نظرة موحدة على المراحل والصفوف والشعب والمواد، مع أعداد الطلاب المسجلين.</p></div><div class="actions"><a class="btn btn-primary" href="classes.php?action=create" title="إضافة فصل/شعبة جديدة">+ إضافة شعبة</a><a class="btn btn-light" href="subjects.php?action=create" title="إضافة مادة دراسية">+ مادة</a></div></div>
<div class="stats-grid">
<div class="stat-card"><div class="stat-icon blue">◉</div><small>طلاب نشطون</small><strong><?=number_format($stats['students'])?></strong></div>
<div class="stat-card"><div class="stat-icon green">◇</div><small>معلمون نشطون</small><strong><?=number_format($stats['teachers'])?></strong></div>
<div class="stat-card"><div class="stat-icon orange">▦</div><small>الشعب والفصول</small><strong><?=number_format($stats['classes'])?></strong></div>
<div class="stat-card"><div class="stat-icon purple">◫</div><small>المواد</small><strong><?=number_format($stats['subjects'])?></strong></div>
</div>
<section class="panel"><div class="panel-header"><div><h2>الشعب الدراسية</h2><p class="panel-subtitle">كل شعبة مرتبطة بمرحلة وصف وعدد طلابها.</p></div><a href="classes.php">إدارة الفصول ←</a></div>
<div class="table-wrap"><table class="data-table"><thead><tr><th>المرحلة</th><th>الصف</th><th>الشعبة</th><th>الغرفة</th><th>الطلاب</th><th>العام</th></tr></thead><tbody>
<?php foreach($classes as $c): ?><tr><td><?=e($c['stage']??'-')?></td><td class="student-name"><?=e($c['grade_level']??$c['grade'])?></td><td><span class="badge badge-blue"><?=e($c['section']??$c['name'])?></span></td><td><?=e($c['room']??'-')?></td><td><?=number_format((int)$c['student_count'])?></td><td><?=e($c['academic_year']??'-')?></td></tr><?php endforeach; ?>
<?php if(!$classes): ?><tr><td class="empty" colspan="6">لا توجد شعب دراسية.</td></tr><?php endif; ?></tbody></table></div></section>
<section class="panel"><div class="panel-header"><div><h2>المواد الدراسية</h2><p class="panel-subtitle">مواد موزعة على المراحل التعليمية.</p></div><a href="subjects.php">إدارة المواد ←</a></div>
<div class="chip-grid"><?php foreach($subjects as $s): ?><div class="info-chip"><strong><?=e($s['name'])?></strong><small><?=e($s['code']??'')?><?=!empty($s['stage'])?' · '.e($s['stage']):''?></small></div><?php endforeach; ?></div></section>
<?php require 'partials/footer.php'; ?>
