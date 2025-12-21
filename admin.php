<?php
require_once __DIR__ . '/config.php';


// 最大ミュート期間（分）
define('MAX_MUTE_MINUTES', 10080); // 7日間

$me = user();
if (!$me || !in_array($me['role'], ['mod','admin'])) { http_response_code(403); echo "forbidden"; exit; }
$pdo = db();
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $action = $_POST['action'] ?? '';
  
  if ($action === 'add_banword' && isset($_POST['banword']) && $_POST['banword']!==''){
    $pdo->prepare("INSERT IGNORE INTO banned_words(word) VALUES(?)")->execute([$_POST['banword']]);
  }
  
  if ($action === 'mute_user' && isset($_POST['mute_uid'], $_POST['minutes'])){
    $minutes = max(1, min(MAX_MUTE_MINUTES, (int)$_POST['minutes']));
    $pdo->prepare("UPDATE users SET muted_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id=?")
        ->execute([$minutes, (int)$_POST['mute_uid']]);
  }
  
  if ($action === 'freeze_user' && isset($_POST['freeze_uid'])){
    $pdo->prepare("UPDATE users SET frozen=1 WHERE id=?")->execute([(int)$_POST['freeze_uid']]);
  }
  
  if ($action === 'unfreeze_user' && isset($_POST['user_id'])){
    $pdo->prepare("UPDATE users SET frozen=0 WHERE id=?")->execute([(int)$_POST['user_id']]);
  }
  
  if ($action === 'unmute_user' && isset($_POST['user_id'])){
    $pdo->prepare("UPDATE users SET muted_until=NULL WHERE id=?")->execute([(int)$_POST['user_id']]);
  }
  
  // 通報処理
  if ($action === 'resolve_report' && isset($_POST['report_id'], $_POST['report_action'])){
    $report_id = (int)$_POST['report_id'];
    $report_action = $_POST['report_action'];
    $admin_comment = $_POST['admin_comment'] ?? '';
    
    if ($report_action === 'delete_post') {
      // 投稿を削除
      $stmt = $pdo->prepare("SELECT post_id FROM reports WHERE id=?");
      $stmt->execute([$report_id]);
      $report = $stmt->fetch();
      if ($report) {
        $pdo->prepare("UPDATE posts SET deleted_at=NOW(), deleted_by_mod=1 WHERE id=?")->execute([$report['post_id']]);
      }
    }
    
    // 通報ステータス更新
    $pdo->prepare("UPDATE reports SET status='resolved', reviewed_by=?, reviewed_at=NOW(), admin_comment=? WHERE id=?")
        ->execute([$me['id'], $admin_comment, $report_id]);
  }
  
  if ($action === 'dismiss_report' && isset($_POST['report_id'])){
    $report_id = (int)$_POST['report_id'];
    $admin_comment = $_POST['admin_comment'] ?? '';
    $pdo->prepare("UPDATE reports SET status='dismissed', reviewed_by=?, reviewed_at=NOW(), admin_comment=? WHERE id=?")
        ->execute([$me['id'], $admin_comment, $report_id]);
  }
  
  // 異議申し立て処理
  if ($action === 'approve_appeal' && isset($_POST['appeal_id'])){
    $appeal_id = (int)$_POST['appeal_id'];
    $admin_comment = $_POST['admin_comment'] ?? '';
    
    // ユーザーのミュート解除
    $stmt = $pdo->prepare("SELECT user_id FROM appeals WHERE id=?");
    $stmt->execute([$appeal_id]);
    $appeal = $stmt->fetch();
    if ($appeal) {
      $pdo->prepare("UPDATE users SET muted_until=NULL WHERE id=?")->execute([$appeal['user_id']]);
    }
    
    // 異議申し立てステータス更新
    $pdo->prepare("UPDATE appeals SET status='approved', reviewed_by=?, reviewed_at=NOW(), admin_comment=? WHERE id=?")
        ->execute([$me['id'], $admin_comment, $appeal_id]);
  }
  
  if ($action === 'reject_appeal' && isset($_POST['appeal_id'])){
    $appeal_id = (int)$_POST['appeal_id'];
    $admin_comment = $_POST['admin_comment'] ?? '';
    $pdo->prepare("UPDATE appeals SET status='rejected', reviewed_by=?, reviewed_at=NOW(), admin_comment=? WHERE id=?")
        ->execute([$me['id'], $admin_comment, $appeal_id]);
  }
  
  header("Location: admin.php"); exit;
}

// ユーザー検索
$search_query = $_GET['search'] ?? '';
$search_condition = '';
$search_params = [];
if ($search_query !== '') {
    $search_condition = "WHERE handle LIKE ? OR id = ?";
    $search_params = ['%' . $search_query . '%', (int)$search_query];
}

