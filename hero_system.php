<?php
// ===============================================
// hero_system.php
// ヒーローシステム管理ページ
// ===============================================

require_once __DIR__ . '/config.php';

$me = user();
if (!$me) {
    header('Location: ./login.php');
    exit;
}

$pdo = db();

// ヒーロー一覧を取得
$stmt = $pdo->prepare("
    SELECT h.*, 
           COALESCE(uh.star_level, 0) as user_star_level,
           COALESCE(uh.shards, 0) as user_shards,
           COALESCE(uh.is_equipped, 0) as is_equipped,
           uh.unlocked_at
    FROM heroes h
    LEFT JOIN user_heroes uh ON h.id = uh.hero_id AND uh.user_id = ?
    WHERE h.generation = 0
    ORDER BY h.rarity DESC, h.id ASC
");
$stmt->execute([$me['id']]);
$heroes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// レアリティ色マップ
$RARITY_COLORS = [
    'common' => '#808080',
    'uncommon' => '#00cc00',
    'rare' => '#0080ff',
    'epic' => '#cc00ff',
    'legendary' => '#ffcc00'
];

$RARITY_NAMES = [
    'common' => 'コモン',
    'uncommon' => 'アンコモン',
    'rare' => 'レア',
    'epic' => 'エピック',
    'legendary' => 'レジェンダリー'
];
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ヒーローシステム - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
.hero-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.hero-header {
    background: linear-gradient(135deg, #ff6b6b 0%, #ffd93d 50%, #6bcb77 100%);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 8px 16px rgba(0,0,0,0.3);
}

.hero-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.currency-bar {
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
    background: rgba(0,0,0,0.2);
    padding: 8px 16px;
    border-radius: 20px;
}

.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    background: #2d2d44;
    color: #888;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s;
}

.tab-btn.active {
    background: linear-gradient(135deg, #ff6b6b 0%, #ffd93d 100%);
    color: #333;
    font-weight: bold;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* ヒーロー一覧 */
.hero-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.hero-card {
    background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%);
    border-radius: 16px;
    padding: 20px;
    position: relative;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.hero-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.4);
}

.hero-card.locked {
    opacity: 0.6;
}

.hero-card.unlocked {
    border: 2px solid gold;
}

.hero-icon {
    font-size: 64px;
    text-align: center;
    margin-bottom: 10px;
}

.hero-name {
    font-size: 20px;
    font-weight: bold;
    text-align: center;
    color: #fff;
}

.hero-title {
    font-size: 14px;
    text-align: center;
    color: var(--muted);
    margin-bottom: 10px;
}

.hero-rarity {
    text-align: center;
    font-size: 12px;
    font-weight: bold;
    padding: 4px 12px;
    border-radius: 12px;
    display: inline-block;
    margin: 5px auto;
}

.hero-stars {
    text-align: center;
    font-size: 20px;
    margin: 10px 0;
    color: #ffd700;
}

.hero-shards {
    text-align: center;
    font-size: 14px;
    color: var(--muted);
    margin-bottom: 15px;
}

.hero-shards-bar {
    background: rgba(0,0,0,0.3);
    border-radius: 10px;
    height: 10px;
    overflow: hidden;
    margin: 5px 0;
}

.hero-shards-fill {
    height: 100%;
    background: linear-gradient(90deg, #ffd700, #ffaa00);
    transition: width 0.5s;
}

.hero-skills {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.skill-item {
    margin-bottom: 10px;
}

.skill-name {
    font-weight: bold;
    color: #4ecdc4;
    font-size: 14px;
}

.skill-desc {
    font-size: 12px;
    color: var(--muted);
}

.hero-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.hero-actions button {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.unlock-btn {
    background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
    color: #333;
}

.unlock-btn:disabled {
    background: #444;
    color: #888;
    cursor: not-allowed;
}

.equip-btn {
    background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
    color: white;
}

/* ガチャセクション */
.gacha-section {
    background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%);
    border-radius: 16px;
    padding: 30px;
    text-align: center;
}

.gacha-options {
    display: flex;
    gap: 30px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 30px;
}

.gacha-option {
    background: linear-gradient(135deg, #2d2d44 0%, #3d3d5c 100%);
    border-radius: 16px;
    padding: 30px;
    width: 280px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.gacha-option:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.4);
}

.gacha-option h3 {
    margin: 0 0 15px 0;
    font-size: 24px;
}

.gacha-cost {
    font-size: 18px;
    margin-bottom: 20px;
}

.gacha-btn {
    width: 100%;
    padding: 15px;
    border: none;
    border-radius: 10px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.gacha-btn.normal {
    background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
    color: #333;
}

.gacha-btn.crystal {
    background: linear-gradient(135deg, #9d4edd 0%, #c77dff 100%);
    color: white;
}

.gacha-btn:disabled {
    background: #444;
    color: #888;
    cursor: not-allowed;
}

.gacha-rewards {
    margin-top: 15px;
    font-size: 12px;
    color: var(--muted);
    text-align: left;
}

/* ガチャ結果モーダル */
.gacha-modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.gacha-modal.hidden {
    display: none;
}

.gacha-result {
    background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%);
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    max-width: 400px;
    width: 90%;
    animation: gachaAppear 0.5s ease-out;
}

@keyframes gachaAppear {
    from {
        transform: scale(0.5);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.gacha-reward-icon {
    font-size: 80px;
    margin-bottom: 20px;
}

.gacha-reward-name {
    font-size: 24px;
    font-weight: bold;
    color: #ffd700;
    margin-bottom: 10px;
}

.gacha-reward-detail {
    font-size: 16px;
    color: var(--muted);
    margin-bottom: 20px;
}

/* 10連ガチャ結果 */
.gacha10-result {
    background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%);
    border-radius: 20px;
    padding: 30px;
    max-width: 800px;
    width: 95%;
    max-height: 80vh;
    overflow-y: auto;
    animation: gachaAppear 0.5s ease-out;
}

.gacha10-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.gacha10-item {
    background: rgba(0,0,0,0.3);
    border-radius: 12px;
    padding: 15px;
    text-align: center;
    border: 2px solid rgba(255,215,0,0.3);
    transition: transform 0.3s, box-shadow 0.3s;
}

.gacha10-item:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(255,215,0,0.3);
}

.gacha10-icon {
    font-size: 40px;
    margin-bottom: 10px;
}

.gacha10-name {
    font-weight: bold;
    color: #ffd700;
    font-size: 14px;
    margin-bottom: 5px;
}

.gacha10-detail {
    font-size: 12px;
    color: var(--muted);
}

.back-link {
    display: inline-block;
    color: var(--blue);
    text-decoration: none;
    margin-bottom: 20px;
}
</style>
</head>
<body>
<div class="hero-container">
    <a href="./" class="back-link">← フィードに戻る</a>
    
    <div class="hero-header">
        <h1>🦸 ヒーローシステム</h1>
        <p>ヒーローを集めてバトルと内政を有利に進めよう！</p>
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
    </div>
    
    <div class="tabs">
        <button class="tab-btn active" data-tab="heroes">🦸 ヒーロー一覧</button>
        <button class="tab-btn" data-tab="gacha">🎰 ガチャ</button>
    </div>
    
    <!-- ヒーロー一覧タブ -->
    <div class="tab-content active" id="tab-heroes">
        <div class="info-box" style="margin-bottom: 20px; background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 170, 0, 0.2) 100%); border: 2px solid #ffd700; border-radius: 10px; padding: 20px;">
            <h3 style="color: #ffd700; margin: 0 0 10px 0;">⭐ ヒーローのスターレベル効果</h3>
            <p style="color: #f5deb3; margin: 5px 0;">ヒーローの星を上げることで以下の効果が得られます：</p>
            <ul style="color: #f5deb3; margin: 10px 0; padding-left: 20px;">
                <li><strong>スキル発動率UP</strong>：基本15% + 星レベル×2%（最大8★で31%）</li>
                <li><strong>スキル効果UP</strong>：星レベル毎に+5%（8★で+35%効果増加）</li>
                <li><strong>攻撃力ボーナス</strong>：星レベル×5（8★で+40攻撃力）</li>
                <li><strong>防御力ボーナス</strong>：星レベル×3（8★で+24防御力）</li>
                <li><strong>体力ボーナス</strong>：星レベル×50（8★で+400体力）</li>
            </ul>
            <p style="color: #87ceeb; margin: 10px 0 0 0; font-size: 14px;">
                💡 スターアップには対応するヒーローの欠片が必要です。ガチャで欠片を集めましょう！
            </p>
        </div>
        <div class="hero-grid">
            <?php foreach ($heroes as $hero): 
                $isUnlocked = $hero['user_star_level'] > 0;
                $starLevel = $hero['user_star_level'];
                $shards = $hero['user_shards'];
                $unlockShards = $hero['unlock_shards'];
                $stars = str_repeat('⭐', $starLevel) . str_repeat('☆', max(0, 8 - $starLevel));
                $starUpShards = json_decode($hero['star_up_shards'], true) ?: [15, 25, 40, 60, 90, 130, 180];
                $nextStarShards = $starLevel > 0 && $starLevel < 8 ? $starUpShards[$starLevel - 1] : 0;
                $battleSkill = json_decode($hero['battle_skill_effect'], true) ?: [];
                $passiveSkill = json_decode($hero['passive_skill_effect'], true) ?: [];
                $rarityColor = $RARITY_COLORS[$hero['rarity']] ?? '#808080';
                $rarityName = $RARITY_NAMES[$hero['rarity']] ?? $hero['rarity'];
            ?>
            <div class="hero-card <?= $isUnlocked ? 'unlocked' : 'locked' ?>" data-hero-id="<?= $hero['id'] ?>">
                <div class="hero-icon"><?= $hero['icon'] ?></div>
                <div class="hero-name"><?= htmlspecialchars($hero['name']) ?></div>
                <div class="hero-title"><?= htmlspecialchars($hero['title']) ?></div>
                <div style="text-align: center;">
                    <span class="hero-rarity" style="background: <?= $rarityColor ?>; color: #fff;"><?= $rarityName ?></span>
                </div>
                
                <?php if ($isUnlocked): ?>
                <div class="hero-stars"><?= $stars ?></div>
                <?php else: ?>
                <div class="hero-stars" style="opacity: 0.3;">☆☆☆☆☆☆☆☆</div>
                <?php endif; ?>
                
                <div class="hero-shards">
                    欠片: <?= $shards ?> / <?= $isUnlocked ? ($starLevel < 8 ? $nextStarShards : 'MAX') : $unlockShards ?>
                </div>
                <div class="hero-shards-bar">
                    <?php 
                    $targetShards = $isUnlocked ? ($starLevel < 8 ? $nextStarShards : 1) : $unlockShards;
                    $progress = $targetShards > 0 ? min(100, ($shards / $targetShards) * 100) : 0;
                    ?>
                    <div class="hero-shards-fill" style="width: <?= $progress ?>%"></div>
                </div>
                
                <div class="hero-skills">
                    <div class="skill-item">
                        <div class="skill-name">⚔️ <?= htmlspecialchars($hero['battle_skill_name']) ?></div>
                        <div class="skill-desc"><?= htmlspecialchars($hero['battle_skill_desc']) ?></div>
                    </div>
                    <div class="skill-item">
                        <div class="skill-name">🏛️ <?= htmlspecialchars($hero['passive_skill_name']) ?></div>
                        <div class="skill-desc"><?= htmlspecialchars($hero['passive_skill_desc']) ?></div>
                    </div>
                    <?php if ($isUnlocked): ?>
                    <div class="skill-item" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.1);">
                        <div class="skill-name" style="color: #ffd700;">⭐ スターレベル効果 (★<?= $starLevel ?>)</div>
                        <div class="skill-desc" style="font-size: 11px;">
                            • スキル発動率: <?= 15 + $starLevel * 2 ?>%<br>
                            • スキル効果: +<?= ($starLevel - 1) * 5 ?>%<br>
                            • 攻撃力: +<?= $starLevel * 5 ?><br>
                            • 防御力: +<?= $starLevel * 3 ?><br>
                            • 体力: +<?= $starLevel * 50 ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="hero-actions">
                    <?php if (!$isUnlocked): ?>
                    <button class="unlock-btn" onclick="unlockHero(<?= $hero['id'] ?>)" <?= $shards < $unlockShards ? 'disabled' : '' ?>>
                        🔓 アンロック (<?= $unlockShards ?>欠片)
                    </button>
                    <?php elseif ($starLevel < 8): ?>
                    <button class="unlock-btn" onclick="starUpHero(<?= $hero['id'] ?>)" <?= $shards < $nextStarShards ? 'disabled' : '' ?>>
                        ⭐ スターアップ (<?= $nextStarShards ?>欠片)
                    </button>
                    <?php else: ?>
                    <button class="unlock-btn" disabled>MAX</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- ガチャタブ -->
    <div class="tab-content" id="tab-gacha">
        <div class="gacha-section">
            <h2>🎰 ヒーローガチャ</h2>
            <p>ガチャを回してヒーローの欠片や報酬をゲット！</p>
            
            <div class="gacha-options">
                <div class="gacha-option">
                    <h3>🪙 ノーマルガチャ</h3>
                    <div class="gacha-cost">1,000 コイン</div>
                    <button class="gacha-btn normal" onclick="pullGacha('normal')">1回ガチャを回す</button>
                    <div style="margin-top: 10px;">
                        <div class="gacha-cost" style="color: #ffd700;">10連: 9,000 コイン <span style="font-size: 12px; color: #48bb78;">(10%OFF!)</span></div>
                        <button class="gacha-btn normal" onclick="pullGacha10('normal')" style="margin-top: 5px;">🔥 10連ガチャ</button>
                    </div>
                    <div class="gacha-rewards">
                        <p>🎁 報酬内容:</p>
                        <ul>
                            <li>ヒーローの欠片 (1-3個)</li>
                            <li>経験値 (50-200)</li>
                            <li>コイン (100-500)</li>
                            <li>各種トークン</li>
                        </ul>
                    </div>
                </div>
                
                <div class="gacha-option">
                    <h3>💎 クリスタルガチャ</h3>
                    <div class="gacha-cost">100 クリスタル</div>
                    <button class="gacha-btn crystal" onclick="pullGacha('crystal')">1回ガチャを回す</button>
                    <div style="margin-top: 10px;">
                        <div class="gacha-cost" style="color: #c77dff;">10連: 900 クリスタル <span style="font-size: 12px; color: #48bb78;">(10%OFF!)</span></div>
                        <button class="gacha-btn crystal" onclick="pullGacha10('crystal')" style="margin-top: 5px;">🔥 10連ガチャ</button>
                    </div>
                    <div class="gacha-rewards">
                        <p>🎁 報酬内容 (確率UP!):</p>
                        <ul>
                            <li>ヒーローの欠片 (2-5個)</li>
                            <li>経験値 (100-500)</li>
                            <li>クリスタル (10-50)</li>
                            <li>レアトークン</li>
                            <li>稀に装備そのもの!</li>
                        </ul>
                    </div>
                </div>
                
                <div class="gacha-option">
                    <h3>💠 ダイヤモンドガチャ</h3>
                    <div class="gacha-cost" style="color: #00d9ff;">10 ダイヤモンド</div>
                    <button class="gacha-btn" onclick="pullGacha('diamond')" style="background: linear-gradient(135deg, #00d9ff 0%, #00b4d8 100%);">1回ガチャを回す</button>
                    <div style="margin-top: 10px;">
                        <div class="gacha-cost" style="color: #00d9ff;">10連: 90 ダイヤモンド <span style="font-size: 12px; color: #48bb78;">(10%OFF!)</span></div>
                        <button class="gacha-btn" onclick="pullGacha10('diamond')" style="margin-top: 5px; background: linear-gradient(135deg, #00d9ff 0%, #00b4d8 100%);">🔥 10連ガチャ</button>
                    </div>
                    <div class="gacha-rewards">
                        <p>🎁 報酬内容 (クリスタルガチャと同等!):</p>
                        <ul>
                            <li>ヒーローの欠片 (2-5個)</li>
                            <li>経験値 (100-500)</li>
                            <li>クリスタル (10-50)</li>
                            <li>レアトークン</li>
                            <li>稀に装備そのもの!</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ガチャ結果モーダル -->
<div id="gachaModal" class="gacha-modal hidden">
    <div class="gacha-result">
        <div class="gacha-reward-icon" id="gachaRewardIcon">🎁</div>
        <div class="gacha-reward-name" id="gachaRewardName">報酬を獲得!</div>
        <div class="gacha-reward-detail" id="gachaRewardDetail"></div>
        <button onclick="closeGachaModal()" style="padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer;">OK</button>
    </div>
</div>

<!-- 10連ガチャ結果モーダル -->
<div id="gacha10Modal" class="gacha-modal hidden">
    <div class="gacha10-result">
        <h2 style="margin: 0 0 20px 0; color: #ffd700; text-align: center;">🔥 10連ガチャ結果 🔥</h2>
        <div class="gacha10-grid" id="gacha10Content"></div>
        <button onclick="closeGacha10Modal()" style="display: block; margin: 20px auto 0; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer;">OK</button>
    </div>
</div>

<script>
// タブ切り替え
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
});

// ガチャを回す
async function pullGacha(type) {
    try {
        const res = await fetch('hero_gacha_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'pull', type: type})
        });
        const data = await res.json();
        
        if (data.ok) {
            showGachaResult(data.reward);
            document.getElementById('userCoins').textContent = data.balance.coins.toLocaleString();
            document.getElementById('userCrystals').textContent = data.balance.crystals.toLocaleString();
        } else {
            alert('エラー: ' + (data.error || '不明なエラー'));
        }
    } catch (e) {
        alert('ネットワークエラー');
    }
}

