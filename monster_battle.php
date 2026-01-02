<?php
// ===============================================
// monster_battle.php
// 放浪モンスター＆ワールドボスシステム（フロントエンド）
// ===============================================

require_once __DIR__ . '/config.php';

$me = user();
if (!$me) {
    header('Location: ./login.php');
    exit;
}

$pdo = db();
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>モンスター討伐 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
* {
    box-sizing: border-box;
}

body {
    background: linear-gradient(180deg, #0a0a1a 0%, #1a1030 50%, #0a0a1a 100%);
    min-height: 100vh;
    margin: 0;
    color: #e0d0f0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.monster-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 20px;
    background: rgba(255,255,255,0.1);
    color: #e0d0f0;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}

.back-link:hover {
    background: #9932cc;
    color: #fff;
}

/* ヘッダー */
.monster-header {
    background: linear-gradient(135deg, rgba(139, 0, 0, 0.8) 0%, rgba(75, 0, 130, 0.8) 100%);
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
    border: 2px solid #dc143c;
    text-align: center;
}

.monster-title {
    font-size: 32px;
    font-weight: bold;
    color: #ffd700;
    text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
    margin-bottom: 10px;
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
    border: 2px solid #4b0082;
    border-radius: 10px;
    background: rgba(0,0,0,0.3);
    color: #a090c0;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s;
}

.tab-btn.active {
    background: linear-gradient(135deg, #dc143c 0%, #ff6b6b 100%);
    color: #fff;
    border-color: #ffd700;
}

/* フィルターボタン */
.filter-btn {
    padding: 8px 16px;
    border: 2px solid #4b0082;
    border-radius: 8px;
    background: rgba(0,0,0,0.3);
    color: #a090c0;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.filter-btn:hover {
    background: rgba(139, 0, 0, 0.5);
    border-color: #dc143c;
}

.filter-btn.active {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #000;
    border-color: #ffd700;
    font-weight: bold;
}

/* ベテランラベル */
.veteran-label {
    display: inline-block;
    background: linear-gradient(135deg, #ff6b6b 0%, #dc143c 100%);
    color: #fff;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
    margin-top: 5px;
    box-shadow: 0 2px 8px rgba(220, 20, 60, 0.4);
    border: 1px solid #ffd700;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* モンスターカード */
.monster-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.monster-card {
    background: linear-gradient(135deg, rgba(50, 30, 80, 0.9) 0%, rgba(30, 20, 50, 0.9) 100%);
    border-radius: 12px;
    border: 2px solid #4b0082;
    padding: 20px;
    transition: all 0.3s;
    cursor: pointer;
}

.monster-card:hover {
    transform: translateY(-5px);
    border-color: #ffd700;
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.4);
}

.monster-card.boss {
    border-color: #dc143c;
    background: linear-gradient(135deg, rgba(139, 0, 0, 0.5) 0%, rgba(50, 30, 80, 0.9) 100%);
}

.monster-icon {
    font-size: 48px;
    text-align: center;
    margin-bottom: 10px;
}

.monster-name {
    font-size: 18px;
    font-weight: bold;
    color: #ffd700;
    text-align: center;
    margin-bottom: 5px;
}

.monster-level {
    font-size: 12px;
    color: #a090c0;
    text-align: center;
    margin-bottom: 15px;
}

.monster-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 5px;
    text-align: center;
    font-size: 12px;
}

.stat-item {
    background: rgba(0,0,0,0.3);
    padding: 5px;
    border-radius: 5px;
}

.stat-value {
    color: #ffd700;
    font-weight: bold;
}

/* バトルモーダル */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    display: none;
}

.modal-overlay.active {
    display: flex;
}

.modal-content {
    background: linear-gradient(135deg, #1a1030 0%, #0a0a1a 100%);
    border-radius: 16px;
    padding: 30px;
    max-width: 700px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    border: 2px solid #dc143c;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-title {
    font-size: 24px;
    font-weight: bold;
    color: #ffd700;
}

.modal-close {
    background: none;
    border: none;
    color: #a090c0;
    font-size: 24px;
    cursor: pointer;
}

/* HPバー */
.hp-bar-container {
    margin: 20px 0;
}

.hp-bar-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.hp-bar {
    height: 20px;
    background: rgba(0,0,0,0.5);
    border-radius: 10px;
    overflow: hidden;
}

.hp-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #32cd32 0%, #228b22 100%);
    transition: width 0.3s;
}

.hp-bar-fill.danger {
    background: linear-gradient(90deg, #ff6b6b 0%, #dc143c 100%);
}

/* 部隊選択 */
.troop-selector {
    display: grid;
    gap: 10px;
    margin-top: 15px;
}

.troop-select-row {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.05);
    padding: 10px;
    border-radius: 8px;
}

.troop-info {
    flex: 1;
}

.troop-icon {
    font-size: 20px;
}

.troop-name {
    color: #e0d0f0;
    font-weight: bold;
}

.troop-stats {
    font-size: 11px;
    color: #a090c0;
}

.troop-count-input {
    width: 80px;
    padding: 8px;
    background: rgba(0,0,0,0.3);
    border: 1px solid #4b0082;
    border-radius: 6px;
    color: #e0d0f0;
    text-align: center;
}

.troop-slider {
    width: 100px;
    -webkit-appearance: none;
    height: 8px;
    border-radius: 4px;
    background: #4b0082;
}

.troop-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #dc143c;
    cursor: pointer;
}

.troop-available {
    font-size: 11px;
    color: #888;
    min-width: 60px;
    text-align: right;
}

/* ボタン */
.action-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 14px;
}

