<?php
// ===============================================
// word_master.php
// 英単語マスター（HAMARUスタイル）
// 金を基調としたゴージャスなデザイン
// ===============================================

require_once __DIR__ . '/config.php';
$me = user();
if (!$me) {
    header('Location: ./login.php');
    exit;
}

$pdo = db();

// エラーハンドリング用変数
$dbError = false;
$dbErrorMessage = '';

// セクション数を計算（20単語ごと）
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM english_words");
    $totalWords = (int)$stmt->fetchColumn();
    $sectionsCount = max(1, ceil($totalWords / 20));
} catch (PDOException $e) {
    $dbError = true;
    $dbErrorMessage = 'データベースエラーが発生しました。テーブルが存在しないか、アクセスできません。スキーマを実行してください。';
    $totalWords = 0;
    $sectionsCount = 0;
}

// ユーザーの進捗を取得
$userProgress = [];
if (!$dbError) {
    try {
        $stmt = $pdo->prepare("
            SELECT section_id, level, best_score, completed 
            FROM user_word_master_progress 
            WHERE user_id = ?
        ");
        $stmt->execute([$me['id']]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $userProgress[$row['section_id'] . '_' . $row['level']] = $row;
        }
    } catch (PDOException $e) {
        // データベースエラー時は空配列のまま続行
    }
}

// 英単語マスター報酬バフの確認
$rewardBuff = null;
$buffMultiplier = 1;
if (!$dbError) {
    try {
        $stmt = $pdo->prepare("
            SELECT level, end_time 
            FROM user_buffs 
            WHERE user_id = ? AND type = 'word_master_reward' AND end_time > NOW()
            ORDER BY level DESC 
            LIMIT 1
        ");
        $stmt->execute([$me['id']]);
        $rewardBuff = $stmt->fetch(PDO::FETCH_ASSOC);
        $buffMultiplier = $rewardBuff ? (1 + ($rewardBuff['level'] * 0.2)) : 1;
    } catch (PDOException $e) {
        // データベースエラー時はバフなしで続行
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>英単語マスター - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
/* HAMARUスタイル - ゴージャスな金を基調としたデザイン */
body {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f0f23 100%);
    min-height: 100vh;
}

.word-master-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.word-master-header {
    background: linear-gradient(135deg, #d4af37 0%, #ffd700 25%, #f0e68c 50%, #ffd700 75%, #d4af37 100%);
    background-size: 200% 200%;
    animation: goldShimmer 3s ease-in-out infinite;
    color: #1a1a2e;
    padding: 40px;
    border-radius: 20px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(212, 175, 55, 0.4);
    border: 3px solid #ffd700;
    position: relative;
    overflow: hidden;
}

.word-master-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
    animation: shimmerMove 4s linear infinite;
}

@keyframes goldShimmer {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

@keyframes shimmerMove {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.word-master-header h1 {
    margin: 0 0 15px 0;
    font-size: 36px;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
}

.word-master-header p {
    margin: 0;
    font-size: 18px;
    position: relative;
    z-index: 1;
}

.currency-bar {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin-top: 20px;
    font-size: 16px;
    position: relative;
    z-index: 1;
}

.currency-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(26, 26, 46, 0.8);
    color: #ffd700;
    padding: 10px 20px;
    border-radius: 25px;
    border: 2px solid #ffd700;
    font-weight: bold;
}

.buff-indicator {
    background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);
    color: white;
    padding: 10px 20px;
    border-radius: 10px;
    margin-top: 15px;
    display: inline-block;
    font-weight: bold;
    animation: pulse 2s infinite;
    position: relative;
    z-index: 1;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* セクショングリッド */
.sections-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.section-card {
    background: linear-gradient(135deg, #2d2d44 0%, #3d3d5c 100%);
    border-radius: 16px;
    padding: 25px;
    border: 2px solid rgba(255, 215, 0, 0.3);
    transition: all 0.3s;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.section-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3);
    border-color: #ffd700;
}

.section-card.completed {
    border-color: #48bb78;
    background: linear-gradient(135deg, #1a4731 0%, #2d5a45 100%);
}

.section-card.test-section {
    border-color: #ff6b6b;
    background: linear-gradient(135deg, #4a1e1e 0%, #5c2d2d 100%);
}

.section-number {
    font-size: 48px;
    font-weight: bold;
    color: #ffd700;
    text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
    margin-bottom: 10px;
}

.section-title {
    font-size: 20px;
    font-weight: bold;
    color: #fff;
    margin-bottom: 10px;
}

.section-info {
    color: #a0a0c0;
    font-size: 14px;
    margin-bottom: 15px;
}

.section-progress {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.level-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.level-badge.selection {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.level-badge.input {
    background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%);
    color: white;
}

.level-badge.completed {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
}

.level-badge.locked {
    background: #4a5568;
    color: #a0aec0;
}

.best-score {
    font-size: 14px;
    color: #ffd700;
    margin-top: 10px;
}

.start-btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
    color: #1a1a2e;
    border: none;
    border-radius: 10px;
    font-weight: bold;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 10px;
}

.start-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
}

.start-btn:disabled {
    background: #4a5568;
    color: #a0aec0;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* タブ */
.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 12px 24px;
    border: 2px solid #ffd700;
    border-radius: 10px;
    background: transparent;
    color: #ffd700;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    transition: all 0.3s;
}

.tab-btn.active {
    background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
    color: #1a1a2e;
}

.back-link {
    display: inline-block;
    color: #ffd700;
    text-decoration: none;
    margin-bottom: 20px;
    font-weight: bold;
    transition: all 0.3s;
}

.back-link:hover {
    color: #ffaa00;
}

/* レベル選択モーダル */
.level-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.level-modal.hidden {
    display: none;
}

.level-modal-content {
    background: linear-gradient(135deg, #2d2d44 0%, #3d3d5c 100%);
    border-radius: 20px;
    padding: 40px;
    max-width: 500px;
    width: 90%;
    border: 3px solid #ffd700;
    text-align: center;
}

.level-modal-title {
    font-size: 28px;
    font-weight: bold;
    color: #ffd700;
    margin-bottom: 30px;
}

.level-options {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.level-option-btn {
    padding: 20px;
    border: 2px solid #ffd700;
    border-radius: 12px;
    background: transparent;
    color: #fff;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.level-option-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
    color: #1a1a2e;
}

.level-option-btn:disabled {
    border-color: #4a5568;
    color: #4a5568;
    cursor: not-allowed;
}

.level-option-desc {
    font-size: 14px;
    opacity: 0.8;
}

.close-modal-btn {
    margin-top: 20px;
    padding: 12px 30px;
    background: #4a5568;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .word-master-container {
        padding: 10px;
    }
    
    .word-master-header {
        padding: 20px 15px;
        border-radius: 15px;
        margin-bottom: 20px;
    }
    
    .word-master-header h1 {
        font-size: 24px;
        margin-bottom: 10px;
    }
    
    .word-master-header p {
        font-size: 14px;
    }
    
    .currency-bar {
        flex-direction: column;
        gap: 10px;
        margin-top: 15px;
    }
    
    .currency-item {
        padding: 8px 16px;
        font-size: 14px;
    }
    
    .buff-indicator {
        font-size: 12px;
        padding: 8px 16px;
    }
    
    .sections-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .section-card {
        padding: 20px;
    }
    
    .section-number {
        font-size: 36px;
    }
    
    .section-title {
        font-size: 18px;
    }
    
    .section-info {
        font-size: 13px;
    }
    
    .section-progress {
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .level-badge {
        padding: 4px 10px;
        font-size: 11px;
    }
    
    .best-score {
        font-size: 12px;
    }
    
    .start-btn {
        padding: 10px;
        font-size: 14px;
    }
    
    .tabs {
        gap: 8px;
        justify-content: center;
    }
    
    .tab-btn {
        padding: 10px 16px;
        font-size: 14px;
        flex: 1;
        min-width: 100px;
        text-align: center;
    }
    
    .level-modal-content {
        padding: 25px 20px;
        max-width: 95%;
    }
    
    .level-modal-title {
        font-size: 22px;
        margin-bottom: 20px;
    }
    
    .level-option-btn {
        padding: 15px;
        font-size: 16px;
    }
    
    .level-option-desc {
        font-size: 12px;
    }
    
    .close-modal-btn {
        padding: 10px 24px;
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .word-master-header h1 {
        font-size: 20px;
    }
    
    .section-number {
        font-size: 28px;
    }
    
    .tab-btn {
        padding: 8px 12px;
        font-size: 12px;
        min-width: 80px;
    }
}
</style>
</head>
<body>
<div class="word-master-container">
    <a href="./" class="back-link">← フィードに戻る</a>
    
    <div class="word-master-header">
        <h1>📚 英単語マスター</h1>
        <p>HAMARUスタイルで英単語をマスターしよう！</p>
        <div class="currency-bar">
            <div class="currency-item">
                <span>🪙</span>
                <span id="userCoins"><?= number_format($me['coins']) ?></span>
            </div>
            <div class="currency-item">
                <span>💎</span>
                <span id="userCrystals"><?= number_format($me['crystals']) ?></span>
            </div>
        </div>
        <?php if ($rewardBuff): ?>
        <div class="buff-indicator">
            🔥 報酬 +<?= intval($rewardBuff['level'] * 20) ?>% バフ発動中！
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($dbError): ?>
    <div style="background: #f56565; color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
        <h3 style="margin: 0 0 10px 0;">⚠️ データベースエラー</h3>
        <p style="margin: 0;"><?= htmlspecialchars($dbErrorMessage) ?></p>
        <p style="margin: 10px 0 0 0; font-size: 14px;">管理者に連絡するか、<code>schema_new_features.sql</code>を実行してください。</p>
    </div>
    <?php elseif ($totalWords === 0): ?>
    <div style="background: #ed8936; color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
        <h3 style="margin: 0 0 10px 0;">📚 英単語データがありません</h3>
        <p style="margin: 0;">英単語データがまだ登録されていません。管理者がデータをインポートするまでお待ちください。</p>
    </div>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab-btn active" data-tab="sections">📖 セクション</button>
        <button class="tab-btn" data-tab="test">🎯 テストモード</button>
        <button class="tab-btn" data-tab="weak">⚡ 苦手克服</button>
    </div>
    
    <!-- セクション一覧 -->
    <div class="tab-content active" id="tab-sections">
        <div class="sections-grid">
            <?php for ($i = 1; $i <= $sectionsCount; $i++): 
                $selectionKey = $i . '_selection';
                $inputKey = $i . '_input';
                $selectionCompleted = isset($userProgress[$selectionKey]) && $userProgress[$selectionKey]['completed'];
                $inputCompleted = isset($userProgress[$inputKey]) && $userProgress[$inputKey]['completed'];
                $selectionScore = $userProgress[$selectionKey]['best_score'] ?? null;
                $inputScore = $userProgress[$inputKey]['best_score'] ?? null;
                $startWord = ($i - 1) * 20 + 1;
                $endWord = min($i * 20, $totalWords);
            ?>
            <div class="section-card <?= ($selectionCompleted && $inputCompleted) ? 'completed' : '' ?>" 
                 onclick="openLevelModal(<?= $i ?>, <?= $selectionCompleted ? 'true' : 'false' ?>, <?= $inputCompleted ? 'true' : 'false' ?>)">
                <div class="section-number"><?= $i ?></div>
                <div class="section-title">セクション <?= $i ?></div>
                <div class="section-info">単語 #<?= $startWord ?> - #<?= $endWord ?></div>
                <div class="section-progress">
                    <span class="level-badge <?= $selectionCompleted ? 'completed' : 'selection' ?>">
                        選択式 <?= $selectionCompleted ? '✓' : '' ?>
                    </span>
                    <span class="level-badge <?= $inputCompleted ? 'completed' : ($selectionCompleted ? 'input' : 'locked') ?>">
                        入力式 <?= $inputCompleted ? '✓' : ($selectionCompleted ? '' : '🔒') ?>
                    </span>
                </div>
                <?php if ($selectionScore !== null || $inputScore !== null): ?>
                <div class="best-score">
                    ベストスコア: 
                    <?php if ($selectionScore !== null): ?>選択 <?= number_format($selectionScore, 3) ?>点<?php endif; ?>
                    <?php if ($inputScore !== null): ?> / 入力 <?= number_format($inputScore, 3) ?>点<?php endif; ?>
                </div>
                <?php endif; ?>
                <button class="start-btn">挑戦する</button>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    
    <!-- テストモード -->
    <div class="tab-content hidden" id="tab-test">
        <div class="sections-grid">
            <?php for ($i = 1; $i <= 4; $i++): 
                $testKey = "test_{$i}";
                $testProgress = $userProgress[$testKey . '_selection'] ?? null;
                $stageWords = [
                    1 => '1-600',
                    2 => '601-1200',
                    3 => '1201-1700',
                    4 => '1701-2027'
                ];
            ?>
            <div class="section-card test-section" onclick="startTestMode(<?= $i ?>)">
                <div class="section-number">🎯</div>
                <div class="section-title">ステージ <?= $i ?> テスト</div>
                <div class="section-info">単語 #<?= $stageWords[$i] ?> から全問出題</div>
                <?php if ($testProgress): ?>
                <div class="best-score">
                    ベストスコア: <?= number_format($testProgress['best_score'], 3) ?>点
                </div>
                <?php endif; ?>
                <button class="start-btn">テスト開始</button>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    
    <!-- 苦手克服モード -->
    <div class="tab-content hidden" id="tab-weak">
        <div class="section-card" style="max-width: 400px; margin: 0 auto;" onclick="startWeakMode()">
            <div class="section-number">⚡</div>
            <div class="section-title">苦手克服モード</div>
            <div class="section-info">過去に間違えた単語を集中的に練習</div>
            <button class="start-btn">苦手克服開始</button>
        </div>
    </div>
</div>

<!-- レベル選択モーダル -->
<div id="levelModal" class="level-modal hidden">
    <div class="level-modal-content">
        <div class="level-modal-title">📖 セクション <span id="modalSectionNum"></span></div>
        <div class="level-options">
            <button class="level-option-btn" id="selectionBtn" onclick="startGame('selection')">
                <span>🔘 選択式</span>
                <span class="level-option-desc">4択から正解を選ぶ</span>
            </button>
            <button class="level-option-btn" id="inputBtn" onclick="startGame('input')">
                <span>⌨️ 入力式</span>
                <span class="level-option-desc">キーボードで英語を入力</span>
            </button>
        </div>
        <button class="close-modal-btn" onclick="closeLevelModal()">キャンセル</button>
    </div>
</div>

<script>
let currentSection = 1;
let selectionUnlocked = true;
let inputUnlocked = false;

// タブ切り替え
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.remove('hidden');
    });
});

function openLevelModal(section, selectionCompleted, inputCompleted) {
    currentSection = section;
    selectionUnlocked = true;
    inputUnlocked = selectionCompleted;
    
    document.getElementById('modalSectionNum').textContent = section;
    document.getElementById('inputBtn').disabled = !inputUnlocked;
    document.getElementById('levelModal').classList.remove('hidden');
}

function closeLevelModal() {
    document.getElementById('levelModal').classList.add('hidden');
}

function startGame(level) {
    window.location.href = `word_master_game.php?section=${currentSection}&level=${level}`;
}

function startTestMode(stage) {
    window.location.href = `word_master_game.php?mode=test&stage=${stage}`;
}

function startWeakMode() {
    window.location.href = `word_master_game.php?mode=weak`;
}

// モーダル外クリックで閉じる
document.getElementById('levelModal').addEventListener('click', (e) => {
    if (e.target.id === 'levelModal') {
        closeLevelModal();
    }
});
</script>
</body>
</html>
