<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);


$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? 'fetch';

function serialize_post($row, $uid, $pdo){
    $content_html = null;

    if ($row['deleted_at']) {
        $content_html = $row['deleted_by_mod'] ? 'モデレータにより削除済み' : '削除済み';
    } elseif (!empty($row['content_html'])) {
        $content_html = $row['content_html'];
    } elseif (!empty($row['content_md'])) {
        // MarkdownをHTML化
        $content_html = nl2br(htmlspecialchars($row['content_md']));

        // @handle をリンク化
        // serialize_post内のメンションリンク修正
$content_html = preg_replace_callback(
    '/@([a-zA-Z0-9_]+)/',
    function($matches) use ($pdo) {
        $handle = $matches[1];
        $stmt = $pdo->prepare("SELECT id FROM users WHERE handle=?");
        $stmt->execute([$handle]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $url = "profile.php?id=" . (int)$user['id'];  // IDベースに変更
            return '<a href="' . $url . '" class="mention">@' . htmlspecialchars($handle) . '</a>';
        } else {
            return '@' . htmlspecialchars($handle);
        }
    },
    $content_html
);
    } else {
        $content_html = '';
    }

    // 以下省略せずにそのまま
    $quoted_post = null;
    if (!empty($row['quote_post_id'])) {
        $q = $pdo->prepare("
            SELECT posts.id, posts.user_id, u.handle, posts.content_md, posts.content_html, posts.deleted_at, posts.deleted_by_mod
            FROM posts
            JOIN users u ON u.id = posts.user_id
            WHERE posts.id = ?
        ");
        $q->execute([$row['quote_post_id']]);
        $qp = $q->fetch(PDO::FETCH_ASSOC);
        if ($qp) {
            $quoted_post = [
                'id' => (int)$qp['id'],
                'handle' => $qp['handle'],
                'content_md' => $qp['content_md'],
                'content_html' => $qp['content_html'],
                'deleted' => (bool)$qp['deleted_at']
            ];
        }
    }

    return [
    'id'=> (int)$row['id'],
    // ここをリンク化
    'handle'=> $row['handle'],
    'created_at'=> $row['created_at'],
    'content_html'=> $content_html,
    'content_md'  => $row['deleted_at'] ? null : $row['content_md'],
    'deleted'=> (bool)$row['deleted_at'],
    'nsfw'=> (int)$row['nsfw']===1,
    'media_path'=> $row['media_path'],
    'media_type'=> $row['media_type'],
    'like_count'=> (int)($row['like_count'] ?? 0),
    'repost_count'=> (int)($row['repost_count'] ?? 0),
    'reply_count'=> (int)($row['reply_count'] ?? 0),
    'liked'=> (bool)($row['liked'] ?? false),
    'reposted'=> (bool)($row['reposted'] ?? false),
    'is_repost_of'=> $row['is_repost_of'] ? (int)$row['is_repost_of'] : null,
    'reposter'=> $row['reposter'] ?? null,
    'frame_class'=> $row['frame_class'] ?? null,
    '_can_delete' => false,
    'quoted_post' => $quoted_post
];

}


$pdo = db();
$uid = $_SESSION['uid'] ?? 0;
$me = $uid ? user() : null;

if ($action === 'fetch' || $action === 'fetch_more') {
    // 修正ポイント: GET からも feed を受け取る
    $feed = $input['feed'] ?? ($_GET['feed'] ?? 'global');
    $limit = max(1, min(100, (int)($input['limit'] ?? ($_GET['limit'] ?? 50))));


    // --- bookmarks ---
if ($feed === 'bookmarks') {
    if (!$me) { echo json_encode(['ok'=>false,'error'=>'login_required']); exit; }
    $sql = "SELECT p.*, u.handle, p.deleted_at, p.deleted_by_mod,
              (SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) AS like_count,
              (SELECT COUNT(*) FROM reposts r WHERE r.post_id=p.id) AS repost_count,
              (SELECT COUNT(*) FROM replies rp WHERE rp.post_id=p.id) AS reply_count,
              EXISTS(SELECT 1 FROM likes l2 WHERE l2.post_id=p.id AND l2.user_id=?) AS liked,
              EXISTS(SELECT 1 FROM reposts r2 WHERE r2.post_id=p.id AND r2.user_id=?) AS reposted,
              (SELECT handle FROM users u2 WHERE u2.id = (SELECT user_id FROM posts WHERE id = p.is_repost_of)) AS reposter,
              (SELECT css_token FROM frames f WHERE f.id = (SELECT active_frame_id FROM users WHERE id=p.user_id)) AS frame_class
            FROM bookmarks b
            JOIN posts p ON p.id = b.post_id
            JOIN users u ON u.id = p.user_id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
            LIMIT ?";
    $st = $pdo->prepare($sql);
    // ここで整数としてバインド
    $st->bindValue(1, $uid, PDO::PARAM_INT);
    $st->bindValue(2, $uid, PDO::PARAM_INT);
    $st->bindValue(3, $uid, PDO::PARAM_INT);
    $st->bindValue(4, $limit, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rows as $row) {
        $p = serialize_post($row, $uid, $pdo);
        $p['_can_delete'] = ($uid && ($uid === (int)$row['user_id'] || ($me && ($me['role']==='mod' || $me['role']==='admin'))));
        $items[] = $p;
    }
    echo json_encode(['ok'=>true,'items'=>$items]);
    exit;
}

// --- communities ---
if ($feed === 'communities') {
    if (!$me) { echo json_encode(['ok'=>false,'error'=>'login_required']); exit; }
    $sql = "SELECT p.*, u.handle, p.deleted_at, p.deleted_by_mod,
              (SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) AS like_count,
              (SELECT COUNT(*) FROM reposts r WHERE r.post_id=p.id) AS repost_count,
              (SELECT COUNT(*) FROM replies rp WHERE rp.post_id=p.id) AS reply_count,
              EXISTS(SELECT 1 FROM likes l2 WHERE l2.post_id=p.id AND l2.user_id=?) AS liked,
              EXISTS(SELECT 1 FROM reposts r2 WHERE r2.post_id=p.id AND r2.user_id=?) AS reposted,
              (SELECT handle FROM users u2 WHERE u2.id = (SELECT user_id FROM posts WHERE id = p.is_repost_of)) AS reposter,
              (SELECT css_token FROM frames f WHERE f.id = (SELECT active_frame_id FROM users WHERE id=p.user_id)) AS frame_class
            FROM community_members m
            JOIN posts p ON p.community_id = m.community_id
            JOIN users u ON u.id = p.user_id
            WHERE m.user_id = ?
            ORDER BY p.id DESC
            LIMIT ?";
    $st = $pdo->prepare($sql);
    // ここも整数としてバインド
    $st->bindValue(1, $uid, PDO::PARAM_INT);
    $st->bindValue(2, $uid, PDO::PARAM_INT);
    $st->bindValue(3, $uid, PDO::PARAM_INT);
    $st->bindValue(4, $limit, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rows as $row) {
        $p = serialize_post($row, $uid, $pdo);
        $p['_can_delete'] = ($uid && ($uid === (int)$row['user_id'] || ($me && ($me['role']==='mod' || $me['role']==='admin'))));
        $items[] = $p;
    }
    echo json_encode(['ok'=>true,'items'=>$items]);
    exit;
}


    // --- recommended ---
if ($feed === 'recommended') {
    $sql = "
    SELECT 
        p.*,
        u.handle,
        p.deleted_at,
        p.deleted_by_mod,
        (SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) AS like_count,
        (SELECT COUNT(*) FROM reposts r WHERE r.post_id=p.id) AS repost_count,
        (SELECT COUNT(*) FROM replies rp WHERE rp.post_id=p.id) AS reply_count,
        ".($uid ? "EXISTS(SELECT 1 FROM likes l2 WHERE l2.post_id=p.id AND l2.user_id=$uid)" : "0")." AS liked,
        ".($uid ? "EXISTS(SELECT 1 FROM reposts r2 WHERE r2.post_id=p.id AND r2.user_id=$uid)" : "0")." AS reposted,
        u2.handle AS reposter,
        f.css_token AS frame_class
    FROM posts p
    JOIN users u ON u.id = p.user_id
    LEFT JOIN posts rp_post ON rp_post.id = p.is_repost_of
    LEFT JOIN users u2 ON u2.id = rp_post.user_id
    LEFT JOIN frames f ON f.id = (SELECT active_frame_id FROM users WHERE id=p.user_id)
    WHERE p.nsfw = 0 AND p.created_at >= (NOW() - INTERVAL 3 DAY) AND p.deleted_at IS NULL
    ORDER BY ((SELECT COUNT(*) FROM replies rpX WHERE rpX.post_id=p.id)*3
             + (SELECT COUNT(*) FROM reposts rX WHERE rX.post_id=p.id)*2
             + (SELECT COUNT(*) FROM likes lX WHERE lX.post_id=p.id)) DESC,
             p.id DESC
    LIMIT ?
    ";

    $st = $pdo->prepare($sql);
    // 数値としてバインド
    $st->bindValue(1, $limit, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rows as $row) {
        $p = serialize_post($row, $uid, $pdo);
        $p['_can_delete'] = ($uid && ($uid === (int)$row['user_id'] || ($me && ($me['role']==='mod' || $me['role']==='admin'))));
        $items[] = $p;
    }
    echo json_encode(['ok'=>true,'items'=>$items]);
    exit;
}

// --- global / following / user feed ---
$where = "p.deleted_at IS NULL";
$order = "p.id DESC";
$since_id = (int)($input['since_id'] ?? 0);
$max_id = (int)($input['max_id'] ?? 0);
$params = [];

// followingフィード
if ($feed === 'following' && $uid) {
    $where .= " AND p.user_id IN (SELECT followee_id FROM follows WHERE follower_id=?)";
    $params[] = $uid;
}

// user_xxxフィード（IDベースに統一）
if (strpos($feed, 'user_') === 0) {
    $targetId = (int)substr($feed, 5);
    $where .= " AND p.user_id = ?";
    $params[] = $targetId;
}
if ($since_id > 0) { $where .= " AND p.id > ?"; $params[] = $since_id; }
if ($max_id > 0) { $where .= " AND p.id <= ?"; $params[] = $max_id; }

$sql = "SELECT p.*, u.handle, p.deleted_at, p.deleted_by_mod,
            (SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) AS like_count,
            (SELECT COUNT(*) FROM reposts r WHERE r.post_id=p.id) AS repost_count,
            (SELECT COUNT(*) FROM replies rp WHERE rp.post_id=p.id) AS reply_count,
            ".($uid ? "EXISTS(SELECT 1 FROM likes l2 WHERE l2.post_id=p.id AND l2.user_id=$uid)" : "0")." AS liked,
            ".($uid ? "EXISTS(SELECT 1 FROM reposts r2 WHERE r2.post_id=p.id AND r2.user_id=$uid)" : "0")." AS reposted,
            (SELECT handle FROM users u2 WHERE u2.id = (SELECT user_id FROM posts WHERE id = p.is_repost_of)) AS reposter,
            (SELECT css_token FROM frames f WHERE f.id = (SELECT active_frame_id FROM users WHERE id=p.user_id)) AS frame_class
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE $where
        ORDER BY $order
        LIMIT ?";

$st = $pdo->prepare($sql);
$i = 1;
foreach ($params as $val) { $st->bindValue($i++, $val, PDO::PARAM_INT); }
// ここも整数としてバインド
$st->bindValue($i, $limit, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$items = [];
foreach ($rows as $row) {
    $p = serialize_post($row, $uid, $pdo);
    $p['_can_delete'] = ($uid && ($uid === (int)$row['user_id'] || ($me && ($me['role']==='mod' || $me['role']==='admin'))));
    $items[] = $p;
}
echo json_encode(['ok'=>true,'items'=>$items]);
exit;

}

echo json_encode(['ok'=>false,'error'=>'unknown_action']);