.attack-btn {
    background: linear-gradient(135deg, #dc143c 0%, #ff6b6b 100%);
    color: #fff;
}

.summon-btn {
    background: linear-gradient(135deg, #ffd700 0%, #ffa500 100%);
    color: #1a1030;
}

.retreat-btn {
    background: linear-gradient(135deg, #808080 0%, #a0a0a0 100%);
    color: #fff;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
}

.action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* ランキング */
.ranking-table {
    width: 100%;
    border-collapse: collapse;
}

.ranking-table th,
.ranking-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #4b0082;
}

.ranking-table th {
    color: #dc143c;
    font-weight: bold;
}

.ranking-table tr:hover {
    background: rgba(255,255,255,0.05);
}

.rank-1 { color: #ffd700; font-weight: bold; }
.rank-2 { color: #c0c0c0; font-weight: bold; }
.rank-3 { color: #cd7f32; font-weight: bold; }

/* 報酬表示 */
.reward-display {
    background: rgba(255, 215, 0, 0.1);
    border: 1px solid #ffd700;
    border-radius: 10px;
    padding: 15px;
    margin-top: 15px;
}

.reward-item {
    display: inline-block;
    margin: 5px 10px;
    padding: 5px 10px;
    background: rgba(0,0,0,0.3);
    border-radius: 5px;
}

/* 通知 */
.notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: linear-gradient(135deg, #32cd32 0%, #228b22 100%);
    color: #fff;
    padding: 15px 25px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
    z-index: 1001;
    animation: slideIn 0.3s ease-out;
}

.notification.error {
    background: linear-gradient(135deg, #dc143c 0%, #8b0000 100%);
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* ローディング */
.loading {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 50px;
    color: #a090c0;
}

.loading::after {
    content: '';
    width: 40px;
    height: 40px;
    border: 4px solid #4b0082;
    border-top-color: #dc143c;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 15px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* アクティブボス表示 */
.active-boss-section {
    background: linear-gradient(135deg, rgba(139, 0, 0, 0.3) 0%, rgba(75, 0, 130, 0.3) 100%);
    border: 2px solid #dc143c;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.active-boss-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.timer {
    font-size: 18px;
    color: #ff6b6b;
    font-weight: bold;
}

/* バトルログ表示 */
.battle-log-container {
    background: rgba(0, 0, 0, 0.5);
    border-radius: 10px;
    padding: 15px;
    max-height: 400px;
    overflow-y: auto;
    font-size: 13px;
    line-height: 1.6;
}

.battle-turn {
    background: rgba(75, 0, 130, 0.3);
    border-left: 3px solid #9932cc;
    padding: 10px 15px;
    margin-bottom: 10px;
    border-radius: 0 8px 8px 0;
}

.battle-turn-header {
    font-weight: bold;
    color: #ffd700;
    margin-bottom: 5px;
}

.battle-turn-message {
    color: #e0d0f0;
    white-space: pre-wrap;
}

.battle-turn-hp {
    display: flex;
    gap: 20px;
    margin-top: 8px;
    font-size: 12px;
}

.battle-turn-hp .attacker-hp {
    color: #32cd32;
}

.battle-turn-hp .defender-hp {
    color: #ff6b6b;
}

.battle-summary {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 165, 0, 0.2) 100%);
    border: 1px solid #ffd700;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
    text-align: center;
}

.battle-summary-title {
    font-size: 18px;
    font-weight: bold;
    color: #ffd700;
    margin-bottom: 10px;
}

.battle-summary-stats {
    display: flex;
    justify-content: center;
    gap: 30px;
    flex-wrap: wrap;
}

.battle-summary-stat {
    text-align: center;
}

.battle-summary-stat-label {
    font-size: 11px;
    color: #a090c0;
}

.battle-summary-stat-value {
    font-size: 16px;
    font-weight: bold;
    color: #e0d0f0;
}

.view-log-btn {
    background: linear-gradient(135deg, #4b0082 0%, #9932cc 100%);
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    margin-top: 10px;
    transition: all 0.3s;
}

.view-log-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(153, 50, 204, 0.4);
}
</style>
</head>
<body>
<div class="monster-container">
    <a href="./civilization.php" class="back-link">← 文明育成に戻る</a>
    
    <div class="monster-header">
        <div class="monster-title">⚔️ モンスター討伐</div>
        <p style="color: #c0a0d0;">放浪モンスターを倒してコイン・クリスタル・資源・兵士を獲得しよう！</p>
    </div>
    
    <div class="tabs">
        <button class="tab-btn active" data-tab="wandering">🐺 放浪モンスター</button>
        <button class="tab-btn" data-tab="worldboss">🐉 ワールドボス</button>
        <button class="tab-btn" data-tab="history">📜 討伐履歴</button>
    </div>
    
    <div class="tab-content active" id="tab-wandering">
        <div id="activeEncounter"></div>
        <h3 style="color: #ffd700; margin-bottom: 15px;">遭遇可能なモンスター</h3>
        <div id="monsterList" class="monster-grid">
            <div class="loading">読み込み中...</div>
        </div>
    </div>
    
    <div class="tab-content" id="tab-worldboss">
        <div id="activeBosses"></div>
        <h3 style="color: #ffd700; margin-bottom: 15px;">召喚可能なワールドボス</h3>
        
        <!-- フィルターボタン -->
        <div class="boss-filter-buttons" style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
            <button class="filter-btn active" data-filter="all" onclick="filterWorldBosses('all')">
                🐉 すべて
            </button>
            <button class="filter-btn" data-filter="veteran" onclick="filterWorldBosses('veteran')">
                💪 ベテランのみ
            </button>
            <button class="filter-btn" data-filter="normal" onclick="filterWorldBosses('normal')">
                📋 ベテラン以外
            </button>
        </div>
        
        <div id="bossList" class="monster-grid">
            <div class="loading">読み込み中...</div>
        </div>
    </div>
    
    <div class="tab-content" id="tab-history">
        <div id="battleHistory">
            <div class="loading">読み込み中...</div>
        </div>
    </div>
</div>

<!-- バトルモーダル -->
<div class="modal-overlay" id="battleModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="battleModalTitle">バトル</h3>
            <button class="modal-close" onclick="closeBattleModal()">×</button>
        </div>
        <div id="battleModalContent">
            <div class="loading">読み込み中...</div>
        </div>
    </div>
</div>

<!-- ワールドボス詳細モーダル -->
<div class="modal-overlay" id="bossDetailModal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3 class="modal-title" id="bossDetailTitle">ワールドボス</h3>
            <button class="modal-close" onclick="closeBossDetailModal()">×</button>
        </div>
        <div id="bossDetailContent">
            <div class="loading">読み込み中...</div>
        </div>
    </div>
</div>

<!-- バトルログモーダル -->
<div class="modal-overlay" id="battleLogModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 class="modal-title">📜 バトルログ</h3>
            <button class="modal-close" onclick="closeBattleLogModal()">×</button>
        </div>
        <div id="battleLogContent">
            <div class="loading">読み込み中...</div>
        </div>
    </div>
</div>

<script>
let userTroops = [];
let currentTab = 'wandering';
let activeEncounter = null;
let lastBattleTurnLogs = [];  // 最後のバトルログを保存
let deploymentLimit = { base_limit: 100, building_bonus: 0, total_limit: 100 }; // 出撃上限
let currentMonsterPower = 0; // 現在のモンスター戦力
let currentBossPower = 0; // 現在のボス戦力
let currentBossFilter = 'all'; // ワールドボスフィルター（all, veteran, normal）

// ② ステルス判定ヘルパー関数
function isStealthUnit(troop) {
    return troop.is_stealth === true || troop.is_stealth === 1 || troop.is_stealth === '1';
}

// ② 核ユニット判定ヘルパー関数
function isNuclearUnit(troop) {
    return troop.troop_key && (
        troop.troop_key.includes('nuclear') || 
        (troop.name && (troop.name.includes('原子力') || troop.name.includes('核')))
    );
}

// ② 使い捨てユニット判定ヘルパー関数
function isDisposableUnit(troop) {
    return troop.is_disposable === true || troop.is_disposable === 1 || troop.is_disposable === '1';
}

// ② 出撃画面用のラベルHTMLを生成
function getTroopLabelsHtml(troop) {
    let labels = '';
    if (isNuclearUnit(troop)) {
        labels += `<span style="background: rgba(50, 205, 50, 0.5); padding: 1px 4px; border-radius: 3px; font-size: 9px; margin-left: 3px;">☢️核</span>`;
    }
    if (isStealthUnit(troop)) {
        labels += `<span style="background: rgba(128, 0, 128, 0.5); padding: 1px 4px; border-radius: 3px; font-size: 9px; margin-left: 3px;">👻隠密</span>`;
    }
    if (isDisposableUnit(troop)) {
        labels += `<span style="background: rgba(255, 69, 0, 0.5); padding: 1px 4px; border-radius: 3px; font-size: 9px; margin-left: 3px;">💀捨</span>`;
    }
    return labels;
}

// 初期データ読み込み
async function loadData() {
    await Promise.all([
        loadUserTroops(),
        loadMonsters(),
        loadActiveEncounter(),
        loadWorldBosses()
    ]);
}

// ユーザーの兵士を取得
async function loadUserTroops() {
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_troops'})
        });
        const data = await res.json();
        if (data.ok) {
            userTroops = data.user_troops || [];
            // 出撃上限を保存
            if (data.deployment_limit) {
                deploymentLimit = data.deployment_limit;
            }
        }
    } catch (e) {
        console.error(e);
    }
}

// 放浪モンスター一覧を取得
async function loadMonsters() {
    try {
        const res = await fetch('wandering_monster_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_monsters'})
        });
        const data = await res.json();
        
        if (data.ok) {
            renderMonsterList(data.monsters || [], data.user_level);
        } else {
            console.error('loadMonsters error:', data.error);
            document.getElementById('monsterList').innerHTML = '<p style="color: #888;">モンスターデータの読み込みに失敗しました: ' + escapeHtml(data.error || '不明なエラー') + '</p>';
        }
    } catch (e) {
        console.error(e);
        document.getElementById('monsterList').innerHTML = '<div class="loading">エラーが発生しました</div>';
    }
}

