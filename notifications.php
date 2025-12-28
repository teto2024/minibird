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
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
  .notification-tabs {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      margin-bottom: 16px;
      border-bottom: 1px solid var(--border);
      padding-bottom: 8px;
  }
  .notification-tab {
      padding: 8px 12px;
      border: none;
      background: var(--card);
      color: var(--text);
      cursor: pointer;
      border-radius: 8px 8px 0 0;
      font-size: 14px;
      transition: background 0.2s, color 0.2s;
  }
  .notification-tab:hover {
      background: var(--border);
  }
  .notification-tab.active {
      background: var(--primary);
      color: white;
  }
  #notificationsList {
      list-style: none;
      margin: 0; padding: 0;
  }
  #notificationsList li {
      display: flex;
      gap: 8px;
      padding: 12px;
      border-bottom: 1px solid var(--border);
      align-items: center;
      color: var(--text);
      background: var(--card);
      transition: background 0.2s;
  }
  #notificationsList li:hover {
      background: var(--border);
  }
  #notificationsList li.highlight {
      background: rgba(255, 251, 204, 0.2);
      font-weight: bold;
  }
  #notificationsList li img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      flex-shrink: 0;
  }
  #notificationsList li .notification-content {
      flex: 1;
      min-width: 0;
  }
  #notificationsList li p {
      color: var(--text);
      margin: 0 0 4px 0;
      word-break: break-word;
  }
  #notificationsList li small {
      color: var(--muted);
      font-size: 12px;
  }
  .notification-type-icon {
      font-size: 16px;
      margin-right: 4px;
  }
  .loading-spinner {
      text-align: center;
      padding: 20px;
      color: var(--muted);
  }
  @media (max-width: 600px) {
      .notification-tabs {
          overflow-x: auto;
          flex-wrap: nowrap;
          -webkit-overflow-scrolling: touch;
      }
      .notification-tab {
          flex-shrink: 0;
          font-size: 12px;
          padding: 6px 10px;
      }
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
    
    <div class="notification-tabs">
      <button class="notification-tab active" data-type="">全て</button>
      <button class="notification-tab" data-type="like">❤️ いいね</button>
      <button class="notification-tab" data-type="repost">🔄 リポスト</button>
      <button class="notification-tab" data-type="quote">📝 引用</button>
      <button class="notification-tab" data-type="boost">🚀 ブースト</button>
      <button class="notification-tab" data-type="reply">💬 リプライ</button>
      <button class="notification-tab" data-type="mention">@ メンション</button>
      <button class="notification-tab" data-type="community">🏠 コミュニティ</button>
    </div>
    
    <ul id="notificationsList">
      <li class="loading-spinner">読み込み中...</li>
    </ul>
  </section>
</main>

<script>
let currentType = '';

function getTypeIcon(type) {
    const icons = {
        'like': '❤️',
        'repost': '🔄',
        'quote': '📝',
        'boost': '🚀',
        'reply': '💬',
        'mention': '@',
        'follow': '👤',
        'community_like': '🏠❤️',
        'community_reply': '🏠💬',
        'community_mention': '🏠@'
    };
    return icons[type] || '🔔';
}

async function loadNotifications(type = '') {
    const list = document.getElementById("notificationsList");
    list.innerHTML = '<li class="loading-spinner">読み込み中...</li>';
    
    try {
        const url = type ? `/notifications_api.php?type=${encodeURIComponent(type)}&limit=50` : '/notifications_api.php?limit=50';
        const res = await fetch(url);
        const data = await res.json();

        if (!Array.isArray(data) || data.length === 0) {
            list.innerHTML = "<li>通知はありません。</li>";
            return;
        }

        list.innerHTML = data.map(n => {
            const postLink = n.post && n.post.id 
                ? (n.post.is_community ? `/community_replies.php?post_id=${n.post.id}` : `/replies_enhanced.php?post_id=${n.post.id}`)
                : '#';
            const clickAction = n.post && n.post.id ? `onclick="location.href='${postLink}'" style="cursor: pointer;"` : '';
            const typeIcon = getTypeIcon(n.type);
            return `
            <li class="${n.highlight ? 'highlight' : ''}" ${clickAction}>
                <img src="${n.actor.icon || '/uploads/icons/default_icon.png'}" alt="" onerror="this.src='/uploads/icons/default_icon.png'">
                <div class="notification-content">
                    <p><span class="notification-type-icon">${typeIcon}</span>${n.message}</p>
                    <small>${n.created_at}</small>
                </div>
            </li>
        `;
        }).join("");

        // 既読化（全て表示タブの場合のみ）
        if (type === '') {
            await fetch("/mark_notifications_read.php", { method: "POST" });
        }
    } catch (e) {
        console.error("通知取得エラー", e);
        list.innerHTML = "<li>通知を読み込めませんでした。</li>";
    }
}

// タブ切り替え処理
document.querySelectorAll('.notification-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        // アクティブ状態を更新
        document.querySelectorAll('.notification-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // 通知を読み込み
        currentType = this.dataset.type;
        loadNotifications(currentType);
    });
});

// ページロード時に通知を取得
loadNotifications();
</script>
</body>
</html>
