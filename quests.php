<?php
// ===============================================
// quests.php
// クエスト表示ページ
// ===============================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/quest_progress.php';

$me = user();
if (!$me) {
    header('Location: ./');
    exit;
}

$pdo = db();

// ページロード時に自動的に期限切れクエストをリセット
auto_reset_quests($me['id']);

// クエスト進行状況取得
$stmt = $pdo->prepare("
    SELECT q.*, uqp.progress, uqp.status, uqp.expired_at
    FROM quests q
    LEFT JOIN user_quest_progress uqp ON uqp.quest_id = q.id AND uqp.user_id = ? AND uqp.status != 'expired'
    WHERE q.is_active = TRUE AND q.type IN ('daily', 'weekly')
    ORDER BY q.type, q.id
");
$stmt->execute([$me['id']]);
$quests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// リレークエスト進行状況取得
$stmt = $pdo->prepare("SELECT current_order FROM relay_quest_progress WHERE user_id = ?");
$stmt->execute([$me['id']]);
$relay_progress = $stmt->fetch();
$current_relay_order = $relay_progress ? $relay_progress['current_order'] : 1;

$stmt = $pdo->prepare("
    SELECT q.*, uqp.progress, uqp.status
    FROM quests q
    LEFT JOIN user_quest_progress uqp ON uqp.quest_id = q.id AND uqp.user_id = ?
    WHERE q.type = 'relay' AND q.is_active = TRUE
    ORDER BY q.relay_order
");
$stmt->execute([$me['id']]);
$relay_quests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// クエスト種類別に分類
$daily_quests = array_filter($quests, fn($q) => $q['type'] === 'daily');
$weekly_quests = array_filter($quests, fn($q) => $q['type'] === 'weekly');

// コンプリート状態チェック
$today = date('Y-m-d');
$week_key = date('Y-W');

// デイリーコンプリート状態
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total,
           SUM(CASE WHEN uqp.status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM quests q
    LEFT JOIN user_quest_progress uqp ON uqp.quest_id = q.id AND uqp.user_id = ? 
        AND DATE(uqp.started_at) = ?
    WHERE q.type = 'daily' AND q.is_active = TRUE
");
$stmt->execute([$me['id'], $today]);
$daily_completion = $stmt->fetch(PDO::FETCH_ASSOC);
$daily_all_completed = ($daily_completion['total'] > 0 && $daily_completion['completed'] == $daily_completion['total']);

$stmt = $pdo->prepare("SELECT id FROM quest_completions WHERE user_id = ? AND completion_type = 'daily' AND period_key = ?");
$stmt->execute([$me['id'], $today]);
$daily_reward_claimed = (bool)$stmt->fetch();

// ウィークリーコンプリート状態
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total,
           SUM(CASE WHEN uqp.status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM quests q
    LEFT JOIN user_quest_progress uqp ON uqp.quest_id = q.id AND uqp.user_id = ? 
        AND YEARWEEK(uqp.started_at, 1) = YEARWEEK(NOW(), 1)
    WHERE q.type = 'weekly' AND q.is_active = TRUE
");
$stmt->execute([$me['id']]);
$weekly_completion = $stmt->fetch(PDO::FETCH_ASSOC);
$weekly_all_completed = ($weekly_completion['total'] > 0 && $weekly_completion['completed'] == $weekly_completion['total']);

$stmt = $pdo->prepare("SELECT id FROM quest_completions WHERE user_id = ? AND completion_type = 'weekly' AND period_key = ?");
$stmt->execute([$me['id'], $week_key]);
$weekly_reward_claimed = (bool)$stmt->fetch();

// リレーコンプリート状態
$stmt = $pdo->prepare("SELECT COUNT(*) FROM quests WHERE type = 'relay' AND is_active = TRUE");
$stmt->execute();
$total_relay = (int)$stmt->fetchColumn();
$relay_all_completed = ($current_relay_order > $total_relay);

$completion_key = 'relay_' . date('Y-m-d');
$stmt = $pdo->prepare("SELECT id FROM quest_completions WHERE user_id = ? AND completion_type = 'relay' AND period_key = ?");
$stmt->execute([$me['id'], $completion_key]);
$relay_reward_claimed = (bool)$stmt->fetch();
?>
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>クエスト - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
<style>
.quest-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}
.quest-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    text-align: center;
}
.quest-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
}
.currency-display {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin-top: 20px;
    font-size: 18px;
}
.currency-item {
    display: flex;
    align-items: center;
    gap: 8px;
}
.quest-section {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.quest-section h2 {
    margin: 0 0 15px 0;
    color: #2d3748;
    font-size: 24px;
    border-bottom: 2px solid #667eea;
    padding-bottom: 10px;
}
.quest-card {
    background: #f7fafc;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 12px;
    transition: all 0.3s;
}
.quest-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 6px rgba(102, 126, 234, 0.1);
}
.quest-card.completed {
    background: #c6f6d5;
    border-color: #48bb78;
}
.quest-card.locked {
    opacity: 0.6;
    background: #edf2f7;
}
.quest-title {
    font-size: 18px;
    font-weight: bold;
    color: #2d3748;
    margin-bottom: 5px;
}
.quest-description {
    color: #718096;
    font-size: 14px;
    margin-bottom: 10px;
}
.quest-progress-bar {
    width: 100%;
    height: 20px;
    background: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    margin: 10px 0;
}
.quest-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.5s;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    font-weight: bold;
}
.quest-progress-fill.completed {
    background: linear-gradient(90deg, #48bb78 0%, #38a169 100%);
}
.quest-reward {
    display: flex;
    gap: 15px;
    margin-top: 10px;
    flex-wrap: wrap;
}
.reward-item {
    display: flex;
    align-items: center;
    gap: 5px;
    background: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
}
.relay-quest-chain {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.relay-arrow {
    text-align: center;
    color: #667eea;
    font-size: 24px;
}
.badge-current {
    display: inline-block;
    background: #667eea;
    color: white;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    margin-left: 10px;
}
.completion-bonus {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    border: 3px solid #ff9800;
    border-radius: 12px;
    padding: 20px;
    margin-top: 15px;
    text-align: center;
    box-shadow: 0 4px 8px rgba(255, 152, 0, 0.3);
}
.completion-bonus.claimed {
    background: linear-gradient(135deg, #c0c0c0 0%, #e0e0e0 100%);
    border-color: #999;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
.completion-bonus h3 {
    margin: 0 0 10px 0;
    font-size: 20px;
    color: #333;
}
.completion-bonus .bonus-rewards {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 10px;
}
.completion-bonus .bonus-rewards .reward-item {
    background: white;
    padding: 8px 16px;
    font-size: 16px;
}
</style>
</head>
<body>
<div class="quest-container">
    <div class="quest-header">
        <h1>🎮 クエスト</h1>
        <p>クエストをクリアしてコイン、クリスタル、ダイヤモンドをゲット！</p>
        <div class="currency-display">
            <div class="currency-item">
                <span>🪙</span>
                <span><?= number_format($me['coins'] ?? 0) ?> コイン</span>
            </div>
            <div class="currency-item">
                <span>💎</span>
                <span><?= number_format($me['crystals'] ?? 0) ?> クリスタル</span>
            </div>
            <div class="currency-item">
                <span>💠</span>
                <span><?= number_format($me['diamonds'] ?? 0) ?> ダイヤモンド</span>
            </div>
        </div>
    </div>

    <!-- デイリークエスト -->
    <div class="quest-section">
        <h2>📅 デイリークエスト（毎日リセット）</h2>
        <?php foreach ($daily_quests as $quest): 
            $conditions = json_decode($quest['conditions'], true);
            $required = $conditions['count'] ?? 1;
            $progress = intval($quest['progress'] ?? 0);
            $status = $quest['status'] ?? 'active';
            $percentage = min(100, ($progress / $required) * 100);
        ?>
        <div class="quest-card <?= $status === 'completed' ? 'completed' : '' ?>">
            <div class="quest-title">
                <?= htmlspecialchars($quest['title']) ?>
                <?= $status === 'completed' ? '✅' : '' ?>
            </div>
            <div class="quest-description"><?= htmlspecialchars($quest['description']) ?></div>
            <div class="quest-progress-bar">
                <div class="quest-progress-fill <?= $status === 'completed' ? 'completed' : '' ?>" 
                     style="width: <?= $percentage ?>%">
                    <?= $progress ?> / <?= $required ?>
                </div>
            </div>
            <div class="quest-reward">
                <?php if ($quest['reward_coins'] > 0): ?>
                <div class="reward-item">🪙 <?= number_format($quest['reward_coins']) ?></div>
                <?php endif; ?>
                <?php if ($quest['reward_crystals'] > 0): ?>
                <div class="reward-item">💎 <?= number_format($quest['reward_crystals']) ?></div>
                <?php endif; ?>
                <?php if ($quest['reward_diamonds'] > 0): ?>
                <div class="reward-item">💠 <?= number_format($quest['reward_diamonds']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- デイリーコンプリートボーナス -->
        <div class="completion-bonus <?= $daily_reward_claimed ? 'claimed' : '' ?>">
            <h3>🎉 全クリア達成ボーナス</h3>
            <p><?= $daily_all_completed ? ($daily_reward_claimed ? '本日のボーナスを受け取りました！' : '全てのデイリークエストをクリア！') : "進捗: {$daily_completion['completed']} / {$daily_completion['total']}" ?></p>
            <div class="bonus-rewards">
                <div class="reward-item">🪙 3,000</div>
                <div class="reward-item">💎 15</div>
            </div>
        </div>
    </div>

    <!-- ウィークリークエスト -->
    <div class="quest-section">
        <h2>📆 ウィークリークエスト（毎週日曜リセット）</h2>
        <?php foreach ($weekly_quests as $quest): 
            $conditions = json_decode($quest['conditions'], true);
            $required = $conditions['count'] ?? 1;
            $progress = intval($quest['progress'] ?? 0);
            $status = $quest['status'] ?? 'active';
            $percentage = min(100, ($progress / $required) * 100);
        ?>
        <div class="quest-card <?= $status === 'completed' ? 'completed' : '' ?>">
            <div class="quest-title">
                <?= htmlspecialchars($quest['title']) ?>
                <?= $status === 'completed' ? '✅' : '' ?>
            </div>
            <div class="quest-description"><?= htmlspecialchars($quest['description']) ?></div>
            <div class="quest-progress-bar">
                <div class="quest-progress-fill <?= $status === 'completed' ? 'completed' : '' ?>" 
                     style="width: <?= $percentage ?>%">
                    <?= $progress ?> / <?= $required ?>
                </div>
            </div>
            <div class="quest-reward">
                <?php if ($quest['reward_coins'] > 0): ?>
                <div class="reward-item">🪙 <?= number_format($quest['reward_coins']) ?></div>
                <?php endif; ?>
                <?php if ($quest['reward_crystals'] > 0): ?>
                <div class="reward-item">💎 <?= number_format($quest['reward_crystals']) ?></div>
                <?php endif; ?>
                <?php if ($quest['reward_diamonds'] > 0): ?>
                <div class="reward-item">💠 <?= number_format($quest['reward_diamonds']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- ウィークリーコンプリートボーナス -->
        <div class="completion-bonus <?= $weekly_reward_claimed ? 'claimed' : '' ?>">
            <h3>🎉 全クリア達成ボーナス</h3>
            <p><?= $weekly_all_completed ? ($weekly_reward_claimed ? '今週のボーナスを受け取りました！' : '全てのウィークリークエストをクリア！') : "進捗: {$weekly_completion['completed']} / {$weekly_completion['total']}" ?></p>
            <div class="bonus-rewards">
                <div class="reward-item">🪙 10,000</div>
                <div class="reward-item">💎 50</div>
                <div class="reward-item">💠 2</div>
            </div>
        </div>
    </div>

    <!-- リレークエスト -->
    <div class="quest-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <div>
                <h2 style="margin: 0;">🔗 リレークエスト（順番にクリア）</h2>
                <p style="color: #718096; margin: 5px 0;">前のクエストをクリアすると次のクエストが解放されます</p>
            </div>
            <button id="resetRelayBtn" style="padding: 10px 20px; background: #f56565; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">
                🔄 リセット
            </button>
        </div>
        <div class="relay-quest-chain">
        <?php foreach ($relay_quests as $index => $quest): 
            $conditions = json_decode($quest['conditions'], true);
            $required = $conditions['count'] ?? 1;
            $progress = intval($quest['progress'] ?? 0);
            $status = $quest['status'] ?? 'active';
            $percentage = min(100, ($progress / $required) * 100);
            $is_current = ($quest['relay_order'] == $current_relay_order);
            $is_locked = ($quest['relay_order'] > $current_relay_order);
            $is_completed = ($status === 'completed');
        ?>
        <div class="quest-card <?= $is_completed ? 'completed' : '' ?> <?= $is_locked ? 'locked' : '' ?>">
            <div class="quest-title">
                <?= $is_locked ? '🔒' : '' ?>
                <?= htmlspecialchars($quest['title']) ?>
                <?= $is_completed ? '✅' : '' ?>
                <?= $is_current ? '<span class="badge-current">進行中</span>' : '' ?>
            </div>
            <div class="quest-description"><?= htmlspecialchars($quest['description']) ?></div>
            <?php if (!$is_locked): ?>
            <div class="quest-progress-bar">
                <div class="quest-progress-fill <?= $is_completed ? 'completed' : '' ?>" 
                     style="width: <?= $percentage ?>%">
                    <?= $progress ?> / <?= $required ?>
                </div>
            </div>
            <div class="quest-reward">
                <?php if ($quest['reward_coins'] > 0): ?>
                <div class="reward-item">🪙 <?= number_format($quest['reward_coins']) ?></div>
                <?php endif; ?>
                <?php if ($quest['reward_crystals'] > 0): ?>
                <div class="reward-item">💎 <?= number_format($quest['reward_crystals']) ?></div>
                <?php endif; ?>
                <?php if ($quest['reward_diamonds'] > 0): ?>
                <div class="reward-item">💠 <?= number_format($quest['reward_diamonds']) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($index < count($relay_quests) - 1): ?>
        <div class="relay-arrow">⬇️</div>
        <?php endif; ?>
        <?php endforeach; ?>
        </div>
        
        <!-- リレーコンプリートボーナス -->
        <div class="completion-bonus <?= $relay_reward_claimed ? 'claimed' : '' ?>">
            <h3>🎉 全クリア達成ボーナス</h3>
            <p><?= $relay_all_completed ? ($relay_reward_claimed ? '本日のボーナスを受け取りました！' : '全てのリレークエストをクリア！') : "進捗: " . ($current_relay_order - 1) . " / {$total_relay}" ?></p>
            <div class="bonus-rewards">
                <div class="reward-item">🪙 2,000</div>
                <div class="reward-item">💠 1</div>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <button onclick="location.href='index.php'" style="padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer;">
            フィードに戻る
        </button>
    </div>
</div>

<script>
// リレークエストリセット
document.getElementById('resetRelayBtn')?.addEventListener('click', async () => {
    if (!confirm('リレークエストをリセットしますか？進行状況は最初に戻ります。')) {
        return;
    }
    
    try {
        const res = await fetch('quest_reset_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'reset_relay'})
        });
        const data = await res.json();
        
        if (data.ok) {
            alert('リレークエストをリセットしました');
            location.reload();
        } else {
            alert('リセット失敗: ' + data.error);
        }
    } catch (err) {
        alert('エラーが発生しました');
    }
});

// 5秒ごとに自動リロード（クエスト進行状況更新）
setInterval(() => {
    location.reload();
}, 5000);
</script>
</body>
</html>
