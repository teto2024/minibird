<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';
$pdo = db();

$me = user();
$q = trim($_GET['q'] ?? '');
$tab = $_GET['tab'] ?? 'posts'; // posts or users

$users = [];
$posts = [];

if ($q !== '') {
    if ($tab === 'users' || $tab === 'all') {
        $stmt = $pdo->prepare("SELECT id, handle, display_name FROM users WHERE handle LIKE :q OR display_name LIKE :q LIMIT 50");
        $stmt->execute(['q' => "%$q%"]);
        $users = $stmt->fetchAll();
    }
    if ($tab === 'posts' || $tab === 'all') {
        // 投稿内ハッシュ検索
$stmt = $pdo->prepare("SELECT id, content_md FROM posts WHERE content_md LIKE :q ORDER BY created_at DESC LIMIT 50");
$stmt->execute(['q' => "%$q%"]);
$posts = $stmt->fetchAll();

    }
}

function linkify_handles($content) {
    return preg_replace(
        '/@([a-zA-Z0-9_]+)/',
        '<a href="/profile.php?handle=$1" class="mention">@$1</a>',
        htmlspecialchars($content)
    );
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>検索結果 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
</head>
<body>
<header class="topbar">
  <a href="/" class="logo">🐦 MiniBird</a>
  <div class="search">
    <form method="get" action="/search.php">
      <input id="q" name="q" placeholder="検索..." value="<?=htmlspecialchars($q)?>">
      <button>検索</button>
    </form>
  </div>
</header>

<main class="layout">
  <section class="center">
    <h2>検索結果: "<?=htmlspecialchars($q)?>"</h2>
    <div class="feed-tabs">
      <a class="feedTab <?=($tab==='posts')?'active':''?>" href="?q=<?=urlencode($q)?>&tab=posts">投稿</a>
      <a class="feedTab <?=($tab==='users')?'active':''?>" href="?q=<?=urlencode($q)?>&tab=users">ユーザー</a>
      <a class="feedTab <?=($tab==='all')?'active':''?>" href="?q=<?=urlencode($q)?>&tab=all">全て</a>
    </div>

    <div id="feed">
      <?php if (($tab==='posts' || $tab==='all') && count($posts) > 0): ?>
        <h3>投稿</h3>
        <ul>
        <?php foreach($posts as $p): ?>
        <li style="cursor: pointer;" onclick="handlePostClick(event, <?=$p['id']?>)">
          <?=linkify_handles($p['content_md'])?>
        </li>
        <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if (($tab==='users' || $tab==='all') && count($users) > 0): ?>
        <h3>ユーザー</h3>
        <ul>
        <?php foreach($users as $u): ?>
          <li>
            <a href="/profile.php?handle=<?=$u['handle']?>">
              @<?=$u['handle']?>
              <?php if (!empty($u['display_name'])): ?>
                (<?=htmlspecialchars($u['display_name'])?>)
              <?php endif; ?>
            </a>
          </li>
        <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if (count($posts) === 0 && count($users) === 0): ?>
        <p>検索結果はありませんでした。</p>
      <?php endif; ?>
    </div>
  </section>
</main>

<script>
function handlePostClick(event, postId) {
  // メンションリンクをクリックした場合は、そのリンクを優先
  if (event.target.tagName === 'A' && event.target.classList.contains('mention')) {
    return; // メンションリンクのデフォルト動作を実行
  }
  
  // それ以外の場合は投稿の詳細ページに遷移
  if (event.target.tagName !== 'A') {
    window.location.href = '/replies_enhanced.php?post_id=' + postId;
  }
}
</script>
</body>
</html>
