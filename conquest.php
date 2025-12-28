<?php
// ===============================================
// conquest.php
// 占領戦システム（フロントエンド）
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
<title>占領戦 - MiniBird</title>
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

.conquest-container {
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
.conquest-header {
    background: linear-gradient(135deg, rgba(153, 50, 204, 0.8) 0%, rgba(75, 0, 130, 0.8) 100%);
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
    border: 2px solid #9932cc;
    text-align: center;
}

.conquest-title {
    font-size: 32px;
    font-weight: bold;
    color: #ffd700;
    text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
    margin-bottom: 15px;
}

.season-info {
    display: flex;
    justify-content: center;
    gap: 30px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.season-stat {
    background: rgba(0,0,0,0.3);
    padding: 12px 24px;
    border-radius: 10px;
    text-align: center;
}

.season-stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #ffd700;
}

.season-stat-label {
    font-size: 12px;
    color: #a090c0;
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
    background: linear-gradient(135deg, #9932cc 0%, #da70d6 100%);
    color: #fff;
    border-color: #ffd700;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* マップ */
.conquest-map {
    display: grid;
    gap: 10px;
    margin-bottom: 25px;
    justify-content: center;
}

.castle-cell {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, rgba(50, 30, 80, 0.9) 0%, rgba(30, 20, 50, 0.9) 100%);
    border-radius: 12px;
    border: 3px solid #4b0082;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.castle-cell:hover {
    transform: scale(1.05);
    border-color: #ffd700;
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.4);
}

.castle-cell.owned {
    border-color: #32cd32;
    background: linear-gradient(135deg, rgba(34, 139, 34, 0.5) 0%, rgba(30, 20, 50, 0.9) 100%);
}

.castle-cell.attackable {
    border-color: #ff6b6b;
    animation: pulse-attack 2s ease-in-out infinite;
}

.castle-cell.sacred {
    border-color: #ffd700;
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.3) 0%, rgba(30, 20, 50, 0.9) 100%);
    box-shadow: 0 0 30px rgba(255, 215, 0, 0.5);
}

@keyframes pulse-attack {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; box-shadow: 0 0 15px rgba(255, 107, 107, 0.6); }
}

.castle-icon {
    font-size: 36px;
    margin-bottom: 5px;
}

