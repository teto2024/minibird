<?php
require_once __DIR__ . '/config.php';


header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

if ($action === 'toggle_like') {
  require_login();
  $post_id = (int)($input['post_id'] ?? 0);
  if ($post_id<=0) { echo json_encode(['ok'=>false]); exit; }
  $pdo = db();
  $st = $pdo->prepare("SELECT 1 FROM likes WHERE user_id=? AND post_id=?");
  $st->execute([$_SESSION['uid'],$post_id]);
  if ($st->fetch()) {
    $pdo->prepare("DELETE FROM likes WHERE user_id=? AND post_id=?")->execute([$_SESSION['uid'],$post_id]);
  } else {
    $pdo->prepare("INSERT INTO likes(user_id,post_id) VALUES(?,?)")->execute([$_SESSION['uid'],$post_id]);
  }
  $cnt = (int)$pdo->query("SELECT COUNT(*) FROM likes WHERE post_id=".$post_id)->fetchColumn();
  echo json_encode(['ok'=>true,'count'=>$cnt,'liked'=>!$st->fetch()]);
  exit;
}

if ($action === 'toggle_repost') {
    require_login();

    $post_id = (int)($input['post_id'] ?? 0);
    if ($post_id <= 0) {
        echo json_encode(['ok' => false]);
        exit;
    }

    $pdo = db();

    // 既にリポストしているか確認
    $st = $pdo->prepare("SELECT 1 FROM reposts WHERE user_id=? AND post_id=?");
    $st->execute([$_SESSION['uid'], $post_id]);

    if ($st->fetch()) {
        // リポスト解除
        $pdo->prepare("DELETE FROM reposts WHERE user_id=? AND post_id=?")
            ->execute([$_SESSION['uid'], $post_id]);

        // リポストのコピー投稿を削除（オプション）
        $pdo->prepare("DELETE FROM posts WHERE user_id=? AND is_repost_of=?")
            ->execute([$_SESSION['uid'], $post_id]);

        $reposted = false;
    } else {
        // リポスト追加
        $pdo->prepare("INSERT INTO reposts(user_id, post_id) VALUES(?, ?)")
            ->execute([$_SESSION['uid'], $post_id]);

        // タイムライン用に投稿コピーを作成
        $pdo->prepare("
            INSERT INTO posts(user_id, content_md, content_html, is_repost_of, created_at)
            SELECT ?, content_md, content_html, id, NOW() 
            FROM posts 
            WHERE id=?
        ")->execute([$_SESSION['uid'], $post_id]);

        $reposted = true;
    }

    // リポスト数を取得
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM reposts WHERE post_id=" . $post_id)->fetchColumn();

    echo json_encode(['ok' => true, 'count' => $cnt, 'reposted' => $reposted]);
    exit;
}




if ($action === 'toggle_bookmark') {
  require_login();
  $post_id = (int)($input['post_id'] ?? 0);
  $pdo = db();
  $st = $pdo->prepare("SELECT 1 FROM bookmarks WHERE user_id=? AND post_id=?");
  $st->execute([$_SESSION['uid'],$post_id]);
  if ($st->fetch()) {
    $pdo->prepare("DELETE FROM bookmarks WHERE user_id=? AND post_id=?")->execute([$_SESSION['uid'],$post_id]);
  } else {
    $pdo->prepare("INSERT INTO bookmarks(user_id,post_id) VALUES(?,?)")->execute([$_SESSION['uid'],$post_id]);
  }
  echo json_encode(['ok'=>true]); exit;
}
// actions.php の中（require_once config 等は既にある想定）
if ($action === 'delete_post') {
    require_login();
    $uid = $_SESSION['uid'];
    $post_id = (int)($input['post_id'] ?? $_POST['post_id'] ?? 0);
    if ($post_id <= 0) { echo json_encode(['ok'=>false, 'error'=>'bad_input']); exit; }

    $pdo = db();
    // 投稿の所有者と現在のユーザーのロールを取得
    $st = $pdo->prepare("SELECT user_id, deleted_at FROM posts WHERE id = ?");
    $st->execute([$post_id]);
    $post = $st->fetch(PDO::FETCH_ASSOC);
    if (!$post) { echo json_encode(['ok'=>false,'error'=>'not_found']); exit; }

    // 権限チェック：投稿者本人 OR mod/admin
    $st2 = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $st2->execute([$uid]);
    $role = $st2->fetchColumn();

    $is_owner = ((int)$post['user_id'] === (int)$uid);
    $is_mod_or_admin = ($role === 'mod' || $role === 'admin');

    if (!$is_owner && !$is_mod_or_admin) {
        echo json_encode(['ok'=>false,'error'=>'forbidden']); exit;
    }

    // mark deleted; if mod deletes, set deleted_by_mod=1
    $deleted_by_mod = $is_mod_or_admin && !$is_owner ? 1 : 0;
    $pdo->prepare("UPDATE posts SET deleted_at = NOW(), deleted_by_mod = ? WHERE id = ?")
        ->execute([$deleted_by_mod, $post_id]);

    echo json_encode(['ok'=>true, 'deleted_by_mod'=>$deleted_by_mod]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'unknown_action']);
