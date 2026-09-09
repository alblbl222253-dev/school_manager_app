<?php
require_once 'config.php';
require_once 'functions.php';

if (!empty($_SESSION['user'])) {
    redirect('index.php');
}

$error = '';
$school = getSchoolSettings($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'أدخل اسم المستخدم وكلمة المرور أولًا.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'local';
        try {
            $rate = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address=:ip AND attempted_at > (NOW() - INTERVAL 10 MINUTE)');
            $rate->execute(['ip'=>$ip]);
            if ((int)$rate->fetchColumn() >= 8) {
                $error = 'تم إيقاف محاولات الدخول مؤقتًا. انتظر عدة دقائق ثم حاول مرة أخرى.';
            }
        } catch (Throwable $e) { /* تعمل النسخة حتى قبل تطبيق الترقية، مع بقاء بقية الحماية. */ }
        if ($error === '') {
        try {
            $statement = $pdo->prepare('SELECT * FROM users WHERE username = :username AND is_active=1 LIMIT 1');
            $statement->execute(['username' => $username]);
            $user = $statement->fetch();
        } catch (PDOException $exception) {
            // قاعدة قديمة لم تُطبّق عليها ترقية v4. لا نعرض Fatal Error للمستخدم.
            if ($exception->getCode() === '42S22') {
                $error = 'قاعدة البيانات أقدم من إصدار النظام الحالي. استورد ملف upgrade_v4.sql مرة واحدة من phpMyAdmin ثم أعد تسجيل الدخول.';
                $user = false;
            } else {
                throw $exception;
            }
        }

        $valid = $error === '' && $user && password_verify($password, $user['password_hash']);
        if ($valid) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
            ];
            $_SESSION['created_at'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            try {
                $lastLogin = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
                $lastLogin->execute(['id' => (int)$user['id']]);
            } catch (Throwable $exception) { /* لا نمنع الدخول بسبب حقل اختياري في قاعدة قديمة. */ }
            redirect('index.php');
        }
        if (!$valid && $error === '') {
            try {
                $log = $pdo->prepare('INSERT INTO login_attempts(ip_address,username,attempted_at) VALUES(:ip,:username,NOW())');
                $log->execute(['ip'=>$ip,'username'=>$username]);
            } catch (Throwable $e) {}
            $error = 'تعذر تسجيل الدخول. تحقق من اسم المستخدم وكلمة المرور.';
        }
        }
    }
}
$logo = schoolLogoUrl($school['school_logo'] ?? null);
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | <?= e($school['school_name'] ?? 'مدار') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <main class="login-card">
        <div class="brand">
            <?php if($logo): ?><img class="brand-logo" src="<?= e($logo) ?>" alt="شعار المدرسة"><?php else: ?><div class="brand-mark">م</div><?php endif; ?>
            <div><strong><?= e($school['school_name'] ?? 'مدار') ?></strong><small>نظام الإدارة التعليمية</small></div>
        </div>
        <h1>مرحبًا بعودتك</h1>
        <p>أدخل بياناتك المعتمدة للوصول إلى خدمات المدرسة.</p>
        <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" data-validate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <div class="form-group"><label for="username">اسم المستخدم</label><input class="form-control" id="username" name="username" autocomplete="username" required minlength="3" placeholder="مثال: admin" data-hint="أدخل اسم مستخدم مكوّنًا من 3 أحرف على الأقل."></div>
            <div class="form-group" style="margin-top:14px"><label for="password">كلمة المرور</label><input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required minlength="6" placeholder="أدخل كلمة المرور" data-hint="أدخل كلمة مرور صحيحة من 6 أحرف على الأقل."></div>
            <button class="btn btn-primary" style="width:100%;margin-top:20px" type="submit" title="التحقق من البيانات وفتح لوحة النظام">دخول آمن إلى النظام</button>
        </form>
        <div class="login-note">تُنهى الجلسة تلقائيًا بعد فترة من عدم النشاط، ويمكنك الخروج من القائمة الجانبية في أي وقت.</div>
    </main>
</body>
</html>
