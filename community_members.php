<?php
// ===============================================
// community_members.php
// コミュニティメンバー管理ページ
// ===============================================

require_once __DIR__ . '/config.php';
$me = user();
if (!$me) {
    header('Location: ./');
    exit;
}

$pdo = db();
$community_id = intval($_GET['id'] ?? 0);

if (!$community_id) {
    header('Location: ./');
    exit;
}

// コミュニティ情報取得
$stmt = $pdo->prepare("
    SELECT c.*, u.handle as owner_handle
    FROM communities c
    JOIN users u ON u.id = c.owner_id
    WHERE c.id = ?
");
$stmt->execute([$community_id]);
$community = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$community) {
    echo "コミュニティが見つかりません";
    exit;
}

// 権限チェック
$is_owner = ($community['owner_id'] == $me['id']);
if (!$is_owner) {
    echo "このページにアクセスする権限がありません";
    exit;
}

// メンバー一覧取得
$stmt = $pdo->prepare("
    SELECT cm.*, u.handle, u.display_name, u.icon, u.created_at as joined_at
    FROM community_members cm
    JOIN users u ON u.id = cm.user_id
    WHERE cm.community_id = ?
    ORDER BY cm.joined_at DESC
");
$stmt->execute([$community_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// メンバー追加処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_member') {
        $handle = trim($_POST['handle'] ?? '');
        
        if (!empty($handle)) {
            // ユーザーIDを取得
            $stmt = $pdo->prepare("SELECT id FROM users WHERE handle = ?");
            $stmt->execute([$handle]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // 既にメンバーかチェック
                $stmt = $pdo->prepare("SELECT 1 FROM community_members WHERE community_id = ? AND user_id = ?");
                $stmt->execute([$community_id, $user['id']]);
                
                if (!$stmt->fetch()) {
                    // メンバー追加
                    $stmt = $pdo->prepare("
                        INSERT INTO community_members (community_id, user_id, role, joined_at)
                        VALUES (?, ?, 'member', NOW())
                    ");
                    $stmt->execute([$community_id, $user['id']]);
                    header("Location: community_members.php?id=$community_id&success=added");
                    exit;
                } else {
                    $error = "既にメンバーです";
                }
            } else {
                $error = "ユーザーが見つかりません";
            }
        } else {
            $error = "ハンドル名を入力してください";
        }
    }
    
    if ($_POST['action'] === 'remove_member') {
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if ($user_id > 0 && $user_id !== $me['id']) {
            $stmt = $pdo->prepare("DELETE FROM community_members WHERE community_id = ? AND user_id = ?");
            $stmt->execute([$community_id, $user_id]);
            header("Location: community_members.php?id=$community_id&success=removed");
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>メンバー管理 - <?= htmlspecialchars($community['name']) ?></title>
<link rel="stylesheet" href="assets/style.css">
<style>
.container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}
.header-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}
.header-section h1 {
    margin: 0 0 10px 0;
    font-size: 28px;
}
.header-section p {
    margin: 5px 0;
    opacity: 0.9;
}
.actions {
    margin: 20px 0;
    display: flex;
    gap: 10px;
}
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
}
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}
.btn-secondary {
    background: #cbd5e0;
    color: #2d3748;
}
.btn-secondary:hover {
    background: #a0aec0;
}
.btn-danger {
    background: #f56565;
    color: white;
}
.btn-danger:hover {
    background: #e53e3e;
}
.add-member-form {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}
.add-member-form h2 {
    margin: 0 0 15px 0;
    color: #2d3748;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #4a5568;
}
.form-group input {
    width: 100%;
    padding: 10px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
}
.form-group input:focus {
    outline: none;
    border-color: #667eea;
}
.members-list {
    background: white;
    border-radius: 12px;
    padding: 20px;
}
.members-list h2 {
    margin: 0 0 20px 0;
    color: #2d3748;
}
.member-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: 10px;
    transition: all 0.3s;
}
.member-item:hover {
    border-color: #667eea;
    background: #f7fafc;
}
.member-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}
.member-info {
    flex: 1;
}
.member-name {
    font-weight: bold;
    color: #2d3748;
    font-size: 16px;
}
.member-handle {
    color: #718096;
    font-size: 14px;
}
.member-role {
    display: inline-block;
    padding: 4px 12px;
    background: #667eea;
    color: white;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}
.alert {
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.alert-success {
    background: #c6f6d5;
    color: #2f855a;
    border: 1px solid #9ae6b4;
}
.alert-error {
    background: #fed7d7;
    color: #c53030;
    border: 1px solid #fc8181;
}
</style>
</head>
<body>
<div class="container">
    <div class="header-section">
        <h1><?= htmlspecialchars($community['name']) ?></h1>
        <p><?= htmlspecialchars($community['description']) ?></p>
        <p>オーナー: @<?= htmlspecialchars($community['owner_handle']) ?></p>
    </div>
    
    <div class="actions">
        <a href="community_feed.php?id=<?= $community_id ?>" class="btn btn-secondary">← フィードに戻る</a>
        <a href="communities.php" class="btn btn-secondary">コミュニティ一覧</a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php if ($_GET['success'] === 'added'): ?>
            メンバーを追加しました
        <?php elseif ($_GET['success'] === 'removed'): ?>
            メンバーを削除しました
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <div class="add-member-form">
        <h2>メンバーを追加</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add_member">
            <div class="form-group">
                <label>ユーザーのハンドル名 (@username)</label>
                <input type="text" name="handle" placeholder="例: username" required>
            </div>
            <button type="submit" class="btn btn-primary">追加する</button>
        </form>
    </div>
    
    <div class="members-list">
        <h2>メンバー一覧 (<?= count($members) ?>人)</h2>
        <?php if (empty($members)): ?>
        <p style="text-align: center; color: #a0aec0; padding: 40px;">まだメンバーがいません</p>
        <?php else: ?>
        <?php foreach ($members as $member): ?>
        <div class="member-item">
            <img src="<?= htmlspecialchars($member['icon'] ?? '/uploads/icons/default_icon.png') ?>" 
                 alt="<?= htmlspecialchars($member['handle']) ?>" 
                 class="member-avatar">
            <div class="member-info">
                <div class="member-name">
                    <?= htmlspecialchars($member['display_name'] ?? $member['handle']) ?>
                    <?php if ($member['user_id'] == $community['owner_id']): ?>
                    <span class="member-role">オーナー</span>
                    <?php endif; ?>
                </div>
                <div class="member-handle">@<?= htmlspecialchars($member['handle']) ?></div>
            </div>
            <?php if ($member['user_id'] !== $community['owner_id']): ?>
            <form method="POST" style="display: inline;" onsubmit="return confirm('このメンバーを削除しますか？')">
                <input type="hidden" name="action" value="remove_member">
                <input type="hidden" name="user_id" value="<?= $member['user_id'] ?>">
                <button type="submit" class="btn btn-danger">削除</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
