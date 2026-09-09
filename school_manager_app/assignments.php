<?php
require_once 'config.php'; require_once 'functions.php'; requireLogin(); requirePermission($pdo,'teachers.manage');
if($_SERVER['REQUEST_METHOD']==='POST'){
 verifyCsrf(); $action=$_POST['action']??'';
 if($action==='save'){
  $class=(int)($_POST['class_id']??0);$subject=(int)($_POST['subject_id']??0);$teacher=(int)($_POST['teacher_id']??0);
  if(!$class||!$subject||!$teacher){flash('error','اختر الشعبة والمادة والمعلم.');redirect('assignments.php');}
  try{$st=$pdo->prepare('INSERT INTO class_subjects(class_id,subject_id,teacher_id) VALUES(:c,:s,:t) ON DUPLICATE KEY UPDATE teacher_id=VALUES(teacher_id)');$st->execute(['c'=>$class,'s'=>$subject,'t'=>$teacher]);logAudit($pdo,'توزيع مادة','class_subject',$class,'تم ربط مادة بمعلم');flash('success','تم حفظ التوزيع التعليمي.');}catch(Throwable $e){flash('error','تعذر حفظ التوزيع.');}
 } elseif($action==='delete'){
  $class=(int)($_POST['class_id']??0);$subject=(int)($_POST['subject_id']??0);
  $st=$pdo->prepare('DELETE FROM class_subjects WHERE class_id=:c AND subject_id=:s');$st->execute(['c'=>$class,'s'=>$subject]);logAudit($pdo,'حذف توزيع','class_subject',$class,'تم إزالة ربط المادة');flash('success','تم حذف التوزيع.');
 }
 redirect('assignments.php');
}
$classes=$pdo->query('SELECT id,name,stage,grade_level,section FROM classes ORDER BY stage,grade_level,section')->fetchAll();
$subjects=$pdo->query('SELECT id,name,code FROM subjects ORDER BY name')->fetchAll();
$teachers=$pdo->query('SELECT id,full_name,subject FROM teachers WHERE status="active" ORDER BY full_name')->fetchAll();
$rows=$pdo->query('SELECT cs.class_id,cs.subject_id,c.name class_name,c.stage,c.grade_level,c.section,s.name subject_name,s.code,t.full_name teacher_name FROM class_subjects cs JOIN classes c ON c.id=cs.class_id JOIN subjects s ON s.id=cs.subject_id LEFT JOIN teachers t ON t.id=cs.teacher_id ORDER BY c.stage,c.grade_level,c.section,s.name')->fetchAll();
$pageTitle='التوزيع التعليمي';require 'partials/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">الجدول الأكاديمي</p><h1>توزيع المواد والمعلمين</h1><p>اربط كل مادة بالشعبة والمعلم المسؤول عنها، لتصبح قاعدة البيانات جاهزة للتوسع لاحقًا إلى الجدول والدرجات.</p></div></div>
<section class="panel"><div class="panel-header"><h2>إضافة أو تحديث توزيع</h2></div><form method="post" data-validate><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="action" value="save"><div class="form-grid">
<div class="form-group"><label>الشعبة *</label><select class="form-control" name="class_id" required><option value="">اختر الشعبة</option><?php foreach($classes as $x):?><option value="<?=$x['id']?>"><?=e($x['stage'].' — '.$x['grade_level'].' '.$x['section'])?></option><?php endforeach;?></select></div>
<div class="form-group"><label>المادة *</label><select class="form-control" name="subject_id" required><option value="">اختر المادة</option><?php foreach($subjects as $x):?><option value="<?=$x['id']?>"><?=e($x['name'].' — '.$x['code'])?></option><?php endforeach;?></select></div>
<div class="form-group"><label>المعلم *</label><select class="form-control" name="teacher_id" required><option value="">اختر المعلم</option><?php foreach($teachers as $x):?><option value="<?=$x['id']?>"><?=e($x['full_name'].' — '.$x['subject'])?></option><?php endforeach;?></select></div>
</div><div class="form-actions"><button class="btn btn-primary" type="submit">حفظ التوزيع</button></div></form></section>
<section class="panel"><div class="panel-header"><h2>التوزيعات الحالية <span class="badge badge-blue"><?=count($rows)?></span></h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>المرحلة</th><th>الشعبة</th><th>المادة</th><th>المعلم</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><?=e($r['stage'])?></td><td class="student-name"><?=e($r['grade_level'].' '.$r['section'])?></td><td><?=e($r['subject_name'])?><small class="student-code"><?=e($r['code'])?></small></td><td><?=e($r['teacher_name']??'غير محدد')?></td><td><form method="post" class="inline-form" data-confirm-delete><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="class_id" value="<?=$r['class_id']?>"><input type="hidden" name="subject_id" value="<?=$r['subject_id']?>"><button class="btn btn-danger btn-sm" type="submit">إزالة</button></form></td></tr><?php endforeach;?>
<?php if(!$rows):?><tr><td class="empty" colspan="5">لا توجد توزيعات.</td></tr><?php endif;?></tbody></table></div></section>
<?php require 'partials/footer.php';?>
