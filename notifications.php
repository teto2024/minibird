<?php
require_once __DIR__ . '/config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$me = user();
if (!$me) {
    header('Location: /'); // 未ログインはトップへ
    exit;
}

?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>通知 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
<style>
  #notificationsList {
      list-style: none;
      margin: 0; padding: 0;
  }
  #notificationsList li {
      display: flex;
      gap: 8px;
      padding: 8px;
      border-bottom: 1px solid #eee;
      align-items: center;
  }
  #notificationsList li.highlight {
      background: #fffbcc;
      font-weight: bold;
  }
  #notificationsList li img {
      width: 32px;
      height: 32px;
      border-radius: 50%;
  }
</style>
</head>
<body>
<header class="topbar">
  <div class="logo">🐦 MiniBird</div>
  <a href="/">ホーム</a>
</header>

<main class="layout">
  <section class="center">
    <h2>通知</h2>
    <ul id="notificationsList">
      <li>読み込み中...</li>
    </ul>
  </section>
</main>

<script>
async function loadAllNotifications() {
    try {
        const res = await fetch("/notifications_api.php");
        const data = await res.json();
        const list = document.getElementById("notificationsList");

        if (data.length === 0) {
            list.innerHTML = "<li>通知はありません。</li>";
            return;
        }

        list.innerHTML = data.map(n => {
            const postLink = n.post && n.post.id ? `/replies_enhanced.php?pid=${n.post.id}` : '#';
            const clickAction = n.post && n.post.id ? `onclick="location.href='${postLink}'" style="cursor: pointer;"` : '';
            return `
            <li class="${n.highlight ? 'highlight' : ''}" ${clickAction}>
                <img src="${n.actor.icon}" alt="">
                <div>
                    <p>${n.message}</p>
                    <small>${n.created_at}</small>
                </div>
            </li>
        `;
        }).join("");

        // 既読化
        await fetch("/mark_notifications_read.php", { method: "POST" });
    } catch (e) {
        console.error("通知取得エラー", e);
        document.getElementById("notificationsList").innerHTML = "<li>通知を読み込めませんでした。</li>";
    }
}

// ページロード時に通知を取得
loadAllNotifications();
</script>
</body>
</html>
