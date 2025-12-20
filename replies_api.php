<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

if ($action==='list'){
  $post_id = (int)($input['post_id'] ?? 0);
  $pdo = db();
  $uid = $_SESSION['uid'] ?? 0;
  $me = $uid ? user() : null;
  
  $st = $pdo->prepare("
    SELECT r.*, u.handle, u.display_name, u.icon, u.active_frame_id,
           f.css_token as frame_class,
           ut.title_id, tp.title_text, tp.title_css,
           (SELECT COUNT(*) FROM likes WHERE post_id = r.id) as like_count,
           ".($uid ? "EXISTS(SELECT 1 FROM likes WHERE post_id = r.id AND user_id = $uid)" : "0")." as user_liked
    FROM replies r
    JOIN users u ON u.id = r.user_id
    LEFT JOIN frames f ON f.id = u.active_frame_id
    LEFT JOIN user_titles ut ON ut.user_id = u.id AND ut.is_equipped = TRUE
    LEFT JOIN title_packages tp ON tp.id = ut.title_id
    WHERE r.post_id = ?
    ORDER BY r.id DESC LIMIT 200
  ");
  $st->execute([$post_id]);
  $items = [];
  foreach ($st as $row){
    $can_delete = ($me && ($row['user_id'] == $me['id'] || in_array($me['role'], ['mod', 'admin'])));
    $items[] = [
      'id'=>(int)$row['id'],
      'user_id'=>(int)$row['user_id'],
      'handle'=>$row['handle'],
      'display_name'=>$row['display_name'] ?? $row['handle'],
      'icon'=>$row['icon'] ?? '/uploads/icons/default_icon.png',
      'frame_class'=>$row['frame_class'],
      'title_text'=>$row['title_text'],
      'title_css'=>$row['title_css'],
      'content_md'=>$row['content_md'],
      'content_html'=>$row['content_html'],
      'created_at'=>$row['created_at'],
      'like_count'=>(int)($row['like_count'] ?? 0),
      'user_liked'=>!empty($row['user_liked']),
      '_can_delete'=>$can_delete
    ];
  }
  echo json_encode(['ok'=>true,'items'=>$items]); exit;
}

if ($action==='create'){
    require_login();
    $post_id = (int)($input['post_id'] ?? 0);
    $content = trim($input['content'] ?? '');
    $nsfw = (int)($input['nsfw'] ?? 0);

    if ($post_id<=0 || $content===''){ 
        echo json_encode(['ok'=>false,'error'=>'bad_input']); 
        exit; 
    }

    $pdo = db();
    $html = markdown_to_html($content);
    $pdo->prepare("INSERT INTO replies(post_id,user_id,content_md,content_html,nsfw,created_at) VALUES(?,?,?,?,?,NOW())")
        ->execute([$post_id,$_SESSION['uid'],$content,$html,$nsfw]);

    // --------------------
    // 通知作成
    // --------------------
    $post_owner_id = (int)$pdo->query("SELECT user_id FROM posts WHERE id=$post_id")->fetchColumn();
    if ($post_owner_id !== $_SESSION['uid']) {
        $st = $pdo->prepare("
            INSERT INTO notifications (user_id, actor_id, type, post_id, created_at, is_read)
            VALUES (?, ?, 'reply', ?, NOW(), 0)
        ");
        $st->execute([$post_owner_id, $_SESSION['uid'], $post_id]);
    }

    // メンション通知の処理
    preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $mentions);
    if (!empty($mentions[1])) {
        $mentioned_handles = array_unique($mentions[1]);
        foreach ($mentioned_handles as $handle) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE handle=?");
            $stmt->execute([$handle]);
            $mentioned_user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($mentioned_user && $mentioned_user['id'] != $_SESSION['uid']) {
                // メンション通知を作成
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, actor_id, type, post_id, created_at, is_read)
                    VALUES (?, ?, 'mention', ?, NOW(), 0)
                ");
                $stmt->execute([$mentioned_user['id'], $_SESSION['uid'], $post_id]);
            }
        }
    }

    echo json_encode(['ok'=>true]); 
    exit;
}

// --------------------
// 返信削除
// --------------------
if ($action === 'delete') {
    require_login();
    $reply_id = (int)($input['reply_id'] ?? 0);

    if ($reply_id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'bad_input']);
        exit;
    }

    $pdo = db();
    $me = user();
    
    // 返信の所有者を確認
    $stmt = $pdo->prepare("SELECT user_id FROM replies WHERE id = ?");
    $stmt->execute([$reply_id]);
    $reply = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reply) {
        echo json_encode(['ok' => false, 'error' => 'not_found']);
        exit;
    }

    // 自分の返信 or モデレータ/管理者のみ削除可能
    if ($reply['user_id'] == $me['id'] || in_array($me['role'], ['mod', 'admin'])) {
        $pdo->prepare("DELETE FROM replies WHERE id = ?")->execute([$reply_id]);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
    }
    exit;
}

echo json_encode(['ok'=>false,'error'=>'unknown_action']);
