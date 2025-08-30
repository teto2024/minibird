<?php
// common.php
// -------------------------
// 共通設定・DB接続・セッション・ユーザー情報ロード
// utf8mb4 対応
// -------------------------

// 設定読み込み
require_once __DIR__ . '/config.php';

// -------------------------
// PDO接続（utf8mb4対応）
// -------------------------
function db() {
    static $pdo;
    if ($pdo) return $pdo;
    global $db_host, $db_name, $db_user, $db_pass;
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

// -------------------------
// セッション管理（DB保存）
// -------------------------
class PDOSessionHandler implements SessionHandlerInterface {
    private $pdo;
    private $table = 'sessions';
    private $ttl;

    public function __construct(PDO $pdo, $ttl = 1440) {
        $this->pdo = $pdo;
        $this->ttl = $ttl;
    }

    public function open($savePath, $sessionName) { return true; }
    public function close() { return true; }

    public function read($id) {
        $stmt = $this->pdo->prepare("SELECT data FROM {$this->table} WHERE id = ? AND expires > ?");
        $stmt->execute([$id, time()]);
        $row = $stmt->fetch();
        return $row['data'] ?? '';
    }

    public function write($id, $data) {
        $expires = time() + $this->ttl;
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (id, data, expires) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE data = ?, expires = ?"
        );
        return $stmt->execute([$id, $data, $expires, $data, $expires]);
    }

    public function destroy($id) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function gc($maxlifetime) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE expires < ?");
        return $stmt->execute([time()]);
    }
}

// PDO セッションハンドラ登録
$handler = new PDOSessionHandler(db());
session_set_save_handler($handler, true);
session_start();

// -------------------------
// ログインチェック・ユーザー情報取得
// -------------------------
function require_login() {
    if (empty($_SESSION['uid'])) {
        http_response_code(403);
        exit('Not logged in');
    }
}

function user() {
    if (empty($_SESSION['uid'])) return null;
    static $cache = [];
    $uid = (int)$_SESSION['uid'];
    if (!isset($cache[$uid])) {
        $st = db()->prepare("SELECT * FROM users WHERE id=?");
        $st->execute([$uid]);
        $cache[$uid] = $st->fetch();
    }
    return $cache[$uid];
}

// -------------------------
// JS向けに全ユーザー情報を埋め込み
// -------------------------
$allUsers = db()->query("SELECT id, handle FROM users")->fetchAll();
?>
<script>
window.userMap = <?php echo json_encode(array_column($allUsers, null, 'handle'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
</script>
