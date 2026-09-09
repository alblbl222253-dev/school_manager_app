<?php
require_once 'config.php'; require_once 'functions.php'; requireLogin(); requirePermission($pdo, 'audit.view');
try {
    $logs = $pdo->query('SELECT a.*, COALESCE(u.full_name, "نظام/مستخدم محذوف") AS user_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 200')->fetchAll();
} catch (Throwable $exception) { $logs = []; }
$pageTitle='سجل التدقيق'; require 'partials/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">الحماية والمساءلة</p><h1>سجل التدقيق</h1><p>راجع أهم التغييرات التي تمت داخل النظام ومن نفذها.</p></div></div>
<section class="panel"><div class="panel-header"><h2>آخر العمليات</h2><a href="users.php">المستخدمون والصلاحيات ←</a></div><div class="table-wrap"><table class="data-table"><thead><tr><th>التاريخ</th><th>المستخدم</th><th>العملية</th><th>الكيان</th><th>التفاصيل</th><th>IP</th></tr></thead><tbody><?php foreach($logs as $log): ?><tr><td><?=e($log['created_at'])?></td><td class="student-name"><?=e($log['user_name'])?></td><td><span class="badge badge-blue"><?=e($log['action_name'])?></span></td><td><?=e($log['entity_type'])?> #<?=e((string)($log['entity_id']??'-'))?></td><td><?=e($log['details']??'-')?></td><td><?=e($log['ip_address']??'-')?></td></tr><?php endforeach;?><?php if(!$logs):?><tr><td class="empty" colspan="6">لا توجد عمليات مسجلة بعد. تأكد من استيراد upgrade_v3.sql.</td></tr><?php endif;?></tbody></table></div></section>
<?php require 'partials/footer.php'; ?>