// アクティブな遭遇を取得
async function loadActiveEncounter() {
    try {
        const res = await fetch('wandering_monster_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_active_encounter'})
        });
        const data = await res.json();
        
        if (data.ok && data.has_encounter) {
            activeEncounter = data.encounter;
            renderActiveEncounter(data.encounter);
        } else {
            activeEncounter = null;
            document.getElementById('activeEncounter').innerHTML = '';
        }
    } catch (e) {
        console.error(e);
    }
}

// ワールドボス一覧を取得
async function loadWorldBosses() {
    try {
        const filterParam = currentBossFilter === 'all' ? {} : { filter_label: currentBossFilter };
        const res = await fetch('world_boss_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_bosses', ...filterParam})
        });
        const data = await res.json();
        
        if (data.ok) {
            renderActiveBosses(data.active_instances || []);
            renderBossList(data.bosses || [], data.user_level);
        } else {
            console.error('loadWorldBosses error:', data.error);
            document.getElementById('activeBosses').innerHTML = '<p style="color: #888;">アクティブボスの読み込みに失敗しました</p>';
            document.getElementById('bossList').innerHTML = '<p style="color: #888;">ボスデータの読み込みに失敗しました: ' + escapeHtml(data.error || '不明なエラー') + '</p>';
        }
    } catch (e) {
        console.error(e);
        document.getElementById('activeBosses').innerHTML = '<p style="color: #888;">エラーが発生しました</p>';
        document.getElementById('bossList').innerHTML = '<div class="loading">エラーが発生しました</div>';
    }
}

// モンスター一覧を描画
function renderMonsterList(monsters, userLevel) {
    if (monsters.length === 0) {
        document.getElementById('monsterList').innerHTML = '<p style="color: #888;">現在遭遇できるモンスターがいません</p>';
        return;
    }
    
    document.getElementById('monsterList').innerHTML = monsters.map(m => `
        <div class="monster-card" onclick="encounterMonster(${m.id})">
            <div class="monster-icon">${m.icon}</div>
            <div class="monster-name">${escapeHtml(m.name)}</div>
            <div class="monster-level">Lv.${m.monster_level} (適正Lv.${m.min_level}-${m.max_level})</div>
            <div class="monster-stats">
                <div class="stat-item">
                    <div>⚔️ 攻撃</div>
                    <div class="stat-value">${m.scaled_attack}</div>
                </div>
                <div class="stat-item">
                    <div>🛡️ 防御</div>
                    <div class="stat-value">${m.scaled_defense}</div>
                </div>
                <div class="stat-item">
                    <div>❤️ HP</div>
                    <div class="stat-value">${m.scaled_health}</div>
                </div>
            </div>
            <div style="margin-top: 10px; text-align: center; font-size: 11px; color: #888;">
                💰 ${m.reward_coins_min}~${m.reward_coins_max} 💠 ${m.reward_diamonds_min}~${m.reward_diamonds_max}
            </div>
        </div>
    `).join('');
}

// アクティブな遭遇を描画
function renderActiveEncounter(encounter) {
    const hpPercent = Math.round((encounter.current_health / encounter.max_health) * 100);
    const hpClass = hpPercent < 30 ? 'danger' : '';
    
    document.getElementById('activeEncounter').innerHTML = `
        <div class="active-boss-section" style="border-color: #ffa500;">
            <div class="active-boss-header">
                <div>
                    <span style="font-size: 32px;">${encounter.icon}</span>
                    <span style="font-size: 20px; font-weight: bold; color: #ffd700; margin-left: 10px;">${escapeHtml(encounter.name)}</span>
                    <span style="color: #888; margin-left: 10px;">Lv.${encounter.monster_level}</span>
                </div>
                <button class="action-btn attack-btn" onclick="openBattleModal(${encounter.id})">⚔️ 攻撃</button>
            </div>
            <div class="hp-bar-container">
                <div class="hp-bar-label">
                    <span>❤️ HP</span>
                    <span>${encounter.current_health} / ${encounter.max_health}</span>
                </div>
                <div class="hp-bar">
                    <div class="hp-bar-fill ${hpClass}" style="width: ${hpPercent}%;"></div>
                </div>
            </div>
            <div style="text-align: center; margin-top: 10px;">
                <button class="action-btn retreat-btn" onclick="retreatFromEncounter(${encounter.id})">🏃 撤退</button>
            </div>
        </div>
    `;
}

// アクティブなワールドボスを描画
function renderActiveBosses(instances) {
    if (!instances || instances.length === 0) {
        document.getElementById('activeBosses').innerHTML = '<p style="color: #888; text-align: center; margin-bottom: 20px;">現在アクティブなワールドボスはいません</p>';
        return;
    }
    
    document.getElementById('activeBosses').innerHTML = instances.map(inst => {
        const hpPercent = inst.max_health > 0 ? Math.round((inst.current_health / inst.max_health) * 100) : 0;
        const hpClass = hpPercent < 30 ? 'danger' : '';
        const remaining = formatTime(inst.seconds_remaining);
        const isVeteran = inst.labels && inst.labels.includes('ベテラン');
        const veteranLabel = isVeteran ? '<span class="veteran-label" style="margin-left: 10px; font-size: 11px;">💪 ベテラン</span>' : '';
        
        return `
            <div class="active-boss-section">
                <div class="active-boss-header">
                    <div>
                        <span style="font-size: 32px;">${inst.boss_icon}</span>
                        <span style="font-size: 20px; font-weight: bold; color: #ffd700; margin-left: 10px;">${escapeHtml(inst.boss_name)}</span>
                        ${veteranLabel}
                        <span style="color: #888; margin-left: 10px;">Lv.${inst.boss_level}</span>
                    </div>
                    <div class="timer">⏰ 残り ${remaining}</div>
                </div>
                <div class="hp-bar-container">
                    <div class="hp-bar-label">
                        <span>❤️ HP</span>
                        <span>${formatNumber(inst.current_health)} / ${formatNumber(inst.max_health)} (${hpPercent}%)</span>
                    </div>
                    <div class="hp-bar">
                        <div class="hp-bar-fill ${hpClass}" style="width: ${hpPercent}%;"></div>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 15px;">
                    <button class="action-btn attack-btn" onclick="openBossDetailModal(${inst.id})" style="font-size: 16px; padding: 15px 30px;">
                        ⚔️ 参戦する
                    </button>
                </div>
                <div style="text-align: center; margin-top: 10px; color: #888; font-size: 12px;">
                    召喚者: @${escapeHtml(inst.summoner_handle)}
                </div>
            </div>
        `;
    }).join('');
}

