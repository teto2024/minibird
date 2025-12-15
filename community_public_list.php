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

// 公開コミュニティ一覧を取得
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        u.handle as owner_handle,
        u.display_name as owner_display_name,
        (SELECT COUNT(*) FROM community_members WHERE community_id = c.id) as member_count,
        (SELECT COUNT(*) FROM community_posts WHERE community_id = c.id) as post_count,
        EXISTS(SELECT 1 FROM community_members WHERE community_id = c.id AND user_id = ?) as is_member
    FROM communities c
    JOIN users u ON u.id = c.owner_id
    WHERE c.is_public = 1
    ORDER BY c.created_at DESC
");
$stmt->execute([$me['id']]);
$communities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>公開コミュニティ一覧 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
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
                    <span>📝 投稿: <?= $community['post_count'] ?>件</span>
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
