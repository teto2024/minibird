<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

// -------------------------
// DBセッションハンドラ設定
// -------------------------
class DbSessionHandler implements SessionHandlerInterface {
    private $pdo;
    private $table = 'sessions';
    private $lifetime;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->lifetime = ini_get('session.gc_maxlifetime');
    }

    public function open($savePath, $sessionName) { return true; }
    public function close() { return true; }

    public function read($id) {
        $st = $this->pdo->prepare("SELECT data FROM {$this->table} WHERE id=? AND expires_at > NOW()");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row['data'] ?? '';
    }

    public function write($id, $data) {
        $expires = date('Y-m-d H:i:s', time() + $this->lifetime);
        $st = $this->pdo->prepare("
            INSERT INTO {$this->table} (id, data, expires_at) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE data=VALUES(data), expires_at=VALUES(expires_at)
        ");
        return $st->execute([$id, $data, $expires]);
    }

    public function destroy($id) {
        $st = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id=?");
        return $st->execute([$id]);
    }

    public function gc($maxlifetime) {
        $st = $this->pdo->prepare("DELETE FROM {$this->table} WHERE expires_at < NOW()");
        return $st->execute();
    }
}

// PDO取得
$pdo = db();

// セッションハンドラ登録
$handler = new DbSessionHandler($pdo);
session_set_save_handler($handler, true);
session_start(); // <- ここから $_SESSION が DB に保存される
// -------------------------

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

// 以下、auth.php の既存処理はそのまま

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

if ($action === 'register') {
  $handle = trim($input['handle'] ?? '');
  $password = $input['password'] ?? '';
  $invited_by = trim($input['invited_by'] ?? '');

  if (!preg_match('/^[A-Za-z0-9_]{3,16}$/', $handle)) {
    echo json_encode(['ok'=>false,'error'=>'invalid_handle']); exit;
  }
  if (strlen($password) < 6) {
    echo json_encode(['ok'=>false,'error'=>'weak_password']); exit;
  }
  $pdo = db();
  $st = $pdo->prepare("SELECT id FROM users WHERE handle=?");
  $st->execute([$handle]);
  if ($st->fetch()) { echo json_encode(['ok'=>false,'error'=>'handle_taken']); exit; }

  $inviterId = null;
  if ($invited_by) {
    $st = $pdo->prepare("SELECT id FROM users WHERE handle=?");
    $st->execute([$invited_by]);
    $inviter = $st->fetch();
    if ($inviter) $inviterId = $inviter['id'];
  }

  $passhash = password_hash($password, PASSWORD_DEFAULT);
  $user_hash = generate_user_hash();
  $pdo->prepare("INSERT INTO users(handle, passhash, user_hash, invite_by, coins, crystals) VALUES(?,?,?,?,?,?)")
      ->execute([$handle,$passhash,$user_hash,$inviterId,100,1]); // welcome bonus

  $uid = $pdo->lastInsertId();
  if ($inviterId) {
    $pdo->prepare("INSERT IGNORE INTO invites(inviter_id, invitee_id) VALUES(?,?)")->execute([$inviterId,$uid]);
    // inviter reward
    $pdo->prepare("UPDATE users SET crystals = crystals + 3 WHERE id=?")->execute([$inviterId]);
    $pdo->prepare("INSERT INTO reward_events(user_id,kind,amount,meta) VALUES(?,?,?,JSON_OBJECT('invitee',?))")
        ->execute([$inviterId,'invite_signup',3,$uid]);
  }

  $_SESSION['uid'] = (int)$uid;
  echo json_encode(['ok'=>true]); exit;
}

if ($action === 'login') {
  $handle = trim($input['handle'] ?? '');
  $password = $input['password'] ?? '';
  $pdo = db();
  $st = $pdo->prepare("SELECT * FROM users WHERE handle=?");
  $st->execute([$handle]);
  $u = $st->fetch();
  if (!$u || !password_verify($password, $u['passhash'])) {
    echo json_encode(['ok'=>false,'error'=>'invalid_credentials']); exit;
  }
  if ((int)$u['frozen'] === 1) {
    echo json_encode(['ok'=>false,'error'=>'account_frozen']); exit;
  }
  $_SESSION['uid'] = (int)$u['id'];
  echo json_encode(['ok'=>true]); exit;
}

if ($action === 'logout') {
  session_destroy();
  echo json_encode(['ok'=>true]); exit;
}

if ($action === 'get_user_hash') {
  require_login();
  $u = user();
  echo json_encode(['ok'=>true, 'user_hash'=>$u['user_hash']]);
  exit;
}

if ($action === 'change_password') {
  require_login();
  $new = $input['new_password'] ?? '';
  if (strlen($new) < 6) { echo json_encode(['ok'=>false,'error'=>'weak_password']); exit; }
  $pdo = db();
  $pdo->prepare("UPDATE users SET passhash=? WHERE id=?")
      ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['uid']]);
  echo json_encode(['ok'=>true]); exit;
}

echo json_encode(['ok'=>false,'error'=>'unknown_action']);
