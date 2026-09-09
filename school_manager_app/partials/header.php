<?php
require_once __DIR__ . '/../functions.php';
$pageTitle = $pageTitle ?? 'لوحة المدرسة';
$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF']);
$school = isset($pdo) ? getSchoolSettings($pdo) : ['school_name'=>'مدار','school_logo'=>null];
$schoolLogo = schoolLogoUrl($school['school_logo'] ?? null);
$notificationCount = 0;
$notificationItems = [];
try {
    $notificationCount = (int) $pdo->query("SELECT COUNT(*) FROM installments WHERE due_date <= CURDATE() AND paid_amount < total_amount")->fetchColumn();
    $notificationItems = $pdo->query("SELECT i.id, i.title, i.due_date, (i.total_amount-i.paid_amount) AS balance, s.full_name
        FROM installments i JOIN students s ON s.id=i.student_id
        WHERE i.due_date <= CURDATE() AND i.paid_amount < i.total_amount
        ORDER BY i.due_date ASC, i.id DESC LIMIT 8")->fetchAll();
} catch (Throwable $exception) { $notificationCount = 0; $notificationItems = []; }
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e($school['school_name'] ?? 'مدار') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="">
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="القائمة الرئيسية">
        <div class="sidebar-scroll">
        <div class="sidebar-top">
            <div class="brand">
                <?php if($schoolLogo): ?><img class="brand-logo" src="<?= e($schoolLogo) ?>" alt="شعار <?= e($school['school_name'] ?? 'المدرسة') ?>"><?php else: ?><div class="brand-mark">م</div><?php endif; ?>
                <div class="brand-copy"><strong><?= e($school['school_name'] ?? 'مدار') ?></strong><small>نظام إدارة المدرسة</small></div>
                <button class="sidebar-close" id="sidebarClose" type="button" title="إغلاق القائمة" aria-label="إغلاق القائمة">×</button>
                <button class="sidebar-collapse" id="sidebarCollapse" type="button" title="تصغير القائمة" aria-label="تصغير القائمة"><span>‹</span></button>
            </div>
        </div>
        <nav class="main-nav" aria-label="واجهات النظام">
            <?php if(hasPermission($pdo,'dashboard.view')): ?><a class="nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php" data-tooltip="عرض ملخص المدرسة والإحصاءات"><span>⌂</span> لوحة التحكم</a><?php endif; ?>
            <?php if(hasPermission($pdo,'academic.view')): ?><a class="nav-item <?= $currentPage === 'academic.php' ? 'active' : '' ?>" href="academic.php" data-tooltip="نظرة موحدة على المراحل والصفوف والشعب والمواد"><span>⌘</span> الهيكل الأكاديمي</a><?php endif; ?>
            <?php if(hasPermission($pdo,'students.view')): ?><a class="nav-item <?= $currentPage === 'students.php' ? 'active' : '' ?>" href="students.php" data-tooltip="إدارة سجلات الطلاب"><span>◉</span> الطلاب</a><?php endif; ?>
            <?php if(hasPermission($pdo,'teachers.manage')): ?><a class="nav-item <?= $currentPage === 'assignments.php' ? 'active' : '' ?>" href="assignments.php" data-tooltip="توزيع المواد على المعلمين والشعب"><span>≡</span> التوزيع التعليمي</a><?php endif; ?>
            <?php if(hasPermission($pdo,'teachers.view')): ?><a class="nav-item <?= $currentPage === 'teachers.php' ? 'active' : '' ?>" href="teachers.php" data-tooltip="إدارة الكادر التعليمي"><span>◇</span> المعلمون</a><?php endif; ?>
            <?php if(hasPermission($pdo,'classes.view')): ?><a class="nav-item <?= $currentPage === 'classes.php' ? 'active' : '' ?>" href="classes.php" data-tooltip="إدارة الفصول"><span>▦</span> الفصول الدراسية</a><?php endif; ?>
            <?php if(hasPermission($pdo,'subjects.view')): ?><a class="nav-item <?= $currentPage === 'subjects.php' ? 'active' : '' ?>" href="subjects.php" data-tooltip="إدارة المواد الدراسية"><span>◫</span> المواد الدراسية</a><?php endif; ?>
            <?php if(hasPermission($pdo,'attendance.view')): ?><a class="nav-item <?= $currentPage === 'attendance.php' ? 'active' : '' ?>" href="attendance.php" data-tooltip="تسجيل الحضور والغياب"><span>✓</span> الحضور والغياب</a><?php endif; ?>
            <?php if(hasPermission($pdo,'payments.view')): ?><a class="nav-item <?= $currentPage === 'payments.php' ? 'active' : '' ?>" href="payments.php" data-tooltip="تسجيل المدفوعات"><span>ر</span> المدفوعات</a><?php endif; ?>
            <?php if(hasPermission($pdo,'payments.manage')): ?><a class="nav-item <?= $currentPage === 'installments.php' ? 'active' : '' ?>" href="installments.php" data-tooltip="متابعة الأقساط المتأخرة"><span>!</span> الأقساط والتنبيهات</a><?php endif; ?>
            <?php if(hasPermission($pdo,'reports.view')): ?><a class="nav-item <?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="reports.php" data-tooltip="إنشاء وطباعة التقارير"><span>▤</span> التقارير والطباعة</a><?php endif; ?>
            <?php if(hasPermission($pdo,'settings.manage')): ?><a class="nav-item <?= $currentPage === 'settings.php' ? 'active' : '' ?>" href="settings.php" data-tooltip="تحديث هوية المدرسة وإعداداتها"><span>⚙</span> إعدادات المدرسة</a><?php endif; ?>
            <?php if(hasPermission($pdo,'users.manage')): ?><a class="nav-item <?= $currentPage === 'users.php' ? 'active' : '' ?>" href="users.php" data-tooltip="إدارة المستخدمين والصلاحيات"><span>♙</span> المستخدمون والصلاحيات</a><?php endif; ?>
            <?php if(hasPermission($pdo,'audit.view')): ?><a class="nav-item <?= $currentPage === 'audit.php' ? 'active' : '' ?>" href="audit.php" data-tooltip="مراجعة عمليات النظام"><span>◷</span> سجل التدقيق</a><?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="support-card"><span>✦</span><strong>جلسة محمية</strong><small>تنتهي بعد عدم النشاط</small></div>
            <form method="post" action="logout.php" class="logout-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><button class="nav-item logout" type="submit" data-tooltip="إنهاء الجلسة والخروج من النظام"><span>↪</span> تسجيل الخروج</button></form>
        </div>
        </div>
    </aside>
    <button class="sidebar-overlay" id="sidebarOverlay" aria-label="إغلاق القائمة"></button>
    <main class="main-content">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" type="button" title="فتح أو إغلاق قائمة الواجهات" aria-label="فتح قائمة الواجهات">☰</button>
            <form class="global-search" action="search.php" method="get" role="search"><span>⌕</span><input id="globalSearch" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="ابحث عن طالب، معلم، أو واجهة..." title="ابحث بالاسم أو الرمز أو اسم الواجهة"></form>
            <div class="topbar-actions">
                <button class="icon-button" id="themeToggle" type="button" title="تبديل الوضع الفاتح والداكن" aria-label="تبديل الوضع الداكن">☾</button>
                <div class="topbar-menu-wrap">
                    <button class="icon-button notification-button" id="notificationToggle" type="button" aria-haspopup="true" aria-expanded="false" title="عرض أهم التنبيهات" aria-label="عرض التنبيهات">♢<?php if($notificationCount): ?><b><?= $notificationCount > 99 ? '99+' : $notificationCount ?></b><?php endif; ?></button>
                    <div class="dropdown-panel notification-panel" id="notificationPanel" hidden>
                        <div class="dropdown-head"><div><strong>التنبيهات المهمة</strong><small><?= $notificationCount ? 'لديك '.$notificationCount.' قسطًا يحتاج متابعة' : 'لا توجد تنبيهات مستحقة' ?></small></div><a href="installments.php">عرض الكل</a></div>
                        <div class="notification-list">
                        <?php if($notificationItems): foreach($notificationItems as $notice): ?>
                            <a class="notification-item" href="installments.php">
                                <span class="notification-dot"></span><div><strong><?= e($notice['full_name']) ?></strong><small><?= e($notice['title']) ?> · <?= e($notice['due_date']) ?></small></div><b><?= e(formatMoney($notice['balance'])) ?></b>
                            </a>
                        <?php endforeach; else: ?>
                            <div class="dropdown-empty"><span>✓</span><strong>كل شيء على ما يرام</strong><small>لا توجد أقساط متأخرة أو مستحقة اليوم.</small></div>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="topbar-menu-wrap user-menu-wrap">
                    <button class="topbar-user user-trigger" id="userMenuToggle" type="button" aria-haspopup="true" aria-expanded="false" title="خيارات المستخدم الحالي">
                        <div class="avatar"><?= e(mb_substr($_SESSION['user']['full_name'] ?? 'م', 0, 1)) ?></div><div class="user-copy"><strong><?= e($_SESSION['user']['full_name'] ?? 'مدير النظام') ?></strong><small><?= e($_SESSION['user']['role'] ?? '') ?></small></div><span class="chevron">⌄</span>
                    </button>
                    <div class="dropdown-panel user-panel" id="userPanel" hidden>
                        <div class="user-panel-head"><div class="avatar avatar-lg"><?= e(mb_substr($_SESSION['user']['full_name'] ?? 'م', 0, 1)) ?></div><div><strong><?= e($_SESSION['user']['full_name'] ?? 'مدير النظام') ?></strong><small><?= e($_SESSION['user']['role'] ?? '') ?></small></div></div>
                        <div class="session-status"><span></span><div><strong>الجلسة نشطة وآمنة</strong><small>يتم حفظ التغييرات التي تنفذها داخل النظام مباشرة.</small></div></div>
                        <div class="quick-actions">
                            <a href="index.php" title="العودة إلى لوحة التحكم"><span>⌂</span> لوحة التحكم</a>
                            <?php if(hasPermission($pdo,'settings.manage')): ?><a href="settings.php" title="تعديل إعدادات وهوية المدرسة"><span>⚙</span> إعدادات المدرسة</a><?php endif; ?>
                            <button type="button" id="appearanceToggle" title="تخصيص ألوان ومظهر المنصة"><span>✦</span> تخصيص المظهر</button>
                            <button type="button" id="refreshPage" title="تحديث الصفحة وإعادة جلب أحدث البيانات"><span>↻</span> تحديث البيانات</button>
                        </div>
                        <div class="appearance-options" id="appearanceOptions" hidden>
                            <small>لون الواجهة</small><div class="accent-swatches"><button data-accent="blue" title="أزرق" aria-label="لون أزرق"></button><button data-accent="green" title="أخضر" aria-label="لون أخضر"></button><button data-accent="purple" title="بنفسجي" aria-label="لون بنفسجي"></button><button data-accent="gold" title="ذهبي" aria-label="لون ذهبي"></button></div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <form method="post" action="logout.php" class="dropdown-logout"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><button type="submit"><span>↪</span> تسجيل الخروج / تغيير المستخدم</button></form>
                    </div>
                </div>
            </div>
        </header>
        <section class="page-container">
            <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>" role="alert" data-auto-dismiss><?= e($flash['message']) ?></div><?php endif; ?>