// 10連ガチャを回す
async function pullGacha10(type) {
    const costText = type === 'normal' ? '9,000 コイン' : '900 クリスタル';
    if (!confirm(`10連ガチャを回しますか？\n\n必要: ${costText}`)) {
        return;
    }
    
    try {
        const res = await fetch('hero_gacha_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'pull_10', type: type})
        });
        const data = await res.json();
        
        if (data.ok) {
            showGacha10Result(data.rewards);
            document.getElementById('userCoins').textContent = data.balance.coins.toLocaleString();
            document.getElementById('userCrystals').textContent = data.balance.crystals.toLocaleString();
        } else {
            alert('エラー: ' + (data.error || '不明なエラー'));
        }
    } catch (e) {
        alert('ネットワークエラー');
    }
}

function showGachaResult(reward) {
    const icons = {
        'hero_shards': '🦸',
        'exp': '⭐',
        'coins': '🪙',
        'crystals': '💎',
        'tokens': '🎫',
        'equipment': '⚔️'
    };
    
    document.getElementById('gachaRewardIcon').textContent = icons[reward.type] || '🎁';
    document.getElementById('gachaRewardName').textContent = reward.name;
    document.getElementById('gachaRewardDetail').textContent = reward.detail;
    document.getElementById('gachaModal').classList.remove('hidden');
}

