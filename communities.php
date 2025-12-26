<?php
// ===============================================
// communities.php
// コミュニティ一覧・作成ページ
// ===============================================

require_once __DIR__ . '/config.php';
$me = user();
if (!$me) {
    header('Location: ./');
    exit;
}

$pdo = db();

// ソート順を取得
$sort = $_GET['sort'] ?? 'created';
$valid_sorts = ['created', 'latest_post', 'active'];
if (!in_array($sort, $valid_sorts)) {
    $sort = 'created';
}

// ソート順に応じたORDER BY句を設定
switch ($sort) {
    case 'latest_post':
        // 新規投稿順（最新の投稿がある順）
        $order_by = "(SELECT MAX(created_at) FROM community_posts WHERE community_id = c.id) DESC";
        break;
    case 'active':
        // アクティブ順（直近24時間の投稿数が多い順）
        $order_by = "(SELECT COUNT(*) FROM community_posts WHERE community_id = c.id AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) DESC";
        break;
    case 'created':
    default:
        // 作成順
        $order_by = "c.created_at DESC";
        break;
}

// 自分が参加しているコミュニティ一覧
$stmt = $pdo->prepare("
    SELECT c.*, cm.role, u.handle as owner_handle,
           (SELECT COUNT(*) FROM community_members WHERE community_id = c.id) as member_count,
           (SELECT COUNT(*) FROM community_posts WHERE community_id = c.id) as post_count,
           (SELECT COUNT(*) FROM community_posts WHERE community_id = c.id AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as posts_24h,
           (SELECT MAX(created_at) FROM community_posts WHERE community_id = c.id) as latest_post_at
    FROM communities c
    JOIN community_members cm ON cm.community_id = c.id
    JOIN users u ON u.id = c.owner_id
    WHERE cm.user_id = ?
    ORDER BY {$order_by}
");
$stmt->execute([$me['id']]);
$communities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// アクティブメーターの段階を計算
foreach ($communities as &$community) {
    $posts_24h = (int)$community['posts_24h'];
    if ($posts_24h >= 20) {
        $community['active_level'] = 5;
        $community['active_color'] = 'green';
    } elseif ($posts_24h >= 15) {
        $community['active_level'] = 4;
        $community['active_color'] = 'green';
    } elseif ($posts_24h >= 10) {
        $community['active_level'] = 3;
        $community['active_color'] = 'orange';
    } elseif ($posts_24h >= 5) {
        $community['active_level'] = 2;
        $community['active_color'] = 'orange';
    } else {
        $community['active_level'] = 1;
        $community['active_color'] = 'red';
    }
}
unset($community);

$msg = $_GET['msg'] ?? '';
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>コミュニティ - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
.communities-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}
.communities-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    text-align: center;
}
.communities-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
}
.create-community-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.create-community-section h2 {
    margin: 0 0 20px 0;
    color: #2d3748;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
    color: #4a5568;
}
.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: border-color 0.3s;
}
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
}
.form-group textarea {
    min-height: 100px;
    resize: vertical;
}
.btn-create {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: opacity 0.3s;
}
.btn-create:hover {
    opacity: 0.9;
}
.community-list {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.community-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}
.community-list-header h2 {
    margin: 0;
    color: #2d3748;
}
.sort-selector {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #4a5568;
    font-size: 14px;
}
.sort-selector select {
    padding: 8px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    background: white;
    color: #2d3748;
    font-size: 14px;
    cursor: pointer;
    transition: border-color 0.3s;
}
.sort-selector select:focus {
    outline: none;
    border-color: #667eea;
}
.sort-selector select:hover {
    border-color: #667eea;
}
.community-list h2 {
    margin: 0 0 20px 0;
    color: #2d3748;
}
.community-card {
    background: #f7fafc;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
    cursor: pointer;
}
.community-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
    transform: translateY(-2px);
}
.community-card-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 10px;
}
.community-name {
    font-size: 20px;
    font-weight: bold;
    color: #2d3748;
    margin-bottom: 5px;
}
.community-role {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}
.role-owner {
    background: #f59e0b;
    color: white;
}
.role-admin {
    background: #8b5cf6;
    color: white;
}
.role-member {
    background: #3b82f6;
    color: white;
}
.community-description {
    color: #718096;
    margin-bottom: 15px;
    line-height: 1.5;
}
.community-stats {
    display: flex;
    gap: 20px;
    font-size: 14px;
    color: #a0aec0;
}
.community-stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
}
.active-meter {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
}
.active-meter-bars {
    display: inline-flex;
    gap: 2px;
    align-items: flex-end;
}
.active-meter-bar {
    width: 8px;
    height: 15px;
    background: #e2e8f0;
    border-radius: 2px;
}
.active-meter-bar.active.green {
    background: #48bb78;
}
.active-meter-bar.active.orange {
    background: #ed8936;
}
.active-meter-bar.active.red {
    background: #f56565;
}
.message {
    background: #48bb78;
    color: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
}
.message.error {
    background: #f56565;
}
.empty-state {
    text-align: center;
    padding: 40px;
    color: #a0aec0;
}
.empty-state-icon {
    font-size: 64px;
    margin-bottom: 15px;
}
</style>
</head>
<body>
<header class="topbar">
    <div class="logo"><a href="index.php">← フィードに戻る</a></div>
