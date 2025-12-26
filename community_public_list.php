<?php
// ===============================================
// community_public_list.php
// 公開コミュニティ一覧ページ
// ===============================================

require_once __DIR__ . '/config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$me = user();
if (!$me) {
    header('Location: ./');
    exit;
}

$pdo = db();

// ソート順の取得（デフォルト: 作成順）
$sort = $_GET['sort'] ?? 'created';

// ソート順に応じたORDER BY句を設定（ホワイトリスト方式で安全に設定）
$order_by_map = [
    'latest' => "(SELECT MAX(created_at) FROM community_posts WHERE community_id = c.id) DESC",
    'active' => "(SELECT COUNT(*) FROM community_posts WHERE community_id = c.id AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) DESC",
    'created' => "c.created_at DESC"
];

// ホワイトリストに存在しない場合はデフォルトを使用
if (!isset($order_by_map[$sort])) {
    $sort = 'created';
}
$order_by = $order_by_map[$sort];

// 公開コミュニティ一覧を取得
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        u.handle as owner_handle,
        u.display_name as owner_display_name,
        (SELECT COUNT(*) FROM community_members WHERE community_id = c.id) as member_count,
        (SELECT COUNT(*) FROM community_posts WHERE community_id = c.id) as post_count,
        (SELECT COUNT(*) FROM community_posts WHERE community_id = c.id AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as posts_24h,
        (SELECT MAX(created_at) FROM community_posts WHERE community_id = c.id) as latest_post_at,
        EXISTS(SELECT 1 FROM community_members WHERE community_id = c.id AND user_id = ?) as is_member
    FROM communities c
    JOIN users u ON u.id = c.owner_id
    WHERE c.is_public = 1
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
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>公開コミュニティ一覧 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
.community-list {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}
.community-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.community-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: box-shadow 0.2s;
}
.community-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.community-card h3 {
    margin: 0 0 10px 0;
    color: #2d3748;
}
.community-card p {
    color: #718096;
    margin: 5px 0;
}
.community-meta {
    display: flex;
    gap: 15px;
    margin: 10px 0;
    font-size: 14px;
    color: #718096;
}
.community-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
}
.btn-primary {
    background: #667eea;
    color: white;
}
.btn-secondary {
    background: #cbd5e0;
    color: #2d3748;
}
.btn-success {
    background: #48bb78;
    color: white;
}
.badge {
    background: #edf2f7;
    color: #2d3748;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
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
.sort-selector {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
}
.sort-btn {
    padding: 8px 16px;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    background: white;
    color: #2d3748;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s;
}
.sort-btn:hover {
    background: #edf2f7;
}
.sort-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}
</style>
</head>
<body>
<div class="community-list">
    <div class="community-header">
        <h1>公開コミュニティ一覧</h1>
        <p>誰でも参加できる公開コミュニティです</p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <a href="communities.php" class="btn btn-secondary">← マイコミュニティに戻る</a>
        <a href="index.php" class="btn btn-secondary">フィードに戻る</a>
    </div>
    
    <div class="sort-selector">
        <span style="color: #718096;">並び替え:</span>
        <a href="?sort=created" class="sort-btn <?= $sort === 'created' ? 'active' : '' ?>">作成順</a>
        <a href="?sort=latest" class="sort-btn <?= $sort === 'latest' ? 'active' : '' ?>">新規投稿順</a>
        <a href="?sort=active" class="sort-btn <?= $sort === 'active' ? 'active' : '' ?>">アクティブ順</a>
    </div>
    
    <?php if (empty($communities)): ?>
        <p style="text-align: center; color: #718096; padding: 40px 0;">
            公開コミュニティがまだありません
        </p>
    <?php else: ?>
        <?php foreach ($communities as $community): ?>
            <div class="community-card">
                <h3><?= htmlspecialchars($community['name']) ?></h3>
                <p><?= htmlspecialchars($community['description']) ?></p>
                
                <div class="community-meta">
                    <span>👤 オーナー: @<?= htmlspecialchars($community['owner_handle']) ?></span>
                    <span>👥 メンバー: <?= $community['member_count'] ?>人</span>
                    <span>📝 総投稿: <?= $community['post_count'] ?>件</span>
                    <span>⏰ 24時間: <?= $community['posts_24h'] ?>件</span>
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
                
                <div class="community-actions">
                    <?php if ($community['is_member']): ?>
                        <span class="badge">✓ 参加中</span>
                        <a href="community_feed.php?id=<?= $community['id'] ?>" class="btn btn-primary">フィードを見る</a>
                    <?php else: ?>
                        <button class="btn btn-success" onclick="joinCommunity(<?= $community['id'] ?>)">参加する</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
async function joinCommunity(communityId) {
    if (!confirm('このコミュニティに参加しますか？')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'join_community');
        formData.append('community_id', communityId);
        
        const res = await fetch('community_manage.php', {
            method: 'POST',
            body: formData
        });
        
        if (res.ok) {
            alert('コミュニティに参加しました');
            location.reload();
        } else {
            alert('参加に失敗しました');
        }
    } catch (err) {
        alert('ネットワークエラー');
    }
}
</script>
</body>
</html>
