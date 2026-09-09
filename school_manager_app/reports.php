<?php
require_once 'config.php'; require_once 'functions.php'; requireLogin(); requirePermission($pdo, 'reports.view');

$school = getSchoolSettings($pdo);
$type = $_GET['type'] ?? 'students';
$reportId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$types = ['students'=>'الطلاب','teachers'=>'المعلمون','subjects'=>'المواد','financial'=>'مالي','custom'=>'تقرير مخصص'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePermission($pdo, 'reports.create');
    verifyCsrf();
    $title = trim($_POST['title'] ?? '');
    $content = sanitizeReportHtml($_POST['report_html'] ?? '');
    if ($title === '' || trim(strip_tags($content)) === '') {
        $error = 'اكتب عنوان التقرير وأضف محتوى واضحًا قبل الحفظ.';
        $type = 'custom';
    } else {
        $st = $pdo->prepare("INSERT INTO reports(title,report_type,content,created_by) VALUES(:title,'custom',:content,:user)");
        $st->execute(['title'=>$title,'content'=>$content,'user'=>$_SESSION['user']['id']]);
        $newId = (int)$pdo->lastInsertId();
        logAudit($pdo, 'create_report', 'report', $newId, $title);
        flash('success', 'تم حفظ التقرير المخصص وهو جاهز للمعاينة والطباعة.');
        redirect('reports.php?id='.$newId);
    }
}

$title = 'تقرير الطلاب'; $columns = []; $rows = []; $customContent = null;
if ($reportId) {
    $st = $pdo->prepare('SELECT * FROM reports WHERE id=:id'); $st->execute(['id'=>$reportId]); $report = $st->fetch();
    if ($report) { $type='custom'; $title=$report['title']; $customContent=sanitizeReportHtml($report['content']); }
} elseif ($type === 'teachers') {
    $title='تقرير المعلمين'; $columns=['الاسم','التخصص','الهاتف','البريد']; $rows=$pdo->query('SELECT full_name,subject,phone,email FROM teachers ORDER BY full_name')->fetchAll();
} elseif ($type === 'subjects') {
    $title='تقرير المواد الدراسية'; $columns=['اسم المادة','الرمز','الوصف']; $rows=$pdo->query('SELECT name,code,description FROM subjects ORDER BY name')->fetchAll();
} elseif ($type === 'financial') {
    $title='تقرير المدفوعات والأقساط'; $columns=['الطالب','اسم القسط','المتبقي','تاريخ الاستحقاق','الحالة']; $rows=$pdo->query("SELECT s.full_name,i.title,(i.total_amount-i.paid_amount) balance,i.due_date,i.status FROM installments i JOIN students s ON s.id=i.student_id ORDER BY i.due_date")->fetchAll();
} elseif ($type === 'custom') {
    $title='كتابة تقرير رسمي';
} else {
    $columns=['رمز الطالب','الاسم','الفصل','الحالة']; $rows=$pdo->query("SELECT s.student_code,s.full_name,COALESCE(c.name,'غير محدد') class_name,s.status FROM students s LEFT JOIN classes c ON c.id=s.class_id ORDER BY s.full_name")->fetchAll();
}
$savedReports=[]; try { $savedReports=$pdo->query('SELECT id,title,created_at FROM reports ORDER BY id DESC LIMIT 10')->fetchAll(); } catch(Throwable $exception) {}
$schoolLogo = schoolLogoUrl($school['school_logo'] ?? null);
$pageTitle='التقارير والطباعة'; require 'partials/header.php';
?>
<link rel="stylesheet" href="assets/vendor/quill/quill.snow.css">
<div class="page-heading no-print"><div><p class="eyebrow">وثائق مدرسية رسمية</p><h1>مركز التقارير</h1><p>أنشئ تقريرًا من البيانات أو اكتب تقريرًا حرًا، ثم راجعه واطبعه بهوية المدرسة.</p></div><div class="actions"><?php if(hasPermission($pdo,'reports.print')): ?><button class="btn btn-primary" type="button" onclick="window.print()" title="طباعة التقرير الظاهر بهوية المدرسة">طباعة / حفظ PDF</button><?php endif; ?></div></div>
<div class="report-tabs no-print"><?php foreach($types as $key=>$label): if($key==='custom' && !hasPermission($pdo,'reports.create')) continue; ?><a href="reports.php?type=<?=e($key)?>" class="<?= $type===$key?'active':''?>" title="عرض <?=e($label)?>"><?=e($label)?></a><?php endforeach; ?></div>

<?php if($type==='custom' && !$reportId && hasPermission($pdo,'reports.create')): ?>
<section class="panel no-print"><div class="panel-header"><div><h2>محرر التقرير المتقدم</h2><p class="panel-subtitle">يمكنك تغيير الخط واللون والمحاذاة وإضافة رموز وروابط وصور وجداول.</p></div></div>
<?php if($error): ?><div class="alert alert-error" role="alert"><?=e($error)?></div><?php endif; ?>
<form method="post" id="reportForm" data-validate><input type="hidden" name="csrf_token" id="reportCsrf" value="<?=e(csrfToken())?>"><input type="hidden" name="report_html" id="reportHtml"><div class="form-group"><label for="reportTitle">عنوان التقرير *</label><input class="form-control" id="reportTitle" name="title" required value="<?=e($_POST['title']??'')?>" placeholder="مثال: تقرير اجتماع أولياء الأمور" data-hint="سيظهر العنوان في منتصف التقرير المطبوع."></div><div class="form-group" style="margin-top:18px"><label>محتوى التقرير *</label><div id="reportEditor" class="rich-editor" aria-label="محرر محتوى التقرير"></div><small class="field-help">استخدم زر الصورة لرفع PNG أو JPG أو WEBP بحجم لا يزيد عن 2MB.</small></div><div class="form-actions"><button class="btn btn-primary" type="submit" title="حفظ التقرير وفتحه للمعاينة">حفظ وفتح التقرير</button><a class="btn btn-light" href="reports.php?type=students">إلغاء</a></div></form></section>
<?php endif; ?>

