<?php
require_once __DIR__ . '/config.php';
$me = user();

// URLパラメータからプロフィール表示対象
$handle = $_GET['handle'] ?? '';
if (!$handle) die('ユーザーが指定されていません');

// 対象ユーザー取得
$stmt = $db->prepare("SELECT id, handle, coins, crystals FROM users WHERE handle = ?");
$stmt->execute([$handle]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) die('ユーザーが見つかりません');
$targetId = $user['id'];

// フォロー状況確認（ログイン中の場合）
$isFollowing = false;
if ($me) {
    $stmt = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$me['id'], $targetId]);
    $isFollowing = (bool)$stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@<?=htmlspecialchars($user['handle'])?> のプロフィール</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header>
    <h1>@<?=htmlspecialchars($user['handle'])?></h1>
</header>
<main class="layout">
    <aside class="right">
        <div class="card">
            <h3>プロフィール</h3>
            <button id="follow-btn" data-user-id="<?=$targetId?>" class="<?= $isFollowing ? 'following' : '' ?>">
                <?= $isFollowing ? 'フォロー解除' : 'フォローする' ?>
            </button>
            <div>コイン: <?=$user['coins']?> / クリスタル: <?=$user['crystals']?></div>
        </div>
    </aside>
    <section class="center">
        <div id="feed" data-feed="user_<?=$targetId?>"></div>
        <div id="loading">読み込み中...</div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="assets/app.js"></script>
<script>
// ユーザー専用フィードとして state.feed を変更
state.feed = "user_<?=$targetId?>";
refreshFeed(true);
</script>
</body>
</html>
