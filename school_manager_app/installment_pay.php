<?php
require_once 'config.php'; require_once 'functions.php'; requireLogin(); requirePermission($pdo, 'payments.manage');
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$id){redirect('installments.php');}
$st=$pdo->prepare('SELECT i.*,s.full_name,s.student_code FROM installments i JOIN students s ON s.id=i.student_id WHERE i.id=:id');$st->execute(['id'=>$id]);$item=$st->fetch();if(!$item){flash('error','القسط غير موجود.');redirect('installments.php');}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verifyCsrf();$amount=(float)($_POST['amount']??0);if($amount<=0){flash('error','أدخل مبلغًا صحيحًا.');redirect('installment_pay.php?id='.$id);}
 $remaining=max(0,(float)$item['total_amount']-(float)$item['paid_amount']);
 if($amount>$remaining+0.00001){ flash('error','مبلغ الدفعة أكبر من المتبقي.'); redirect('installment_pay.php?id='.$id); }
 $newPaid=(float)$item['paid_amount']+$amount;$status=$newPaid>=(float)$item['total_amount']?'paid':($newPaid>0?'partial':'pending');if($item['due_date']<date('Y-m-d')&&$status!=='paid')$status='overdue';
 $pdo->beginTransaction();try{$st=$pdo->prepare('UPDATE installments SET paid_amount=:paid,status=:status WHERE id=:id');$st->execute(['paid'=>$newPaid,'status'=>$status,'id'=>$id]);$st=$pdo->prepare('INSERT INTO payments(student_id,amount,payment_date,description) VALUES(:student,:amount,CURDATE(),:description)');$st->execute(['student'=>$item['student_id'],'amount'=>$amount,'description'=>'دفعة على '.$item['title']]);logAudit($pdo,'تسجيل دفعة','installment',(int)$id,'المبلغ: '.formatMoney($amount)); $pdo->commit();flash('success','تم تسجيل الدفعة وتحديث حالة القسط.');}catch(Throwable $e){$pdo->rollBack();flash('error','تعذر تسجيل الدفعة.');}redirect('installments.php');
}
$pageTitle='تسجيل دفعة قسط';require 'partials/header.php';$remaining=(float)$item['total_amount']-(float)$item['paid_amount'];
?>
<div class="page-heading"><div><p class="eyebrow">تحديث الاستحقاق</p><h1>تسجيل دفعة قسط</h1><p><?=e($item['full_name'])?> — المتبقي: <?=formatMoney($remaining)?></p></div></div><section class="panel"><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><div class="form-grid"><div class="form-group"><label>القسط</label><input class="form-control" value="<?=e($item['title'])?>" readonly></div><div class="form-group"><label>المبلغ المدفوع الآن</label><input class="form-control" type="number" name="amount" min="1" max="<?=$remaining?>" step="0.01" required></div></div><div class="form-actions"><button class="btn btn-primary">تسجيل الدفعة</button><a class="btn btn-light" href="installments.php">إلغاء</a></div></form></section>
<?php require 'partials/footer.php'; ?>
