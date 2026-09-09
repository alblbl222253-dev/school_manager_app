<?php
// إعدادات الاتصال المحلية لـ XAMPP. غيّر بيانات root إذا كان MySQL لديك محميًا بكلمة مرور.
$host = '127.0.0.1';
$dbName = 'school_manager';
$dbUser = 'root';
$dbPassword = '';
$dsn = "mysql:host={$host};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    http_response_code(503);
    exit('تعذر الاتصال بقاعدة البيانات. شغّل MySQL في XAMPP وتأكد من استيراد قاعدة school_manager.');
}
// رؤوس دفاعية مناسبة لتطبيق محلي؛ لا تعتمد عليها وحدها كطبقة أمن.
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
}
