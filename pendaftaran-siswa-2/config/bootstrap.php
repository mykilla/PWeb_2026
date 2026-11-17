<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../lib/pure.php';

date_default_timezone_set('Asia/Jakarta');
if (is_production()) { ini_set('display_errors', '0'); } 

/* ---------- HTTPS, header keamanan, sesi ---------- */
function client_ip(): string
{
    if (env('TRUST_PROXY', '0') === '1' && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    return env('TRUST_PROXY', '0') === '1' && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

if (PHP_SAPI !== 'cli') {
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (is_https()) header('Strict-Transport-Security: max-age=15552000');

    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('PSBSESSID');
        session_set_cookie_params(['httponly' => true, 'secure' => is_https(), 'samesite' => 'Lax', 'path' => '/']);
        session_start();
    }
    $idle = (int)env('SESSION_IDLE_MINUTES', 30) * 60;
    $now = time();
    if (!empty($_SESSION['user_id'])) {
        if (($now - ($_SESSION['last_activity'] ?? $now)) > $idle || ($now - ($_SESSION['login_at'] ?? $now)) > 8 * 3600) {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['flash'][] = ['error', 'Sesi berakhir karena tidak aktif. Silakan masuk kembali.'];
        } else {
            $_SESSION['last_activity'] = $now;
        }
    }
}

require_once __DIR__ . '/database.php';

/* ---------- Helper tampilan ---------- */
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

function base_path(): string
{
    static $base = null;
    if ($base !== null) return $base;
    $root = realpath(__DIR__ . '/..');
    $doc = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $base = ($root && $doc && strpos($root, $doc) === 0) ? str_replace('\\', '/', substr($root, strlen($doc))) : '';
    return $base;
}
function url(string $path = ''): string { return base_path() . '/' . ltrim($path, '/'); }
function redirect(string $path): void { header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path))); exit; }

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flash_render(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as [$t, $m]) $out .= '<div class="alert ' . e($t) . '">' . e($m) . '</div>';
    unset($_SESSION['flash']);
    return $out;
}

function status_badge(string $s): string { return '<span class="badge badge-' . e($s) . '">' . e(status_label($s)) . '</span>'; }
function old(array $src, string $k, string $d = ''): string { return e($src[$k] ?? $d); }

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(403);
        exit('Permintaan tidak valid. Muat ulang halaman dan coba kembali.');
    }
}
function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Metode tidak diizinkan.'); }
    verify_csrf();
}

/* ---------- Rate limiting & audit ---------- */
function rate_bucket(string $name, string $key): string { return hash('sha256', $name . '|' . strtolower($key)); }
function rate_hit(string $bucket): void
{
    global $conn;
    $stmt = $conn->prepare('INSERT INTO rate_limit_hits (bucket, created_at) VALUES (?, NOW())');
    $stmt->bind_param('s', $bucket); $stmt->execute();
    if (random_int(1, 50) === 1) $conn->query('DELETE FROM rate_limit_hits WHERE created_at < (NOW() - INTERVAL 1 DAY)');
}
function rate_exceeded(string $bucket, int $max, int $windowSec): bool
{
    global $conn;
    $stmt = $conn->prepare('SELECT COUNT(*) c FROM rate_limit_hits WHERE bucket = ? AND created_at > (NOW() - INTERVAL ? SECOND)');
    $stmt->bind_param('si', $bucket, $windowSec); $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['c'] >= $max;
}
function rate_clear(string $bucket): void
{
    global $conn;
    $stmt = $conn->prepare('DELETE FROM rate_limit_hits WHERE bucket = ?');
    $stmt->bind_param('s', $bucket); $stmt->execute();
}

function audit(string $aksi, ?string $entitas = null, $entitasId = null, ?string $detail = null): void
{
    global $conn;
    $uid = $_SESSION['user_id'] ?? null; $eid = $entitasId !== null ? (string)$entitasId : null;
    $ip = client_ip(); $detail = $detail !== null ? mb_substr($detail, 0, 500) : null;
    $stmt = $conn->prepare('INSERT INTO audit_log (user_id, aksi, entitas, entitas_id, detail, ip) VALUES (?,?,?,?,?,?)');
    $stmt->bind_param('isssss', $uid, $aksi, $entitas, $eid, $detail, $ip); $stmt->execute();
}

/* ---------- Autentikasi & otorisasi ---------- */
function is_logged_in(): bool { return !empty($_SESSION['user_id']); }
function is_admin(): bool { return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin'; }
function is_pendaftar(): bool { return is_logged_in() && ($_SESSION['role'] ?? '') === 'pendaftar'; }

function home_for_role(): string { return is_admin() ? 'admin/index.php' : 'pendaftar/index.php'; }

function login_user(array $u): void
{
    global $conn;
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$u['id'];
    $_SESSION['role'] = $u['role'];
    $_SESSION['nama'] = $u['nama'];
    $_SESSION['must_change'] = (int)$u['must_change_password'];
    $_SESSION['login_at'] = $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $stmt = $conn->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
    $stmt->bind_param('i', $u['id']); $stmt->execute();
}

function require_login(): void
{
    if (!is_logged_in()) { flash('error', 'Silakan masuk terlebih dahulu.'); redirect('login.php'); }
    if (!empty($_SESSION['must_change']) && basename($_SERVER['SCRIPT_NAME']) !== 'ganti-password.php') {
        flash('error', 'Anda harus mengganti password sebelum melanjutkan.');
        redirect('ganti-password.php');
    }
}
function require_role(string $role): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) { http_response_code(403); exit('Akses ditolak.'); }
}
function require_admin(): void { require_role('admin'); }
function require_pendaftar(): void { require_role('pendaftar'); }

/* ---------- Logika bisnis (akses database) ---------- */
require_once __DIR__ . '/../lib/registration.php';
require_once __DIR__ . '/../lib/payment.php';
