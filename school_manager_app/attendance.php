<?php
require_once 'config.php'; require_once 'functions.php'; requireLogin(); requirePermission($pdo, 'attendance.view');
$day=$_GET['date']??date('Y-m-d');
if ($_SERVER['REQUEST_METHOD']==='POST') {
    requirePermission($pdo, 'attendance.manage');
    verifyCsrf();
    $studentId=(int)($_POST['student_id']??0); $state=$_POST['state']??'present'; $note=trim($_POST['note']??''); $date=$_POST['attendance_date']??date('Y-m-d');
    if ($studentId && validDate($date) && mb_strlen($note)<=255 && in_array($state,['present','absent','late','excused'],true)) {
        $check=$pdo->prepare('SELECT id FROM students WHERE id=:id AND status="active"'); $check->execute(['id'=>$studentId]);
        if(!$check->fetchColumn()){ flash('error','الطالب المحدد غير موجود أو غير نشط.'); redirect('attendance.php?date='.urlencode($date)); }
        $sql='INSERT INTO attendance (student_id,attendance_date,state,note) VALUES (:student,:day,:state,:note) ON DUPLICATE KEY UPDATE state=VALUES(state), note=VALUES(note)';
        $st=$pdo->prepare($sql); $st->execute(['student'=>$studentId,'day'=>$date,'state'=>$state,'note'=>$note?:null]); logAudit($pdo,'تسجيل حضور','attendance',$studentId,'التاريخ: '.$date.' الحالة: '.$state); flash('success','تم حفظ حالة الحضور.');
    }
    redirect('attendance.php?date='.urlencode($date));
}
$students=$pdo->query("SELECT id,student_code,full_name FROM students WHERE status='active' ORDER BY full_name")->fetchAll();
$st=$pdo->prepare('SELECT a.*,s.full_name,s.student_code FROM attendance a JOIN students s ON s.id=a.student_id WHERE a.attendance_date=:day ORDER BY s.full_name'); $st->execute(['day'=>$day]); $records=$st->fetchAll();
$pageTitle='الحضور والغياب'; require 'partials/header.php';
$states=['present'=>'حاضر','absent'=>'غائب','late'=>'متأخر','excused'=>'بعذر'];
?>
<div class="page-heading"><div><p class="eyebrow">المتابعة اليومية</p><h1>الحضور والغياب</h1><p>سجل حضور الطلاب وتابع حالاتهم اليومية.</p></div></div>
<section class="panel"><div class="panel-header"><h2>تسجيل حالة</h2><form class="search-row" method="get"><input class="form-control" type="date" name="date" value="<?= e($day) ?>"><button class="btn btn-light">عرض اليوم</button></form></div><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div class="form-grid"><div class="form-group"><label>الطالب</label><select class="form-control" name="student_id" required><option value="">اختر الطالب</option><?php foreach($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['full_name']) ?> — <?= e($s['student_code']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>التاريخ</label><input class="form-control" type="date" name="attendance_date" value="<?= e($day) ?>"></div><div class="form-group"><label>الحالة</label><select class="form-control" name="state"><?php foreach($states as $key=>$label): ?><option value="<?= $key ?>"><?= $label ?></option><?php endforeach; ?></select></div><div class="form-group"><label>ملاحظة</label><input class="form-control" name="note"></div></div><div class="form-actions"><button class="btn btn-primary">حفظ الحضور</button></div></form></section>
<section class="panel"><div class="panel-header"><h2>سجل يوم <?= e($day) ?></h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>الطالب</th><th>الحالة</th><th>الملاحظة</th></tr></thead><tbody><?php foreach($records as $r): ?><tr><td class="student-name"><?= e($r['full_name']) ?><small class="student-code"><?= e($r['student_code']) ?></small></td><td><span class="badge badge-blue"><?= e($states[$r['state']]??$r['state']) ?></span></td><td><?= e($r['note']?:'-') ?></td></tr><?php endforeach; ?><?php if(!$records): ?><tr><td class="empty" colspan="3">لم يتم تسجيل حالات لهذا اليوم.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require 'partials/footer.php'; ?>