.castle-name {
    font-size: 11px;
    color: #c0b0d0;
    text-align: center;
    max-width: 100px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.castle-owner {
    font-size: 10px;
    color: #ffd700;
    margin-top: 3px;
}

.castle-power {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(0,0,0,0.6);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    color: #ff6b6b;
}

/* 城詳細モーダル */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
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
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    border: 2px solid #9932cc;
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

.castle-detail-section {
    background: rgba(0,0,0,0.3);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
}

.castle-detail-section h4 {
    color: #da70d6;
    margin: 0 0 10px 0;
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

.troop-count-input:focus {
    border-color: #9932cc;
    outline: none;
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
    background: #da70d6;
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

.defense-btn {
    background: linear-gradient(135deg, #32cd32 0%, #90ee90 100%);
    color: #1a1030;
}

.withdraw-btn {
    background: linear-gradient(135deg, #ffa500 0%, #ffd700 100%);
    color: #1a1030;
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
    color: #da70d6;
    font-weight: bold;
}

.ranking-table tr:hover {
    background: rgba(255,255,255,0.05);
}

.rank-1 { color: #ffd700; }
.rank-2 { color: #c0c0c0; }
.rank-3 { color: #cd7f32; }

/* 戦闘ログ */
.battle-log-item {
    background: rgba(0,0,0,0.3);
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    border-left: 3px solid #4b0082;
}

.battle-log-item.victory {
    border-left-color: #32cd32;
}

.battle-log-item.defeat {
    border-left-color: #dc143c;
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
    border-top-color: #da70d6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 15px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* レスポンシブ */
@media (max-width: 768px) {
    .castle-cell {
        width: 60px;
        height: 60px;
    }
    
    .castle-icon {
        font-size: 24px;
    }
    
    .castle-name,
    .castle-owner,
    .castle-power {
        display: none;
    }
    
    .season-info {
        flex-direction: column;
        gap: 10px;
    }
    
    .modal-content {
        padding: 20px;
    }
    
    .troop-select-row {
        flex-wrap: wrap;
    }
    
    .troop-slider {
        width: 100%;
        order: 3;
    }
}
</style>
</head>
<body>
<div class="conquest-container">
    <a href="./civilization.php" class="back-link">← 文明育成に戻る</a>
    
    <div id="app">
        <div class="loading">データを読み込み中...</div>
    </div>
</div>

<!-- 城詳細モーダル -->
<div class="modal-overlay" id="castleModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalCastleName">城名</h3>
            <button class="modal-close" onclick="closeCastleModal()">×</button>
        </div>
        <div id="modalContent">
            <div class="loading">読み込み中...</div>
        </div>
    </div>
</div>

<script>
let seasonData = null;
let userTroops = [];
let selectedCastle = null;
let currentTab = 'map';
const isAdmin = <?= (isset($me['role']) && $me['role'] === 'admin') ? 'true' : 'false' ?>;

// 初期データ読み込み
async function loadData() {
    try {
        // シーズンデータを取得
        const seasonRes = await fetch('conquest_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_season'})
        });
        seasonData = await seasonRes.json();
        
        if (!seasonData.ok) {
            document.getElementById('app').innerHTML = `<div class="loading">エラー: ${seasonData.error}</div>`;
            return;
        }
        
        // ユーザーの兵士を取得
        const troopsRes = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_troops'})
        });
        const troopsData = await troopsRes.json();
        
        if (troopsData.ok) {
            userTroops = troopsData.user_troops || [];
        }
        
        renderApp();
    } catch (e) {
        console.error(e);
        document.getElementById('app').innerHTML = '<div class="loading">読み込みエラーが発生しました</div>';
    }
}

// メイン描画
function renderApp() {
    const season = seasonData.season;
    const castles = seasonData.castles;
    const mapSize = seasonData.map_size;
    
    // 残り時間をフォーマット
    const remainingTime = formatTime(seasonData.remaining_seconds);
    
    document.getElementById('app').innerHTML = `
        <div class="conquest-header">
            <div class="conquest-title">⚔️ 占領戦 Season ${season.season_number}</div>
            <p style="color: #c0b0d0;">隣接する城を攻め落とし、中央の神城を目指せ！</p>
            <div class="season-info">
                <div class="season-stat">
                    <div class="season-stat-value">⏰ ${remainingTime}</div>
                    <div class="season-stat-label">シーズン残り時間</div>
                </div>
                <div class="season-stat">
                    <div class="season-stat-value">🏰 ${seasonData.owned_castle_ids.length}</div>
                    <div class="season-stat-label">占領城</div>
                </div>
                <div class="season-stat">
                    <div class="season-stat-value">⚔️ ${seasonData.attackable_castle_ids.length}</div>
                    <div class="season-stat-label">攻撃可能</div>
                </div>
            </div>
            ${isAdmin ? `
                <div style="margin-top: 15px;">
                    <button class="action-btn" style="background: linear-gradient(135deg, #8b0000 0%, #dc143c 100%); color: #fff;" onclick="adminResetSeason()">
                        🔄 シーズンリセット（管理者）
                    </button>
                </div>
            ` : ''}
        </div>
        
        <div class="tabs">
            <button class="tab-btn ${currentTab === 'map' ? 'active' : ''}" data-tab="map">🗺️ マップ</button>
            <button class="tab-btn ${currentTab === 'ranking' ? 'active' : ''}" data-tab="ranking">🏆 ランキング</button>
            <button class="tab-btn ${currentTab === 'history' ? 'active' : ''}" data-tab="history">📜 過去シーズン</button>
        </div>
        
        <div class="tab-content ${currentTab === 'map' ? 'active' : ''}" id="tab-map">
            <div class="conquest-map" style="grid-template-columns: repeat(${mapSize}, 1fr);">
                ${renderMap(castles, mapSize)}
            </div>
            
            <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 10px; margin-top: 20px;">
                <h4 style="color: #da70d6; margin: 0 0 10px 0;">📋 ルール</h4>
                <ul style="color: #a090c0; margin: 0; padding-left: 20px; line-height: 1.8;">
                    <li>城を持っていない場合、外周の城から攻撃できます</li>
                    <li>城を占領すると、隣接する城を攻撃できます</li>
                    <li>中央の神城⛩️を占領した状態でシーズン終了すると勝利</li>
                    <li>占領した城には防御部隊を配置できます</li>
                    <li>シーズン終了時、ランキング順位に応じてコイン・クリスタル・ダイヤモンドの報酬を獲得</li>
                </ul>
            </div>
        </div>
        
        <div class="tab-content ${currentTab === 'ranking' ? 'active' : ''}" id="tab-ranking">
            <div id="rankingContent">
                <div class="loading">ランキングを読み込み中...</div>
            </div>
        </div>
        
        <div class="tab-content ${currentTab === 'history' ? 'active' : ''}" id="tab-history">
            <div id="historyContent">
                <div class="loading">過去のシーズンを読み込み中...</div>
            </div>
        </div>
    `;
    
    // タブ切り替え
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentTab = btn.dataset.tab;
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
            
            if (btn.dataset.tab === 'ranking') {
                loadRanking();
            } else if (btn.dataset.tab === 'history') {
                loadHistory();
            }
        });
    });
    
    if (currentTab === 'ranking') {
        loadRanking();
    } else if (currentTab === 'history') {
        loadHistory();
    }
}