// ワールドボス一覧を描画
function renderBossList(bosses, userLevel) {
    if (bosses.length === 0) {
        document.getElementById('bossList').innerHTML = '<p style="color: #888;">現在召喚できるボスがいません</p>';
        return;
    }
    
    document.getElementById('bossList').innerHTML = bosses.map(b => {
        const canSummon = userLevel >= b.min_user_level;
        const isVeteran = b.labels && b.labels.includes('ベテラン');
        const veteranLabel = isVeteran ? '<div class="veteran-label">💪 ベテラン</div>' : '';
        
        return `
            <div class="monster-card boss ${canSummon ? '' : 'disabled'}" onclick="${canSummon ? `summonBoss(${b.id})` : ''}">
                <div class="monster-icon">${b.icon}</div>
                <div class="monster-name">${escapeHtml(b.name)}</div>
                ${veteranLabel}
                <div class="monster-level">必要レベル: ${b.min_user_level}</div>
                <div class="monster-stats">
                    <div class="stat-item">
                        <div>⚔️ 攻撃</div>
                        <div class="stat-value">${formatNumber(b.base_attack)}</div>
                    </div>
                    <div class="stat-item">
                        <div>🛡️ 防御</div>
                        <div class="stat-value">${formatNumber(b.base_defense)}</div>
                    </div>
                    <div class="stat-item">
                        <div>❤️ HP</div>
                        <div class="stat-value">${formatNumber(b.base_health)}</div>
                    </div>
                </div>
                <div style="margin-top: 10px; text-align: center;">
                    <button class="action-btn summon-btn" ${canSummon ? '' : 'disabled'}>
                        💠 ${b.summon_cost_diamonds} で召喚
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// ワールドボスのフィルターを変更
function filterWorldBosses(filter) {
    currentBossFilter = filter;
    
    // フィルターボタンのアクティブ状態を更新
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`.filter-btn[data-filter="${filter}"]`).classList.add('active');
    
    // ボス一覧を再読み込み
    loadWorldBosses();
}

// モンスターに遭遇
async function encounterMonster(monsterId) {
    if (activeEncounter) {
        showNotification('すでにモンスターと遭遇中です', true);
        return;
    }
    
    try {
        const res = await fetch('wandering_monster_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'encounter_monster', monster_id: monsterId})
        });
        const data = await res.json();
        
        if (data.ok) {
            showNotification(data.message);
            await loadActiveEncounter();
            openBattleModal(data.encounter_id);
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// バトルモーダルを開く
async function openBattleModal(encounterId) {
    document.getElementById('battleModal').classList.add('active');
    document.getElementById('battleModalContent').innerHTML = '<div class="loading">読み込み中...</div>';
    
    try {
        const res = await fetch('wandering_monster_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_active_encounter'})
        });
        const data = await res.json();
        
        if (data.ok && data.has_encounter) {
            renderBattleModal(data.encounter);
        } else {
            closeBattleModal();
            showNotification('遭遇が見つかりません', true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// ユーザーの総戦力を計算
function calculateUserPower() {
    let totalPower = 0;
    for (const troop of userTroops) {
        if (troop.count > 0) {
            const attackPower = parseInt(troop.attack_power) || 0;
            const defensePower = parseInt(troop.defense_power) || 0;
            const healthPoints = parseInt(troop.health_points) || 100;
            // 戦力計算: (攻撃力 + 防御力/2 + 体力/20) × 兵数
            const unitPower = attackPower + Math.floor(defensePower / 2) + Math.floor(healthPoints / 20);
            totalPower += unitPower * parseInt(troop.count);
        }
    }
    return totalPower;
}

// バトルモーダルを描画
function renderBattleModal(encounter) {
    const hpPercent = Math.round((encounter.current_health / encounter.max_health) * 100);
    const hpClass = hpPercent < 30 ? 'danger' : '';
    
    // モンスター戦力を保存
    const monsterPower = (parseInt(encounter.scaled_attack || encounter.attack_power) || 0) + 
                         Math.floor((parseInt(encounter.scaled_defense || encounter.defense_power) || 0) / 2);
    currentMonsterPower = monsterPower;
    
    document.getElementById('battleModalTitle').textContent = `${encounter.icon} ${encounter.name} Lv.${encounter.monster_level}`;
    document.getElementById('battleModalContent').innerHTML = `
        <div class="hp-bar-container">
            <div class="hp-bar-label">
                <span>❤️ モンスターHP</span>
                <span>${encounter.current_health} / ${encounter.max_health}</span>
            </div>
            <div class="hp-bar">
                <div class="hp-bar-fill ${hpClass}" style="width: ${hpPercent}%;"></div>
            </div>
        </div>
        
        <div id="monsterAdvantageDisplay"></div>
        
        <div id="monsterPowerComparison" data-monster-power="${monsterPower}" style="display: flex; justify-content: space-between; margin-bottom: 15px; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 8px;">
            <div style="text-align: center;">
                <div style="color: #888; font-size: 11px;">選択した戦力</div>
                <div style="color: #32cd32; font-weight: bold; font-size: 18px;">⚔️ <span id="monsterMyPower">0</span></div>
            </div>
            <div style="align-self: center; color: #888;">VS</div>
            <div style="text-align: center;">
                <div style="color: #888; font-size: 11px;">モンスター戦力</div>
                <div style="color: #ff6b6b; font-weight: bold; font-size: 18px;">👹 ${monsterPower}</div>
            </div>
        </div>
        
        <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 10px; margin: 15px 0;">
            <h4 style="color: #ffd700; margin: 0 0 10px 0;">⚔️ 攻撃部隊を選択</h4>
            <div class="troop-selector" id="attackTroopSelector">
                ${renderTroopSelector()}
            </div>
        </div>
        
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button class="action-btn attack-btn" onclick="attackMonster(${encounter.id})">⚔️ 攻撃する</button>
            <button class="action-btn retreat-btn" onclick="retreatFromEncounter(${encounter.id}); closeBattleModal();">🏃 撤退</button>
        </div>
    `;
    
    setupTroopSliders();
}

// 部隊選択UIを描画
function renderTroopSelector() {
    if (userTroops.length === 0 || userTroops.filter(t => t.count > 0).length === 0) {
        return '<p style="color: #888;">使用できる兵士がいません</p>';
    }
    
    return `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 6px;">
            <div style="color: #da70d6; font-size: 12px;">出撃兵数: <span id="monster-troop-count" style="color: #32cd32;">0</span>/${deploymentLimit.total_limit}人</div>
            <button type="button" onclick="selectMaxByStrongest('monster')" style="padding: 4px 10px; font-size: 11px; background: linear-gradient(135deg, #dc143c 0%, #ff6b6b 100%); color: #fff; border: none; border-radius: 4px; cursor: pointer;">💪 強い順に一括選択</button>
        </div>
    ` + userTroops.filter(t => t.count > 0).map(troop => `
        <div class="troop-select-row">
            <div class="troop-info">
                <span class="troop-icon">${troop.icon}</span>
                <span class="troop-name">${troop.name}${getTroopLabelsHtml(troop)}</span>
                <div class="troop-stats">⚔️${troop.attack_power} 🛡️${troop.defense_power}</div>
            </div>
            <input type="range" class="troop-slider" 
                   id="attack-slider-${troop.troop_type_id}"
                   min="0" max="${troop.count}" value="0"
                   data-troop-id="${troop.troop_type_id}"
                   data-attack="${troop.attack_power}"
                   data-defense="${troop.defense_power}">
            <input type="number" class="troop-count-input" 
                   id="attack-count-${troop.troop_type_id}"
                   min="0" max="${troop.count}" value="0"
                   data-troop-id="${troop.troop_type_id}">
            <span class="troop-available">/ ${troop.count}</span>
        </div>
    `).join('');
}

// 強い順に一括選択
function selectMaxByStrongest(type) {
    const prefix = type === 'monster' ? 'attack' : 'boss';
    const limit = type === 'boss' ? 1000 : deploymentLimit.total_limit; // ワールドボスは1000固定
    
    // まずすべてをリセット
    document.querySelectorAll(`[id^="${prefix}-count-"]`).forEach(input => {
        input.value = 0;
        const troopId = input.dataset.troopId;
        const slider = document.getElementById(`${prefix}-slider-${troopId}`);
        if (slider) slider.value = 0;
    });
    
    // 兵種を攻撃力+防御力/2でソート（強い順）
    const sortedTroops = [...userTroops].filter(t => t.count > 0).sort((a, b) => {
        const powerA = parseInt(a.attack_power) + Math.floor(parseInt(a.defense_power) / 2);
        const powerB = parseInt(b.attack_power) + Math.floor(parseInt(b.defense_power) / 2);
        return powerB - powerA;
    });
    
    let remaining = limit;
    
    for (const troop of sortedTroops) {
        if (remaining <= 0) break;
        
        const troopId = troop.troop_type_id;
        const available = parseInt(troop.count);
        const toSelect = Math.min(available, remaining);
        
        const input = document.getElementById(`${prefix}-count-${troopId}`);
        const slider = document.getElementById(`${prefix}-slider-${troopId}`);
        
        if (input && slider) {
            input.value = toSelect;
            slider.value = toSelect;
            remaining -= toSelect;
        }
    }
    
    updateMonsterTroopCount(type);
}

// 合計兵数を更新
function updateMonsterTroopCount(type) {
    const prefix = type === 'monster' ? 'attack' : 'boss';
    const countId = type === 'monster' ? 'monster-troop-count' : 'boss-troop-count';
    const limit = type === 'boss' ? 1000 : deploymentLimit.total_limit;
    
    let totalCount = 0;
    let totalPower = 0;
    document.querySelectorAll(`[id^="${prefix}-count-"]`).forEach(input => {
        const count = parseInt(input.value) || 0;
        totalCount += count;
        
        // パワー計算
        if (count > 0) {
            const troopId = input.dataset.troopId;
            const slider = document.getElementById(`${prefix}-slider-${troopId}`);
            if (slider) {
                const attack = parseInt(slider.dataset.attack) || 0;
                const defense = parseInt(slider.dataset.defense) || 0;
                totalPower += (attack + Math.floor(defense / 2)) * count;
            }
        }
    });
    
    const countEl = document.getElementById(countId);
    if (countEl) {
        countEl.textContent = totalCount;
        if (totalCount > limit) {
            countEl.style.color = '#ff6b6b';
        } else {
            countEl.style.color = '#32cd32';
        }
    }
    
    // 有利/不利表示を更新
    if (type === 'monster') {
        updateMonsterAdvantageDisplay(totalPower);
    } else {
        updateBossAdvantageDisplay(totalPower);
    }
}

// モンスター戦闘の有利/不利表示を更新
function updateMonsterAdvantageDisplay(myPower) {
    const powerEl = document.getElementById('monsterMyPower');
    const advantageEl = document.getElementById('monsterAdvantageDisplay');
    
    if (powerEl) {
        powerEl.textContent = myPower;
    }
    
    if (!advantageEl) return;
    
    const enemyPower = currentMonsterPower;
    const powerDiff = myPower - enemyPower;
    const threshold = enemyPower * 0.2;
    
    let advantageHtml = '';
    if (myPower <= 0) {
        advantageHtml = '';
    } else if (powerDiff > threshold) {
        advantageHtml = '<div style="background: rgba(50, 205, 50, 0.3); padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;"><span style="color: #32cd32; font-weight: bold;">✅ 有利</span><span style="color: #888; margin-left: 10px;">あなたの戦力が上回っています</span></div>';
    } else if (powerDiff < -threshold) {
        advantageHtml = '<div style="background: rgba(255, 100, 100, 0.3); padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;"><span style="color: #ff6b6b; font-weight: bold;">⚠️ 不利</span><span style="color: #888; margin-left: 10px;">モンスターの戦力が上回っています</span></div>';
    } else {
        advantageHtml = '<div style="background: rgba(255, 215, 0, 0.2); padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;"><span style="color: #ffd700; font-weight: bold;">⚖️ 互角</span><span style="color: #888; margin-left: 10px;">戦力は拮抗しています</span></div>';
    }
    
    advantageEl.innerHTML = advantageHtml;
}

// ワールドボス戦闘の有利/不利表示を更新
function updateBossAdvantageDisplay(myPower) {
    const powerEl = document.getElementById('bossMyPower');
    const advantageEl = document.getElementById('bossAdvantageDisplay');
    
    if (powerEl) {
        powerEl.textContent = myPower;
    }
    
    if (!advantageEl) return;
    
    const enemyPower = currentBossPower;
    const powerDiff = myPower - enemyPower;
    const threshold = enemyPower * 0.2;
    
    let advantageHtml = '';
    if (myPower <= 0) {
        advantageHtml = '';
    } else if (powerDiff > threshold) {
        advantageHtml = '<div style="background: rgba(50, 205, 50, 0.3); padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;"><span style="color: #32cd32; font-weight: bold;">✅ 有利</span><span style="color: #888; margin-left: 10px;">あなたの戦力が上回っています</span></div>';
    } else if (powerDiff < -threshold) {
        advantageHtml = '<div style="background: rgba(255, 100, 100, 0.3); padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;"><span style="color: #ff6b6b; font-weight: bold;">⚠️ 不利</span><span style="color: #888; margin-left: 10px;">ボスの戦力が上回っています</span></div>';
    } else {
        advantageHtml = '<div style="background: rgba(255, 215, 0, 0.2); padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;"><span style="color: #ffd700; font-weight: bold;">⚖️ 互角</span><span style="color: #888; margin-left: 10px;">戦力は拮抗しています</span></div>';
    }
    
    advantageEl.innerHTML = advantageHtml;
}

// スライダーのイベント設定
function setupTroopSliders() {
    document.querySelectorAll('.troop-slider').forEach(slider => {
        const troopId = slider.dataset.troopId;
        const isBoss = slider.id.startsWith('boss-');
        const prefix = isBoss ? 'boss' : 'attack';
        const countInput = document.getElementById(`${prefix}-count-${troopId}`);
        
        slider.addEventListener('input', () => {
            countInput.value = slider.value;
            updateMonsterTroopCount(isBoss ? 'boss' : 'monster');
        });
        
        countInput.addEventListener('input', () => {
            const max = parseInt(slider.max);
            let value = parseInt(countInput.value) || 0;
            value = Math.max(0, Math.min(max, value));
            countInput.value = value;
            slider.value = value;
            updateMonsterTroopCount(isBoss ? 'boss' : 'monster');
        });
    });
}

// 選択した部隊を取得
function getSelectedTroops() {
    const troops = [];
    document.querySelectorAll('[id^="attack-count-"]').forEach(input => {
        const count = parseInt(input.value) || 0;
        if (count > 0) {
            troops.push({
                troop_type_id: parseInt(input.dataset.troopId),
                count: count
            });
        }
    });
    return troops;
}

// モンスターを攻撃
async function attackMonster(encounterId) {
    const troops = getSelectedTroops();
    
    if (troops.length === 0) {
        showNotification('攻撃部隊を選択してください', true);
        return;
    }
    
    try {
        const res = await fetch('wandering_monster_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'attack_monster',
                encounter_id: encounterId,
                troops: troops
            })
        });
        const data = await res.json();
        
        if (data.ok) {
            // バトルログを保存
            lastBattleTurnLogs = data.turn_logs || [];
            
            showNotification(data.message, !data.is_defeated);
            
            if (data.is_defeated) {
                // 報酬表示
                let rewardText = `💰 ${data.rewards.coins} 💎 ${data.rewards.crystals} 💠 ${data.rewards.diamonds}`;
                if (data.rewards.resources.length > 0) {
                    rewardText += ' ' + data.rewards.resources.map(r => `${r.icon}${r.amount}`).join(' ');
                }
                if (data.rewards.troops.length > 0) {
                    rewardText += ' ' + data.rewards.troops.map(t => `${t.icon}×${t.count}`).join(' ');
                }
                showNotification(`報酬獲得: ${rewardText}`);
                
                // バトル結果とログを表示
                showBattleResult(data);
                await loadActiveEncounter();
                await loadUserTroops();
            } else {
                // 継続中 - バトルログを表示してからモーダルを更新
                showBattleResult(data);
                await loadActiveEncounter();
                await loadUserTroops();
            }
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// 遭遇から撤退
async function retreatFromEncounter(encounterId) {
    if (!confirm('撤退しますか？')) return;
    
    try {
        const res = await fetch('wandering_monster_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'retreat', encounter_id: encounterId})
        });
        const data = await res.json();
        
        if (data.ok) {
            showNotification(data.message);
            await loadActiveEncounter();
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// ワールドボスを召喚
async function summonBoss(bossId) {
    if (!confirm('ダイヤモンドを消費してこのボスを召喚しますか？')) return;
    
    try {
        const res = await fetch('world_boss_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'summon_boss', boss_id: bossId})
        });
        const data = await res.json();
        
        if (data.ok) {
            showNotification(data.message);
            await loadWorldBosses();
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// ワールドボス詳細モーダルを開く
async function openBossDetailModal(instanceId) {
    document.getElementById('bossDetailModal').classList.add('active');
    document.getElementById('bossDetailContent').innerHTML = '<div class="loading">読み込み中...</div>';
    
    try {
        const res = await fetch('world_boss_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_boss_detail', instance_id: instanceId})
        });
        const data = await res.json();
        
        if (data.ok) {
            renderBossDetailModal(data);
        } else {
            showNotification(data.error, true);
            closeBossDetailModal();
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// ワールドボス詳細モーダルを描画
function renderBossDetailModal(data) {
    const inst = data.instance;
    const rankings = data.rankings;
    const myStats = data.my_stats;
    
    const hpPercent = inst.max_health > 0 ? Math.round((inst.current_health / inst.max_health) * 100) : 0;
    const hpClass = hpPercent < 30 ? 'danger' : '';
    const remaining = formatTime(inst.seconds_remaining);
    
    // ボス戦力を保存
    const bossPower = (parseInt(inst.base_attack) || 0) + Math.floor((parseInt(inst.base_defense) || 0) / 2);
    currentBossPower = bossPower;
    
    document.getElementById('bossDetailTitle').textContent = `${inst.boss_icon} ${inst.boss_name}`;
    document.getElementById('bossDetailContent').innerHTML = `
        <div class="hp-bar-container">
            <div class="hp-bar-label">
                <span>❤️ ボスHP</span>
                <span>${formatNumber(inst.current_health)} / ${formatNumber(inst.max_health)} (${hpPercent}%)</span>
            </div>
            <div class="hp-bar">
                <div class="hp-bar-fill ${hpClass}" style="width: ${hpPercent}%;"></div>
            </div>
        </div>
        
        <div style="text-align: center; margin: 10px 0;">
            <span class="timer">⏰ 残り ${remaining}</span>
        </div>
        
        <div id="bossAdvantageDisplay"></div>
        
        <div id="bossPowerComparison" data-boss-power="${bossPower}" style="display: flex; justify-content: space-between; margin-bottom: 15px; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 8px;">
            <div style="text-align: center;">
                <div style="color: #888; font-size: 11px;">選択した戦力</div>
                <div style="color: #32cd32; font-weight: bold; font-size: 18px;">⚔️ <span id="bossMyPower">0</span></div>
            </div>
            <div style="align-self: center; color: #888;">VS</div>
            <div style="text-align: center;">
                <div style="color: #888; font-size: 11px;">ボス戦力</div>
                <div style="color: #dc143c; font-weight: bold; font-size: 18px;">👹 ${bossPower}</div>
            </div>
        </div>
        
        ${myStats ? `
            <div style="background: rgba(255, 215, 0, 0.1); padding: 10px; border-radius: 8px; margin: 15px 0; text-align: center;">
                あなたの総ダメージ: <span style="color: #ffd700; font-weight: bold;">${formatNumber(myStats.damage_dealt)}</span>
                (攻撃回数: ${myStats.attack_count})
            </div>
        ` : ''}
        
        <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 10px; margin: 15px 0;">
            <h4 style="color: #ffd700; margin: 0 0 10px 0;">⚔️ 攻撃部隊を選択</h4>
            <div class="troop-selector" id="bossTroopSelector">
                ${renderBossTroopSelector()}
            </div>
        </div>
        
        <div style="text-align: center; margin: 15px 0;">
            <button class="action-btn attack-btn" onclick="attackBoss(${inst.id})" style="font-size: 18px; padding: 15px 40px;">
                ⚔️ 攻撃！
            </button>
        </div>
        
        <h4 style="color: #dc143c; margin: 20px 0 10px 0;">🏆 ダメージランキング</h4>
        <table class="ranking-table">
            <thead>
                <tr>
                    <th>順位</th>
                    <th>プレイヤー</th>
                    <th>ダメージ</th>
                    <th>攻撃回数</th>
                </tr>
            </thead>
            <tbody>
                ${rankings.map(r => `
                    <tr class="${r.rank_position <= 3 ? 'rank-' + r.rank_position : ''}">
                        <td>${r.rank_position}</td>
                        <td>@${escapeHtml(r.handle)}</td>
                        <td>${formatNumber(r.damage_dealt)}</td>
                        <td>${r.attack_count}</td>
                    </tr>
                `).join('')}
                ${rankings.length === 0 ? '<tr><td colspan="4" style="text-align: center; color: #888;">まだ参加者がいません</td></tr>' : ''}
            </tbody>
        </table>
    `;
    
    setupBossTroopSliders();
}

// ボス用の部隊選択
function renderBossTroopSelector() {
    if (userTroops.length === 0 || userTroops.filter(t => t.count > 0).length === 0) {
        return '<p style="color: #888;">使用できる兵士がいません</p>';
    }
    
    // ワールドボスは1000人固定
    const bossLimit = 1000;
    
    return `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 6px;">
            <div style="color: #ffd700; font-size: 12px;">出撃兵数: <span id="boss-troop-count" style="color: #32cd32;">0</span>/${bossLimit}人（固定）</div>
            <button type="button" onclick="selectMaxByStrongest('boss')" style="padding: 4px 10px; font-size: 11px; background: linear-gradient(135deg, #dc143c 0%, #ff6b6b 100%); color: #fff; border: none; border-radius: 4px; cursor: pointer;">💪 強い順に一括選択</button>
        </div>
    ` + userTroops.filter(t => t.count > 0).map(troop => `
        <div class="troop-select-row">
            <div class="troop-info">
                <span class="troop-icon">${troop.icon}</span>
                <span class="troop-name">${troop.name}${getTroopLabelsHtml(troop)}</span>
                <div class="troop-stats">⚔️${troop.attack_power} 🛡️${troop.defense_power}</div>
            </div>
            <input type="range" class="troop-slider" 
                   id="boss-slider-${troop.troop_type_id}"
                   min="0" max="${troop.count}" value="0"
                   data-troop-id="${troop.troop_type_id}"
                   data-attack="${troop.attack_power}"
                   data-defense="${troop.defense_power}">
            <input type="number" class="troop-count-input" 
                   id="boss-count-${troop.troop_type_id}"
                   min="0" max="${troop.count}" value="0"
                   data-troop-id="${troop.troop_type_id}">
            <span class="troop-available">/ ${troop.count}</span>
        </div>
    `).join('');
}

function setupBossTroopSliders() {
    document.querySelectorAll('[id^="boss-slider-"]').forEach(slider => {
        const troopId = slider.dataset.troopId;
        const countInput = document.getElementById(`boss-count-${troopId}`);
        
        slider.addEventListener('input', () => {
            countInput.value = slider.value;
            updateMonsterTroopCount('boss');
        });
        
        countInput.addEventListener('input', () => {
            const max = parseInt(slider.max);
            let value = parseInt(countInput.value) || 0;
            value = Math.max(0, Math.min(max, value));
            countInput.value = value;
            slider.value = value;
            updateMonsterTroopCount('boss');
        });
    });
}

function getBossSelectedTroops() {
    const troops = [];
    document.querySelectorAll('[id^="boss-count-"]').forEach(input => {
        const count = parseInt(input.value) || 0;
        if (count > 0) {
            troops.push({
                troop_type_id: parseInt(input.dataset.troopId),
                count: count
            });
        }
    });
    return troops;
}

// ワールドボスを攻撃
async function attackBoss(instanceId) {
    const troops = getBossSelectedTroops();
    
    if (troops.length === 0) {
        showNotification('攻撃部隊を選択してください', true);
        return;
    }
    
    try {
        const res = await fetch('world_boss_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'attack_boss',
                instance_id: instanceId,
                troops: troops
            })
        });
        const data = await res.json();
        
        if (data.ok) {
            // バトルログを保存
            lastBattleTurnLogs = data.turn_logs || [];
            
            showNotification(data.message, data.is_defeated ? false : true);
            
            // バトル結果とログを表示
            showBattleResult(data, true);
            
            if (data.is_defeated) {
                await loadWorldBosses();
            } else {
                await loadUserTroops();
            }
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// バトル結果を表示
function showBattleResult(data, isBoss = false) {
    const battleResult = data.battle_result || {};
    const turnLogs = data.turn_logs || [];
    
    let html = `
        <div class="battle-summary">
            <div class="battle-summary-title">⚔️ バトル結果</div>
            <div class="battle-summary-stats">
                <div class="battle-summary-stat">
                    <div class="battle-summary-stat-label">ターン数</div>
                    <div class="battle-summary-stat-value">${battleResult.total_turns || 0}</div>
                </div>
                <div class="battle-summary-stat">
                    <div class="battle-summary-stat-label">与ダメージ</div>
                    <div class="battle-summary-stat-value">${formatNumber(data.damage_dealt || data.damage || 0)}</div>
                </div>
                <div class="battle-summary-stat">
                    <div class="battle-summary-stat-label">自軍残HP</div>
                    <div class="battle-summary-stat-value">${formatNumber(battleResult.attacker_final_hp || 0)} / ${formatNumber(battleResult.attacker_max_hp || 0)}</div>
                </div>
            </div>
        </div>
    `;
    
    if (turnLogs.length > 0) {
        html += `
            <h4 style="color: #ffd700; margin: 15px 0 10px 0;">📜 バトルログ</h4>
            <div class="battle-log-container">
                ${turnLogs.map(log => `
                    <div class="battle-turn">
                        <div class="battle-turn-header">ターン ${log.turn}</div>
                        <div class="battle-turn-message">${log.messages.map(m => escapeHtml(m)).join('<br>')}</div>
                        <div class="battle-turn-hp">
                            <span class="attacker-hp">自軍HP: ${formatNumber(log.attacker_hp)}</span>
                            <span class="defender-hp">敵HP: ${formatNumber(log.defender_hp)}</span>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    if (data.is_defeated && !isBoss) {
        html += `
            <div style="text-align: center; margin-top: 15px;">
                <button class="action-btn attack-btn" onclick="closeBattleLogModal()">閉じる</button>
            </div>
        `;
    } else if (!data.is_defeated && !isBoss) {
        html += `
            <div style="text-align: center; margin-top: 15px;">
                <button class="action-btn attack-btn" onclick="closeBattleLogModal(); openBattleModal(${activeEncounter?.id || 0});">続けて攻撃</button>
                <button class="action-btn retreat-btn" onclick="closeBattleLogModal();">閉じる</button>
            </div>
        `;
    } else {
        html += `
            <div style="text-align: center; margin-top: 15px;">
                <button class="action-btn attack-btn" onclick="closeBattleLogModal();">閉じる</button>
            </div>
        `;
    }
    
    document.getElementById('battleLogContent').innerHTML = html;
    document.getElementById('battleLogModal').classList.add('active');
}

// バトルログモーダルを閉じる
function closeBattleLogModal() {
    document.getElementById('battleLogModal').classList.remove('active');
}

// モーダルを閉じる
function closeBattleModal() {
    document.getElementById('battleModal').classList.remove('active');
}

function closeBossDetailModal() {
    document.getElementById('bossDetailModal').classList.remove('active');
}

// ユーティリティ
function formatTime(seconds) {
    if (seconds <= 0) return '終了';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    if (hours > 0) return `${hours}時間${minutes}分`;
    return `${minutes}分`;
}

function formatNumber(num) {
    if (num >= 1000000000) return (num / 1000000000).toFixed(1) + 'B';
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(message, isError = false) {
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = `notification ${isError ? 'error' : ''}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => notification.remove(), 4000);
}

// タブ切り替え
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        currentTab = btn.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        
        if (btn.dataset.tab === 'history') {
            loadBattleHistory();
        }
    });
});

