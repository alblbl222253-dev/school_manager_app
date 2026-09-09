<?php
require_once 'config.php'; require_once 'functions.php'; requireLogin();
$q=trim($_GET['q']??'');$students=[];$teachers=[];$classes=[];
if($q!==''){
 $like='%'.$q.'%';
 if(hasPermission($pdo,'students.view')){$st=$pdo->prepare('SELECT id,student_code,full_name FROM students WHERE full_name LIKE :q OR student_code LIKE :q LIMIT 8');$st->execute(['q'=>$like]);$students=$st->fetchAll();}
 if(hasPermission($pdo,'teachers.view')){$st=$pdo->prepare('SELECT id,full_name,subject FROM teachers WHERE full_name LIKE :q OR subject LIKE :q LIMIT 8');$st->execute(['q'=>$like]);$teachers=$st->fetchAll();}
 if(hasPermission($pdo,'classes.view')){$st=$pdo->prepare('SELECT id,name,grade FROM classes WHERE name LIKE :q OR grade LIKE :q LIMIT 8');$st->execute(['q'=>$like]);$classes=$st->fetchAll();}
}
$pageTitle='نتائج البحث';require 'partials/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">وصول سريع للمعلومات</p><h1>البحث العام</h1><p><?= $q!==''?'نتائج البحث عن: '.e($q):'اكتب كلمة في مربع البحث للبدء.' ?></p></div></div>
<?php if($q!==''): ?><div class="grid-2"><?php if(hasPermission($pdo,'students.view')):?><section class="panel"><div class="panel-header"><h2>الطلاب</h2><span class="badge badge-blue"><?=count($students)?></span></div><?php foreach($students as $s):?><a class="search-result" href="students.php?id=<?=(int)$s['id']?>"><strong><?=e($s['full_name'])?></strong><small><?=e($s['student_code'])?></small></a><?php endforeach;?><?php if(!$students):?><p class="empty">لا يوجد طلاب مطابقون.</p><?php endif;?></section><?php endif;?><?php if(hasPermission($pdo,'teachers.view')):?><section class="panel"><div class="panel-header"><h2>المعلمون</h2><span class="badge badge-blue"><?=count($teachers)?></span></div><?php foreach($teachers as $t):?><a class="search-result" href="teachers.php?id=<?=(int)$t['id']?>"><strong><?=e($t['full_name'])?></strong><small><?=e($t['subject'])?></small></a><?php endforeach;?><?php if(!$teachers):?><p class="empty">لا يوجد معلمون مطابقون.</p><?php endif;?></section><?php endif;?></div><?php if(hasPermission($pdo,'classes.view')):?><section class="panel"><div class="panel-header"><h2>الفصول الدراسية</h2></div><?php foreach($classes as $c):?><a class="search-result" href="classes.php?id=<?=(int)$c['id']?>"><strong><?=e($c['name'])?></strong><small><?=e($c['grade'])?></small></a><?php endforeach;?><?php if(!$classes):?><p class="empty">لا توجد فصول مطابقة.</p><?php endif;?></section><?php endif;?><?php endif;?>
<?php require 'partials/footer.php'; ?>