<section class="print-report">
<header class="report-header report-header-v3">
    <div class="report-school-ar" dir="rtl"><h2><?=e($school['school_name'] ?? 'مدرسة مدار')?></h2><p><?=e($school['school_address'] ?? '')?></p><p><?=e($school['school_phone'] ?? '')?></p><p><?=e($school['school_website'] ?? $school['school_email'] ?? '')?></p></div>
    <div class="report-logo-wrap"><?php if($schoolLogo): ?><img src="<?=e($schoolLogo)?>" alt="شعار المدرسة"><?php else: ?><div class="report-logo">م</div><?php endif; ?></div>
    <div class="report-school-en" dir="ltr"><h2><?=e($school['school_name_en'] ?? $school['school_name'] ?? 'Madar School')?></h2><p><?=e($school['school_address_en'] ?? $school['school_address'] ?? '')?></p><p><?=e($school['school_phone'] ?? '')?></p><p><?=e($school['school_website'] ?? $school['school_email'] ?? '')?></p></div>
</header>
<div class="report-meta"><span>العام الدراسي: <?=e($school['academic_year'] ?? '')?></span><span>تاريخ الإصدار: <?=date('Y-m-d')?></span></div>
<div class="report-print-note no-print">يمكنك اختيار «Microsoft Print to PDF» أو «Save as PDF» من نافذة الطباعة لحفظ نسخة رسمية.</div>
<div class="report-title"><h1><?=e($title)?></h1><span>رقم التقرير: <?= $reportId ? '#'.(int)$reportId : '—' ?></span></div>
<?php if($customContent !== null): ?><article class="custom-report-content rich-report-content"><?=$customContent?></article><?php elseif($type !== 'custom'): ?><div class="table-wrap"><table class="report-table"><thead><tr><?php foreach($columns as $column): ?><th><?=e($column)?></th><?php endforeach; ?></tr></thead><tbody><?php foreach($rows as $row): ?><tr><?php foreach($row as $value): ?><td><?=e((string)$value)?></td><?php endforeach; ?></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="<?=count($columns)?>">لا توجد بيانات في هذا التقرير.</td></tr><?php endif; ?></tbody></table></div><?php else: ?><p class="report-empty no-print">اكتب تقريرًا مخصصًا من تبويب «تقرير مخصص» ليظهر هنا.</p><?php endif; ?>
<footer class="report-footer"><strong>جميع الحقوق محفوظة لدى نبيل الغباري © <?=date('Y')?></strong><a href="https://wa.me/967770104005" target="_blank" rel="noopener">الدعم والمساعدة عبر واتساب: +967770104005</a></footer>
</section>
<?php if($savedReports): ?><section class="panel no-print"><div class="panel-header"><h2>التقارير المخصصة المحفوظة</h2></div><?php foreach($savedReports as $saved): ?><a class="search-result" href="reports.php?id=<?=(int)$saved['id']?>"><strong><?=e($saved['title'])?></strong><small><?=e($saved['created_at'])?></small></a><?php endforeach; ?></section><?php endif; ?>
<script src="assets/vendor/quill/quill.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const editorElement=document.getElementById('reportEditor'); if(!editorElement || typeof Quill==='undefined') return;
  const toolbar=[['header',[1,2,3,false]],[{font:[]}],[{size:['small',false,'large','huge']}],['bold','italic','underline','strike'],[{color:[]},{background:[]}],[{script:'super'},{script:'sub'}],[{list:'ordered'},{list:'bullet'}],[{align:[]}],['blockquote','code-block','link','image','table'],['clean']];
  const quill=new Quill(editorElement,{theme:'snow',placeholder:'ابدأ كتابة التقرير الرسمي هنا...',modules:{toolbar}});
  const toolbarModule=quill.getModule('toolbar');
  toolbarModule.addHandler('image',()=>{const input=document.createElement('input');input.type='file';input.accept='image/png,image/jpeg,image/webp';input.onchange=async()=>{const file=input.files?.[0];if(!file)return;if(file.size>2*1024*1024){alert('حجم الصورة يجب أن يكون أقل من 2MB.');return;}const form=new FormData();form.append('report_image',file);form.append('csrf_token',document.getElementById('reportCsrf').value);try{const response=await fetch('report_upload.php',{method:'POST',body:form});const data=await response.json();if(!data.ok)throw new Error(data.message);const range=quill.getSelection(true);quill.insertEmbed(range.index,'image',data.url,'user');quill.setSelection(range.index+1);}catch(error){alert(error.message||'تعذر رفع الصورة.');}};input.click();});
  document.getElementById('reportForm')?.addEventListener('submit',()=>{document.getElementById('reportHtml').value=quill.root.innerHTML;});
});
</script>
<?php require 'partials/footer.php'; ?>
