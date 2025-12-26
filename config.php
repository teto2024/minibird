<?php
// ===== config.php (common.php 統合版) =====

// ----- DB設定 -----
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'syugetsu2025_clone';
$DB_USER = getenv('DB_USER') ?: 'syugetsu2025_teto';
$DB_PASS = getenv('DB_PASS') ?: 'omatsuteto2025';

$MAX_UPLOAD_BYTES = 10 * 1024 * 1024;  // 10MB
$NSFW_BLUR = 12;

// ----- キャッシュバスティング用バージョン -----
// ファイル更新時にこの値を変更すると、ブラウザキャッシュをクリアできます
define('ASSETS_VERSION', '1.20.0');

// ----- 集中タイマー設定 -----
define('FOCUS_MAX_MINUTES', 180);

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
    $user = $st->fetch();
    
    // 最終操作時刻を更新（1分ごとに更新）
    if ($user) {
        $last_activity = $user['last_activity'] ?? null;
        $now = time();
        if (!$last_activity || (strtotime($last_activity) + 60) < $now) {
            try {
                $update_st = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
                $update_st->execute([$_SESSION['uid']]);
            } catch (PDOException $e) {
                // カラムがまだ存在しない場合は無視
            }
        }
    }
    
    return $user;
}

// ----- ミュートチェック -----
// ユーザーがミュート中かどうかをチェックし、ミュート中ならJSONエラーを返して終了
function check_mute_and_exit_if_muted() {
    $u = user();
    if ($u && $u['muted_until'] && strtotime($u['muted_until']) > time()) {
        $remaining_seconds = strtotime($u['muted_until']) - time();
        $remaining_hours = floor($remaining_seconds / 3600);
        $remaining_minutes = floor(($remaining_seconds % 3600) / 60);
        $remaining_time_str = '';
        if ($remaining_hours > 0) {
            $remaining_time_str = "{$remaining_hours}時間{$remaining_minutes}分";
        } else {
            $remaining_time_str = "{$remaining_minutes}分";
        }
        echo json_encode([
            'ok' => false,
            'error' => 'muted',
            'muted_until' => $u['muted_until'],
            'remaining_time' => $remaining_time_str
        ]);
        exit;
    }
}

// ----- メンション関連の定数と関数 -----
define('MENTION_PATTERN', '/@([a-zA-Z0-9_]+)/');

// メンションを抽出して通知を作成する共通関数
function create_mention_notifications($content, $actor_id, $post_id, $pdo) {
    preg_match_all(MENTION_PATTERN, $content, $mentions);
    if (empty($mentions[1])) {
        return;
    }
    
    $mentioned_handles = array_unique($mentions[1]);
    
    // バッチクエリで全てのメンションされたユーザーを取得
    $placeholders = implode(',', array_fill(0, count($mentioned_handles), '?'));
    $stmt = $pdo->prepare("SELECT id, handle FROM users WHERE handle IN ($placeholders)");
    $stmt->execute($mentioned_handles);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 通知を作成
    foreach ($users as $user) {
        if ($user['id'] != $actor_id) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, actor_id, type, post_id, created_at, is_read)
                VALUES (?, ?, 'mention', ?, NOW(), 0)
            ");
            $stmt->execute([$user['id'], $actor_id, $post_id]);
        }
    }
}

// ----- マークダウン簡易変換 -----
function markdown_to_html($md) {
    $safe = htmlspecialchars($md, ENT_QUOTES, 'UTF-8');
    $safe = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $safe);
    $safe = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $safe);
    $safe = preg_replace('/`(.+?)`/s', '<code>$1</code>', $safe);
    $safe = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" target="_blank" rel="nofollow">$1</a>', $safe);
    
    // メンションリンクの処理（@username）- バッチクエリで最適化
    preg_match_all(MENTION_PATTERN, $safe, $mentions);
    if (!empty($mentions[1])) {
        $mentioned_handles = array_unique($mentions[1]);
        $pdo = db();
        $placeholders = implode(',', array_fill(0, count($mentioned_handles), '?'));
        $stmt = $pdo->prepare("SELECT id, handle FROM users WHERE handle IN ($placeholders)");
        $stmt->execute($mentioned_handles);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ユーザーハンドルからIDへのマップを作成
        $handle_to_id = [];
        foreach ($users as $user) {
            $handle_to_id[$user['handle']] = $user['id'];
        }
        
        // メンションをリンクに置換
        $safe = preg_replace_callback(
            MENTION_PATTERN,
            function($matches) use ($handle_to_id) {
                $handle = $matches[1];
                if (isset($handle_to_id[$handle])) {
                    $url = htmlspecialchars("profile.php?id=" . (int)$handle_to_id[$handle], ENT_QUOTES, 'UTF-8');
                    return '<a href="' . $url . '" class="mention">@' . htmlspecialchars($handle) . '</a>';
                }
                return '@' . htmlspecialchars($handle);
            },
            $safe
        );
    }
    
    // ハッシュタグリンクの処理（#hashtag）
    // 日本語、英数字、アンダースコアに対応
    $safe = preg_replace_callback(
        '/#([a-zA-Z0-9_\p{L}]+)/u',
        function($matches) {
            $tag = $matches[1];
            $url = htmlspecialchars("search.php?q=" . urlencode('#' . $tag), ENT_QUOTES, 'UTF-8');
            return '<a href="' . $url . '" class="hashtag">#' . htmlspecialchars($tag) . '</a>';
        },
        $safe
    );
    
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
