<?php
require 'config.php';


$targetId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$handle = $_GET['handle'] ?? null;

if (!$targetId && $handle) {
    $st = db()->prepare("SELECT id FROM users WHERE handle=?");
    $st->execute([$handle]);
    $userRow = $st->fetch();
    if ($userRow) $targetId = (int)$userRow['id'];
}

if (!$targetId) die('ユーザーIDが指定されていません');

$pdo = db();
$st = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$st->execute([$targetId]);
$user = $st->fetch();
if (!$user) die('ユーザーが存在しません');



// ログイン中ユーザー
$me = user();

// フォロー状態
$isFollowing = false;
if ($me && $me['id'] !== $targetId) {
    $st = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
    $st->execute([$me['id'], $targetId]);
    $isFollowing = (bool)$st->fetchColumn();
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
            <?php if ($me && $me['id'] !== $targetId): ?>
                <button id="follow-btn" data-user-id="<?=$targetId?>" class="<?= $isFollowing ? 'following' : '' ?>">
                    <?= $isFollowing ? 'フォロー解除' : 'フォローする' ?>
                </button>
            <?php endif; ?>
            <div>コイン: <?=$user['coins']?> / クリスタル: <?=$user['crystals']?></div>
        </div>
    </aside>
    <section class="center">
        <div id="feed" data-feed="user_<?=$targetId?>"></div>
        <div id="loading">読み込み中...</div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
// ユーザー専用フィードとして state.feed を変更
document.addEventListener('DOMContentLoaded', () => {
    const feedEl = document.getElementById('feed');
    if (feedEl) state.feed = feedEl.dataset.feed;
    refreshFeed(true);

    const btn = document.getElementById("follow-btn");
    if (!btn) return;

    btn.addEventListener("click", async () => {
        const targetId = btn.dataset.userId;
        const isFollowing = btn.classList.contains("following");
        const action = isFollowing ? "unfollow" : "follow";

        try {
            const res = await fetch(`follow_api.php?action=${action}`, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `target_id=${targetId}`,
                credentials: 'include'
            });
            const data = await res.json();

            if (data.status === "success") {
                if (isFollowing) {
                    btn.classList.remove("following");
                    btn.textContent = "フォローする";
                } else {
                    btn.classList.add("following");
                    btn.textContent = "フォロー解除";
                }
            } else {
                alert(data.message || "エラーが発生しました");
            }
        } catch (e) {
            alert("通信エラーが発生しました");
        }
    });
});
</script>
<script src="assets/app.js"></script>
</body>
</html>