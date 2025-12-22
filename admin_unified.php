<?php
require_once __DIR__ . '/config.php';

$me = user();
if (!$me || !in_array($me['role'], ['mod','admin'])) { 
    http_response_code(403); 
    echo "forbidden"; 
    exit; 
}

// 管理者かどうかをチェック（frame_admin と admin_password_reset 用）
$isAdmin = ($me['role'] === 'admin' || (int)$me['id'] === 1);

$pdo = db();

// 最大ミュート期間（分）
define('MAX_MUTE_MINUTES', 10080); // 7日間

// POST処理
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $action = $_POST['action'] ?? '';
  
  // モデレータ権限の処理
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
      $stmt = $pdo->prepare("SELECT post_id FROM reports WHERE id=?");
      $stmt->execute([$report_id]);
      $report = $stmt->fetch();
      if ($report) {
        $pdo->prepare("UPDATE posts SET deleted_at=NOW(), deleted_by_mod=1 WHERE id=?")->execute([$report['post_id']]);
      }
    }
    
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
    
    $stmt = $pdo->prepare("SELECT user_id FROM appeals WHERE id=?");
    $stmt->execute([$appeal_id]);
    $appeal = $stmt->fetch();
    if ($appeal) {
      $pdo->prepare("UPDATE users SET muted_until=NULL WHERE id=?")->execute([$appeal['user_id']]);
    }
    
    $pdo->prepare("UPDATE appeals SET status='approved', reviewed_by=?, reviewed_at=NOW(), admin_comment=? WHERE id=?")
        ->execute([$me['id'], $admin_comment, $appeal_id]);
  }
  
  if ($action === 'reject_appeal' && isset($_POST['appeal_id'])){
    $appeal_id = (int)$_POST['appeal_id'];
    $admin_comment = $_POST['admin_comment'] ?? '';
    $pdo->prepare("UPDATE appeals SET status='rejected', reviewed_by=?, reviewed_at=NOW(), admin_comment=? WHERE id=?")
        ->execute([$me['id'], $admin_comment, $appeal_id]);
  }
  
  // 管理者専用：フレーム審査処理
  if ($isAdmin && $action === 'approve_frame' && isset($_POST['submission_id'])) {
    $submission_id = (int)$_POST['submission_id'];
    
    $sub = $pdo->prepare("SELECT * FROM user_designed_frames WHERE id = ?");
    $sub->execute([$submission_id]);
    $submission = $sub->fetch();
    
    if ($submission) {
      $price_coins = (int)($_POST['final_price_coins'] ?? $submission['proposed_price_coins']);
      $price_crystals = (int)($_POST['final_price_crystals'] ?? $submission['proposed_price_crystals']);
      $price_diamonds = (int)($_POST['final_price_diamonds'] ?? $submission['proposed_price_diamonds']);
      $is_limited = isset($_POST['is_limited']) ? 1 : 0;
      $sale_start = !empty($_POST['sale_start_date']) ? $_POST['sale_start_date'] : null;
      $sale_end = !empty($_POST['sale_end_date']) ? $_POST['sale_end_date'] : null;
      
      $insert = $pdo->prepare("
        INSERT INTO frames 
        (name, css_token, price_coins, price_crystals, price_diamonds, 
         preview_css, is_user_designed, designed_by_user_id, 
         is_limited, sale_start_date, sale_end_date)
        VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)
      ");
      $insert->execute([
        $submission['name'],
        $submission['css_token'],
        $price_coins,
        $price_crystals,
        $price_diamonds,
        $submission['preview_css'],
        $submission['user_id'],
        $is_limited,
        $sale_start,
        $sale_end
      ]);
      
      $frame_id = $pdo->lastInsertId();
      
      $update = $pdo->prepare("
        UPDATE user_designed_frames 
        SET status = 'approved', 
            approved_frame_id = ?,
            reviewed_at = NOW(),
            reviewed_by = ?,
            admin_comment = ?
        WHERE id = ?
      ");
      $update->execute([
        $frame_id,
        $me['id'],
        $_POST['admin_comment'] ?? null,
        $submission_id
      ]);
    }
  }
  
  if ($isAdmin && $action === 'reject_frame' && isset($_POST['submission_id'])) {
    $submission_id = (int)$_POST['submission_id'];
    $update = $pdo->prepare("
      UPDATE user_designed_frames 
      SET status = 'rejected',
          reviewed_at = NOW(),
          reviewed_by = ?,
          admin_comment = ?
      WHERE id = ?
    ");
    $update->execute([
      $me['id'],
      $_POST['admin_comment'] ?? '',
      $submission_id
    ]);
  }
  
  header("Location: admin_unified.php"); 
  exit;
}

// データ取得
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

// 通報一覧
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

// 異議申し立て一覧
$appeals = $pdo->query("
    SELECT a.*,
           u.handle as user_handle
    FROM appeals a
    JOIN users u ON u.id = a.user_id
    WHERE a.status = 'pending'
    ORDER BY a.created_at DESC
    LIMIT 50
")->fetchAll();

// 管理者専用：フレーム審査データ
$pending_frames = [];
$reviewed_frames = [];
if ($isAdmin) {
    $pending_frames = $pdo->query("
        SELECT udf.*, u.handle as submitter_handle, u.display_name as submitter_name
        FROM user_designed_frames udf
        JOIN users u ON udf.user_id = u.id
        WHERE udf.status = 'pending'
        ORDER BY udf.created_at ASC
    ")->fetchAll();
    
    $reviewed_frames = $pdo->query("
        SELECT udf.*, u.handle as submitter_handle, u.display_name as submitter_name,
               r.handle as reviewer_handle
        FROM user_designed_frames udf
        JOIN users u ON udf.user_id = u.id
        LEFT JOIN users r ON udf.reviewed_by = r.id
        WHERE udf.status IN ('approved', 'rejected')
        ORDER BY udf.reviewed_at DESC
        LIMIT 30
    ")->fetchAll();
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>管理ダッシュボード - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
/* 統合管理ページ用スタイル */
body {
    background: var(--bg);
    color: var(--text);
}

.admin-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.admin-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.admin-header h1 {
    margin: 0 0 10px 0;
    font-size: 2em;
}

.admin-header .subtitle {
    opacity: 0.9;
    font-size: 1.1em;
}

.admin-header .back-link {
    display: inline-block;
    margin-top: 15px;
    color: white;
    text-decoration: none;
    padding: 8px 16px;
    background: rgba(255,255,255,0.2);
    border-radius: 8px;
    transition: all 0.3s;
}

.admin-header .back-link:hover {
    background: rgba(255,255,255,0.3);
}

/* タブナビゲーション */
.tab-nav {
    display: flex;
    gap: 10px;
    background: var(--card);
    padding: 10px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid var(--border);
    flex-wrap: wrap;
}

.tab-button {
    padding: 12px 20px;
    border: none;
    background: transparent;
    color: var(--muted);
    cursor: pointer;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    transition: all 0.3s;
    flex: 1;
    min-width: 120px;
}

.tab-button:hover {
    background: rgba(29, 155, 240, 0.1);
    color: var(--blue);
}

.tab-button.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.tab-button.admin-only {
    border: 2px solid #ffd700;
}

/* タブコンテンツ */
.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* セクションスタイル */
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
    text-align: center;
    padding: 40px;
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

.grid-2col {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 24px;
}

/* フレーム審査用スタイル */
.frame-card {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.frame-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}

.toggle-form-btn {
    background: var(--blue);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    margin-top: 10px;
}

.review-form {
    display: none;
    margin-top: 20px;
    padding: 20px;
    background: rgba(102, 126, 234, 0.05);
    border-radius: 8px;
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.review-form.show {
    display: block;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: var(--text);
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="datetime-local"],
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: var(--bg);
    color: var(--text);
    font-size: 14px;
}

.price-inputs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-approve {
    background: var(--green);
    color: white;
}

.btn-reject {
    background: var(--red);
    color: white;
    margin-left: 10px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.status-approved {
    background: rgba(0, 186, 124, 0.2);
    color: var(--green);
}

.status-rejected {
    background: rgba(249, 24, 128, 0.2);
    color: var(--red);
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
    
    .tab-nav {
        overflow-x: auto;
    }
    
    .tab-button {
        min-width: 100px;
        flex: none;
    }
}
</style>
</head>
<body>
<div class="admin-container">
    <div class="admin-header">
        <h1>🛡️ 管理ダッシュボード</h1>
        <div class="subtitle">
            役割: <?= $isAdmin ? '👑 管理者' : '⚙️ モデレータ' ?> | 
            ユーザー: @<?= htmlspecialchars($me['handle']) ?>
        </div>
        <a href="index.php" class="back-link">← MiniBird に戻る</a>
    </div>
    
    <!-- タブナビゲーション -->
    <div class="tab-nav">
        <button class="tab-button active" onclick="switchTab('moderation')">
            🔨 モデレーション
        </button>
        <button class="tab-button" onclick="switchTab('users')">
            👥 ユーザー管理
        </button>
        <button class="tab-button" onclick="switchTab('posts')">
            📝 投稿管理
        </button>
        <?php if ($isAdmin): ?>
        <button class="tab-button admin-only" onclick="switchTab('frames')">
            🎨 フレーム審査 <span style="font-size: 10px;">👑</span>
        </button>
        <button class="tab-button admin-only" onclick="switchTab('password')">
            🔐 パスワード管理 <span style="font-size: 10px;">👑</span>
        </button>
        <?php endif; ?>
    </div>
    
    <!-- モデレーションタブ -->
    <div id="tab-moderation" class="tab-content active">
        <?php if (!empty($reports)): ?>
        <div class="admin-section">
            <h3>🚨 未処理の通報 (<?= count($reports) ?>件)</h3>
            <div style="max-height: 600px; overflow-y: auto;">
                <?php foreach($reports as $report): ?>
                <div style="background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
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
                    <div style="background: rgba(102, 126, 234, 0.1); padding: 10px; border-radius: 6px; margin: 10px 0; border-left: 3px solid var(--blue);">
                        <strong>投稿内容:</strong>
                        <div style="margin-top: 5px;"><?= nl2br(htmlspecialchars(mb_substr($report['post_content'], 0, 200))) ?><?= mb_strlen($report['post_content']) > 200 ? '...' : '' ?></div>
                    </div>
                    <?php if ($report['details']): ?>
                    <div style="background: rgba(255, 165, 0, 0.1); padding: 10px; border-radius: 6px; margin: 10px 0;">
                        <strong>詳細:</strong> <?= nl2br(htmlspecialchars($report['details'])) ?>
                    </div>
                    <?php endif; ?>
                    <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                        <form method="post" style="flex: 1; min-width: 200px;">
                            <input type="hidden" name="action" value="resolve_report">
                            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                            <input type="hidden" name="report_action" value="delete_post">
                            <input name="admin_comment" placeholder="コメント（任意）" style="width: 100%; padding: 6px; margin-bottom: 5px; border: 1px solid var(--border); border-radius: 4px; background: var(--bg); color: var(--text);">
                            <button type="submit" style="width: 100%; background: #f56565; color: white; padding: 8px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">投稿を削除 & 解決</button>
                        </form>
                        <form method="post" style="flex: 1; min-width: 200px;">
                            <input type="hidden" name="action" value="dismiss_report">
                            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                            <input name="admin_comment" placeholder="コメント（任意）" style="width: 100%; padding: 6px; margin-bottom: 5px; border: 1px solid var(--border); border-radius: 4px; background: var(--bg); color: var(--text);">
                            <button type="submit" style="width: 100%; background: var(--muted); color: white; padding: 8px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">却下</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($appeals)): ?>
        <div class="admin-section">
            <h3>📝 未処理の異議申し立て (<?= count($appeals) ?>件)</h3>
            <div style="max-height: 600px; overflow-y: auto;">
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
                    <div style="background: rgba(66, 153, 225, 0.1); padding: 12px; border-radius: 6px; margin: 10px 0; border-left: 3px solid #4299e1;">
                        <strong>申し立て理由:</strong>
                        <div style="margin-top: 5px; white-space: pre-wrap;"><?= htmlspecialchars($appeal['reason']) ?></div>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                        <form method="post" style="flex: 1; min-width: 200px;">
                            <input type="hidden" name="action" value="approve_appeal">
                            <input type="hidden" name="appeal_id" value="<?= $appeal['id'] ?>">
                            <input name="admin_comment" placeholder="コメント（任意）" style="width: 100%; padding: 6px; margin-bottom: 5px; border: 1px solid var(--border); border-radius: 4px; background: var(--bg); color: var(--text);">
                            <button type="submit" style="width: 100%; background: #48bb78; color: white; padding: 8px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">承認（ミュート解除）</button>
                        </form>
                        <form method="post" style="flex: 1; min-width: 200px;">
                            <input type="hidden" name="action" value="reject_appeal">
                            <input type="hidden" name="appeal_id" value="<?= $appeal['id'] ?>">
                            <input name="admin_comment" placeholder="コメント（任意）" style="width: 100%; padding: 6px; margin-bottom: 5px; border: 1px solid var(--border); border-radius: 4px; background: var(--bg); color: var(--text);">
                            <button type="submit" style="width: 100%; background: #f56565; color: white; padding: 8px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">却下</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
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
    </div>
    
    <!-- ユーザー管理タブ -->
    <div id="tab-users" class="tab-content">
        <div class="admin-section">
            <h3>🔍 ユーザー検索</h3>
            <form method="get" class="admin-form">
                <input name="search" placeholder="ユーザーIDまたはハンドルを入力..." value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit">検索</button>
                <?php if ($search_query): ?>
                <a href="admin_unified.php" style="padding: 8px 16px; background: var(--muted); color: white; text-decoration: none; border-radius: 8px;">クリア</a>
                <?php endif; ?>
            </form>
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
                    echo "<div style='display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;'>";
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
                    echo "<div style='margin-top: 5px; color: var(--muted); font-size: 12px;'>$statusIcon ";
                    if ($u['frozen']) echo "凍結中";
                    elseif ($u['muted_until']) echo "ミュート期限: {$u['muted_until']}";
                    else echo "通常";
                    echo "</div></li>";
                } 
                ?>
            </ul>
        </div>
    </div>
    
    <!-- 投稿管理タブ -->
    <div id="tab-posts" class="tab-content">
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
    </div>
    
    <?php if ($isAdmin): ?>
    <!-- フレーム審査タブ（管理者のみ） -->
    <div id="tab-frames" class="tab-content">
        <div class="admin-section">
            <h3>🎨 フレーム審査待ち (<?= count($pending_frames) ?>件)</h3>
            
            <?php if(empty($pending_frames)): ?>
                <div class="empty-state">現在、審査待ちのフレームはありません。</div>
            <?php else: ?>
                <?php foreach($pending_frames as $sub): ?>
                <div class="frame-card">
                    <div class="frame-header">
                        <div>
                            <h4 style="margin: 0 0 10px 0; color: var(--blue);"><?= htmlspecialchars($sub['name']) ?></h4>
                            <p style="margin: 5px 0; color: var(--muted); font-size: 14px;">
                                <strong>提出者:</strong> @<?= htmlspecialchars($sub['submitter_handle']) ?>
                                <?= $sub['submitter_name'] ? ' (' . htmlspecialchars($sub['submitter_name']) . ')' : '' ?>
                            </p>
                            <p style="margin: 5px 0; color: var(--muted); font-size: 14px;">
                                <strong>提出日:</strong> <?= htmlspecialchars($sub['created_at']) ?>
                            </p>
                        </div>
                    </div>
                    
                    <div style="background: rgba(102, 126, 234, 0.05); padding: 15px; border-radius: 8px; margin: 15px 0;">
                        <p><strong>CSSトークン:</strong> <code style="background: var(--bg); padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($sub['css_token']) ?></code></p>
                        <?php if($sub['description']): ?>
                        <p><strong>説明:</strong><br><?= nl2br(htmlspecialchars($sub['description'])) ?></p>
                        <?php endif; ?>
                        <?php if($sub['preview_css']): ?>
                        <p><strong>プレビューCSS:</strong></p>
                        <pre style="background: var(--bg); padding: 10px; border-radius: 6px; overflow-x: auto; font-size: 12px;"><?= htmlspecialchars($sub['preview_css']) ?></pre>
                        <?php endif; ?>
                        <p><strong>提案価格:</strong> 
                            🪙<?= number_format($sub['proposed_price_coins']) ?> 
                            💎<?= number_format($sub['proposed_price_crystals']) ?> 
                            💠<?= number_format($sub['proposed_price_diamonds']) ?>
                        </p>
                    </div>
                    
                    <button class="toggle-form-btn" onclick="toggleReviewForm(<?= $sub['id'] ?>)">
                        審査フォームを表示
                    </button>
                    
                    <div id="review-form-<?= $sub['id'] ?>" class="review-form">
                        <form method="post">
                            <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>">
                            
                            <div class="form-group">
                                <label>最終価格</label>
                                <div class="price-inputs">
                                    <div>
                                        <label style="font-weight: normal; font-size: 13px;">🪙 コイン</label>
                                        <input type="number" name="final_price_coins" value="<?= $sub['proposed_price_coins'] ?>" min="0">
                                    </div>
                                    <div>
                                        <label style="font-weight: normal; font-size: 13px;">💎 クリスタル</label>
                                        <input type="number" name="final_price_crystals" value="<?= $sub['proposed_price_crystals'] ?>" min="0">
                                    </div>
                                    <div>
                                        <label style="font-weight: normal; font-size: 13px;">💠 ダイヤ</label>
                                        <input type="number" name="final_price_diamonds" value="<?= $sub['proposed_price_diamonds'] ?>" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_limited" value="1">
                                    期間限定フレームにする
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label>販売開始日時</label>
                                <input type="datetime-local" name="sale_start_date">
                            </div>
                            
                            <div class="form-group">
                                <label>販売終了日時</label>
                                <input type="datetime-local" name="sale_end_date">
                            </div>
                            
                            <div class="form-group">
                                <label>管理者コメント（任意）</label>
                                <textarea name="admin_comment" rows="3" placeholder="ユーザーへのメッセージ"></textarea>
                            </div>
                            
                            <div>
                                <button type="submit" name="action" value="approve_frame" class="btn btn-approve">✅ 承認してショップに追加</button>
                                <button type="submit" name="action" value="reject_frame" class="btn btn-reject" 
                                        onclick="return confirm('このフレームを却下しますか？')">❌ 却下</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($reviewed_frames)): ?>
        <div class="admin-section">
            <h3>審査済みフレーム（最近30件）</h3>
            <?php foreach($reviewed_frames as $sub): ?>
            <div class="frame-card" style="opacity: 0.8;">
                <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="margin: 0;">
                            <?= htmlspecialchars($sub['name']) ?>
                            <span class="status-badge status-<?= $sub['status'] ?>">
                                <?= $sub['status'] === 'approved' ? '承認済み' : '却下' ?>
                            </span>
                        </h4>
                        <p style="margin: 5px 0; font-size: 14px; color: var(--muted);">
                            提出者: @<?= htmlspecialchars($sub['submitter_handle']) ?> | 
                            審査者: @<?= htmlspecialchars($sub['reviewer_handle'] ?? 'unknown') ?> | 
                            審査日: <?= htmlspecialchars($sub['reviewed_at']) ?>
                        </p>
                    </div>
                </div>
                <?php if($sub['admin_comment']): ?>
                <div style="margin-top: 10px; padding: 10px; background: rgba(102, 126, 234, 0.05); border-radius: 6px; font-size: 14px;">
                    <strong>コメント:</strong> <?= nl2br(htmlspecialchars($sub['admin_comment'])) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- パスワード管理タブ（管理者のみ） -->
    <div id="tab-password" class="tab-content">
        <div class="admin-section">
            <h3>🔐 パスワードリセット管理</h3>
            <p style="color: var(--muted); margin-bottom: 20px;">
                この機能は管理者専用です。パスワードリセット申請の管理はAPIを介して行われます。
            </p>
            <div style="background: rgba(102, 126, 234, 0.1); padding: 20px; border-radius: 12px; border: 1px solid rgba(102, 126, 234, 0.3);">
                <p style="margin: 0 0 15px 0;">パスワードリセット管理機能へのアクセス：</p>
                <a href="admin_password_reset.php" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    パスワードリセット管理画面を開く
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// タブ切り替え
function switchTab(tabName) {
    // 全てのタブボタンとコンテンツを非アクティブに
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    // 選択されたタブをアクティブに
    const button = event.target.closest('.tab-button');
    if (button) button.classList.add('active');
    
    const content = document.getElementById('tab-' + tabName);
    if (content) content.classList.add('active');
}

// フレーム審査フォームの表示切り替え
function toggleReviewForm(id) {
    const form = document.getElementById('review-form-' + id);
    if (form) {
        form.classList.toggle('show');
    }
}
</script>
</body>
</html>
