<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? 'fetch';

function serialize_post($row, $uid, $pdo){
    // --- メッセージ内容 ---
    $content_html = '';
    if (!empty($row['deleted_at'])) {
        $content_html = !empty($row['deleted_by_mod']) ? 'モデレータにより削除済み' : '削除済み';
    } elseif (!empty($row['content_html'])) {
        $content_html = $row['content_html'];
    } elseif (!empty($row['content_md'])) {
        $content_html = nl2br(htmlspecialchars($row['content_md']));
        $content_html = preg_replace_callback(
            '/@([a-zA-Z0-9_]+)/',
            function($matches) use ($pdo) {
                $handle = $matches[1];
                $stmt = $pdo->prepare("SELECT id FROM users WHERE handle=?");
                $stmt->execute([$handle]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $url = "profile.php?id=" . (int)$user['id'];
                    return '<a href="' . $url . '" class="mention">@' . htmlspecialchars($handle) . '</a>';
                }
                return '@' . htmlspecialchars($handle);
            },
            $content_html
        );
    }

    // --- 投稿者情報（通常投稿 or リポスト元） ---
    $is_repost = !empty($row['original_id']);
    if ($is_repost) {
        $display_name = $row['original_display_name'] ?? $row['original_handle'] ?? 'unknown';
        $icon = !empty($row['original_icon']) ? '/' . ltrim($row['original_icon'], '/') : '/uploads/icons/default_icon.png';
        $vip_level = isset($row['original_vip']) ? (int)$row['original_vip'] : 0;
        $handle = $row['original_handle'] ?? 'unknown';
        $frame_class = $row['original_frame_class'] ?? null;
    } else {
        $display_name = !empty($row['display_name']) ? $row['display_name'] : ($row['handle'] ?? 'unknown');
        $icon = !empty($row['icon']) ? '/' . ltrim($row['icon'], '/') : '/uploads/icons/default_icon.png';
        $vip_level = isset($row['vip_level']) ? (int)$row['vip_level'] : 0;
        $handle = $row['handle'] ?? 'unknown';
        $frame_class = $row['frame_class'] ?? null;
    }

    // --- 引用投稿 ---
    $quoted_post = null;
    if (!empty($row['quote_post_id'])) {
        $q = $pdo->prepare("
            SELECT posts.id, posts.user_id, u.handle, u.display_name, u.icon, u.vip_level, u.role,
                   posts.content_md, posts.content_html, posts.deleted_at, posts.deleted_by_mod
            FROM posts
            JOIN users u ON u.id = posts.user_id
            WHERE posts.id = ?
        ");
        $q->execute([$row['quote_post_id']]);
        $qp = $q->fetch(PDO::FETCH_ASSOC);
        if ($qp) {
            $quoted_post = [
                'id' => (int)($qp['id'] ?? 0),
                'user_id' => (int)($qp['user_id'] ?? 0),
                'handle' => $qp['handle'] ?? 'unknown',
                'display_name' => !empty($qp['display_name']) ? $qp['display_name'] : ($qp['handle'] ?? 'unknown'),
                'icon' => !empty($qp['icon']) ? '/' . ltrim($qp['icon'], '/') : '/uploads/icons/default_icon.png',
                'vip_level' => isset($qp['vip_level']) ? (int)$qp['vip_level'] : 0,
                'content_md' => $qp['content_md'] ?? '',
                'content_html' => $qp['content_html'] ?? '',
                'deleted' => !empty($qp['deleted_at'])
            ];
        }
    }

    // 複数画像パスの処理
    $media_paths = null;
    if (!empty($row['media_paths'])) {
        $decoded = json_decode($row['media_paths'], true);
        if (is_array($decoded)) {
            $media_paths = $decoded;
        }
    }

    // Get user role for badge display
    $user_role = null;
    if ($is_repost) {
        $user_role = $row['original_role'] ?? null;
    } else {
        $user_role = $row['role'] ?? null;
    }

    return [
        'id'=> (int)($row['id'] ?? 0),
        'user_id'=> $is_repost ? (int)($row['original_id'] ?? 0) : (int)($row['user_id'] ?? 0),
        'handle'=> $handle,
        'display_name'=> $display_name,
        'icon'=> $icon,
        'vip_level'=> $vip_level,
        'role'=> $user_role,
        'title_text'=> $row['title_text'] ?? null,
        'title_css'=> $row['title_css'] ?? null,
        'created_at'=> $row['created_at'] ?? null,
        'content_html'=> $content_html,
        'content_md'  => !empty($row['deleted_at']) ? null : ($row['content_md'] ?? ''),
        'deleted'=> !empty($row['deleted_at']),
        'nsfw'=> (int)($row['nsfw'] ?? 0)===1,
        'media_path'=> $row['media_path'] ?? null,
        'media_type'=> $row['media_type'] ?? null,
        'media_paths'=> $media_paths,
        'like_count'=> (int)($row['like_count'] ?? 0),
        'repost_count'=> (int)($row['repost_count'] ?? 0),
        'reply_count'=> (int)($row['reply_count'] ?? 0),
        'boost_count'=> (int)($row['boost_count'] ?? 0),
        'liked'=> !empty($row['liked']),
        'reposted'=> !empty($row['reposted']),
        'is_repost_of'=> !empty($row['is_repost_of']) ? (int)$row['is_repost_of'] : null,
        'reposter'=> [
            'id'     => isset($row['reposter_id']) ? (int)$row['reposter_id'] : null,
            'handle' => $row['reposter_handle'] ?? null,
            'display_name' => $row['reposter_display_name'] ?? $row['reposter_handle'] ?? null,
            'icon'   => !empty($row['reposter_icon']) ? '/' . ltrim($row['reposter_icon'], '/') : null,
        ],
        'frame_class'=> $frame_class,
        '_can_delete' => false,
        'quoted_post' => $quoted_post
    ];
}

$pdo = db();
$uid = $_SESSION['uid'] ?? 0;
$me = $uid ? user() : null;

// --- 共通 fetch 処理 ---
function fetch_feed($sql, $params, $uid, $pdo, $me, $limit){
    $st = $pdo->prepare($sql);
    $i = 1;
    foreach($params as $val){ $st->bindValue($i++, $val, PDO::PARAM_INT); }
    $st->bindValue($i, $limit, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $items = [];
    foreach($rows as $row){
        $p = serialize_post($row, $uid, $pdo);
        $p['_can_delete'] = ($uid && ($uid === (int)$row['user_id'] || ($me && in_array($me['role'],['mod','admin']))));
        $items[] = $p;
    }
    return $items;
}

// ========================
// fetch / fetch_more
// ========================
if($action === 'fetch' || $action === 'fetch_more'){
    $feed = $input['feed'] ?? ($_GET['feed'] ?? 'global');
    $limit = max(1, min(100, (int)($input['limit'] ?? ($_GET['limit'] ?? 50))));

    // --- boost feed ---
    if($feed === 'boost'){
        $sql = "SELECT p.*, u.handle, u.display_name, u.icon, u.vip_level, u.role, p.deleted_at, p.deleted_by_mod,
                  (SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) AS like_count,
                  (SELECT COUNT(*) FROM reposts r WHERE r.post_id=p.id) AS repost_count,
                  (SELECT COUNT(*) FROM replies rp WHERE rp.post_id=p.id) AS reply_count,
                  ".($uid?"EXISTS(SELECT 1 FROM likes l2 WHERE l2.post_id=p.id AND l2.user_id=$uid)":"0")." AS liked,
                  ".($uid?"EXISTS(SELECT 1 FROM reposts r2 WHERE r2.post_id=p.id AND r2.user_id=$uid)":"0")." AS reposted,
                  (SELECT COUNT(*) FROM post_boosts pb WHERE pb.post_id=p.id AND pb.expires_at > NOW()) AS boost_count,
                  ou.id AS original_id,
                  ou.handle AS original_handle,
                  ou.display_name AS original_display_name,
                  ou.icon AS original_icon,
                  ou.vip_level AS original_vip,
                  ou.role AS original_role,
                  f_orig.css_token AS original_frame_class,
                  ru.id AS reposter_id,
                  ru.handle AS reposter_handle,
                  ru.display_name AS reposter_display_name,
                  ru.icon AS reposter_icon,
                  f.css_token AS frame_class,
                  tp.title_text, tp.title_css
                FROM posts p
                JOIN users u ON u.id = p.user_id
                INNER JOIN post_boosts pb ON pb.post_id = p.id AND pb.expires_at > NOW()
                LEFT JOIN posts op ON op.id = p.is_repost_of
                LEFT JOIN users ou ON ou.id = op.user_id
                LEFT JOIN frames f_orig ON f_orig.id = (SELECT active_frame_id FROM users WHERE id=ou.id)
                LEFT JOIN users ru ON ru.id = p.user_id
                LEFT JOIN frames f ON f.id = (SELECT active_frame_id FROM users WHERE id=p.user_id)
                LEFT JOIN user_titles ut ON ut.user_id = u.id AND ut.is_equipped = TRUE
                LEFT JOIN title_packages tp ON tp.id = ut.title_id
                WHERE p.deleted_at IS NULL
                GROUP BY p.id
                ORDER BY boost_count DESC, p.created_at DESC
                LIMIT ?";
        $items = fetch_feed($sql, [], $uid, $pdo, $me, $limit);
        // Add boost_count to each item
        foreach($items as &$item) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_boosts WHERE post_id = ? AND expires_at > NOW()");
            $stmt->execute([$item['id']]);
            $item['boost_count'] = (int)$stmt->fetchColumn();
        }
        echo json_encode(['ok'=>true,'items'=>$items]);
        exit;
    }

    // --- bookmarks ---
    if($feed === 'bookmarks'){
        if(!$me){ echo json_encode(['ok'=>false,'error'=>'login_required']); exit; }
        $sql = "SELECT p.*, u.handle, u.display_name, u.icon, u.vip_level, u.role, p.deleted_at, p.deleted_by_mod,
                  (SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) AS like_count,
                  (SELECT COUNT(*) FROM reposts r WHERE r.post_id=p.id) AS repost_count,
                  (SELECT COUNT(*) FROM replies rp WHERE rp.post_id=p.id) AS reply_count,
                  EXISTS(SELECT 1 FROM likes l2 WHERE l2.post_id=p.id AND l2.user_id=?) AS liked,
                  EXISTS(SELECT 1 FROM reposts r2 WHERE r2.post_id=p.id AND r2.user_id=?) AS reposted,
                  ou.id AS original_id,
                  ou.handle AS original_handle,
                  ou.display_name AS original_display_name,
                  ou.icon AS original_icon,
                  ou.vip_level AS original_vip,
                  f_orig.css_token AS original_frame_class,
                  ru.id AS reposter_id,
                  ru.handle AS reposter_handle,
                  ru.display_name AS reposter_display_name,
                  ru.icon AS reposter_icon,
                  f.css_token AS frame_class,
                  tp.title_text, tp.title_css
                FROM bookmarks b
                JOIN posts p ON p.id = b.post_id
                JOIN users u ON u.id = p.user_id
                LEFT JOIN posts op ON op.id = p.is_repost_of
                LEFT JOIN users ou ON ou.id = op.user_id
                LEFT JOIN frames f_orig ON f_orig.id = (SELECT active_frame_id FROM users WHERE id=ou.id)
                LEFT JOIN users ru ON ru.id = p.user_id
                LEFT JOIN frames f ON f.id = (SELECT active_frame_id FROM users WHERE id=p.user_id)
                LEFT JOIN user_titles ut ON ut.user_id = u.id AND ut.is_equipped = TRUE
                LEFT JOIN title_packages tp ON tp.id = ut.title_id
                WHERE b.user_id = ?
                ORDER BY b.created_at DESC
                LIMIT ?";
        $items = fetch_feed($sql, [$uid,$uid,$uid], $uid, $pdo, $me, $limit);
        echo json_encode(['ok'=>true,'items'=>$items]);
        exit;
    }

    // --- communities ---
    if($feed === 'communities'){
        if(!$me){ echo json_encode(['ok'=>false,'error'=>'login_required']); exit; }
        $sql = "SELECT p.*, u.handle, u.display_name, u.icon, u.vip_level, u.role, p.deleted_at, p.deleted_by_mod,
                  (SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) AS like_count,
                  (SELECT COUNT(*) FROM reposts r WHERE r.post_id=p.id) AS repost_count,
                  (SELECT COUNT(*) FROM replies rp WHERE rp.post_id=p.id) AS reply_count,
                  EXISTS(SELECT 1 FROM likes l2 WHERE l2.post_id=p.id AND l2.user_id=?) AS liked,
                  EXISTS(SELECT 1 FROM reposts r2 WHERE r2.post_id=p.id AND r2.user_id=?) AS reposted,
                  ou.id AS original_id,
                  ou.handle AS original_handle,
                  ou.display_name AS original_display_name,
                  ou.icon AS original_icon,
                  ou.vip_level AS original_vip,
                  f_orig.css_token AS original_frame_class,
                  ru.id AS reposter_id,
                  ru.handle AS reposter_handle,
                  ru.display_name AS reposter_display_name,
                  ru.icon AS reposter_icon,
                  f.css_token AS frame_class,
                  tp.title_text, tp.title_css
                FROM community_members m
                JOIN posts p ON p.community_id = m.community_id
                JOIN users u ON u.id = p.user_id
                LEFT JOIN posts op ON op.id = p.is_repost_of
                LEFT JOIN users ou ON ou.id = op.user_id
                LEFT JOIN frames f_orig ON f_orig.id = (SELECT active_frame_id FROM users WHERE id=ou.id)
                LEFT JOIN users ru ON ru.id = p.user_id
                LEFT JOIN frames f ON f.id = (SELECT active_frame_id FROM users WHERE id=p.user_id)
                LEFT JOIN user_titles ut ON ut.user_id = u.id AND ut.is_equipped = TRUE
                LEFT JOIN title_packages tp ON tp.id = ut.title_id
                WHERE m.user_id = ?
                ORDER BY p.id DESC
                LIMIT ?";
        $items = fetch_feed($sql, [$uid,$uid,$uid], $uid, $pdo, $me, $limit);
        echo json_encode(['ok'=>true,'items'=>$items]);
        exit;
    }

    // --- recommended ---
    if($feed === 'recommended'){
        $sql = "SELECT p.*, u.handle, u.display_name, u.icon, u.vip_level,
                    p.deleted_at, p.deleted_by_mod,
                    (SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) AS like_count,
                    (SELECT COUNT(*) FROM reposts r WHERE r.post_id=p.id) AS repost_count,
                    (SELECT COUNT(*) FROM replies rp WHERE rp.post_id=p.id) AS reply_count,
                    (SELECT COUNT(*) FROM post_boosts pb WHERE pb.post_id=p.id AND pb.expires_at > NOW()) AS boost_count,
                    ".($uid?"EXISTS(SELECT 1 FROM likes l2 WHERE l2.post_id=p.id AND l2.user_id=$uid)":"0")." AS liked,
                    ".($uid?"EXISTS(SELECT 1 FROM reposts r2 WHERE r2.post_id=p.id AND r2.user_id=$uid)":"0")." AS reposted,
                    ou.id AS original_id,
                    ou.handle AS original_handle,
                    ou.display_name AS original_display_name,
                    ou.icon AS original_icon,
                    ou.vip_level AS original_vip,
                    f_orig.css_token AS original_frame_class,
                    ru.id AS reposter_id,
                    ru.handle AS reposter_handle,
                    ru.display_name AS reposter_display_name,
                    ru.icon AS reposter_icon,
                    f.css_token AS frame_class,
                    tp.title_text, tp.title_css
                FROM posts p
                JOIN users u ON u.id = p.user_id
                LEFT JOIN posts op ON op.id = p.is_repost_of
                LEFT JOIN users ou ON ou.id = op.user_id
                LEFT JOIN frames f_orig ON f_orig.id = (SELECT active_frame_id FROM users WHERE id=ou.id)
                LEFT JOIN users ru ON ru.id = p.user_id
                LEFT JOIN frames f ON f.id = (SELECT active_frame_id FROM users WHERE id=p.user_id)
                LEFT JOIN user_titles ut ON ut.user_id = u.id AND ut.is_equipped = TRUE
                LEFT JOIN title_packages tp ON tp.id = ut.title_id
                WHERE p.nsfw = 0 AND p.created_at >= (NOW() - INTERVAL 3 DAY) AND p.deleted_at IS NULL
                ORDER BY ((SELECT COUNT(*) FROM replies rpX WHERE rpX.post_id=p.id)*3
                         + (SELECT COUNT(*) FROM reposts rX WHERE rX.post_id=p.id)*2
                         + (SELECT COUNT(*) FROM likes lX WHERE lX.post_id=p.id)) DESC,
                         p.id DESC
                LIMIT ?";
        $items = fetch_feed($sql, [], $uid, $pdo, $me, $limit);
        echo json_encode(['ok'=>true,'items'=>$items]);
        exit;
    }

    // --- global / following / user ---
    $where = "p.deleted_at IS NULL";
    $params = [];
    $since_id = (int)($input['since_id'] ?? 0);
    $max_id = (int)($input['max_id'] ?? 0);

    if($feed==='following' && $uid){
        $where .= " AND p.user_id IN (SELECT followee_id FROM follows WHERE follower_id=?)";
        $params[] = $uid;
    }
    if(strpos($feed,'user_')===0){
        $targetId = (int)substr($feed,5);
        $where .= " AND p.user_id = ?";
        $params[] = $targetId;
    }
    if($since_id>0){ $where.=" AND p.id > ?"; $params[]=$since_id; }
    if($max_id>0){ $where.=" AND p.id <= ?"; $params[]=$max_id; }

    $sql = "SELECT p.*, u.handle, u.display_name, u.icon, u.vip_level, u.role, p.deleted_at, p.deleted_by_mod,
                (SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) AS like_count,
                (SELECT COUNT(*) FROM reposts r WHERE r.post_id=p.id) AS repost_count,
                (SELECT COUNT(*) FROM replies rp WHERE rp.post_id=p.id) AS reply_count,
                (SELECT COUNT(*) FROM post_boosts pb WHERE pb.post_id=p.id AND pb.expires_at > NOW()) AS boost_count,
                ".($uid?"EXISTS(SELECT 1 FROM likes l2 WHERE l2.post_id=p.id AND l2.user_id=$uid)":"0")." AS liked,
                ".($uid?"EXISTS(SELECT 1 FROM reposts r2 WHERE r2.post_id=p.id AND r2.user_id=$uid)":"0")." AS reposted,
                ou.id AS original_id,
                ou.handle AS original_handle,
                ou.display_name AS original_display_name,
                ou.icon AS original_icon,
                ou.vip_level AS original_vip,
                f_orig.css_token AS original_frame_class,
                ru.id AS reposter_id,
                ru.handle AS reposter_handle,
                ru.display_name AS reposter_display_name,
                ru.icon AS reposter_icon,
                f.css_token AS frame_class,
                tp.title_text, tp.title_css
            FROM posts p
            JOIN users u ON u.id = p.user_id
            LEFT JOIN posts op ON op.id = p.is_repost_of
            LEFT JOIN users ou ON ou.id = op.user_id
            LEFT JOIN frames f_orig ON f_orig.id = (SELECT active_frame_id FROM users WHERE id=ou.id)
            LEFT JOIN users ru ON ru.id = p.user_id
            LEFT JOIN frames f ON f.id = (SELECT active_frame_id FROM users WHERE id=p.user_id)
            LEFT JOIN user_titles ut ON ut.user_id = u.id AND ut.is_equipped = TRUE
            LEFT JOIN title_packages tp ON tp.id = ut.title_id
            WHERE $where
            ORDER BY p.id DESC
            LIMIT ?";
    $items = fetch_feed($sql, $params, $uid, $pdo, $me, $limit);
    echo json_encode(['ok'=>true,'items'=>$items]);
    exit;
}
