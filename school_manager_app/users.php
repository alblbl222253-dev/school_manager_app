<?php
require_once 'config.php'; require_once 'functions.php'; requireLogin(); requirePermission($pdo, 'users.manage');

$roles = ['admin'=>'مدير النظام','manager'=>'مدير المدرسة','accountant'=>'محاسب','teacher'=>'معلم','staff'=>'موظف'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$user = null;
if ($id) {
    $st = $pdo->prepare('SELECT * FROM users WHERE id = :id'); $st->execute(['id'=>$id]); $user = $st->fetch();
    if (!$user) { flash('error', 'المستخدم المطلوب غير موجود.'); redirect('users.php'); }
}
$permissions = getPermissionGroups($pdo);
$groupedPermissions = [];
foreach ($permissions as $permission) $groupedPermissions[$permission['module_name']][] = $permission;

$selectedIds = [];
if ($id) {
    try {
        $st = $pdo->prepare('SELECT permission_id FROM user_permissions WHERE user_id = :id AND is_allowed = 1');
        $st->execute(['id'=>$id]); $selectedIds = array_map('intval', array_column($st->fetchAll(), 'permission_id'));
        // المستخدم القديم الذي لا يملك تخصيصًا يأخذ افتراضيات دوره لتظهر للمسؤول.
        if (!$selectedIds && !in_array($user['role'], ['admin','manager'], true)) {
            $st = $pdo->prepare('SELECT permission_id FROM role_permissions WHERE role_key = :role');
            $st->execute(['role'=>$user['role']]); $selectedIds = array_map('intval', array_column($st->fetchAll(), 'permission_id'));
        }
    } catch (Throwable $exception) { }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? 'staff';
    $password = $_POST['password'] ?? '';
    $requestedPermissions = array_map('intval', $_POST['permissions'] ?? []);
    if ($name === '' || $username === '' || !isset($roles[$role]) || mb_strlen($username)>60 || mb_strlen($name)>120) { flash('error', 'أكمل البيانات وتأكد من أطوال الاسم واسم المستخدم.'); redirect($id ? "users.php?id={$id}" : 'users.php?action=create'); }
    if($id && $id === (int)($_SESSION['user']['id']??0) && !in_array($role,['admin','manager'],true)){
        flash('error','لا يمكنك خفض صلاحية حسابك الإداري بنفسك. اطلب من مدير نظام آخر تنفيذ ذلك.'); redirect('users.php?id='.$id);
    }

    try {
        $pdo->beginTransaction();
        if ($id) {
            if ($password !== '') {
                if (mb_strlen($password) < 6) throw new RuntimeException('كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل.');
                $st = $pdo->prepare('UPDATE users SET full_name=:name, username=:username, role=:role, password_hash=:hash WHERE id=:id');
                $st->execute(['name'=>$name,'username'=>$username,'role'=>$role,'hash'=>password_hash($password, PASSWORD_DEFAULT),'id'=>$id]);
            } else {
                $st = $pdo->prepare('UPDATE users SET full_name=:name, username=:username, role=:role WHERE id=:id');
                $st->execute(['name'=>$name,'username'=>$username,'role'=>$role,'id'=>$id]);
            }
            $userId = $id; $action = 'update_user';
        } else {
            if (mb_strlen($password) < 6) throw new RuntimeException('كلمة المرور يجب أن تكون 6 أحرف على الأقل.');
            $st = $pdo->prepare('INSERT INTO users(full_name,username,password_hash,role) VALUES(:name,:username,:hash,:role)');
            $st->execute(['name'=>$name,'username'=>$username,'hash'=>password_hash($password, PASSWORD_DEFAULT),'role'=>$role]);
            $userId = (int)$pdo->lastInsertId(); $action = 'create_user';
        }

        // المدير ومدير المدرسة لهما الوصول الكامل بموجب السياسة؛ أما بقية الحسابات فتخزن خياراتها الفردية.
        if (!in_array($role, ['admin','manager'], true)) {
            $delete = $pdo->prepare('DELETE FROM user_permissions WHERE user_id = :id'); $delete->execute(['id'=>$userId]);
            $insert = $pdo->prepare('INSERT INTO user_permissions (user_id, permission_id, is_allowed) VALUES (:user_id,:permission_id,1)');
            foreach (array_unique($requestedPermissions) as $permissionId) $insert->execute(['user_id'=>$userId, 'permission_id'=>$permissionId]);
        } else {
            $delete = $pdo->prepare('DELETE FROM user_permissions WHERE user_id = :id'); $delete->execute(['id'=>$userId]);
        }
        $pdo->commit();
        logAudit($pdo, $action, 'user', $userId, 'الدور: '.$role);
        flash('success', 'تم حفظ المستخدم والصلاحيات بنجاح.');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'تعذر الحفظ. تحقق من أن اسم المستخدم غير مكرر وأن ترقية v3 مستوردة.');
    }
    redirect('users.php');
}

