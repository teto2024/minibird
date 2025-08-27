<?php
// ===== Basic Config =====
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'syugetsu2025_clone';
$DB_USER = getenv('DB_USER') ?: 'syugetsu2025_teto';
$DB_PASS = getenv('DB_PASS') ?: 'omatsuteto2025';

// Max upload size 10MB
$MAX_UPLOAD_BYTES = 10 * 1024 * 1024;

// NSFW blur (px)
$NSFW_BLUR = 12;

// Session
session_set_cookie_params([
  'lifetime' => 60*60*24*30,
  'path' => '/',
  'httponly' => true,
  'samesite' => 'Lax'
]);
session_start();

function db() {
  static $pdo;
  global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
  if (!$pdo) {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  }
  return $pdo;
}

function require_login() {
  if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false, 'error'=>'not_logged_in']);
    exit;
  }
}

function user() {
  if (!isset($_SESSION['uid'])) return null;
  $pdo = db();
  $st = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $st->execute([$_SESSION['uid']]);
  return $st->fetch();
}

function markdown_to_html($md) {
  // Tiny markdown (bold **, italic *, inline code ``, links [text](url), line breaks)
  $safe = htmlspecialchars($md, ENT_QUOTES, 'UTF-8');
  $safe = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $safe);
  $safe = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $safe);
  $safe = preg_replace('/`(.+?)`/s', '<code>$1</code>', $safe);
  $safe = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" target="_blank" rel="nofollow">$1</a>', $safe);
  $safe = nl2br($safe);
  return $safe;
}

function generate_user_hash() {
  return bin2hex(random_bytes(16));
}

function now_utc() {
  return (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
}
?>
