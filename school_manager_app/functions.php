<?php
/**
 * وظائف نظام مدار — طبقة مشتركة للحماية، الجلسات، الصلاحيات، العرض، والتدقيق.
 */
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_trans_sid', '0');
    session_name('madar_session');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => $isHttps,
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
}
const SESSION_IDLE_TIMEOUT = 1800;
const SESSION_ABSOLUTE_TIMEOUT = 28800;

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function redirect(string $url): never { header('Location: '.$url, true, 303); exit; }
function flash(string $type, string $message): void { $_SESSION['flash']=['type'=>$type,'message'=>$message]; }
function getFlash(): ?array { $m=$_SESSION['flash']??null; unset($_SESSION['flash']); return $m; }

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verifyCsrf(): void {
    $sent=$_POST['csrf_token']??'';
    if (!$sent || !hash_equals($_SESSION['csrf_token']??'', $sent)) {
        http_response_code(419); exit('انتهت صلاحية الطلب. أعد تحميل الصفحة ثم حاول مرة أخرى.');
    }
}
function destroyUserSession(): void {
    $_SESSION=[];
    if (ini_get('session.use_cookies')) {
        $p=session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'=>time()-42000, 'path'=>$p['path']??'/', 'domain'=>$p['domain']??'',
            'secure'=>(bool)($p['secure']??false), 'httponly'=>(bool)($p['httponly']??true),
            'samesite'=>$p['samesite']??'Lax'
        ]);
    }
    session_destroy();
}
function enforceSessionLifetime(): void {
    if (empty($_SESSION['user'])) return;
    $now=time();
    $created=(int)($_SESSION['created_at']??$now);
    $last=(int)($_SESSION['last_activity']??$now);
    if (($now-$last)>SESSION_IDLE_TIMEOUT || ($now-$created)>SESSION_ABSOLUTE_TIMEOUT) {
        destroyUserSession(); session_start();
        flash('error','انتهت جلستك تلقائيًا للحفاظ على أمان الحساب. يرجى تسجيل الدخول مرة أخرى.');
        redirect('login.php');
    }
    $_SESSION['last_activity']=$now;
}
function requireLogin(): void { if(empty($_SESSION['user'])) redirect('login.php'); enforceSessionLifetime(); }
function old(string $key,string $default=''): string { return e($_POST[$key]??$default); }

function getSchoolSettings(PDO $pdo): array {
    try {
        $s=$pdo->query('SELECT * FROM school_settings WHERE id=1')->fetch();
        return $s ?: ['school_name'=>'مدرسة مدار النموذجية','school_logo'=>null,'academic_year'=>''];
    } catch(Throwable $e) {
        return ['school_name'=>'مدرسة مدار النموذجية','school_logo'=>null,'academic_year'=>''];
    }
}
function schoolLogoUrl(?string $logo): ?string {
    if(!$logo || str_contains($logo,'..') || str_starts_with($logo,'http://') || str_starts_with($logo,'https://')) return null;
    $relative=ltrim($logo,'/');
    $full=__DIR__.'/'.$relative;
    return is_file($full) ? $relative.'?v='.(int)filemtime($full) : null;
}
function hasRole(array|string $roles): bool { return in_array($_SESSION['user']['role']??'',(array)$roles,true); }
function requireRole(array|string $roles): void {
    if(!hasRole($roles)){ flash('error','لا تملك الصلاحية اللازمة للوصول إلى هذه الصفحة.'); redirect('index.php'); }
}
function formatMoney(float|int|string $amount): string { return number_format((float)$amount,2).' ر.ي'; }

