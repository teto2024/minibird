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
<style>
/* プロフィールページのレスポンシブデザイン */
.profile-header {
    background: var(--card);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.profile-info {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.user-icon {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--blue);
    margin: 0 auto;
    display: block;
}

.user-display-name {
    font-size: 24px;
    font-weight: bold;
    text-align: center;
    margin-top: 10px;
}

.user-handle {
    color: var(--muted);
    text-align: center;
    font-size: 16px;
}

.user-bio {
    text-align: center;
    margin: 15px 0;
    line-height: 1.6;
}

.user-stats {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.stat-item {
    background: linear-gradient(135deg, rgba(29, 155, 240, 0.1), rgba(118, 75, 162, 0.1));
    padding: 12px 20px;
    border-radius: 8px;
    text-align: center;
    min-width: 100px;
}

.stat-label {
    font-size: 12px;
    color: var(--muted);
    display: block;
}

.stat-value {
    font-size: 20px;
    font-weight: bold;
    color: var(--blue);
}

.profile-actions {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
}

.profile-actions button {
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

#follow-btn {
    background: var(--blue);
    color: white;
    border: none;
}

#follow-btn.following {
    background: transparent;
    border: 2px solid var(--blue);
    color: var(--blue);
}

/* 編集フォーム */
.profile-edit {
    background: var(--card);
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
}

.profile-edit h3 {
    margin-bottom: 15px;
}

.profile-edit form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.profile-edit label {
    display: block;
    color: var(--muted);
    margin-bottom: 5px;
    font-size: 14px;
}

.profile-edit input,
.profile-edit textarea {
    width: 100%;
    padding: 10px;
    border: 2px solid var(--border);
    border-radius: 8px;
    background: var(--bg);
    color: var(--text);
    font-family: inherit;
}

.profile-edit button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
}

/* タブレット・モバイル対応 */
@media (max-width: 768px) {
    .user-icon {
        width: 100px;
        height: 100px;
    }
    
    .user-display-name {
        font-size: 20px;
    }
    
    .user-stats {
        gap: 10px;
    }
    
    .stat-item {
        min-width: 80px;
        padding: 10px 15px;
    }
    
    .stat-value {
        font-size: 18px;
    }
}
</style>
</head>
<body>
<header>
    <h1><a href="/" style="text-decoration: none; color: inherit;">🐦 MiniBird</a></h1>
</header>
<main class="layout">
    <section class="center">
        <div class="profile-header">
            <div class="profile-info">
                <!-- アイコン -->
                <img src="<?= htmlspecialchars($user['icon'] ?? 'assets/default_icon.png') ?>" 
                     class="user-icon" alt="アイコン">

                <!-- 表示名 -->
                <div class="user-display-name"><?= htmlspecialchars($user['display_name'] ?? $user['handle']) ?></div>
                <div class="user-handle">@<?= htmlspecialchars($user['handle']) ?></div>

                <!-- 自己紹介 -->
                <div class="user-bio"><?= nl2br(htmlspecialchars($user['bio'] ?? '')) ?></div>

                <!-- 通貨情報 -->
                <div class="user-stats">
                    <div class="stat-item">
                        <span class="stat-label">コイン</span>
                        <span class="stat-value">💰<?=$user['coins']?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">クリスタル</span>
                        <span class="stat-value">💎<?=$user['crystals']?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">ダイヤモンド</span>
                        <span class="stat-value">💠<?=$user['diamonds'] ?? 0?></span>
                    </div>
                </div>

                <?php if ($me && $me['id'] !== $targetId): ?>
                    <div class="profile-actions">
                        <button id="follow-btn" data-user-id="<?= $user['id'] ?>" class="<?= $isFollowing ? 'following' : '' ?>">
                            <?= $isFollowing ? 'フォロー解除' : 'フォローする' ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($me && $me['id'] === $targetId): ?>
        <div class="profile-edit">
            <h3>プロフィール編集</h3>
            <form id="profileForm" enctype="multipart/form-data">
                <div>
                    <label>表示名:</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($user['display_name'] ?? '') ?>">
                </div>

                <div>
                    <label>自己紹介:</label>
                    <textarea name="bio" maxlength="280"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>

                <div>
                    <label>アイコン:</label>
                    <input type="file" name="icon" accept="image/*">
                </div>
                
                <button type="submit">保存</button>
            </form>
        </div>
        <?php endif; ?>
        
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
