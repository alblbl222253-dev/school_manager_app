<?php
require_once 'config.php'; require_once 'functions.php';
requireLogin(); requirePermission($pdo,'students.manage'); verifyCsrf();

$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT)?:0;
$code=trim($_POST['student_code']??''); $name=trim($_POST['full_name']??'');
$gender=$_POST['gender']??''; $birth=trim($_POST['birth_date']??'');
$classId=filter_input(INPUT_POST,'class_id',FILTER_VALIDATE_INT);
$classId=$classId?:null; $status=$_POST['status']??'active';
$guardian=trim($_POST['guardian_name']??''); $guardianPhone=trim($_POST['guardian_phone']??''); $phone=trim($_POST['phone']??'');

$validGender=['male','female']; $validStatus=['active','inactive'];
$errors=[];
if($code===''||mb_strlen($code)>30) $errors[]='رمز الطالب مطلوب وبحد أقصى 30 حرفًا.';
if($name===''||mb_strlen($name)>120) $errors[]='اسم الطالب مطلوب وبحد أقصى 120 حرفًا.';
if(!in_array($gender,$validGender,true)) $errors[]='اختر جنسًا صحيحًا.';
if(!in_array($status,$validStatus,true)) $errors[]='اختر حالة صحيحة.';
if($birth!==''&&!validDate($birth)) $errors[]='تاريخ الميلاد غير صحيح.';
foreach([['الهاتف',$phone,30],['هاتف ولي الأمر',$guardianPhone,30],['اسم ولي الأمر',$guardian,120]] as [$label,$value,$max]){
    if(mb_strlen($value)>$max) $errors[]="$label يتجاوز الحد المسموح.";
}
if($classId){
    $st=$pdo->prepare('SELECT id,capacity FROM classes WHERE id=:id LIMIT 1'); $st->execute(['id'=>$classId]); $class=$st->fetch();
    if(!$class) $errors[]='الفصل المحدد غير موجود.';
    else { $st=$pdo->prepare('SELECT COUNT(*) FROM students WHERE class_id=:cid AND status="active" AND id<>:id'); $st->execute(['cid'=>$classId,'id'=>$id]); if((int)$st->fetchColumn() >= (int)$class['capacity'] && $status==='active') $errors[]='الشعبة وصلت إلى السعة القصوى. اختر شعبة أخرى أو زد السعة أولًا.'; }
}
if($errors){ flash('error',implode(' ',$errors)); redirect($id?"students.php?id=$id":'students.php?action=create'); }

try{
    if($id){
        $st=$pdo->prepare('UPDATE students SET student_code=:code,full_name=:name,gender=:gender,birth_date=:birth,phone=:phone,guardian_name=:guardian,guardian_phone=:guardian_phone,class_id=:class_id,status=:status WHERE id=:id');
        $st->execute(['code'=>$code,'name'=>$name,'gender'=>$gender,'birth'=>$birth?:null,'phone'=>$phone?:null,'guardian'=>$guardian?:null,'guardian_phone'=>$guardianPhone?:null,'class_id'=>$classId,'status'=>$status,'id'=>$id]);
        logAudit($pdo,'تحديث طالب','student',$id,'تم تعديل بيانات الطالب');
        flash('success','تم تحديث بيانات الطالب بنجاح.');
    }else{
        $st=$pdo->prepare('INSERT INTO students(student_code,full_name,gender,birth_date,phone,guardian_name,guardian_phone,class_id,status) VALUES(:code,:name,:gender,:birth,:phone,:guardian,:guardian_phone,:class_id,:status)');
        $st->execute(['code'=>$code,'name'=>$name,'gender'=>$gender,'birth'=>$birth?:null,'phone'=>$phone?:null,'guardian'=>$guardian?:null,'guardian_phone'=>$guardianPhone?:null,'class_id'=>$classId,'status'=>$status]);
        $newId=(int)$pdo->lastInsertId();
        logAudit($pdo,'إضافة طالب','student',$newId,'تم إنشاء سجل طالب جديد');
        flash('success','تمت إضافة الطالب بنجاح.');
    }
}catch(PDOException $e){
    $msg=$e->getCode()==='23000'?'رمز الطالب مستخدم مسبقًا أو توجد بيانات مكررة.':'تعذر حفظ البيانات. تحقق من القيم ثم حاول مرة أخرى.';
    flash('error',$msg);
}
redirect('students.php');