// 10連ガチャ結果を表示
function showGacha10Result(rewards) {
    const icons = {
        'hero_shards': '🦸',
        'exp': '⭐',
        'coins': '🪙',
        'crystals': '💎',
        'tokens': '🎫',
        'equipment': '⚔️'
    };
    
    const rewardsHtml = rewards.map((reward, index) => `
        <div class="gacha10-item">
            <div class="gacha10-icon">${icons[reward.type] || '🎁'}</div>
            <div class="gacha10-name">${reward.name}</div>
            <div class="gacha10-detail">${reward.detail}</div>
        </div>
    `).join('');
    
    document.getElementById('gacha10Content').innerHTML = rewardsHtml;
    document.getElementById('gacha10Modal').classList.remove('hidden');
}

function closeGachaModal() {
    document.getElementById('gachaModal').classList.add('hidden');
    // ガチャタブに留まったまま次のガチャを回せるように、リロードしない
}

function closeGacha10Modal() {
    document.getElementById('gacha10Modal').classList.add('hidden');
    // ガチャタブに留まったまま次のガチャを回せるように、リロードしない
}

// ヒーローアンロック
async function unlockHero(heroId) {
    if (!confirm('このヒーローをアンロックしますか？')) return;
    
    try {
        const res = await fetch('hero_gacha_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'unlock', hero_id: heroId})
        });
        const data = await res.json();
        
        if (data.ok) {
            alert('ヒーローをアンロックしました！');
            location.reload();
        } else {
            alert('エラー: ' + (data.error || '不明なエラー'));
        }
    } catch (e) {
        alert('ネットワークエラー');
    }
}

// スターアップ
async function starUpHero(heroId) {
    if (!confirm('このヒーローをスターアップしますか？')) return;
    
    try {
        const res = await fetch('hero_gacha_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'star_up', hero_id: heroId})
        });
        const data = await res.json();
        
        if (data.ok) {
            alert(`スターアップ成功！ ⭐${data.new_star_level}`);
            location.reload();
        } else {
            alert('エラー: ' + (data.error || '不明なエラー'));
        }
    } catch (e) {
        alert('ネットワークエラー');
    }
}
</script>
</body>
</html>