// マップを描画
function renderMap(castles, mapSize) {
    return castles.map(castle => {
        const castleId = parseInt(castle.id, 10);
        const isOwned = seasonData.owned_castle_ids.includes(castleId);
        const isAttackable = seasonData.attackable_castle_ids.includes(castleId);
        const isSacred = castle.is_sacred == 1;
        
        let classes = 'castle-cell';
        if (isOwned) classes += ' owned';
        if (isAttackable && !isOwned) classes += ' attackable';
        if (isSacred) classes += ' sacred';
        
        return `
            <div class="${classes}" onclick="openCastleModal(${castle.id})">
                <span class="castle-icon">${castle.icon}</span>
                <span class="castle-name">${escapeHtml(castle.name)}</span>
                ${castle.owner_user_id ? `<span class="castle-owner">${escapeHtml(castle.owner_civ_name || '不明')}</span>` : ''}
                <span class="castle-power">⚔️${castle.npc_defense_power}</span>
            </div>
        `;
    }).join('');
}

// 城モーダルを開く
async function openCastleModal(castleId) {
    selectedCastle = castleId;
    document.getElementById('castleModal').classList.add('active');
    document.getElementById('modalContent').innerHTML = '<div class="loading">読み込み中...</div>';
    
    try {
        const res = await fetch('conquest_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_castle', castle_id: castleId})
        });
        const data = await res.json();
        
        if (data.ok) {
            renderCastleDetail(data);
        } else {
            document.getElementById('modalContent').innerHTML = `<div class="loading">エラー: ${data.error}</div>`;
        }
    } catch (e) {
        document.getElementById('modalContent').innerHTML = '<div class="loading">エラーが発生しました</div>';
    }
}

