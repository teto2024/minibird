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
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
/* 管理ページ専用スタイル */
.admin-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.admin-section {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.admin-section h3 {
    margin: 0 0 16px 0;
    color: var(--blue);
    font-size: 1.4em;
    border-bottom: 2px solid var(--border);
    padding-bottom: 8px;
}

.admin-form {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    flex-wrap: wrap;
    align-items: center;
}

.admin-form input {
    flex: 1;
    min-width: 150px;
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--bg);
    color: var(--text);
    font-size: 14px;
}

.admin-form button {
    padding: 8px 16px;
    background: var(--blue);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}

.admin-form button:hover {
    background: #1a8cd8;
    transform: translateY(-1px);
}

.admin-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.admin-list li {
    padding: 10px 12px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 14px;
    transition: all 0.2s;
}

.admin-list li:hover {
    background: #1c2731;
}

.empty-state {
    color: var(--muted);
    font-style: italic;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.admin-table th {
    background: var(--bg);
    color: var(--blue);
    padding: 12px 8px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid var(--border);
}

.admin-table td {
    padding: 12px 8px;
    border-bottom: 1px solid var(--border);
    vertical-align: top;
}

.admin-table tr:hover {
    background: rgba(29, 155, 240, 0.05);
}

.admin-table .post-content {
    max-width: 400px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.admin-table .post-id {
    color: var(--muted);
    font-weight: 600;
}

.admin-table .user-handle {
    color: var(--blue);
}

.action-link {
    color: var(--red);
    text-decoration: none;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s;
}

.action-link:hover {
    background: rgba(249, 24, 128, 0.1);
    text-decoration: underline;
}

.deleted-label {
    color: var(--muted);
    font-style: italic;
}

.user-status {
    font-size: 12px;
    color: var(--muted);
}

.user-status-active {
    color: var(--green);
}

.user-status-muted {
    color: var(--red);
}

.grid-2col {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 24px;
}

@media (max-width: 768px) {
    .grid-2col {
        grid-template-columns: 1fr;
    }
    
    .admin-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .admin-form input {
        width: 100%;
    }
}
</style>
</head><body>
<header class="topbar">
    <div class="logo"><a class="link" href="./">← MiniBird に戻る</a></div>
    <div style="color: var(--muted);">管理者ダッシュボード</div>
</header>
<main class="admin-container">
  <div class="grid-2col">
    <div class="admin-section">
      <h3>🚫 禁止語句管理</h3>
      <form method="post" class="admin-form">
        <input name="banword" placeholder="禁止する単語を入力..." required>
        <button type="submit">追加</button>
      </form>
      <ul class="admin-list">
        <?php 
        if (empty($words)) {
            echo "<li class='empty-state'>登録されている禁止語句はありません</li>";
        } else {
            foreach($words as $w){ 
                echo "<li>🚫 ".htmlspecialchars($w['word'])."</li>"; 
            } 
        }
        ?>
      </ul>
    </div>
    
    <div class="admin-section">
      <h3>👥 ユーザー制御</h3>
      <form method="post" class="admin-form">
        <input name="mute_uid" type="number" placeholder="ユーザーID" required min="1">
        <input name="minutes" type="number" value="30" placeholder="分" required min="1">
        <button type="submit">ミュート</button>
      </form>
      <form method="post" class="admin-form">
        <input name="freeze_uid" type="number" placeholder="ユーザーID" required min="1">
        <button type="submit" style="background: var(--red);">凍結</button>
      </form>
      <p style="margin: 16px 0 8px; font-weight: 600; color: var(--blue);">最近のユーザー：</p>
      <ul class="admin-list">
        <?php 
        foreach($users as $u){ 
            $statusClass = $u['frozen'] ? 'user-status-muted' : ($u['muted_until'] ? 'user-status-muted' : 'user-status-active');
            $statusIcon = $u['frozen'] ? '❄️' : ($u['muted_until'] ? '🔇' : '✅');
            echo "<li>";
            echo "<div><strong class='post-id'>#{$u['id']}</strong> ";
            echo "<span class='user-handle'>@".htmlspecialchars($u['handle'])."</span></div>";
            echo "<div class='user-status $statusClass'>$statusIcon ";
            if ($u['frozen']) echo "凍結中";
            elseif ($u['muted_until']) echo "ミュート期限: {$u['muted_until']}";
            else echo "通常";
            echo "</div></li>";
        } 
        ?>
      </ul>
    </div>
  </div>
  
  <div class="admin-section">
    <h3>📝 投稿管理</h3>
    <div style="overflow-x: auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 80px;">ID</th>
            <th style="width: 150px;">ユーザー</th>
            <th>内容</th>
            <th style="width: 100px;">操作</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          foreach($posts as $p){ 
            echo "<tr>";
            echo "<td class='post-id'>#{$p['id']}</td>";
            echo "<td class='user-handle'>@".htmlspecialchars($p['handle'])."</td>";
            echo "<td class='post-content'>".htmlspecialchars($p['content_md'])."</td>";
            echo "<td>";
            if ($p['deleted_at']) {
                echo "<span class='deleted-label'>削除済み</span>";
            } else {
                echo "<a href='moderate.php?del={$p['id']}' class='action-link' onclick=\"return confirm('本当に削除しますか？');\">削除</a>";
            }
            echo "</td>";
            echo "</tr>";
          } 
          ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body></html>