function hasPermission(PDO $pdo,string $permissionKey): bool {
    if(hasRole(['admin','manager'])) return true;
    static $cache=[];
    $uid=(int)($_SESSION['user']['id']??0); $key=$uid.':'.$permissionKey;
    if(array_key_exists($key,$cache)) return $cache[$key];
    try {
        $st=$pdo->prepare('SELECT p.permission_key, COALESCE(up.is_allowed,1) allowed
            FROM permissions p
            LEFT JOIN role_permissions rp ON rp.permission_id=p.id AND rp.role_key=:role
            LEFT JOIN user_permissions up ON up.permission_id=p.id AND up.user_id=:uid
            WHERE p.permission_key=:permission AND (rp.permission_id IS NOT NULL OR up.permission_id IS NOT NULL)
            LIMIT 1');
        $st->execute(['role'=>$_SESSION['user']['role']??'','uid'=>$uid,'permission'=>$permissionKey]);
        $row=$st->fetch();
        return $cache[$key]=$row ? (bool)$row['allowed'] : false;
    } catch(Throwable $e) { return $cache[$key]=false; }
}
function requirePermission(PDO $pdo,string $permissionKey): void {
    if(!hasPermission($pdo,$permissionKey)){ flash('error','لا تملك الصلاحية اللازمة لتنفيذ هذه العملية.'); redirect('index.php'); }
}
function getPermissionGroups(PDO $pdo): array {
    try { return $pdo->query('SELECT * FROM permissions ORDER BY module_name,sort_order,label')->fetchAll(); }
    catch(Throwable $e){ return []; }
}
function logAudit(PDO $pdo,string $action,string $entityType,?int $entityId=null,?string $details=null): void {
    try {
        $st=$pdo->prepare('INSERT INTO audit_logs(user_id,action_name,entity_type,entity_id,details,ip_address)
            VALUES(:uid,:action,:entity,:eid,:details,:ip)');
        $st->execute(['uid'=>$_SESSION['user']['id']??null,'action'=>$action,'entity'=>$entityType,
            'eid'=>$entityId,'details'=>$details,'ip'=>$_SERVER['REMOTE_ADDR']??null]);
    } catch(Throwable $e) {}
}
function validDate(?string $date): bool {
    if($date===null || $date==='') return true;
    $d=DateTime::createFromFormat('Y-m-d',$date);
    return $d && $d->format('Y-m-d')===$date;
}
function sanitizeReportHtml(string $html): string {
    $allowed=['p','br','strong','b','em','i','u','s','span','div','h1','h2','h3','h4','blockquote','ul','ol','li','a','img','table','thead','tbody','tr','th','td'];
    $attrs=['href','src','alt','title','style','class','target','rel','width','height'];
    if(!class_exists('DOMDocument')){
        $out=strip_tags($html,'<p><br><strong><b><em><i><u><s><span><div><h1><h2><h3><h4><blockquote><ul><ol><li><a><img><table><thead><tbody><tr><th><td>');
        $out=preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i','',$out)??'';
        $out=preg_replace('/(href|src)\s*=\s*([\'"]?)\s*(javascript:|data:)/i','$1=$2',$out)??'';
        return $out;
    }
    $dom=new DOMDocument('1.0','UTF-8'); libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?><div id="report-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
    libxml_clear_errors(); $xp=new DOMXPath($dom);
    foreach(iterator_to_array($xp->query('//*')) as $node){
        if($node->nodeName==='div' && $node->getAttribute('id')==='report-root') continue;
        if(!in_array($node->nodeName,$allowed,true)){ $node->parentNode?->removeChild($node); continue; }
        if($node->hasAttributes()) foreach(iterator_to_array($node->attributes) as $a){
            $n=strtolower($a->name); $v=trim($a->value);
            $badUrl=in_array($n,['href','src'],true) && !preg_match('#^(https?://|/|uploads/)#i',$v);
            if(!in_array($n,$attrs,true)||str_starts_with($n,'on')||$badUrl){ $node->removeAttribute($a->name); continue; }
            if($n==='style'){
                $safeStyle=preg_replace('/url\s*\(|expression\s*\(|javascript\s*:|@import/i','',$v)??'';
                $safeStyle=preg_replace('/[^a-zA-Z0-9#(),.%\s:\-;\/]/','',$safeStyle)??'';
                $node->setAttribute('style',$safeStyle);
            }
        }
    }
    $r=$dom->getElementById('report-root'); if(!$r)return '';
    $out=''; foreach($r->childNodes as $c)$out.=$dom->saveHTML($c); return $out;
}
