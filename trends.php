<?php
require_once __DIR__ . '/config.php';
$me = user();

$pdo = db();


// 最新のトレンドデータ取得（上位30件）
$stmt = $pdo->prepare("
    SELECT * FROM trend_words
    WHERE calculated_at >= ?
    ORDER BY trend_score DESC
    LIMIT 30
");
$stmt->execute([date('Y-m-d H:i:s', strtotime('-2 hours'))]);
$trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// トレンドデータがない場合は計算実行
if (empty($trends)) {
    include __DIR__ . '/trend_calculator.php';
    
    // 再取得
    $stmt->execute([date('Y-m-d H:i:s', strtotime('-2 hours'))]);
    $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>トレンド - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
.trend-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}
.trend-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    text-align: center;
}
.trend-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
}
.trend-info {
    color: rgba(255,255,255,0.9);
    font-size: 14px;
    margin-top: 10px;
}
.trend-list {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.trend-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #e2e8f0;
    transition: all 0.3s;
    cursor: pointer;
}
.trend-item:hover {
    background: #f7fafc;
    transform: translateX(5px);
}
.trend-item:last-child {
    border-bottom: none;
}
.trend-rank {
    font-size: 24px;
    font-weight: bold;
    color: #cbd5e0;
    width: 50px;
    text-align: center;
}
.trend-rank.top1 { color: #f59e0b; }
.trend-rank.top2 { color: #94a3b8; }
.trend-rank.top3 { color: #cd7f32; }
.trend-word {
    flex: 1;
    margin: 0 15px;
}
.trend-word-text {
    font-size: 20px;
    font-weight: bold;
    color: #2d3748;
    margin-bottom: 5px;
}
.trend-stats {
    display: flex;
    gap: 15px;
    font-size: 13px;
    color: #718096;
}
.trend-stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
}
.trend-score {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    min-width: 100px;
}
.trend-score-value {
    font-size: 18px;
    font-weight: bold;
    color: #667eea;
}
.trend-score-label {
    font-size: 11px;
    color: #a0aec0;
}
.empty-message {
    text-align: center;
    padding: 40px;
    color: #718096;
}
.refresh-button {
    text-align: center;
    margin-top: 20px;
}
.refresh-button button {
    padding: 10px 20px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}
.back-button {
    text-align: center;
    margin-top: 30px;
}
.back-button button {
    padding: 12px 30px;
    background: #cbd5e0;
    color: #2d3748;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
}
</style>
</head>
<body>
<div class="trend-container">
    <div class="trend-header">
        <h1>🔥 トレンド</h1>
        <p>今話題のワードをチェック！</p>
        <div class="trend-info">
            過去48時間または最新200件の投稿から算出
        </div>
    </div>

    <div class="trend-list">
        <?php if (empty($trends)): ?>
        <div class="empty-message">
            <p>まだトレンドデータがありません</p>
            <p style="font-size: 14px; margin-top: 10px;">投稿が増えるとトレンドが表示されます</p>
        </div>
        <?php else: ?>
        <?php foreach ($trends as $index => $trend): 
            $rank = $index + 1;
            $rank_class = '';
            if ($rank === 1) $rank_class = 'top1';
            elseif ($rank === 2) $rank_class = 'top2';
            elseif ($rank === 3) $rank_class = 'top3';
        ?>
        <div class="trend-item" onclick="searchWord('<?= htmlspecialchars($trend['word'], ENT_QUOTES) ?>')">
            <div class="trend-rank <?= $rank_class ?>">
                <?= $rank ?>
            </div>
            <div class="trend-word">
                <div class="trend-word-text">
                    <?= htmlspecialchars($trend['word']) ?>
                </div>
                <div class="trend-stats">
                    <div class="trend-stat-item">
                        📝 <?= number_format($trend['post_count']) ?>件の投稿
                    </div>
                    <div class="trend-stat-item">
                        💬 <?= number_format($trend['occurrence_count']) ?>回登場
                    </div>
                    <div class="trend-stat-item">
                        ❤️ <?= number_format($trend['total_likes']) ?>
                    </div>
                    <div class="trend-stat-item">
                        🔁 <?= number_format($trend['total_reposts']) ?>
                    </div>
                </div>
            </div>
            <div class="trend-score">
                <div class="trend-score-value">
                    <?= number_format($trend['trend_score'], 1) ?>
                </div>
                <div class="trend-score-label">
                    SCORE
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($trends)): ?>
    <div class="refresh-button">
        <button onclick="refreshTrends()">🔄 トレンドを更新</button>
    </div>
    <?php endif; ?>

    <div class="back-button">
        <button onclick="location.href='index.php'">フィードに戻る</button>
    </div>
</div>

<script>
function searchWord(word) {
    location.href = 'search.php?q=' + encodeURIComponent(word);
}

async function refreshTrends() {
    if (!confirm('トレンドを再計算しますか？（少し時間がかかる場合があります）')) {
        return;
    }
    
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = '計算中...';
    
    try {
        const res = await fetch('trend_calculator.php');
        const text = await res.text();
        console.log(text);
        
        // ページリロード
        location.reload();
    } catch (err) {
        alert('エラーが発生しました');
        btn.disabled = false;
        btn.textContent = '🔄 トレンドを更新';
    }
}
</script>
</body>
</html>