</header>

<div class="communities-container">
    <?php if ($msg): ?>
    <div class="message <?= strpos($msg, 'エラー') !== false ? 'error' : '' ?>">
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <div class="communities-header">
        <h1>🏘️ コミュニティ</h1>
        <p>プライベートなグループでコミュニケーションを楽しもう</p>
        <div style="margin-top: 15px;">
            <a href="community_public_list.php" style="background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; display: inline-block;">
                🌐 公開コミュニティ一覧を見る
            </a>
        </div>
    </div>

    <!-- コミュニティ作成 -->
    <div class="create-community-section">
        <h2>新しいコミュニティを作成</h2>
        <form method="POST" action="community_manage.php">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label for="name">コミュニティ名 *</label>
                <input type="text" id="name" name="name" required maxlength="100" placeholder="例: 趣味の集まり">
            </div>
            <div class="form-group">
                <label for="description">説明</label>
                <textarea id="description" name="description" placeholder="コミュニティの説明を入力してください"></textarea>
            </div>
            <button type="submit" class="btn-create">コミュニティを作成</button>
        </form>
    </div>

    <!-- コミュニティ一覧 -->
    <div class="community-list">
        <div class="community-list-header">
            <h2>参加中のコミュニティ（<?= count($communities) ?>）</h2>
            <div class="sort-selector">
                <span>並び替え:</span>
                <select id="sortSelect" onchange="location.href='?sort=' + this.value">
                    <option value="created" <?= $sort === 'created' ? 'selected' : '' ?>>作成順</option>
                    <option value="latest_post" <?= $sort === 'latest_post' ? 'selected' : '' ?>>新規投稿順</option>
                    <option value="active" <?= $sort === 'active' ? 'selected' : '' ?>>アクティブ順</option>
                </select>
            </div>
        </div>
        
        <?php if (empty($communities)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <p>まだコミュニティに参加していません</p>
            <p style="font-size: 14px; margin-top: 10px;">上のフォームから新しいコミュニティを作成してみましょう！</p>
        </div>
        <?php else: ?>
        <?php foreach ($communities as $community): ?>
        <div class="community-card" onclick="location.href='community_feed.php?id=<?= $community['id'] ?>'">
            <div class="community-card-header">
                <div>
                    <div class="community-name"><?= htmlspecialchars($community['name']) ?></div>
                    <small style="color: #a0aec0;">オーナー: @<?= htmlspecialchars($community['owner_handle']) ?></small>
                </div>
                <span class="community-role role-<?= $community['role'] ?>">
                    <?= $community['role'] === 'owner' ? '👑 オーナー' : ($community['role'] === 'admin' ? '⚙️ 管理者' : '👤 メンバー') ?>
                </span>
            </div>
            
            <?php if ($community['description']): ?>
            <div class="community-description">
                <?= nl2br(htmlspecialchars($community['description'])) ?>
            </div>
            <?php endif; ?>
            
            <div class="community-stats">
                <div class="community-stat-item">
                    👥 <?= $community['member_count'] ?> メンバー
                </div>
                <div class="community-stat-item">
                    📝 総投稿: <?= $community['post_count'] ?>件
                </div>
                <div class="community-stat-item">
                    ⏰ 24時間: <?= $community['posts_24h'] ?>件
                </div>
                <div class="active-meter">
                    <span>アクティブ:</span>
                    <div class="active-meter-bars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="active-meter-bar <?= $i <= $community['active_level'] ? 'active ' . $community['active_color'] : '' ?>"></div>
                        <?php endfor; ?>
                    </div>
                    <span>(<?= $community['active_level'] ?>/5)</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// フォーム送信後のリダイレクト
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('msg')) {
    // メッセージ表示後、3秒後にURLからmsgパラメータを削除
    setTimeout(() => {
        const url = new URL(window.location);
        url.searchParams.delete('msg');
        window.history.replaceState({}, '', url);
    }, 3000);
}
</script>
</body>
</html>
