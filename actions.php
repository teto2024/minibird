<?php
require_once __DIR__ . '/config.php';
ob_start();
require_login();

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

function insertNotification($type, $actor_id, $target_user_id, $post_id = null) {
    if ($actor_id === $target_user_id) return; // 自分のアクションは通知不要
    $pdo = db();
    $st = $pdo->prepare("
        INSERT INTO notifications (user_id, actor_id, type, post_id, created_at, is_read)
        VALUES (?, ?, ?, ?, NOW(), 0)
    ");
    $st->execute([$target_user_id, $actor_id, $type, $post_id]);
}

if ($action === 'toggle_like') {
    require_login();
    $post_id = (int)($input['post_id'] ?? 0);
    if ($post_id <= 0) { echo json_encode(['ok'=>false]); exit; }

    $pdo = db();
    $st = $pdo->prepare("SELECT 1 FROM likes WHERE user_id=? AND post_id=?");
    $st->execute([$_SESSION['uid'],$post_id]);
    $alreadyLiked = (bool)$st->fetch();

    if ($alreadyLiked) {
        $pdo->prepare("DELETE FROM likes WHERE user_id=? AND post_id=?")->execute([$_SESSION['uid'],$post_id]);
    } else {
        $pdo->prepare("INSERT INTO likes(user_id,post_id) VALUES(?,?)")->execute([$_SESSION['uid'],$post_id]);

        // 投稿者に通知
        $post_owner_id = (int)$pdo->query("SELECT user_id FROM posts WHERE id=$post_id")->fetchColumn();
        insertNotification('like', $_SESSION['uid'], $post_owner_id, $post_id);
    }

    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM likes WHERE post_id=".$post_id)->fetchColumn();
    echo json_encode(['ok'=>true,'count'=>$cnt,'liked'=>!$alreadyLiked]);
    exit;
}

if ($action === 'toggle_repost') {
    require_login();
    $post_id = (int)($input['post_id'] ?? 0);
    if ($post_id <= 0) { echo json_encode(['ok'=>false]); exit; }

    $pdo = db();
    $st = $pdo->prepare("SELECT 1 FROM reposts WHERE user_id=? AND post_id=?");
    $st->execute([$_SESSION['uid'], $post_id]);
    $alreadyReposted = (bool)$st->fetch();

    if ($alreadyReposted) {
        $pdo->prepare("DELETE FROM reposts WHERE user_id=? AND post_id=?")->execute([$_SESSION['uid'], $post_id]);
        // 投稿コピーも削除
        $pdo->prepare("DELETE FROM posts WHERE user_id=? AND is_repost_of=?")->execute([$_SESSION['uid'], $post_id]);
        $reposted = false;
    } else {
        $pdo->prepare("INSERT INTO reposts(user_id, post_id) VALUES(?, ?)")->execute([$_SESSION['uid'], $post_id]);

        // タイムライン用にコピー作成
        $pdo->prepare("
            INSERT INTO posts(user_id, content_md, content_html, is_repost_of, created_at)
            SELECT ?, content_md, content_html, id, NOW() 
            FROM posts 
            WHERE id=?
        ")->execute([$_SESSION['uid'], $post_id]);

        // 投稿者に通知
        $original_post_owner_id = (int)$pdo->query("SELECT user_id FROM posts WHERE id=$post_id")->fetchColumn();
        insertNotification('repost', $_SESSION['uid'], $original_post_owner_id, $post_id);

        $reposted = true;
    }

    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM reposts WHERE post_id=".$post_id)->fetchColumn();
    echo json_encode(['ok'=>true, 'count'=>$cnt, 'reposted'=>$reposted]);
    exit;
}

if ($action === 'toggle_bookmark') {
    require_login();
    $post_id = (int)($input['post_id'] ?? 0);
    $pdo = db();
    $st = $pdo->prepare("SELECT 1 FROM bookmarks WHERE user_id=? AND post_id=?");
    $st->execute([$_SESSION['uid'],$post_id]);
    $alreadyBookmarked = (bool)$st->fetch();

    if ($alreadyBookmarked) {
        $pdo->prepare("DELETE FROM bookmarks WHERE user_id=? AND post_id=?")->execute([$_SESSION['uid'],$post_id]);
    } else {
        $pdo->prepare("INSERT INTO bookmarks(user_id,post_id) VALUES(?,?)")->execute([$_SESSION['uid'],$post_id]);
    }
    echo json_encode(['ok'=>true]);
    exit;
}

if ($action === 'delete_post') {
    require_login();
    $uid = $_SESSION['uid'];
    $post_id = (int)($input['post_id'] ?? $_POST['post_id'] ?? 0);
    if ($post_id <= 0) { echo json_encode(['ok'=>false,'error'=>'bad_input']); exit; }

    $pdo = db();
    $st = $pdo->prepare("SELECT user_id, deleted_at FROM posts WHERE id = ?");
    $st->execute([$post_id]);
    $post = $st->fetch(PDO::FETCH_ASSOC);
    if (!$post) { echo json_encode(['ok'=>false,'error'=>'not_found']); exit; }

    $st2 = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $st2->execute([$uid]);
    $role = $st2->fetchColumn();

    $is_owner = ((int)$post['user_id'] === (int)$uid);
    $is_mod_or_admin = ($role === 'mod' || $role === 'admin');

    if (!$is_owner && !$is_mod_or_admin) { echo json_encode(['ok'=>false,'error'=>'forbidden']); exit; }

    $deleted_by_mod = $is_mod_or_admin && !$is_owner ? 1 : 0;
    $pdo->prepare("UPDATE posts SET deleted_at = NOW(), deleted_by_mod = ? WHERE id = ?")
        ->execute([$deleted_by_mod, $post_id]);

    echo json_encode(['ok'=>true, 'deleted_by_mod'=>$deleted_by_mod]);
    exit;
}

$output = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
ob_end_clean();
echo $output;
