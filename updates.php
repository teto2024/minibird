<?php
require_once __DIR__ . '/config.php';

$me = user();
$pdo = db();

// 公開されているアップデート情報を取得
$stmt = $pdo->prepare("
    SELECT u.*, us.handle as creator_handle, us.display_name as creator_name
    FROM updates u
    LEFT JOIN users us ON us.id = u.created_by
    WHERE u.is_published = TRUE
    ORDER BY u.created_at DESC
");
$stmt->execute();
$updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>アップデート情報 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
body {
  margin: 0;
  min-height: 100vh;
  background: linear-gradient(135deg, #0d0d0d 0%, #1a1a2e 50%, #16213e 100%);
  overflow-x: hidden;
  position: relative;
  color: #fff;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* 背景アニメーション */
body::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: 
    radial-gradient(circle at 20% 50%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(118, 75, 162, 0.1) 0%, transparent 50%);
  pointer-events: none;
  z-index: 0;
  animation: bgShift 20s ease-in-out infinite;
}

@keyframes bgShift {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 0.8; }
}

.container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
  position: relative;
  z-index: 1;
}

.page-header {
  text-align: center;
  margin-bottom: 40px;
  animation: fadeIn 0.6s ease-out;
}

.page-header h1 {
  font-size: clamp(2rem, 5vw, 3rem);
  margin: 0 0 10px 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-weight: bold;
}

.page-header p {
  color: #a0a0c0;
  font-size: 1.1rem;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}

.updates-list {
  display: grid;
  gap: 20px;
  animation: slideUp 0.6s ease-out 0.2s both;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.update-card {
  background: linear-gradient(135deg, rgba(30, 30, 50, 0.95) 0%, rgba(20, 20, 35, 0.95) 100%);
  border-radius: 16px;
  padding: 28px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.6), 0 0 1px rgba(102, 126, 234, 0.5);
  border: 1px solid rgba(102, 126, 234, 0.2);
  backdrop-filter: blur(10px);
  transition: all 0.3s;
  animation: fadeInCard 0.6s ease-out both;
}

@keyframes fadeInCard {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

.update-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 50px rgba(102, 126, 234, 0.3);
  border-color: rgba(102, 126, 234, 0.5);
}

.update-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
  gap: 16px;
}

.update-title-section {
  flex: 1;
}

.update-title {
  font-size: 1.6rem;
  font-weight: bold;
  color: #fff;
  margin: 0 0 8px 0;
}

.update-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  color: #a0a0c0;
  font-size: 0.9rem;
}

.update-category {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  text-transform: uppercase;
}

.category-feature {
  background: linear-gradient(135deg, rgba(72, 187, 120, 0.3) 0%, rgba(56, 161, 105, 0.3) 100%);
  border: 1px solid rgba(72, 187, 120, 0.5);
  color: #68d391;
}

.category-bugfix {
  background: linear-gradient(135deg, rgba(245, 101, 101, 0.3) 0%, rgba(229, 62, 62, 0.3) 100%);
  border: 1px solid rgba(245, 101, 101, 0.5);
  color: #fc8181;
}

.category-improvement {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.3) 0%, rgba(118, 75, 162, 0.3) 100%);
  border: 1px solid rgba(102, 126, 234, 0.5);
  color: #a0aeff;
}

.category-announcement {
  background: linear-gradient(135deg, rgba(237, 137, 54, 0.3) 0%, rgba(221, 107, 32, 0.3) 100%);
  border: 1px solid rgba(237, 137, 54, 0.5);
  color: #f6ad55;
}

.update-version {
  padding: 6px 12px;
  background: rgba(102, 126, 234, 0.2);
  border: 1px solid rgba(102, 126, 234, 0.3);
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #a0aeff;
}

.update-content {
  color: #e0e0e0;
  line-height: 1.8;
  font-size: 1rem;
  margin-bottom: 16px;
  white-space: pre-wrap;
}

.update-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  border-top: 1px solid rgba(102, 126, 234, 0.2);
  font-size: 0.85rem;
  color: #a0a0c0;
}

.update-author {
  display: flex;
  align-items: center;
  gap: 8px;
}

.update-date {
  color: #808090;
}

.empty-state {
  text-align: center;
  padding: 80px 20px;
  color: #a0a0c0;
  font-size: 1.2rem;
}

.empty-state-icon {
  font-size: 5rem;
  margin-bottom: 20px;
  opacity: 0.5;
}

@media (max-width: 768px) {
  .update-header {
    flex-direction: column;
  }
  
  .update-meta {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
</head>
<body>
<header class="topbar">
  <div class="logo"><a class="link" href="index.php">← フィードに戻る</a></div>
</header>

<div class="container">
  <div class="page-header">
    <h1>📢 アップデート情報</h1>
    <p>MiniBirdの最新情報をお届けします</p>
  </div>

  <?php if ($updates): ?>
    <div class="updates-list">
      <?php foreach($updates as $update): 
        $category_icon = [
          'feature' => '✨',
          'bugfix' => '🐛',
          'improvement' => '🔧',
          'announcement' => '📢'
        ];
        $icon = $category_icon[$update['category']] ?? '📝';
        
        $category_label = [
          'feature' => '新機能',
          'bugfix' => 'バグ修正',
          'improvement' => '改善',
          'announcement' => 'お知らせ'
        ];
        $label = $category_label[$update['category']] ?? 'その他';
      ?>
      <div class="update-card">
        <div class="update-header">
          <div class="update-title-section">
            <h2 class="update-title"><?= htmlspecialchars($update['title']) ?></h2>
            <div class="update-meta">
              <span class="update-category category-<?= $update['category'] ?>">
                <?= $icon ?> <?= $label ?>
              </span>
              <?php if ($update['version']): ?>
              <span class="update-version">v<?= htmlspecialchars($update['version']) ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="update-content"><?= nl2br(htmlspecialchars($update['content'])) ?></div>
        <div class="update-footer">
          <div class="update-author">
            👤 <?= htmlspecialchars($update['creator_name'] ?? $update['creator_handle'] ?? 'MiniBird') ?>
          </div>
          <div class="update-date">
            📅 <?= date('Y年m月d日', strtotime($update['created_at'])) ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <div class="empty-state-icon">📭</div>
      <p>まだアップデート情報がありません</p>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