// 城詳細を描画
function renderCastleDetail(data) {
    const castle = data.castle;
    const defense = data.defense;
    const isOwned = seasonData.owned_castle_ids.includes(castle.id);
    const isAttackable = seasonData.attackable_castle_ids.includes(castle.id);
    
    document.getElementById('modalCastleName').textContent = `${castle.icon} ${castle.name}`;
    
    // 耐久度の計算
    const durability = castle.durability !== undefined ? parseInt(castle.durability) : 100;
    const maxDurability = castle.max_durability !== undefined ? parseInt(castle.max_durability) : 100;
    const durabilityPercent = maxDurability > 0 ? Math.round((durability / maxDurability) * 100) : 100;
    const durabilityColor = durabilityPercent > 60 ? '#32cd32' : (durabilityPercent > 30 ? '#ffa500' : '#ff6b6b');
    
    let html = `
        <div class="castle-detail-section">
            <h4>📊 城情報</h4>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                <div>
                    <span style="color: #888;">種類:</span>
                    <span style="color: #e0d0f0;">${getCastleTypeName(castle.castle_type)}</span>
                </div>
                <div>
                    <span style="color: #888;">座標:</span>
                    <span style="color: #e0d0f0;">(${castle.position_x}, ${castle.position_y})</span>
                </div>
                <div>
                    <span style="color: #888;">所有者:</span>
                    <span style="color: #ffd700;">${castle.owner_user_id ? escapeHtml(castle.owner_civ_name) : 'NPC'}</span>
                </div>
                <div>
                    <span style="color: #888;">防御力:</span>
                    <span style="color: #ff6b6b;">⚔️ ${defense.total_power}</span>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span style="color: #888;">🏰 城壁耐久度:</span>
                    <span style="color: ${durabilityColor};">${durability} / ${maxDurability} (${durabilityPercent}%)</span>
                </div>
                <div style="background: rgba(0,0,0,0.5); border-radius: 4px; height: 12px; overflow: hidden;">
                    <div style="background: linear-gradient(90deg, ${durabilityColor} 0%, ${durabilityColor}88 100%); height: 100%; width: ${durabilityPercent}%; transition: width 0.3s;"></div>
                </div>
                <div style="color: #888; font-size: 11px; margin-top: 5px;">
                    💡 守備兵がいない城への攻撃は耐久度を削ります。耐久度が0になると城を占領できます。
                </div>
            </div>
        </div>
    `;
    
    // 防御部隊
    if (defense.troops && defense.troops.length > 0) {
        html += `
            <div class="castle-detail-section">
                <h4>🛡️ 防御部隊</h4>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    ${defense.troops.map(t => `
                        <span style="background: rgba(255,255,255,0.1); padding: 5px 10px; border-radius: 5px; font-size: 12px;">
                            ${t.icon} ${t.name} ×${t.count}
                        </span>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    // 攻撃UI（攻撃可能な場合）
    if (isAttackable && !isOwned) {
        html += `
            <div class="castle-detail-section">
                <h4>⚔️ 攻撃部隊を選択</h4>
                <div class="troop-selector" id="attackTroopSelector">
                    ${renderTroopSelector('attack')}
                </div>
                <div style="margin-top: 15px; text-align: center;">
                    <button class="action-btn attack-btn" onclick="attackCastle(${castle.id})">
                        ⚔️ 攻撃する
                    </button>
                </div>
            </div>
        `;
    }
    
    // 防御設定UI（所有している場合）
    if (isOwned) {
        html += `
            <div class="castle-detail-section">
                <h4>🛡️ 防御部隊を設定</h4>
                <div class="troop-selector" id="defenseTroopSelector">
                    ${renderTroopSelector('defense')}
                </div>
                <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center;">
                    <button class="action-btn defense-btn" onclick="setDefense(${castle.id})">
                        🛡️ 防御部隊を配置
                    </button>
                    <button class="action-btn withdraw-btn" onclick="withdrawDefense(${castle.id})">
                        ↩️ 撤退
                    </button>
                </div>
            </div>
        `;
    }
    
    // 砲撃状況（占領者がいる場合）
    if (castle.owner_user_id && data.bombardment_status) {
        const bombStatus = data.bombardment_status;
        const minutesUntil = bombStatus.minutes_until_next || 0;
        const warningClass = minutesUntil <= 5 ? 'style="color: #ff6b6b;"' : '';
        html += `
            <div class="castle-detail-section" style="background: linear-gradient(135deg, rgba(255, 100, 0, 0.2) 0%, rgba(139, 0, 0, 0.2) 100%); border: 1px solid #ff6b00;">
                <h4>💥 砲撃状況</h4>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="color: #888; font-size: 11px;">次の砲撃まで</div>
                        <div ${warningClass} style="font-size: 18px; font-weight: bold;">
                            ${minutesUntil > 0 ? `${minutesUntil}分` : '間もなく発生'}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="color: #888; font-size: 11px;">最終砲撃</div>
                        <div style="font-size: 12px;">
                            ${bombStatus.last_bombardment_at ? new Date(bombStatus.last_bombardment_at).toLocaleString('ja-JP') : '未発生'}
                        </div>
                    </div>
                </div>
                <div style="margin-top: 10px; font-size: 11px; color: #888;">
                    💡 砲撃は${bombStatus.interval_minutes}分おきに発生し、配置した兵士が負傷します。低コスト兵ほど被害が大きくなります。
                </div>
            </div>
        `;
    }
    
    // 最近の戦闘
    if (data.recent_battles && data.recent_battles.length > 0) {
        html += `
            <div class="castle-detail-section">
                <h4>📜 最近の戦闘・砲撃</h4>
                <div style="max-height: 200px; overflow-y: auto;">
                    ${data.recent_battles.map(battle => {
                        const logType = battle.log_type || 'battle';
                        
                        // 砲撃ログの場合
                        if (logType === 'bombardment') {
                            return `
                                <div class="battle-log-item" style="border-left: 3px solid #ff6b00; background: rgba(255, 100, 0, 0.1);">
                                    <div style="display: flex; justify-content: space-between;">
                                        <span style="color: #ff6b00;">💥 砲撃被害</span>
                                        <span style="color: #888; font-size: 11px;">${new Date(battle.battle_at).toLocaleString('ja-JP')}</span>
                                    </div>
                                    <div style="margin-top: 5px; font-size: 12px; color: #ff6b6b;">
                                        負傷兵: ${battle.total_turns}体
                                    </div>
                                    <button onclick="showConquestBattleLogs(${battle.id})" style="margin-top: 5px; padding: 3px 8px; background: linear-gradient(135deg, #ff6b00 0%, #ff8c00 100%); color: #fff; border: none; border-radius: 4px; font-size: 10px; cursor: pointer;">
                                        📜 詳細
                                    </button>
                                </div>
                            `;
                        }
                        
                        // 通常の戦闘ログ
                        const isWin = battle.castle_captured;
                        const totalTurns = battle.total_turns || 0;
                        const turnsText = totalTurns > 0 ? `<span style="color: #87ceeb; font-size: 10px; margin-left: 5px;">⚡${totalTurns}ターン</span>` : '';
                        const detailBtn = totalTurns > 0 ? `
                            <button onclick="showConquestBattleLogs(${battle.id})" style="margin-top: 5px; padding: 3px 8px; background: linear-gradient(135deg, #4169e1 0%, #6495ed 100%); color: #fff; border: none; border-radius: 4px; font-size: 10px; cursor: pointer;">
                                📜 詳細
                            </button>
                        ` : '';
                        return `
                            <div class="battle-log-item ${isWin ? 'victory' : 'defeat'}">
                                <div style="display: flex; justify-content: space-between;">
                                    <span>${isWin ? '🏆 占領' : '⚔️ 防衛'}${turnsText}</span>
                                    <span style="color: #888; font-size: 11px;">${new Date(battle.battle_at).toLocaleString('ja-JP')}</span>
                                </div>
                                <div style="margin-top: 5px; font-size: 12px;">
                                    攻撃者: ${escapeHtml(battle.attacker_civ_name || '不明')} (@${escapeHtml(battle.attacker_handle)})
                                </div>
                                <div style="font-size: 11px; color: #888;">
                                    ⚔️${battle.attacker_power} vs 🛡️${battle.defender_power}
                                    ${battle.attacker_final_hp !== undefined ? `| HP: ${battle.attacker_final_hp}/${battle.defender_final_hp}` : ''}
                                </div>
                                ${detailBtn}
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }
    
    document.getElementById('modalContent').innerHTML = html;
    
    // スライダーイベントを設定
    setupTroopSliders();
}

// 部隊選択UIを描画
function renderTroopSelector(type) {
    if (userTroops.length === 0) {
        return '<p style="color: #888;">兵士がいません。文明育成で兵士を訓練してください。</p>';
    }
    
    return userTroops.filter(t => t.count > 0).map(troop => `
        <div class="troop-select-row">
            <div class="troop-info">
                <span class="troop-icon">${troop.icon}</span>
                <span class="troop-name">${troop.name}</span>
                <div class="troop-stats">⚔️${troop.attack_power} 🛡️${troop.defense_power}</div>
            </div>
            <input type="range" class="troop-slider" 
                   id="${type}-slider-${troop.troop_type_id}"
                   min="0" max="${troop.count}" value="0"
                   data-troop-id="${troop.troop_type_id}"
                   data-type="${type}">
            <input type="number" class="troop-count-input" 
                   id="${type}-count-${troop.troop_type_id}"
                   min="0" max="${troop.count}" value="0"
                   data-troop-id="${troop.troop_type_id}"
                   data-type="${type}">
            <span class="troop-available">/ ${troop.count}</span>
        </div>
    `).join('');
}

// スライダーのイベントを設定
function setupTroopSliders() {
    document.querySelectorAll('.troop-slider').forEach(slider => {
        const troopId = slider.dataset.troopId;
        const type = slider.dataset.type;
        const countInput = document.getElementById(`${type}-count-${troopId}`);
        
        slider.addEventListener('input', () => {
            countInput.value = slider.value;
        });
        
        countInput.addEventListener('input', () => {
            const max = parseInt(slider.max);
            let value = parseInt(countInput.value) || 0;
            value = Math.max(0, Math.min(max, value));
            countInput.value = value;
            slider.value = value;
        });
    });
}

// 選択した部隊を取得
function getSelectedTroops(type) {
    const troops = [];
    document.querySelectorAll(`[id^="${type}-count-"]`).forEach(input => {
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

// 城を攻撃
async function attackCastle(castleId) {
    const troops = getSelectedTroops('attack');
    
    if (troops.length === 0) {
        showNotification('攻撃部隊を選択してください', true);
        return;
    }
    
    if (!confirm('この城を攻撃しますか？兵士に損失が発生する可能性があります。')) {
        return;
    }
    
    try {
        const res = await fetch('conquest_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'attack_castle',
                castle_id: castleId,
                troops: troops
            })
        });
        const data = await res.json();
        
        if (data.ok) {
            const isVictory = data.result === 'victory';
            showNotification(data.message, !isVictory);
            closeCastleModal();
            loadData(); // データを再読み込み
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// 防御部隊を設定
async function setDefense(castleId) {
    const troops = getSelectedTroops('defense');
    
    if (troops.length === 0) {
        showNotification('防御部隊を選択してください', true);
        return;
    }
    
    try {
        const res = await fetch('conquest_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'set_castle_defense',
                castle_id: castleId,
                troops: troops
            })
        });
        const data = await res.json();
        
        if (data.ok) {
            showNotification(data.message);
            closeCastleModal();
            loadData();
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// 防御部隊を撤退
async function withdrawDefense(castleId) {
    if (!confirm('防御部隊を撤退させますか？')) {
        return;
    }
    
    try {
        const res = await fetch('conquest_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'withdraw_castle_defense',
                castle_id: castleId
            })
        });
        const data = await res.json();
        
        if (data.ok) {
            showNotification(data.message);
            closeCastleModal();
            loadData();
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// モーダルを閉じる
function closeCastleModal() {
    document.getElementById('castleModal').classList.remove('active');
    selectedCastle = null;
}

// ランキングを読み込む
async function loadRanking() {
    try {
        const res = await fetch('conquest_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_ranking'})
        });
        const data = await res.json();
        
        if (data.ok) {
            document.getElementById('rankingContent').innerHTML = `
                <table class="ranking-table">
                    <thead>
                        <tr>
                            <th>順位</th>
                            <th>文明名</th>
                            <th>プレイヤー</th>
                            <th>城数</th>
                            <th>神城</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.rankings.map((r, i) => `
                            <tr class="${i < 3 ? 'rank-' + (i + 1) : ''}">
                                <td>${i + 1}</td>
                                <td>${escapeHtml(r.civilization_name)}</td>
                                <td>@${escapeHtml(r.handle)}</td>
                                <td>${r.castle_count}</td>
                                <td>${r.sacred_count > 0 ? '⛩️' : '-'}</td>
                            </tr>
                        `).join('')}
                        ${data.rankings.length === 0 ? '<tr><td colspan="5" style="text-align: center; color: #888;">まだ城を占領したプレイヤーがいません</td></tr>' : ''}
                    </tbody>
                </table>
            `;
        }
    } catch (e) {
        document.getElementById('rankingContent').innerHTML = '<div class="loading">エラーが発生しました</div>';
    }
}

// 過去のシーズンを読み込む
async function loadHistory() {
    try {
        const res = await fetch('conquest_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_past_seasons'})
        });
        const data = await res.json();
        
        if (data.ok) {
            if (data.past_seasons.length === 0) {
                document.getElementById('historyContent').innerHTML = '<p style="color: #888; text-align: center;">まだ過去のシーズンがありません</p>';
                return;
            }
            
            document.getElementById('historyContent').innerHTML = `
                <table class="ranking-table">
                    <thead>
                        <tr>
                            <th>シーズン</th>
                            <th>期間</th>
                            <th>勝者</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.past_seasons.map(s => `
                            <tr>
                                <td>Season ${s.season_number}</td>
                                <td>${new Date(s.started_at).toLocaleDateString('ja-JP')} - ${new Date(s.ends_at).toLocaleDateString('ja-JP')}</td>
                                <td>${s.winner_civilization_name ? `🏆 ${escapeHtml(s.winner_civilization_name)} (@${escapeHtml(s.winner_handle)})` : '-'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }
    } catch (e) {
        document.getElementById('historyContent').innerHTML = '<div class="loading">エラーが発生しました</div>';
    }
}

