<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

// フォロー状態（ログイン中ユーザーのみ）
$isFollowing = false;
if ($me) {
    if ($me['id'] !== $targetId) {
        $st = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followee_id = ?");
        $st->execute([$me['id'], $targetId]);
        $isFollowing = (bool)$st->fetchColumn();
    }
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

    <!-- アイコン -->
    <img src="<?= htmlspecialchars($user['icon'] ?? 'assets/default_icon.png') ?>" 
         class="user-icon" alt="アイコン">

    <!-- 表示名 -->
    <div class="user-display-name"><?= htmlspecialchars($user['display_name'] ?? $user['handle']) ?></div>

    <!-- 自己紹介 -->
    <div class="user-bio"><?= nl2br(htmlspecialchars($user['bio'] ?? '')) ?></div>

    <?php if ($me && $me['id'] !== $targetId): ?>
        <button id="follow-btn" data-user-id="<?= $user['id'] ?>" class="<?= $isFollowing ? 'following' : '' ?>">
            <?= $isFollowing ? 'フォロー解除' : 'フォローする' ?>
        </button>
    <?php endif; ?>

    <div>コイン: <?=$user['coins']?> / クリスタル: <?=$user['crystals']?></div>
</div>

      <?php if ($me && $me['id'] === $targetId): ?>
    <h3>プロフィール編集</h3>
    <form id="profileForm" enctype="multipart/form-data">
      <label>表示名:</label>
      <input type="text" name="display_name" value="<?= htmlspecialchars($user['display_name'] ?? '') ?>">

      <label>自己紹介:</label>
      <textarea name="bio" maxlength="280"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>

      <label>アイコン:</label>
      <input type="file" name="icon" accept="image/*">
      
      <button type="submit">保存</button>
    </form>
<?php endif; ?>
  
    </aside>
    <section class="center">
        <div id="feed" data-feed="user_<?=$targetId?>"></div>
        <div id="loading">読み込み中...</div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const feedEl = document.getElementById('feed');
    if (feedEl) state.feed = feedEl.dataset.feed;
    refreshFeed(true);

    // フォローボタン
    const btn = document.getElementById("follow-btn");
    if (btn) {
        btn.addEventListener("click", async () => {
            const isFollowing = btn.classList.contains("following");
            const targetId = btn.dataset.userId;
            const action = isFollowing ? "unfollow" : "follow";

            try {
                const res = await fetch('follow_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=${action}&target_id=${targetId}`,
                    credentials: 'same-origin'
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
    }

    // プロフィール編集フォーム
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.onsubmit = async (e) => {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);

            try {
                const res = await fetch('users_api.php', {
                    method: 'POST',
                    body: data
                });
                const json = await res.json();
                if (json.ok) {
                    alert('プロフィール更新完了');
                    if (json.icon) document.querySelector('.user-icon').src = json.icon;
                    if (json.bio) document.querySelector('.user-bio').textContent = json.bio;
                    if (json.display_name) document.querySelector('.user-display-name').textContent = json.display_name;
                } else {
                    alert('更新失敗');
                }
            } catch (err) {
                alert('通信エラーが発生しました');
            }
        };
    }
});

</script>
<script src="assets/app.js"></script>
</body>
</html>
