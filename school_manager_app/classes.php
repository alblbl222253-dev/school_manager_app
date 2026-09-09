<?php
require_once 'config.php'; require_once 'functions.php'; requireLogin(); requirePermission($pdo,'classes.view');
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT)?:0; $class=null;
if($id){$st=$pdo->prepare('SELECT * FROM classes WHERE id=:id');$st->execute(['id'=>$id]);$class=$st->fetch();if(!$class){flash('error','الفصل غير موجود.');redirect('classes.php');}}
if($_SERVER['REQUEST_METHOD']==='POST'){
 requirePermission($pdo,'classes.manage'); verifyCsrf();
 $name=trim($_POST['name']??'');$stage=trim($_POST['stage']??'');$grade=trim($_POST['grade']??'');
 $section=trim($_POST['section']??'');$room=trim($_POST['room']??'');$year=trim($_POST['academic_year']??'2026 - 2027');
 $capacity=(int)($_POST['capacity']??30);
 if($name===''||$stage===''||$grade===''||$section===''||mb_strlen($name)>100||$capacity<1||$capacity>200){flash('error','أكمل بيانات الشعبة وتأكد من السعة (1 إلى 200).');redirect($id?"classes.php?id=$id":'classes.php?action=create');}
 try{
  if($id){$st=$pdo->prepare('UPDATE classes SET name=:name,stage=:stage,grade=:grade,grade_level=:grade_level,section=:section,room=:room,academic_year=:year,capacity=:capacity WHERE id=:id');$st->execute(['name'=>$name,'stage'=>$stage,'grade'=>$grade,'grade_level'=>$grade,'section'=>$section,'room'=>$room?:null,'year'=>$year?:'2026 - 2027','capacity'=>$capacity,'id'=>$id]);logAudit($pdo,'تحديث شعبة','class',$id,'تم تعديل البنية الأكاديمية');flash('success','تم تحديث الفصل.');}
  else{$st=$pdo->prepare('INSERT INTO classes(name,stage,grade,grade_level,section,room,academic_year,capacity) VALUES(:name,:stage,:grade,:grade_level,:section,:room,:year,:capacity)');$st->execute(['name'=>$name,'stage'=>$stage,'grade'=>$grade,'grade_level'=>$grade,'section'=>$section,'room'=>$room?:null,'year'=>$year?:'2026 - 2027','capacity'=>$capacity]);$new=(int)$pdo->lastInsertId();logAudit($pdo,'إضافة شعبة','class',$new,'تم إنشاء شعبة جديدة');flash('success','تمت إضافة الفصل.');}
 }catch(PDOException $e){flash('error','تعذر الحفظ. تأكد من عدم تكرار اسم الشعبة في نفس العام.');}
 redirect('classes.php');
}
$classes=$pdo->query('SELECT c.*,COUNT(s.id) student_count FROM classes c LEFT JOIN students s ON s.class_id=c.id AND s.status="active" GROUP BY c.id ORDER BY c.stage,c.grade_level,c.section')->fetchAll();
$pageTitle='الفصول والشعب';require 'partials/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">التنظيم الأكاديمي</p><h1>الفصول والشعب</h1><p>عرّف المرحلة والصف والشعبة والغرفة والسعة والعام الدراسي بدقة.</p></div><div class="actions"><a class="btn btn-primary" href="classes.php?action=create">+ إضافة شعبة</a></div></div>
<?php if($id||($_GET['action']??'')==='create'): ?>
<section class="panel"><div class="panel-header"><div><h2><?=$id?'تعديل الشعبة':'إضافة شعبة جديدة'?></h2><p class="panel-subtitle">هذه البيانات تحدد مكان الطالب في الهيكل الأكاديمي.</p></div><a href="classes.php">إلغاء</a></div>
<form method="post" data-validate><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><div class="form-grid">
<div class="form-group"><label>اسم الشعبة *</label><input class="form-control" name="name" required value="<?=e($class['name']??'')?>" placeholder="مثال: الصف الأول أ"></div>
<div class="form-group"><label>المرحلة *</label><select class="form-control" name="stage" required><option value="">اختر المرحلة</option><?php foreach(['رياض الأطفال','المرحلة الابتدائية','المرحلة المتوسطة','المرحلة الثانوية'] as $v):?><option <?=$v===($class['stage']??'')?'selected':''?>><?=e($v)?></option><?php endforeach;?></select></div>
<div class="form-group"><label>الصف *</label><input class="form-control" name="grade" required value="<?=e($class['grade_level']??$class['grade']??'')?>" placeholder="مثال: الصف الأول"></div>
<div class="form-group"><label>الشعبة *</label><input class="form-control" name="section" required value="<?=e($class['section']??'أ')?>" placeholder="أ"></div>
<div class="form-group"><label>رقم الغرفة</label><input class="form-control" name="room" value="<?=e($class['room']??'')?>" placeholder="101"></div>
<div class="form-group"><label>العام الدراسي</label><input class="form-control" name="academic_year" value="<?=e($class['academic_year']??'2026 - 2027')?>"></div>
<div class="form-group"><label>السعة القصوى</label><input class="form-control" type="number" min="1" max="200" name="capacity" value="<?=e($class['capacity']??30)?>"></div>
</div><div class="form-actions"><button class="btn btn-primary" type="submit">حفظ الشعبة</button><a class="btn btn-light" href="classes.php">إلغاء</a></div></form></section>
<?php endif;?>
<section class="panel"><div class="panel-header"><div><h2>قائمة الشعب <span class="badge badge-blue"><?=count($classes)?></span></h2></div></div><div class="table-wrap"><table class="data-table"><thead><tr><th>المرحلة</th><th>الصف</th><th>الشعبة</th><th>الغرفة</th><th>الطلاب</th><th>السعة</th><th>العام</th><th></th></tr></thead><tbody>
<?php foreach($classes as $item):?><tr><td><?=e($item['stage'])?></td><td class="student-name"><?=e($item['grade_level'])?></td><td><span class="badge badge-blue"><?=e($item['section'])?></span></td><td><?=e($item['room']?:'-')?></td><td><?=number_format((int)$item['student_count'])?></td><td><?=number_format((int)$item['capacity'])?></td><td><?=e($item['academic_year'])?></td><td><a class="btn btn-light btn-sm" href="classes.php?id=<?=(int)$item['id']?>" title="تعديل بيانات الشعبة">تعديل</a></td></tr><?php endforeach;?>
<?php if(!$classes):?><tr><td class="empty" colspan="8">لا توجد شعب.</td></tr><?php endif;?></tbody></table></div></section>
<?php require 'partials/footer.php';?>
