<?php
require_once __DIR__ . '/config.php';
$me = user();
if (!$me || !in_array($me['role'], ['mod','admin'])) { http_response_code(403); echo "forbidden"; exit; }
$pdo = db();
if ($_SERVER['REQUEST_METHOD']==='POST'){
  if (isset($_POST['banword']) && $_POST['banword']!==''){
    $pdo->prepare("INSERT IGNORE INTO banned_words(word) VALUES(?)")->execute([$_POST['banword']]);
  }
  if (isset($_POST['mute_uid'], $_POST['minutes'])){
    $minutes = max(1, min(1440, (int)$_POST['minutes']));
    $pdo->prepare("UPDATE users SET muted_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id=?")
        ->execute([$minutes, (int)$_POST['mute_uid']]);
  }
  if (isset($_POST['freeze_uid'])){
    $pdo->prepare("UPDATE users SET frozen=1 WHERE id=?")->execute([(int)$_POST['freeze_uid']]);
  }
  header("Location: admin.php"); exit;
}
$posts = $pdo->query("SELECT p.id, u.handle, p.content_md, p.deleted_at FROM posts p JOIN users u ON u.id=p.user_id ORDER BY p.id DESC LIMIT 100")->fetchAll();
$words = $pdo->query("SELECT * FROM banned_words ORDER BY id DESC")->fetchAll();
$users = $pdo->query("SELECT id, handle, muted_until, frozen FROM users ORDER BY id DESC LIMIT 100")->fetchAll();
?>
<!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>管理 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css"></head><body>
<header class="topbar"><div class="logo"><a class="link" href="./">← 戻る</a></div></header>
<main class="layout"><section class="center">
  <div class="card"><h3>禁止語句</h3>
    <form method="post"><input name="banword" placeholder="追加..."><button>追加</button></form>
    <ul><?php foreach($words as $w){ echo "<li>".htmlspecialchars($w['word'])."</li>"; } ?></ul>
  </div>
  <div class="card"><h3>ユーザー制御</h3>
    <form method="post">User ID: <input name="mute_uid" type="number" placeholder="ID"> ミュート(分): <input name="minutes" type="number" value="30"> <button>設定</button></form>
    <form method="post" style="margin-top:8px">User ID: <input name="freeze_uid" type="number" placeholder="ID"> <button>凍結</button></form>
    <p>最近のユーザー：</p>
    <ul><?php foreach($users as $u){ echo "<li>#{$u['id']} @".htmlspecialchars($u['handle'])." / muted_until={$u['muted_until']} / frozen={$u['frozen']}</li>"; } ?></ul>
  </div>
  <div class="card"><h3>投稿管理</h3>
    <table><tr><th>ID</th><th>ユーザー</th><th>内容</th><th>操作</th></tr>
      <?php foreach($posts as $p){ echo "<tr><td>{$p['id']}</td><td>@".htmlspecialchars($p['handle'])."</td><td>".htmlspecialchars($p['content_md'])."</td><td>".($p['deleted_at']?'削除済み':"<a href='moderate.php?del={$p['id']}'>削除</a>")."</td></tr>"; } ?>
    </table>
  </div>
</section></main>
</body></html>
