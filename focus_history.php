<?php
require_once __DIR__ . '/config.php';
require_login();

$pdo = db();
$st = $pdo->prepare("SELECT * FROM focus_tasks WHERE user_id=? ORDER BY created_at DESC");
$st->execute([$_SESSION['uid']]);
$tasks = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>集中タスク履歴 - MiniBird</title>
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
  max-width: 1200px;
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
  text-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
}

.page-header p {
  color: #a0a0c0;
  font-size: 1.1rem;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 40px;
  animation: slideUp 0.6s ease-out 0.2s both;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.stat-card {
  background: linear-gradient(135deg, rgba(30, 30, 50, 0.95) 0%, rgba(20, 20, 35, 0.95) 100%);
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.6), 0 0 1px rgba(102, 126, 234, 0.5);
  border: 1px solid rgba(102, 126, 234, 0.2);
  backdrop-filter: blur(10px);
  transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 50px rgba(102, 126, 234, 0.3);
}

.stat-label {
  font-size: 0.9rem;
  color: #a0a0c0;
  margin-bottom: 8px;
  font-weight: 500;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: bold;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.stat-icon {
  font-size: 2rem;
  margin-bottom: 8px;
}

.history-section {
  animation: slideUp 0.6s ease-out 0.4s both;
}

.section-title {
  font-size: 1.8rem;
  margin: 0 0 24px 0;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-title::before {
  content: '📜';
  font-size: 2rem;
}

.task-list {
  display: grid;
  gap: 16px;
}

.task-card {
  background: linear-gradient(135deg, rgba(30, 30, 50, 0.95) 0%, rgba(20, 20, 35, 0.95) 100%);
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.4);
  border: 1px solid rgba(102, 126, 234, 0.2);
  transition: all 0.3s;
  animation: fadeInCard 0.6s ease-out both;
}

@keyframes fadeInCard {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

.task-card:hover {
  transform: translateX(5px);
  border-color: rgba(102, 126, 234, 0.5);
  box-shadow: 0 6px 30px rgba(102, 126, 234, 0.2);
}

.task-card.success {
  border-left: 4px solid #48bb78;
}

.task-card.fail {
  border-left: 4px solid #f56565;
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.task-status {
  font-weight: bold;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.task-status.success {
  color: #48bb78;
}

.task-status.fail {
  color: #f56565;
}

.task-name {
  font-size: 1.3rem;
  font-weight: bold;
  color: #fff;
  margin-bottom: 12px;
}

.task-meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  font-size: 0.9rem;
  color: #a0a0c0;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid rgba(102, 126, 234, 0.2);
}

.task-meta-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.task-meta-label {
  font-weight: 500;
  color: #b0b0d0;
}

.task-meta-value {
  color: #fff;
  font-weight: 600;
}

.reward-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%);
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
  border: 1px solid rgba(102, 126, 234, 0.3);
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #a0a0c0;
  font-size: 1.2rem;
}

.empty-state-icon {
  font-size: 4rem;
  margin-bottom: 20px;
  opacity: 0.5;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .task-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
}
</style>
</head>
<body>
<header class="topbar">
  <div class="logo"><a class="link" href="focus.php">← 集中モードに戻る</a></div>
</header>

<div class="container">
  <div class="page-header">
    <h1>🔥 集中タスク履歴 🔥</h1>
    <p>あなたの努力の軌跡がここに</p>
  </div>

  <?php if ($tasks): ?>
    <?php
    // 統計情報を計算
    $total_tasks = count($tasks);
    $success_count = 0;
    $fail_count = 0;
    $total_minutes = 0;
    $total_coins = 0;
    $total_crystals = 0;

    foreach ($tasks as $t) {
      if ($t['status'] === 'success') {
        $success_count++;
      } else {
        $fail_count++;
      }
      $total_minutes += (int)$t['minutes'];
      $total_coins += (int)$t['coins'];
      $total_crystals += (int)$t['crystals'];
    }

    $success_rate = $total_tasks > 0 ? round(($success_count / $total_tasks) * 100, 1) : 0;
    ?>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-label">成功率</div>
        <div class="stat-value"><?= $success_rate ?>%</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-label">成功回数</div>
        <div class="stat-value"><?= $success_count ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">⏱️</div>
        <div class="stat-label">累計時間</div>
        <div class="stat-value"><?= $total_minutes ?>分</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🪙</div>
        <div class="stat-label">獲得コイン</div>
        <div class="stat-value"><?= $total_coins ?></div>
      </div>
    </div>

    <div class="history-section">
      <h2 class="section-title">履歴一覧</h2>
      <div class="task-list">
        <?php foreach($tasks as $t): ?>
          <div class="task-card <?= $t['status'] ?>">
            <div class="task-header">
              <div class="task-status <?= $t['status'] ?>">
                <?= $t['status'] === 'success' ? '✅ 成功' : '❌ 失敗' ?>
              </div>
              <div class="reward-badge">
                🪙 <?= (int)$t['coins'] ?> 💎 <?= (int)$t['crystals'] ?>
              </div>
            </div>
            <div class="task-name"><?= htmlspecialchars($t['task']) ?></div>
            <div class="task-meta">
              <div class="task-meta-item">
                <span class="task-meta-label">開始:</span>
                <span class="task-meta-value"><?= date('Y/m/d H:i', strtotime($t['started_at'])) ?></span>
              </div>
              <div class="task-meta-item">
                <span class="task-meta-label">終了:</span>
                <span class="task-meta-value"><?= date('Y/m/d H:i', strtotime($t['ended_at'])) ?></span>
              </div>
              <div class="task-meta-item">
                <span class="task-meta-label">時間:</span>
                <span class="task-meta-value"><?= (int)$t['minutes'] ?>分</span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <div class="empty-state-icon">📭</div>
      <p>まだ記録がありません。</p>
      <p style="font-size: 1rem; margin-top: 10px;">集中モードで最初のタスクを始めましょう！</p>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
