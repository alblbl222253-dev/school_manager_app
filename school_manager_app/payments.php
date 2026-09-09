<?php
require_once 'config.php'; require_once 'functions.php'; requireLogin(); requirePermission($pdo, 'payments.view');
if ($_SERVER['REQUEST_METHOD']==='POST') { requirePermission($pdo, 'payments.manage');
    verifyCsrf();
    $studentId=(int)($_POST['student_id']??0); $amount=(float)($_POST['amount']??0); $date=$_POST['payment_date']??date('Y-m-d'); $description=trim($_POST['description']??'');
    if ($studentId && $amount > 0 && validDate($date) && mb_strlen($description)<=255) {
        $check=$pdo->prepare('SELECT id FROM students WHERE id=:id AND status="active"'); $check->execute(['id'=>$studentId]);
        if(!$check->fetchColumn()){ flash('error','الطالب المحدد غير موجود أو غير نشط.'); }
        else { $st=$pdo->prepare('INSERT INTO payments (student_id,amount,payment_date,description) VALUES (:student,:amount,:day,:description)'); $st->execute(['student'=>$studentId,'amount'=>$amount,'day'=>$date,'description'=>$description?:null]); logAudit($pdo,'تسجيل دفعة','payment',(int)$pdo->lastInsertId(),'المبلغ: '.formatMoney($amount)); flash('success','تم تسجيل الدفعة.'); }
    }
    else { flash('error','اختر طالبًا وأدخل مبلغًا صحيحًا.'); }
    redirect('payments.php');
}
$students=$pdo->query("SELECT id,full_name,student_code FROM students WHERE status='active' ORDER BY full_name")->fetchAll();
$payments=$pdo->query('SELECT p.*,s.full_name,s.student_code FROM payments p JOIN students s ON s.id=p.student_id ORDER BY p.id DESC LIMIT 30')->fetchAll();
$total=(float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments')->fetchColumn();
$pageTitle='الرسوم والمدفوعات'; require 'partials/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">الإدارة المالية</p><h1>الرسوم والمدفوعات</h1><p>سجل التحصيلات وتابع إجمالي المدفوعات.</p></div></div>
<div class="stats-grid"><div class="stat-card"><div class="stat-icon green">ر</div><small>إجمالي المدفوعات</small><strong><?= number_format($total,2) ?></strong><span class="trend">بالعملة المحلية</span></div></div>
<section class="panel"><div class="panel-header"><h2>تسجيل دفعة</h2></div><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div class="form-grid"><div class="form-group"><label>الطالب</label><select class="form-control" name="student_id" required><option value="">اختر الطالب</option><?php foreach($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['full_name']) ?> — <?= e($s['student_code']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>المبلغ</label><input class="form-control" type="number" step="0.01" min="0.01" name="amount" required></div><div class="form-group"><label>التاريخ</label><input class="form-control" type="date" name="payment_date" value="<?= date('Y-m-d') ?>"></div><div class="form-group"><label>الوصف</label><input class="form-control" name="description" placeholder="رسوم شهرية"></div></div><div class="form-actions"><button class="btn btn-primary">حفظ الدفعة</button></div></form></section>
<section class="panel"><div class="panel-header"><h2>آخر الدفعات</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>الطالب</th><th>المبلغ</th><th>التاريخ</th><th>الوصف</th></tr></thead><tbody><?php foreach($payments as $p): ?><tr><td class="student-name"><?= e($p['full_name']) ?><small class="student-code"><?= e($p['student_code']) ?></small></td><td><?= number_format((float)$p['amount'],2) ?></td><td><?= e($p['payment_date']) ?></td><td><?= e($p['description']?:'-') ?></td></tr><?php endforeach; ?><?php if(!$payments): ?><tr><td class="empty" colspan="4">لا توجد دفعات.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require 'partials/footer.php'; ?>
