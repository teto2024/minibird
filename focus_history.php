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
<title>集中タスク履歴</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
.success { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.task-card {
  border-bottom: 1px solid #ddd;
  padding: 8px 0;
}
.task-meta {
  font-size: 0.9em;
  color: #666;
}
</style>
</head>
<body>
<header class="topbar">
  <div class="logo"><a class="link" href="focus.php">← 戻る</a></div>
</header>
<main class="layout">
  <section class="card">
    <h3>集中タスク履歴</h3>
    <?php if (!$tasks): ?>
      <p>まだ記録がありません。</p>
    <?php else: ?>
      <ul>
        <?php foreach($tasks as $t): ?>
          <li class="task-card">
            <span class="<?= $t['status']==='success' ? 'success' : 'fail' ?>">
              <?= $t['status']==='success' ? '✅ 成功' : '❌ 失敗' ?>
            </span>
            <b><?= htmlspecialchars($t['task']) ?></b><br>
            <span class="task-meta">
              <?= htmlspecialchars($t['started_at']) ?> ～ <?= htmlspecialchars($t['ended_at']) ?><br>
              時間: <?= (int)$t['minutes'] ?>分
              コイン: <?= (int)$t['coins'] ?>
              クリスタル: <?= (int)$t['crystals'] ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