// 討伐履歴を読み込み
async function loadBattleHistory() {
    document.getElementById('battleHistory').innerHTML = '<div class="loading">読み込み中...</div>';
    
    try {
        const [monsterRes, bossRes] = await Promise.all([
            fetch('wandering_monster_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'get_battle_history'})
            }),
            fetch('world_boss_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'get_my_rewards'})
            })
        ]);
        
        const monsterData = await monsterRes.json();
        const bossData = await bossRes.json();
        
        let html = '<h4 style="color: #ffd700;">🐺 放浪モンスター討伐履歴</h4>';
        
        if (monsterData.ok && monsterData.battle_history.length > 0) {
            html += '<div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">';
            html += monsterData.battle_history.map(b => `
                <div style="background: rgba(0,0,0,0.3); padding: 10px; border-radius: 8px; margin-bottom: 8px; border-left: 3px solid ${b.is_defeated ? '#32cd32' : '#ffa500'}; cursor: pointer;" onclick="viewBattleLogDetail(${b.id})">
                    <div style="display: flex; justify-content: space-between;">
                        <span>${b.icon} ${escapeHtml(b.name)} - ${b.is_defeated ? '討伐完了' : `${b.damage_dealt}ダメージ`}</span>
                        <span style="color: #888; font-size: 11px;">${new Date(b.battle_at).toLocaleString('ja-JP')}</span>
                    </div>
                    ${b.is_defeated ? `<div style="font-size: 11px; color: #ffd700;">💰${b.reward_coins} 💎${b.reward_crystals} 💠${b.reward_diamonds}</div>` : ''}
                    <div style="font-size: 10px; color: #9932cc; margin-top: 5px;">📜 クリックでバトルログを表示</div>
                </div>
            `).join('');
            html += '</div>';
        } else {
            html += '<p style="color: #888;">まだ討伐履歴がありません</p>';
        }
        
        html += '<h4 style="color: #dc143c;">🐉 ワールドボス報酬履歴</h4>';
        
        if (bossData.ok && bossData.rewards.length > 0) {
            html += '<div style="max-height: 300px; overflow-y: auto;">';
            html += bossData.rewards.map(r => `
                <div style="background: rgba(139,0,0,0.2); padding: 10px; border-radius: 8px; margin-bottom: 8px; border-left: 3px solid #dc143c;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>${r.boss_icon} ${escapeHtml(r.boss_name)} - ${r.rank_position}位</span>
                        <span style="color: #888; font-size: 11px;">${new Date(r.distributed_at).toLocaleString('ja-JP')}</span>
                    </div>
                    <div style="font-size: 11px; color: #ffd700;">
                        ダメージ: ${formatNumber(r.total_damage)} | 💰${r.reward_coins} 💎${r.reward_crystals} 💠${r.reward_diamonds}
                        ${r.reward_resources ? '<br>資源: ' + JSON.parse(r.reward_resources || '[]').map(res => `${res.icon || '📦'}${formatNumber(res.amount)}`).join(' ') : ''}
                    </div>
                </div>
            `).join('');
            html += '</div>';
        } else {
            html += '<p style="color: #888;">まだ報酬履歴がありません</p>';
        }
        
        document.getElementById('battleHistory').innerHTML = html;
    } catch (e) {
        console.error(e);
        document.getElementById('battleHistory').innerHTML = '<div class="loading">エラーが発生しました</div>';
    }
}