$posts = $pdo->query("SELECT p.id, u.handle, p.content_md, p.deleted_at FROM posts p JOIN users u ON u.id=p.user_id ORDER BY p.id DESC LIMIT 100")->fetchAll();
$words = $pdo->query("SELECT * FROM banned_words ORDER BY id DESC")->fetchAll();

if ($search_condition) {
    $stmt = $pdo->prepare("SELECT id, handle, muted_until, frozen, created_at FROM users $search_condition ORDER BY id DESC LIMIT 100");
    $stmt->execute($search_params);
    $users = $stmt->fetchAll();
} else {
    $users = $pdo->query("SELECT id, handle, muted_until, frozen, created_at FROM users ORDER BY id DESC LIMIT 100")->fetchAll();
}

// 通報一覧取得
$reports = $pdo->query("
    SELECT r.*, 
           reporter.handle as reporter_handle,
           p.content_md as post_content,
           post_author.handle as post_author_handle
    FROM reports r
    JOIN users reporter ON reporter.id = r.reporter_id
    JOIN posts p ON p.id = r.post_id
    JOIN users post_author ON post_author.id = p.user_id
    WHERE r.status = 'pending'
    ORDER BY r.created_at DESC
    LIMIT 50
")->fetchAll();

// 異議申し立て一覧取得
$appeals = $pdo->query("
    SELECT a.*,
           u.handle as user_handle
    FROM appeals a
    JOIN users u ON u.id = a.user_id
    WHERE a.status = 'pending'
    ORDER BY a.created_at DESC
    LIMIT 50
")->fetchAll();
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
  <!-- ユーザー検索セクション -->
  <div class="admin-section" style="margin-bottom: 20px;">
    <h3>🔍 ユーザー検索</h3>
    <form method="get" class="admin-form">
      <input name="search" placeholder="ユーザーIDまたはハンドルを入力..." value="<?= htmlspecialchars($search_query) ?>">
      <button type="submit">検索</button>
      <?php if ($search_query): ?>
        <a href="admin.php" style="padding: 8px 16px; background: var(--muted); color: white; text-decoration: none; border-radius: 8px;">クリア</a>
      <?php endif; ?>
    </form>
  </div>
  
  <!-- 通報管理セクション -->
  <?php if (!empty($reports)): ?>
  <div class="admin-section" style="margin-bottom: 20px;">
    <h3>🚨 未処理の通報 (<?= count($reports) ?>件)</h3>
    <div style="max-height: 500px; overflow-y: auto;">
      <?php foreach($reports as $report): ?>
      <div style="background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 15px; margin-bottom: 15px;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
          <div>
            <strong style="color: var(--blue);">通報ID: #<?= $report['id'] ?></strong>
            <div style="color: var(--muted); font-size: 13px; margin-top: 5px;">
              通報者: @<?= htmlspecialchars($report['reporter_handle']) ?> | 
              投稿者: @<?= htmlspecialchars($report['post_author_handle']) ?> | 
              <?= htmlspecialchars($report['created_at']) ?>
            </div>
          </div>
          <span style="background: #f56565; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;"><?= htmlspecialchars($report['reason']) ?></span>
        </div>
        <div style="background: white; padding: 10px; border-radius: 6px; margin: 10px 0; border-left: 3px solid var(--blue);">
          <strong>投稿内容:</strong>
          <div style="margin-top: 5px; color: #2d3748;"><?= nl2br(htmlspecialchars(mb_substr($report['post_content'], 0, 200))) ?><?= mb_strlen($report['post_content']) > 200 ? '...' : '' ?></div>
        </div>
        <?php if ($report['details']): ?>
        <div style="background: #fff4e5; padding: 10px; border-radius: 6px; margin: 10px 0;">
          <strong>詳細:</strong> <?= nl2br(htmlspecialchars($report['details'])) ?>
        </div>
        <?php endif; ?>
        <div style="display: flex; gap: 10px; margin-top: 10px;">
          <form method="post" style="flex: 1;">
            <input type="hidden" name="action" value="resolve_report">
            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
            <input type="hidden" name="report_action" value="delete_post">
            <input name="admin_comment" placeholder="コメント（任意）" style="width: 100%; padding: 6px; margin-bottom: 5px; border: 1px solid var(--border); border-radius: 4px;">
            <button type="submit" style="width: 100%; background: #f56565; color: white; padding: 8px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">投稿を削除 & 解決</button>
          </form>
          <form method="post" style="flex: 1;">
            <input type="hidden" name="action" value="dismiss_report">
            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
            <input name="admin_comment" placeholder="コメント（任意）" style="width: 100%; padding: 6px; margin-bottom: 5px; border: 1px solid var(--border); border-radius: 4px;">
            <button type="submit" style="width: 100%; background: var(--muted); color: white; padding: 8px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">却下</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
  
  <!-- 異議申し立て管理セクション -->
  <?php if (!empty($appeals)): ?>
  <div class="admin-section" style="margin-bottom: 20px;">
    <h3>📝 未処理の異議申し立て (<?= count($appeals) ?>件)</h3>
    <div style="max-height: 500px; overflow-y: auto;">
      <?php foreach($appeals as $appeal): ?>
      <div style="background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 15px; margin-bottom: 15px;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
          <div>
            <strong style="color: var(--blue);">申し立てID: #<?= $appeal['id'] ?></strong>
            <div style="color: var(--muted); font-size: 13px; margin-top: 5px;">
              ユーザー: @<?= htmlspecialchars($appeal['user_handle']) ?> (ID: <?= $appeal['user_id'] ?>) | 
              <?= htmlspecialchars($appeal['created_at']) ?>
            </div>
          </div>
        </div>
        <div style="background: #e6f7ff; padding: 12px; border-radius: 6px; margin: 10px 0; border-left: 3px solid #4299e1;">
          <strong>申し立て理由:</strong>
          <div style="margin-top: 5px; white-space: pre-wrap;"><?= htmlspecialchars($appeal['reason']) ?></div>
        </div>
        <div style="display: flex; gap: 10px; margin-top: 10px;">
          <form method="post" style="flex: 1;">
            <input type="hidden" name="action" value="approve_appeal">
            <input type="hidden" name="appeal_id" value="<?= $appeal['id'] ?>">
            <input name="admin_comment" placeholder="コメント（任意）" style="width: 100%; padding: 6px; margin-bottom: 5px; border: 1px solid var(--border); border-radius: 4px;">
            <button type="submit" style="width: 100%; background: #48bb78; color: white; padding: 8px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">承認（ミュート解除）</button>
          </form>
          <form method="post" style="flex: 1;">
            <input type="hidden" name="action" value="reject_appeal">
            <input type="hidden" name="appeal_id" value="<?= $appeal['id'] ?>">
            <input name="admin_comment" placeholder="コメント（任意）" style="width: 100%; padding: 6px; margin-bottom: 5px; border: 1px solid var(--border); border-radius: 4px;">
            <button type="submit" style="width: 100%; background: #f56565; color: white; padding: 8px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">却下</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
  
  <div class="grid-2col">
    <div class="admin-section">
      <h3>🚫 禁止語句管理</h3>
      <form method="post" class="admin-form">
        <input type="hidden" name="action" value="add_banword">
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
        <input type="hidden" name="action" value="mute_user">
        <input name="mute_uid" type="number" placeholder="ユーザーID" required min="1">
        <input name="minutes" type="number" value="30" placeholder="分" required min="1">
        <button type="submit">ミュート</button>
      </form>
      <form method="post" class="admin-form">
        <input type="hidden" name="action" value="freeze_user">
        <input name="freeze_uid" type="number" placeholder="ユーザーID" required min="1">
        <button type="submit" style="background: var(--red);">凍結</button>
      </form>
      <p style="margin: 16px 0 8px; font-weight: 600; color: var(--blue);">ユーザー一覧：</p>
      <ul class="admin-list">
        <?php 
        foreach($users as $u){ 
            $statusClass = $u['frozen'] ? 'user-status-muted' : ($u['muted_until'] ? 'user-status-muted' : 'user-status-active');
            $statusIcon = $u['frozen'] ? '❄️' : ($u['muted_until'] ? '🔇' : '✅');
            echo "<li>";
            echo "<div style='display: flex; justify-content: space-between; align-items: center;'>";
            echo "<div><strong class='post-id'>#{$u['id']}</strong> ";
            echo "<span class='user-handle'>@".htmlspecialchars($u['handle'])."</span></div>";
            echo "<div style='display: flex; gap: 5px;'>";
            if ($u['frozen']) {
                echo "<form method='post' style='display:inline;'><input type='hidden' name='action' value='unfreeze_user'><input type='hidden' name='user_id' value='{$u['id']}'><button type='submit' style='padding: 4px 8px; font-size: 12px; background: #48bb78; color: white; border: none; border-radius: 4px; cursor: pointer;'>凍結解除</button></form>";
            }
            if ($u['muted_until']) {
                echo "<form method='post' style='display:inline;'><input type='hidden' name='action' value='unmute_user'><input type='hidden' name='user_id' value='{$u['id']}'><button type='submit' style='padding: 4px 8px; font-size: 12px; background: #4299e1; color: white; border: none; border-radius: 4px; cursor: pointer;'>ミュート解除</button></form>";
            }
            echo "</div></div>";
            echo "<div class='user-status $statusClass' style='margin-top: 5px;'>$statusIcon ";
            if ($u['frozen']) echo "凍結中";
            elseif ($u['muted_until']) echo "ミュート期限: {$u['muted_until']}";
            else echo "通常";
            echo "</div></li>";
        } 
        ?>      </ul>
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