$users = $pdo->query('SELECT id,full_name,username,role,created_at FROM users ORDER BY id DESC')->fetchAll();
$pageTitle='المستخدمون والصلاحيات'; require 'partials/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">إدارة الوصول والتحكم</p><h1>المستخدمون والصلاحيات</h1><p>امنح الموظف ما يحتاجه فقط، وتبقى صلاحيات الإدارة الكاملة لمدير النظام ومدير المدرسة.</p></div><div class="actions"><a class="btn btn-primary" href="users.php?action=create" title="إنشاء حساب جديد للكادر">+ مستخدم جديد</a></div></div>
<?php if($id || ($_GET['action'] ?? '') === 'create'): ?>
<section class="panel"><div class="panel-header"><h2><?= $id?'تعديل المستخدم والصلاحيات':'إضافة مستخدم جديد' ?></h2><a href="users.php">إلغاء</a></div>
<form method="post" data-validate><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div class="form-grid"><div class="form-group"><label>الاسم الكامل *</label><input class="form-control" name="full_name" required value="<?= e($user['full_name']??'') ?>" data-hint="الاسم الذي سيظهر في أعلى النظام وسجل التدقيق."></div><div class="form-group"><label>اسم المستخدم *</label><input class="form-control" name="username" required minlength="3" value="<?= e($user['username']??'') ?>" data-hint="3 أحرف على الأقل، ويجب أن يكون فريدًا."></div><div class="form-group"><label>الدور الأساسي</label><select class="form-control" name="role" id="roleSelect" data-hint="المدير ومدير المدرسة يملكان كل الصلاحيات تلقائيًا."><?php foreach($roles as $key=>$label): ?><option value="<?= $key ?>" <?= ($user['role']??'staff')===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div><div class="form-group"><label><?= $id?'كلمة مرور جديدة (اختيارية)':'كلمة المرور *' ?></label><input class="form-control" type="password" name="password" <?= $id?'':'required' ?> minlength="6" data-hint="6 أحرف على الأقل. اتركه فارغًا عند التعديل للاحتفاظ بكلمة المرور الحالية."></div></div>
<div class="permission-header"><div><h3>الصلاحيات التفصيلية</h3><p>حدد الواجهات والإجراءات التي يمكن للمستخدم الوصول إليها.</p></div><label class="permission-toggle"><input type="checkbox" id="toggleAllPermissions"> تحديد الكل</label></div>
<div class="permission-note" id="fullAccessNote">مدير النظام ومدير المدرسة يملكان جميع الصلاحيات تلقائيًا؛ لا حاجة لتحديد مربعات إضافية.</div>
<div class="permission-grid" id="permissionGrid"><?php foreach($groupedPermissions as $module=>$items): ?><fieldset class="permission-group"><legend><?=e($module)?></legend><?php foreach($items as $permission): ?><label class="permission-item"><input type="checkbox" name="permissions[]" value="<?= (int)$permission['id'] ?>" <?= in_array((int)$permission['id'],$selectedIds,true)?'checked':'' ?>><span><strong><?=e($permission['label'])?></strong><small><?=e($permission['description'])?></small></span></label><?php endforeach; ?></fieldset><?php endforeach; ?></div>
<div class="form-actions"><button class="btn btn-primary" type="submit">حفظ المستخدم والصلاحيات</button><a class="btn btn-light" href="users.php">إلغاء</a></div></form></section>
<?php endif; ?>
<section class="panel"><div class="panel-header"><h2>الحسابات الحالية</h2><a href="audit.php">عرض سجل التدقيق ←</a></div><div class="table-wrap"><table class="data-table"><thead><tr><th>المستخدم</th><th>اسم الدخول</th><th>الدور</th><th>تاريخ الإنشاء</th><th>إجراء</th></tr></thead><tbody><?php foreach($users as $item): ?><tr><td class="student-name"><?= e($item['full_name']) ?></td><td><?= e($item['username']) ?></td><td><span class="badge badge-blue"><?= e($roles[$item['role']]??$item['role']) ?></span></td><td><?= e($item['created_at']) ?></td><td><a class="btn btn-light btn-sm" href="users.php?id=<?= (int)$item['id'] ?>" title="تعديل بيانات وصلاحيات المستخدم">تعديل</a></td></tr><?php endforeach; ?></tbody></table></div></section>
<script>
document.addEventListener('DOMContentLoaded',()=>{const role=document.getElementById('roleSelect'),grid=document.getElementById('permissionGrid'),note=document.getElementById('fullAccessNote'),all=document.getElementById('toggleAllPermissions');const refresh=()=>{const full=['admin','manager'].includes(role.value);grid.classList.toggle('is-disabled',full);note.style.display=full?'block':'none';grid.querySelectorAll('input').forEach(x=>x.disabled=full);all.disabled=full;};role.addEventListener('change',refresh);all.addEventListener('change',()=>grid.querySelectorAll('input').forEach(x=>x.checked=all.checked));refresh();});
</script>
<?php require 'partials/footer.php'; ?>