// バトルログ詳細を表示
async function viewBattleLogDetail(battleLogId) {
    document.getElementById('battleLogContent').innerHTML = '<div class="loading">読み込み中...</div>';
    document.getElementById('battleLogModal').classList.add('active');
    
    try {
        const res = await fetch('wandering_monster_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_battle_detail', battle_log_id: battleLogId})
        });
        const data = await res.json();
        
        if (data.ok) {
            const battleLog = data.battle_log;
            const turnLogs = data.turn_logs || [];
            
            let html = `
                <div class="battle-summary">
                    <div class="battle-summary-title">${battleLog.icon} ${escapeHtml(battleLog.name)}</div>
                    <div class="battle-summary-stats">
                        <div class="battle-summary-stat">
                            <div class="battle-summary-stat-label">与ダメージ</div>
                            <div class="battle-summary-stat-value">${formatNumber(battleLog.damage_dealt)}</div>
                        </div>
                        <div class="battle-summary-stat">
                            <div class="battle-summary-stat-label">結果</div>
                            <div class="battle-summary-stat-value">${battleLog.is_defeated == 1 ? '🏆 討伐成功' : '⚔️ 継続中'}</div>
                        </div>
                        <div class="battle-summary-stat">
                            <div class="battle-summary-stat-label">報酬</div>
                            <div class="battle-summary-stat-value">💰${battleLog.reward_coins} 💎${battleLog.reward_crystals}</div>
                        </div>
                    </div>
                </div>
            `;
            
            if (turnLogs.length > 0) {
                html += `
                    <h4 style="color: #ffd700; margin: 15px 0 10px 0;">📜 バトルログ詳細</h4>
                    <div class="battle-log-container">
                        ${turnLogs.map(log => `
                            <div class="battle-turn">
                                <div class="battle-turn-header">ターン ${log.turn_number}</div>
                                <div class="battle-turn-message">${escapeHtml(log.log_message).replace(/\\n/g, '<br>')}</div>
                                <div class="battle-turn-hp">
                                    <span class="attacker-hp">自軍HP: ${formatNumber(log.attacker_hp_after)}</span>
                                    <span class="defender-hp">敵HP: ${formatNumber(log.defender_hp_after)}</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                html += '<p style="color: #888; text-align: center;">詳細ログはありません</p>';
            }
            
            html += `
                <div style="text-align: center; margin-top: 15px;">
                    <button class="action-btn attack-btn" onclick="closeBattleLogModal()">閉じる</button>
                </div>
            `;
            
            document.getElementById('battleLogContent').innerHTML = html;
        } else {
            document.getElementById('battleLogContent').innerHTML = `<p style="color: #ff6b6b; text-align: center;">${escapeHtml(data.error)}</p>`;
        }
    } catch (e) {
        console.error(e);
        document.getElementById('battleLogContent').innerHTML = '<p style="color: #ff6b6b; text-align: center;">エラーが発生しました</p>';
    }
}

// モーダル外クリックで閉じる
document.getElementById('battleModal').addEventListener('click', (e) => {
    if (e.target.id === 'battleModal') closeBattleModal();
});
document.getElementById('bossDetailModal').addEventListener('click', (e) => {
    if (e.target.id === 'bossDetailModal') closeBossDetailModal();
});
document.getElementById('battleLogModal').addEventListener('click', (e) => {
    if (e.target.id === 'battleLogModal') closeBattleLogModal();
});

// 初期読み込み
loadData();

// 定期的にデータを更新（30秒ごと）
const DATA_REFRESH_INTERVAL_MS = 30000;
setInterval(() => {
    if (currentTab === 'worldboss') {
        loadWorldBosses();
    }
    loadActiveEncounter();
}, DATA_REFRESH_INTERVAL_MS);
</script>
</body>
</html>
