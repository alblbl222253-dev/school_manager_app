<?php
require_once 'config.php'; require_once 'functions.php';
requireLogin(); requirePermission($pdo,'students.manage');
if($_SERVER['REQUEST_METHOD']!=='POST'){ redirect('students.php'); }
verifyCsrf();
$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT)?:0;
if(!$id){ flash('error','معرّف الطالب غير صالح.'); redirect('students.php'); }
try{
    $st=$pdo->prepare('SELECT full_name FROM students WHERE id=:id'); $st->execute(['id'=>$id]); $student=$st->fetch();
    if(!$student){ flash('error','الطالب غير موجود.'); redirect('students.php'); }
    $st=$pdo->prepare('DELETE FROM students WHERE id=:id'); $st->execute(['id'=>$id]);
    logAudit($pdo,'حذف طالب','student',$id,'تم حذف: '.$student['full_name']);
    flash('success','تم حذف الطالب بنجاح.');
}catch(Throwable $e){ flash('error','تعذر حذف الطالب.'); }
redirect('students.php');