// ユーティリティ関数
function getCastleTypeName(type) {
    const names = {
        'outer': '外周',
        'middle': '中間',
        'inner': '内周',
        'sacred': '神城'
    };
    return names[type] || type;
}

function formatTime(seconds) {
    if (seconds <= 0) return '終了';
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    
    if (days > 0) return `${days}日 ${hours}時間`;
    if (hours > 0) return `${hours}時間 ${minutes}分`;
    return `${minutes}分`;
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

// 管理者用シーズンリセット
async function adminResetSeason() {
    if (!isAdmin) {
        showNotification('管理者権限が必要です', true);
        return;
    }
    
    if (!confirm('シーズンをリセットしますか？\n\n・現在のシーズンは終了し、報酬が配布されます\n・新しいシーズンが開始されます')) {
        return;
    }
    
    if (!confirm('本当にリセットしますか？この操作は取り消せません。')) {
        return;
    }
    
    try {
        const res = await fetch('conquest_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'reset_season'})
        });
        const data = await res.json();
        
        if (data.ok) {
            showNotification(data.message);
            loadData();
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// モーダル外クリックで閉じる
document.getElementById('castleModal').addEventListener('click', (e) => {
    if (e.target.id === 'castleModal') {
        closeCastleModal();
    }
});

// ユーザー操作検出（更新をスキップするため）
let isUserInteracting = false;
let interactionTimeout = null;

function setUserInteracting() {
    isUserInteracting = true;
    if (interactionTimeout) clearTimeout(interactionTimeout);
    interactionTimeout = setTimeout(() => { isUserInteracting = false; }, 2000);
}

// スクロールイベントのスロットリング
let scrollThrottleTimer = null;
function handleScrollThrottled() {
    if (!scrollThrottleTimer) {
        scrollThrottleTimer = setTimeout(() => {
            setUserInteracting();
            scrollThrottleTimer = null;
        }, 100);
    }
}

document.addEventListener('focusin', (e) => {
    if (e.target.matches('input, select, textarea')) setUserInteracting();
});
document.addEventListener('input', (e) => {
    if (e.target.matches('input, select, textarea')) setUserInteracting();
});
document.addEventListener('scroll', handleScrollThrottled, true);
document.addEventListener('mousedown', (e) => {
    if (e.target.matches('input[type="range"]')) setUserInteracting();
});
document.addEventListener('touchstart', (e) => {
    if (e.target.matches('input[type="range"], input[type="number"]')) setUserInteracting();
}, { passive: true });

// 初期読み込み
loadData();

// 定期的にデータを更新（30秒ごと、ユーザー操作中はスキップ）
setInterval(() => {
    if (!isUserInteracting) {
        loadData();
    }
}, 30000);

// 占領戦バトルログ詳細を表示
async function showConquestBattleLogs(battleId) {
    try {
        const res = await fetch('conquest_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_conquest_battle_turn_logs', battle_id: battleId})
        });
        const data = await res.json();
        
        if (!data.ok) {
            showNotification(data.error || 'バトルログの取得に失敗しました', true);
            return;
        }
        
        const battleLog = data.battle_log;
        const turnLogs = data.turn_logs || [];
        const myUserId = data.my_user_id;
        
        const isAttacker = battleLog.attacker_user_id == myUserId;
        const isWinner = battleLog.winner_user_id == myUserId;
        
        // モーダルを作成
        let modalHtml = `
            <div id="battleLogModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 2000;" onclick="if(event.target.id==='battleLogModal')closeConquestBattleLogModal()">
                <div style="background: linear-gradient(135deg, #1a0f0a 0%, #2d1810 100%); border-radius: 16px; padding: 25px; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; border: 2px solid #9932cc;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="color: #da70d6; margin: 0;">📜 バトルログ詳細</h3>
                        <button onclick="closeConquestBattleLogModal()" style="background: none; border: none; color: #c0a080; font-size: 24px; cursor: pointer;">×</button>
                    </div>
                    
                    <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <div style="color: #ffd700; font-weight: bold; font-size: 16px;">
                                    🏰 ${escapeHtml(battleLog.castle_name || '不明')}
                                </div>
                                <div style="color: ${isWinner ? '#32cd32' : '#ff6b6b'}; margin-top: 5px;">
                                    ${battleLog.castle_captured ? '🏆 占領成功' : '💀 占領失敗'}
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="color: #87ceeb;">⚡ ${battleLog.total_turns || 0}ターン</div>
                                <div style="color: #888; font-size: 11px;">${new Date(battleLog.battle_at).toLocaleString('ja-JP')}</div>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-around; margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1);">
                            <div style="text-align: center;">
                                <div style="color: #ff6b6b;">⚔️ 攻撃側</div>
                                <div style="color: #ffd700; font-size: 16px; font-weight: bold;">${escapeHtml(battleLog.attacker_civ_name || '不明')}</div>
                                <div style="color: #888; font-size: 11px;">HP: ${battleLog.attacker_final_hp || 0}</div>
                            </div>
                            <div style="color: #888; font-size: 24px; align-self: center;">VS</div>
                            <div style="text-align: center;">
                                <div style="color: #32cd32;">🛡️ 防御側</div>
                                <div style="color: #ffd700; font-size: 16px; font-weight: bold;">${escapeHtml(battleLog.defender_civ_name || 'NPC')}</div>
                                <div style="color: #888; font-size: 11px;">HP: ${battleLog.defender_final_hp || 0}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="max-height: 350px; overflow-y: auto; padding: 5px;">
                        ${turnLogs.length > 0 ? turnLogs.map(log => {
                            const isAttackerTurn = log.actor_side === 'attacker';
                            const turnColor = isAttackerTurn ? '#ff6b6b' : '#32cd32';
                            const turnIcon = isAttackerTurn ? '⚔️' : '🛡️';
                            
                            // ログメッセージを行ごとに分割して表示
                            const messages = (log.log_message || '').split('\n').filter(m => m.trim());
                            
                            return `
                                <div style="background: rgba(${isAttackerTurn ? '139,0,0' : '0,100,0'},0.2); padding: 10px; border-radius: 8px; margin-bottom: 8px; border-left: 3px solid ${turnColor};">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                        <span style="color: ${turnColor}; font-weight: bold;">${turnIcon} ターン ${log.turn_number}</span>
                                        <span style="color: #888; font-size: 11px;">
                                            攻:${log.attacker_hp_after} / 防:${log.defender_hp_after}
                                        </span>
                                    </div>
                                    <div style="font-size: 12px; color: #f5deb3;">
                                        ${messages.map(m => `<div style="margin-bottom: 3px;">${escapeHtml(m)}</div>`).join('')}
                                    </div>
                                </div>
                            `;
                        }).join('') : '<p style="color: #888; text-align: center;">詳細なターンログがありません</p>'}
                    </div>
                    
                    <button onclick="closeConquestBattleLogModal()" style="width: 100%; margin-top: 15px; padding: 12px; background: linear-gradient(135deg, #9932cc 0%, #da70d6 100%); color: #fff; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">
                        閉じる
                    </button>
                </div>
            </div>
        `;
        
        // 既存のモーダルを削除
        const existingModal = document.getElementById('battleLogModal');
        if (existingModal) existingModal.remove();
        
        // モーダルを追加
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
    } catch (e) {
        console.error(e);
        showNotification('バトルログの取得に失敗しました', true);
    }
}

// バトルログモーダルを閉じる
function closeConquestBattleLogModal() {
    const modal = document.getElementById('battleLogModal');
    if (modal) modal.remove();
}
</script>
</body>
</html>
