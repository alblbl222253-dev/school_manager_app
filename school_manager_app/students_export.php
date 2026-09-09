<?php
require_once 'config.php'; require_once 'functions.php';
requireLogin(); requirePermission($pdo,'students.view');
$search=trim($_GET['search']??'');
$sql='SELECT s.student_code,s.full_name,s.gender,s.birth_date,c.name class_name,c.stage,c.grade_level,c.section,
s.guardian_name,s.guardian_phone,s.phone,s.status
FROM students s LEFT JOIN classes c ON c.id=s.class_id';
$params=[];
if($search!==''){ $sql.=' WHERE s.full_name LIKE :q OR s.student_code LIKE :q'; $params['q']="%$search%"; }
$sql.=' ORDER BY s.full_name';
$st=$pdo->prepare($sql); $st->execute($params);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="students_'.date('Y-m-d').'.csv"');
echo "\xEF\xBB\xBF";
$out=fopen('php://output','w');
fputcsv($out,['رمز الطالب','اسم الطالب','الجنس','تاريخ الميلاد','المرحلة','الصف','الشعبة','الفصل','ولي الأمر','هاتف ولي الأمر','الهاتف','الحالة']);
while($r=$st->fetch()){
    fputcsv($out,[$r['student_code'],$r['full_name'],$r['gender']==='male'?'ذكر':'أنثى',$r['birth_date'],$r['stage'],$r['grade_level'],$r['section'],$r['class_name'],$r['guardian_name'],$r['guardian_phone'],$r['phone'],$r['status']==='active'?'نشط':'غير نشط']);
}
fclose($out); exit;
