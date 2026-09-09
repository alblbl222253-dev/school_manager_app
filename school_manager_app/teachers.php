<?php
require_once 'config.php'; require_once 'functions.php'; requireLogin(); requirePermission($pdo, 'teachers.view');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$teacher = null;
if ($id) { $st = $pdo->prepare('SELECT * FROM teachers WHERE id=:id'); $st->execute(['id'=>$id]); $teacher = $st->fetch(); }
// عند إرسال النموذج نتحقق من البيانات ثم نقرر هل ننشئ سجلًا أم نحدث سجلًا.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePermission($pdo, 'teachers.manage');
    verifyCsrf();
    $name=trim($_POST['full_name']??'');$subject=trim($_POST['subject']??'');$employee=trim($_POST['employee_code']??'');$phone=trim($_POST['phone']??'');$email=trim($_POST['email']??'');$status=$_POST['status']??'active';
    if($name===''||$subject===''||mb_strlen($name)>120||mb_strlen($subject)>100||mb_strlen($employee)>40||!in_array($status,['active','inactive'],true)){flash('error','تحقق من بيانات المعلم.');redirect($id?"teachers.php?id=$id":'teachers.php?action=create');}
    try{
      if($id){$st=$pdo->prepare('UPDATE teachers SET full_name=:name,employee_code=:employee,subject=:subject,phone=:phone,email=:email,status=:status WHERE id=:id');$st->execute(['name'=>$name,'employee'=>$employee?:null,'subject'=>$subject,'phone'=>$phone?:null,'email'=>$email?:null,'status'=>$status,'id'=>$id]);logAudit($pdo,'تحديث معلم','teacher',$id,'تم تعديل بيانات المعلم');flash('success','تم تحديث بيانات المعلم.');}
      else{$st=$pdo->prepare('INSERT INTO teachers(full_name,employee_code,subject,phone,email,status) VALUES(:name,:employee,:subject,:phone,:email,:status)');$st->execute(['name'=>$name,'employee'=>$employee?:null,'subject'=>$subject,'phone'=>$phone?:null,'email'=>$email?:null,'status'=>$status]);$new=(int)$pdo->lastInsertId();logAudit($pdo,'إضافة معلم','teacher',$new,'تم إنشاء معلم جديد');flash('success','تمت إضافة المعلم.');}
    }catch(PDOException $e){flash('error','تعذر الحفظ. قد يكون رمز الموظف مستخدمًا مسبقًا.');}
    redirect('teachers.php');
}
// جلب جميع المعلمين لعرضهم في الجدول.
$teachers = $pdo->query('SELECT * FROM teachers ORDER BY id DESC')->fetchAll();
$pageTitle = 'المعلمون'; require 'partials/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">الكادر التعليمي</p><h1>المعلمون</h1><p>إدارة بيانات المعلمين وتخصصاتهم.</p></div><div class="actions"><a class="btn btn-primary" href="teachers.php?action=create">+ إضافة معلم</a></div></div>
<?php if ($id || ($_GET['action'] ?? '') === 'create'): ?><section class="panel"><div class="panel-header"><h2><?= $id ? 'تعديل بيانات المعلم' : 'إضافة معلم جديد' ?></h2><a href="teachers.php">إلغاء</a></div><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div class="form-grid"><div class="form-group"><label>الاسم الكامل *</label><input class="form-control" name="full_name" required value="<?= e($teacher['full_name'] ?? '') ?>"></div><div class="form-group"><label>رمز الموظف</label><input class="form-control" name="employee_code" dir="ltr" value="<?= e($teacher['employee_code'] ?? '') ?>" placeholder="T-015"></div><div class="form-group"><label>التخصص *</label><input class="form-control" name="subject" required value="<?= e($teacher['subject'] ?? '') ?>"></div><div class="form-group"><label>الهاتف</label><input class="form-control" name="phone" value="<?= e($teacher['phone'] ?? '') ?>"></div><div class="form-group"><label>البريد الإلكتروني</label><input class="form-control" type="email" name="email" value="<?= e($teacher['email'] ?? '') ?>"></div><div class="form-group"><label>الحالة</label><select class="form-control" name="status"><option value="active">نشط</option><option value="inactive" <?= ($teacher['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>غير نشط</option></select></div></div><div class="form-actions"><button class="btn btn-primary">حفظ البيانات</button><a class="btn btn-light" href="teachers.php">إلغاء</a></div></form></section><?php endif; ?>
<section class="panel"><div class="panel-header"><h2>دليل المعلمين</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>المعلم</th><th>الرمز</th><th>التخصص</th><th>الهاتف</th><th>البريد</th><th>الحالة</th><th>إجراء</th></tr></thead><tbody><?php foreach ($teachers as $item): ?><tr><td class="student-name"><?= e($item['full_name']) ?></td><td><?= e($item['employee_code'] ?: '-') ?></td><td><?= e($item['subject']) ?></td><td><?= e($item['phone'] ?: '-') ?></td><td><?= e($item['email'] ?: '-') ?></td><td><span class="badge <?= $item['status']==='active'?'badge-success':'badge-muted' ?>"><?= $item['status']==='active'?'نشط':'غير نشط' ?></span></td><td><a class="btn btn-light btn-sm" href="teachers.php?id=<?= (int)$item['id'] ?>">تعديل</a></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require 'partials/footer.php'; ?>
