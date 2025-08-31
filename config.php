<?php
// ===== config.php (common.php 統合版) =====

// ----- DB設定 -----
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'syugetsu2025_clone';
$DB_USER = getenv('DB_USER') ?: 'syugetsu2025_teto';
$DB_PASS = getenv('DB_PASS') ?: 'omatsuteto2025';

$MAX_UPLOAD_BYTES = 10 * 1024 * 1024;  // 10MB
$NSFW_BLUR = 12;

// ----- セッション -----
session_set_cookie_params([
    'lifetime' => 60*60*24*30,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
// ユーザーIDの統一
// ログイン時に $_SESSION['uid'] がセットされる場合も $_SESSION['user_id'] にコピー
if (isset($_SESSION['uid']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_SESSION['uid'];
}
// ----- DB接続 -----
function db() {
    static $pdo;
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
    if (!$pdo) {
        $pdo = new PDO(
            "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
            $DB_USER,
            $DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}

// ----- ユーザー認証 -----
function require_login() {
    if (!isset($_SESSION['uid'])) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'error'=>'login_required']);
        exit;
    }
}

function user() {
    if (!isset($_SESSION['uid'])) return null;
    $pdo = db();
    $st = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $st->execute([$_SESSION['uid']]);
    return $st->fetch();
}

// ----- マークダウン簡易変換 -----
function markdown_to_html($md) {
    $safe = htmlspecialchars($md, ENT_QUOTES, 'UTF-8');
    $safe = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $safe);
    $safe = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $safe);
    $safe = preg_replace('/`(.+?)`/s', '<code>$1</code>', $safe);
    $safe = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" target="_blank" rel="nofollow">$1</a>', $safe);
    $safe = nl2br($safe);
    return $safe;
}

// ----- ユーザーハッシュ生成 -----
function generate_user_hash() {
    return bin2hex(random_bytes(16));
}

// ----- UTC時間取得 -----
function now_utc() {
    return (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
}
