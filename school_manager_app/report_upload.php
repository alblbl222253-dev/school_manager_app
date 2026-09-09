<?php
require_once 'config.php'; require_once 'functions.php'; requireLogin(); requirePermission($pdo, 'reports.create');
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new RuntimeException('طريقة الطلب غير مدعومة.');
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) throw new RuntimeException('انتهت صلاحية الطلب. أعد تحميل الصفحة.');
    if (empty($_FILES['report_image']) || $_FILES['report_image']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('تعذر قراءة الصورة المرفوعة.');
    $file = $_FILES['report_image'];
    if ($file['size'] > 2 * 1024 * 1024) throw new RuntimeException('حجم الصورة يجب أن يكون أقل من 2MB.');
    $allowed = ['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) throw new RuntimeException('ارفع صورة PNG أو JPG أو WEBP فقط.');
    if (@getimagesize($file['tmp_name']) === false) throw new RuntimeException('الملف ليس صورة صالحة.');
    $dir = __DIR__ . '/uploads/reports';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('تعذر تجهيز مجلد الصور.');
    if (!is_writable($dir)) throw new RuntimeException('مجلد صور التقارير غير قابل للكتابة.');
    $fileName = 'report-' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir.'/'.$fileName)) throw new RuntimeException('تعذر حفظ الصورة.');
    echo json_encode(['ok'=>true, 'url'=>'uploads/reports/'.$fileName]);
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode(['ok'=>false, 'message'=>$exception->getMessage()]);
}
