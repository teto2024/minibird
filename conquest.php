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
// メンテナンスモード検出（定期的にチェック）
let maintenanceCheckInterval = null;
let isMaintenanceMode = false;

async function checkGameMaintenance() {
    try {
        const res = await fetch('conquest_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'check_game_maintenance'})
        });
        const data = await res.json();
        
        if (data.maintenance && !isMaintenanceMode) {
            isMaintenanceMode = true;
            showMaintenanceOverlay(data.message);
        } else if (!data.maintenance && isMaintenanceMode) {
            isMaintenanceMode = false;
            hideMaintenanceOverlay();
        }
    } catch (e) {
        console.error('メンテナンス状態チェックエラー:', e);
    }
}

function showMaintenanceOverlay(message) {
    // 既存のオーバーレイがあれば削除
    const existing = document.getElementById('maintenance-overlay');
    if (existing) existing.remove();
    
    const overlay = document.createElement('div');
    overlay.id = 'maintenance-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 99999;
        color: #f5deb3;
        font-size: 1.2em;
        text-align: center;
        padding: 20px;
    `;
    overlay.innerHTML = `
        <div style="font-size: 4em; margin-bottom: 20px;">🔧</div>
        <h2 style="margin-bottom: 20px;">メンテナンス中</h2>
        <p style="max-width: 400px;">${message || 'ゲームシステムはメンテナンス中です。しばらくお待ちください。'}</p>
        <p style="margin-top: 30px; font-size: 0.9em; color: #888;">メンテナンス終了後、自動的に再開します</p>
    `;
    document.body.appendChild(overlay);
}

function hideMaintenanceOverlay() {
    const overlay = document.getElementById('maintenance-overlay');
    if (overlay) {
        overlay.remove();
        // データを再読み込み
        loadSeason();
    }
}

// 初回チェック & 30秒ごとにチェック
document.addEventListener('DOMContentLoaded', () => {
    checkGameMaintenance();
    maintenanceCheckInterval = setInterval(checkGameMaintenance, 30000);
});

let seasonData = null;
let userTroops = [];
let selectedCastle = null;
let currentTab = 'map';
let deploymentLimit = { base_limit: 100, building_bonus: 0, total_limit: 100 }; // 出撃上限
const isAdmin = <?= (isset($me['role']) && $me['role'] === 'admin') ? 'true' : 'false' ?>;

// ③ 設定保持用のlocalStorageキー
const DEPLOYMENT_SETTINGS_KEY = 'minibird_deployment_settings';

// ③ 設定を保存
function saveDeploymentSettings(type) {
    const excludeDisposable = document.getElementById(`${type}-exclude-disposable`)?.checked || false;
    const excludeNuclear = document.getElementById(`${type}-exclude-nuclear`)?.checked || false;
    const prioritizeStealth = document.getElementById(`${type}-prioritize-stealth`)?.checked || false;
    const keepSettings = document.getElementById(`${type}-keep-settings`)?.checked || false;
    
    const settings = JSON.parse(localStorage.getItem(DEPLOYMENT_SETTINGS_KEY) || '{}');
    settings[type] = {
        excludeDisposable,
        excludeNuclear,
        prioritizeStealth,
        keepSettings
    };
    localStorage.setItem(DEPLOYMENT_SETTINGS_KEY, JSON.stringify(settings));
}

// ③ 設定を読み込み
function loadDeploymentSettings(type) {
    const settings = JSON.parse(localStorage.getItem(DEPLOYMENT_SETTINGS_KEY) || '{}');
    return settings[type] || { excludeDisposable: false, excludeNuclear: false, prioritizeStealth: false, keepSettings: false };
}

// ③ 保存された設定をチェックボックスに適用
function applyDeploymentSettings(type) {
    const settings = loadDeploymentSettings(type);
    if (!settings.keepSettings) return; // 設定保持が無効なら適用しない
    
    const excludeDisposable = document.getElementById(`${type}-exclude-disposable`);
    const excludeNuclear = document.getElementById(`${type}-exclude-nuclear`);
    const prioritizeStealth = document.getElementById(`${type}-prioritize-stealth`);
    const keepSettings = document.getElementById(`${type}-keep-settings`);
    
    if (excludeDisposable) excludeDisposable.checked = settings.excludeDisposable;
    if (excludeNuclear) excludeNuclear.checked = settings.excludeNuclear;
    if (prioritizeStealth) prioritizeStealth.checked = settings.prioritizeStealth;
    if (keepSettings) keepSettings.checked = settings.keepSettings;
}

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
    try {
        // シーズンデータを取得
        const seasonRes = await fetch('conquest_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_season'})
        });
        seasonData = await seasonRes.json();
        
        if (!seasonData.ok) {
            // メンテナンスモードチェック
            if (seasonData.maintenance || seasonData.error === 'maintenance') {
                if (!isMaintenanceMode) {
                    isMaintenanceMode = true;
                    showMaintenanceOverlay(seasonData.message);
                }
                return;
            }
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
            // 出撃上限を保存
            if (troopsData.deployment_limit) {
                deploymentLimit = troopsData.deployment_limit;
            }
        } else if (troopsData.maintenance || troopsData.error === 'maintenance') {
            // 兵士取得でメンテナンスエラーが返った場合
            if (!isMaintenanceMode) {
                isMaintenanceMode = true;
                showMaintenanceOverlay(troopsData.message);
            }
            return;
        }
        
        renderApp();
    } catch (e) {
        console.error(e);
        document.getElementById('app').innerHTML = '<div class="loading">読み込みエラーが発生しました</div>';
    }
}

// 占領戦レート制限の状態のみを更新
async function updateConquestRateLimitStatus() {
    try {
        const res = await fetch('conquest_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_conquest_rate_limit_status'})
        });
        const rateLimitData = await res.json();
        updateConquestRateLimitDisplay(rateLimitData);
    } catch (e) {
        console.error('Failed to update conquest rate limit status:', e);
    }
}

// 占領戦レート制限の状態を表示
function updateConquestRateLimitDisplay(rateLimitData) {
    if (!rateLimitData || !rateLimitData.ok) return;
    
    const section = document.getElementById('conquestRateLimitSection');
    const remainingEl = document.getElementById('conquestRemainingAttacks');
    const barEl = document.getElementById('conquestRateLimitBar');
    const messageEl = document.getElementById('conquestRateLimitMessage');
    
    if (!section || !remainingEl || !barEl || !messageEl) return;
    
    // セクションを表示
    section.style.display = 'block';
    
    const attackCount = rateLimitData.attack_count || 0;
    const maxAttacks = rateLimitData.max_attacks || 10;
    const remainingAttacks = rateLimitData.remaining_attacks || 0;
    const isLimited = rateLimitData.is_limited || false;
    const waitSeconds = rateLimitData.wait_seconds || 0;
    
    // 残り攻撃回数を表示
    remainingEl.textContent = `${remainingAttacks} / ${maxAttacks}`;
    
    // プログレスバーを更新
    const percentage = (remainingAttacks / maxAttacks) * 100;
    barEl.style.width = `${percentage}%`;
    
    // バーの色を更新
    if (remainingAttacks === 0) {
        barEl.style.background = 'linear-gradient(90deg, #8b0000 0%, #dc143c 100%)';
    } else if (remainingAttacks <= 2) {
        barEl.style.background = 'linear-gradient(90deg, #ffa500 0%, #ff6b6b 100%)';
    } else {
        barEl.style.background = 'linear-gradient(90deg, #32cd32 0%, #228b22 100%)';
    }
    
    // メッセージを更新
    if (isLimited) {
        const hours = Math.floor(waitSeconds / 3600);
        const minutes = Math.floor((waitSeconds % 3600) / 60);
        let timeText = '';
        if (hours > 0) {
            timeText = `${hours}時間${minutes}分`;
        } else if (minutes > 0) {
            timeText = `${minutes}分`;
        } else {
            timeText = '1分未満';
        }
        
        messageEl.innerHTML = `⚠️ <span style="color: #ff6b6b; font-weight: bold;">レート制限中</span> - 次の攻撃まで <span style="color: #ffd700; font-weight: bold;">${timeText}</span> お待ちください`;
        section.style.borderColor = '#ff6b6b';
        section.style.background = 'rgba(139, 0, 0, 0.3)';
    } else if (remainingAttacks === 1) {
        messageEl.innerHTML = `⚠️ あと <span style="color: #ffd700; font-weight: bold;">1回</span> 攻撃すると制限されます`;
        section.style.borderColor = '#ffa500';
        section.style.background = 'rgba(139, 69, 0, 0.2)';
    } else if (remainingAttacks <= 3) {
        messageEl.innerHTML = `💡 あと <span style="color: #ffd700;">${remainingAttacks}回</span> 攻撃できます`;
        section.style.borderColor = '#ffa500';
        section.style.background = 'rgba(139, 69, 0, 0.2)';
    } else {
        messageEl.innerHTML = `✅ 占領戦の攻撃は1時間に${maxAttacks}回まで可能です（残り${remainingAttacks}回）`;
        section.style.borderColor = '#32cd32';
        section.style.background = 'rgba(0, 100, 0, 0.15)';
    }
    
    // エラーハンドリング（APIでエラーが返された場合）
    if (rateLimitData.error) {
        messageEl.innerHTML = `⚠️ レート制限情報の取得に失敗しました`;
        const warningSpan = document.createElement('span');
        warningSpan.style.color = '#ff6b6b';
        warningSpan.style.fontSize = '11px';
        warningSpan.textContent = '⚠️ レート制限機能が利用できません。管理者に連絡してください。';
        messageEl.appendChild(document.createElement('br'));
        messageEl.appendChild(warningSpan);
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
        
        <!-- 占領戦レート制限表示 -->
        <div id="conquestRateLimitSection" style="background: rgba(139, 0, 0, 0.2); border: 2px solid #8b0000; padding: 15px; border-radius: 10px; margin-bottom: 20px; display: none;">
            <h4 style="color: #ff6b6b; margin: 0 0 10px 0;">⏱️ 占領戦レート制限（1時間に10回まで）</h4>
            <div style="margin-bottom: 10px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span style="color: #c0a080;">残り攻撃可能回数</span>
                    <span id="conquestRemainingAttacks" style="color: #ffd700; font-weight: bold;">--</span>
                </div>
                <div style="background: rgba(0,0,0,0.3); border-radius: 8px; height: 12px; overflow: hidden;">
                    <div id="conquestRateLimitBar" style="background: linear-gradient(90deg, #32cd32 0%, #228b22 100%); height: 100%; width: 100%; transition: width 0.3s;"></div>
                </div>
            </div>
            <div id="conquestRateLimitMessage" style="color: #c0a080; font-size: 12px; text-align: center;">
                レート制限情報を取得中...
            </div>
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
                    <li><strong style="color: #ffd700;">中央の神城⛩️を占領すると占領時間が記録されます</strong></li>
                    <li><strong style="color: #ffd700;">シーズン終了時、神城の累計占領時間が最も長いプレイヤーが優勝</strong></li>
                    <li><strong style="color: #ffd700;">累計占領時間が同じ場合は保有城数で順位が決まります</strong></li>
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
    
    // レート制限状態を更新
    updateConquestRateLimitStatus();
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
        // 有利/不利を計算
        const myPower = data.my_power || 0;
        const defPower = defense.total_power || 0;
        const powerDiff = myPower - defPower;
        let advantageHtml = '';
        if (powerDiff > defPower * 0.2) {
            advantageHtml = '<div style="background: rgba(50, 205, 50, 0.3); padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;"><span style="color: #32cd32; font-weight: bold;">✅ 有利</span><span style="color: #888; margin-left: 10px;">あなたの戦力が上回っています</span></div>';
        } else if (powerDiff < -defPower * 0.2) {
            advantageHtml = '<div style="background: rgba(255, 100, 100, 0.3); padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;"><span style="color: #ff6b6b; font-weight: bold;">⚠️ 不利</span><span style="color: #888; margin-left: 10px;">相手の戦力が上回っています</span></div>';
        } else {
            advantageHtml = '<div style="background: rgba(255, 215, 0, 0.2); padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;"><span style="color: #ffd700; font-weight: bold;">⚖️ 互角</span><span style="color: #888; margin-left: 10px;">戦力は拮抗しています</span></div>';
        }
        
        html += `
            <div class="castle-detail-section">
                <h4>⚔️ 攻撃部隊を選択</h4>
                <div id="conquestAdvantageDisplay">${advantageHtml}</div>
                <div id="conquestPowerComparison" data-def-power="${defPower}" style="display: flex; justify-content: space-between; margin-bottom: 15px; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 8px;">
                    <div style="text-align: center;">
                        <div style="color: #888; font-size: 11px;">選択した戦力</div>
                        <div style="color: #32cd32; font-weight: bold; font-size: 18px;">⚔️ <span id="conquestMyPower">0</span></div>
                    </div>
                    <div style="align-self: center; color: #888;">VS</div>
                    <div style="text-align: center;">
                        <div style="color: #888; font-size: 11px;">城の防御力</div>
                        <div style="color: #ff6b6b; font-weight: bold; font-size: 18px;">🛡️ ${defPower}</div>
                    </div>
                </div>
                <div class="troop-selector" id="attackTroopSelector">
                    ${renderTroopSelector('attack')}
                </div>
                <div style="margin-top: 15px; text-align: center; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <button class="action-btn attack-btn" onclick="attackCastle(${castle.id})">
                        ⚔️ 攻撃する
                    </button>
                    <button class="action-btn" onclick="reconnaissanceCastle(${castle.id}, '${escapeHtml(castle.name)}', '(${castle.position_x}, ${castle.position_y})')" style="background: linear-gradient(135deg, #32cd32, #228b22);">
                        🔭 偵察
                    </button>
                </div>
            </div>
        `;
    }
    
    // 偵察UI（自分の城でなく、攻撃可能でない場合も偵察可能にする）
    if (!isOwned && !isAttackable) {
        html += `
            <div class="castle-detail-section" style="background: linear-gradient(135deg, rgba(50, 205, 50, 0.2) 0%, rgba(34, 139, 34, 0.2) 100%); border: 1px solid #32cd32;">
                <h4>🔭 偵察</h4>
                <p style="color: #90ee90; font-size: 12px; margin-bottom: 15px;">
                    この城は攻撃範囲外ですが、偵察を行うことができます。
                </p>
                <div style="text-align: center;">
                    <button class="action-btn" onclick="reconnaissanceCastle(${castle.id}, '${escapeHtml(castle.name)}', '(${castle.position_x}, ${castle.position_y})')" style="background: linear-gradient(135deg, #32cd32, #228b22);">
                        🔭 偵察する
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
    // ③ 保存された設定を適用（攻撃と防御の両方）
    setTimeout(() => {
        applyDeploymentSettings('attack');
        applyDeploymentSettings('defense');
    }, 0);
}

// 部隊選択UIを描画
function renderTroopSelector(type) {
    if (userTroops.length === 0) {
        return '<p style="color: #888;">兵士がいません。文明育成で兵士を訓練してください。</p>';
    }
    
    const availableTroops = userTroops.filter(t => t.count > 0);
    if (availableTroops.length === 0) {
        return '<p style="color: #888;">出撃可能な兵士がいません。</p>';
    }
    
    return `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 6px;">
            <div style="color: #da70d6; font-size: 12px;">出撃兵数: <span id="${type}-troop-count" style="color: #32cd32;">0</span>/${deploymentLimit.total_limit}人</div>
            <div style="display: flex; gap: 5px; align-items: center;">
                <button type="button" onclick="selectMaxByStrongest('${type}')" style="padding: 4px 10px; font-size: 11px; background: linear-gradient(135deg, #9932cc 0%, #da70d6 100%); color: #fff; border: none; border-radius: 4px; cursor: pointer;">💪 強い順に選択</button>
                <button type="button" onclick="selectByLargestNumber('${type}')" style="padding: 4px 10px; font-size: 11px; background: linear-gradient(135deg, #4169e1 0%, #87ceeb 100%); color: #fff; border: none; border-radius: 4px; cursor: pointer;">📊 数が多い順に選択</button>
            </div>
        </div>
        <div style="display: flex; gap: 10px; margin-bottom: 10px; padding: 6px; background: rgba(0,0,0,0.15); border-radius: 4px; font-size: 11px; flex-wrap: wrap;">
            <label style="display: flex; align-items: center; gap: 3px; cursor: pointer; color: #ddd;">
                <input type="checkbox" id="${type}-exclude-disposable" onchange="saveDeploymentSettings('${type}')" style="cursor: pointer;">
                <span>🗑️ 使い捨てを除外</span>
            </label>
            <label style="display: flex; align-items: center; gap: 3px; cursor: pointer; color: #ddd;">
                <input type="checkbox" id="${type}-exclude-nuclear" onchange="saveDeploymentSettings('${type}')" style="cursor: pointer;">
                <span>☢️ 核ユニットを除外</span>
            </label>
            <label style="display: flex; align-items: center; gap: 3px; cursor: pointer; color: #ddd;">
                <input type="checkbox" id="${type}-prioritize-stealth" onchange="saveDeploymentSettings('${type}')" style="cursor: pointer;">
                <span>🥷 ステルスを優先</span>
            </label>
            <label style="display: flex; align-items: center; gap: 3px; cursor: pointer; color: #ffd700; border-left: 1px solid #555; padding-left: 10px; margin-left: 5px;">
                <input type="checkbox" id="${type}-keep-settings" onchange="saveDeploymentSettings('${type}')" style="cursor: pointer;">
                <span>💾 設定を保持</span>
            </label>
        </div>
    ` + availableTroops.map(troop => `
        <div class="troop-select-row">
            <div class="troop-info">
                <span class="troop-icon">${troop.icon}</span>
                <span class="troop-name">${troop.name}${getTroopLabelsHtml(troop)}</span>
                <div class="troop-stats">⚔️${troop.attack_power} 🛡️${troop.defense_power}</div>
            </div>
            <input type="range" class="troop-slider" 
                   id="${type}-slider-${troop.troop_type_id}"
                   min="0" max="${troop.count}" value="0"
                   data-troop-id="${troop.troop_type_id}"
                   data-attack="${troop.attack_power}"
                   data-defense="${troop.defense_power}"
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

// フィルタ適用後の兵種を取得
function getFilteredTroops(type) {
    const excludeDisposable = document.getElementById(`${type}-exclude-disposable`)?.checked || false;
    const excludeNuclear = document.getElementById(`${type}-exclude-nuclear`)?.checked || false;
    const prioritizeStealth = document.getElementById(`${type}-prioritize-stealth`)?.checked || false;
    
    let filtered = [...userTroops].filter(t => t.count > 0);
    
    // フィルタを適用
    if (excludeDisposable) {
        filtered = filtered.filter(t => !isDisposableUnit(t));
    }
    if (excludeNuclear) {
        filtered = filtered.filter(t => !isNuclearUnit(t));
    }
    
    return { filtered, prioritizeStealth };
}

// 強い順に一括選択
function selectMaxByStrongest(type) {
    // まずすべてをリセット
    document.querySelectorAll(`[id^="${type}-count-"]`).forEach(input => {
        input.value = 0;
        const troopId = input.dataset.troopId;
        const slider = document.getElementById(`${type}-slider-${troopId}`);
        if (slider) slider.value = 0;
    });
    
    const { filtered, prioritizeStealth } = getFilteredTroops(type);
    
    // ステルスを優先する場合は2段階でソート
    let sortedTroops;
    if (prioritizeStealth) {
        // ステルスユニットを最優先
        const stealthTroops = filtered.filter(t => isStealthUnit(t));
        const nonStealthTroops = filtered.filter(t => !isStealthUnit(t));
        
        // それぞれを強さでソート
        const sortByPower = (a, b) => {
            const powerA = parseInt(a.attack_power) + Math.floor(parseInt(a.defense_power) / 2);
            const powerB = parseInt(b.attack_power) + Math.floor(parseInt(b.defense_power) / 2);
            return powerB - powerA;
        };
        
        sortedTroops = [...stealthTroops.sort(sortByPower), ...nonStealthTroops.sort(sortByPower)];
    } else {
        // 兵種を攻撃力+防御力/2でソート（強い順）
        sortedTroops = filtered.sort((a, b) => {
            const powerA = parseInt(a.attack_power) + Math.floor(parseInt(a.defense_power) / 2);
            const powerB = parseInt(b.attack_power) + Math.floor(parseInt(b.defense_power) / 2);
            return powerB - powerA;
        });
    }
    
    let remaining = deploymentLimit.total_limit;
    
    for (const troop of sortedTroops) {
        if (remaining <= 0) break;
        
        const troopId = troop.troop_type_id;
        const available = parseInt(troop.count);
        const toSelect = Math.min(available, remaining);
        
        const input = document.getElementById(`${type}-count-${troopId}`);
        const slider = document.getElementById(`${type}-slider-${troopId}`);
        
        if (input && slider) {
            input.value = toSelect;
            slider.value = toSelect;
            remaining -= toSelect;
        }
    }
    
    updateTroopCountDisplay(type);
}

// 数が多い順に一括選択
function selectByLargestNumber(type) {
    // まずすべてをリセット
    document.querySelectorAll(`[id^="${type}-count-"]`).forEach(input => {
        input.value = 0;
        const troopId = input.dataset.troopId;
        const slider = document.getElementById(`${type}-slider-${troopId}`);
        if (slider) slider.value = 0;
    });
    
    const { filtered, prioritizeStealth } = getFilteredTroops(type);
    
    // ステルスを優先する場合は2段階でソート
    let sortedTroops;
    if (prioritizeStealth) {
        // ステルスユニットを最優先
        const stealthTroops = filtered.filter(t => isStealthUnit(t));
        const nonStealthTroops = filtered.filter(t => !isStealthUnit(t));
        
        // それぞれを数でソート
        const sortByCount = (a, b) => parseInt(b.count) - parseInt(a.count);
        
        sortedTroops = [...stealthTroops.sort(sortByCount), ...nonStealthTroops.sort(sortByCount)];
    } else {
        // 兵種を数の多い順でソート
        sortedTroops = filtered.sort((a, b) => parseInt(b.count) - parseInt(a.count));
    }
    
    let remaining = deploymentLimit.total_limit;
    
    for (const troop of sortedTroops) {
        if (remaining <= 0) break;
        
        const troopId = troop.troop_type_id;
        const available = parseInt(troop.count);
        const toSelect = Math.min(available, remaining);
        
        const input = document.getElementById(`${type}-count-${troopId}`);
        const slider = document.getElementById(`${type}-slider-${troopId}`);
        
        if (input && slider) {
            input.value = toSelect;
            slider.value = toSelect;
            remaining -= toSelect;
        }
    }
    
    updateTroopCountDisplay(type);
}

// 合計兵数を更新
function updateTroopCountDisplay(type) {
    let totalCount = 0;
    let totalPower = 0;
    document.querySelectorAll(`[id^="${type}-count-"]`).forEach(input => {
        const count = parseInt(input.value) || 0;
        totalCount += count;
        
        // パワー計算
        if (count > 0) {
            const troopId = input.dataset.troopId;
            const slider = document.getElementById(`${type}-slider-${troopId}`);
            if (slider) {
                const attack = parseInt(slider.dataset.attack) || 0;
                const defense = parseInt(slider.dataset.defense) || 0;
                totalPower += (attack + Math.floor(defense / 2)) * count;
            }
        }
    });
    
    const countEl = document.getElementById(`${type}-troop-count`);
    if (countEl) {
        countEl.textContent = totalCount;
        if (totalCount > deploymentLimit.total_limit) {
            countEl.style.color = '#ff6b6b';
        } else {
            countEl.style.color = '#32cd32';
        }
    }
    
    // 攻撃部隊の場合、パワーと有利/不利を更新
    if (type === 'attack') {
        updateConquestAdvantageDisplay(totalPower);
    }
}

// 占領戦の有利/不利表示を更新
function updateConquestAdvantageDisplay(myPower) {
    const powerEl = document.getElementById('conquestMyPower');
    const advantageEl = document.getElementById('conquestAdvantageDisplay');
    const comparisonEl = document.getElementById('conquestPowerComparison');
    
    if (powerEl) {
        powerEl.textContent = myPower;
    }
    
    if (!advantageEl || !comparisonEl) return;
    
    const defPower = parseInt(comparisonEl.dataset.defPower) || 0;
    const powerDiff = myPower - defPower;
    const threshold = defPower * 0.2;
    
    let advantageHtml = '';
    if (myPower <= 0) {
        advantageHtml = '';
    } else if (powerDiff > threshold) {
        advantageHtml = '<div style="background: rgba(50, 205, 50, 0.3); padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;"><span style="color: #32cd32; font-weight: bold;">✅ 有利</span><span style="color: #888; margin-left: 10px;">あなたの戦力が上回っています</span></div>';
    } else if (powerDiff < -threshold) {
        advantageHtml = '<div style="background: rgba(255, 100, 100, 0.3); padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;"><span style="color: #ff6b6b; font-weight: bold;">⚠️ 不利</span><span style="color: #888; margin-left: 10px;">相手の戦力が上回っています</span></div>';
    } else {
        advantageHtml = '<div style="background: rgba(255, 215, 0, 0.2); padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;"><span style="color: #ffd700; font-weight: bold;">⚖️ 互角</span><span style="color: #888; margin-left: 10px;">戦力は拮抗しています</span></div>';
    }
    
    advantageEl.innerHTML = advantageHtml;
}

// スライダーのイベントを設定
function setupTroopSliders() {
    document.querySelectorAll('.troop-slider').forEach(slider => {
        const troopId = slider.dataset.troopId;
        const type = slider.dataset.type;
        const countInput = document.getElementById(`${type}-count-${troopId}`);
        
        slider.addEventListener('input', () => {
            countInput.value = slider.value;
            updateTroopCountDisplay(type);
        });
        
        countInput.addEventListener('input', () => {
            const max = parseInt(slider.max);
            let value = parseInt(countInput.value) || 0;
            value = Math.max(0, Math.min(max, value));
            countInput.value = value;
            slider.value = value;
            updateTroopCountDisplay(type);
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
            // 攻撃後、レート制限状態のみを更新（効率的）
            updateConquestRateLimitStatus();
        } else {
            // メンテナンスモードチェック
            if (data.maintenance || data.error === 'maintenance') {
                if (!isMaintenanceMode) {
                    isMaintenanceMode = true;
                    showMaintenanceOverlay(data.message);
                }
                return;
            }
            showNotification(data.error, true);
            // エラーの場合もレート制限状態を更新（制限到達の可能性）
            if (data.rate_limited) {
                updateConquestRateLimitStatus();
            }
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
            // メンテナンスモードチェック
            if (data.maintenance || data.error === 'maintenance') {
                if (!isMaintenanceMode) {
                    isMaintenanceMode = true;
                    showMaintenanceOverlay(data.message);
                }
                return;
            }
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
            // メンテナンスモードチェック
            if (data.maintenance || data.error === 'maintenance') {
                if (!isMaintenanceMode) {
                    isMaintenanceMode = true;
                    showMaintenanceOverlay(data.message);
                }
                return;
            }
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
        const [rankingRes, rewardsRes] = await Promise.all([
            fetch('conquest_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'get_ranking'})
            }),
            fetch('conquest_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'get_rewards'})
            })
        ]);
        
        const data = await rankingRes.json();
        const rewardsData = await rewardsRes.json();
        
        if (data.ok && rewardsData.ok) {
            const rewards = rewardsData.rewards;
            
            document.getElementById('rankingContent').innerHTML = `
                <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <h4 style="color: #da70d6; margin: 0 0 10px 0;">🎁 シーズン報酬</h4>
                    <table class="ranking-table" style="margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th>順位</th>
                                <th>💰 コイン</th>
                                <th>💎 クリスタル</th>
                                <th>💠 ダイヤモンド</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rewards.map(r => `
                                <tr>
                                    <td style="font-weight: bold; color: #ffd700;">${r.rank}</td>
                                    <td>${r.coins.toLocaleString()}</td>
                                    <td>${r.crystals.toLocaleString()}</td>
                                    <td>${r.diamonds}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    <p style="color: #888; font-size: 12px; margin: 10px 0 0 0;">
                        ※ シーズン終了時に自動的に配布されます
                    </p>
                </div>
                
                <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 10px;">
                    <h4 style="color: #da70d6; margin: 0 0 10px 0;">🏆 現在のランキング</h4>
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th>順位</th>
                                <th>文明名</th>
                                <th>プレイヤー</th>
                                <th>神城<br>占領時間</th>
                                <th>城数</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.rankings.map((r, i) => {
                                const hours = Math.floor(r.sacred_occupation_seconds / 3600);
                                const minutes = Math.floor((r.sacred_occupation_seconds % 3600) / 60);
                                const timeStr = r.sacred_occupation_seconds > 0 
                                    ? (hours > 0 ? `${hours}時間${minutes}分` : `${minutes}分`)
                                    : '-';
                                
                                return `
                                    <tr class="${i < 3 ? 'rank-' + (i + 1) : ''}">
                                        <td style="font-weight: bold;">${i + 1}</td>
                                        <td>${escapeHtml(r.civilization_name)}</td>
                                        <td>@${escapeHtml(r.handle)}</td>
                                        <td style="color: ${r.sacred_occupation_seconds > 0 ? '#ffd700' : '#888'}; font-weight: ${r.sacred_occupation_seconds > 0 ? 'bold' : 'normal'};">
                                            ${r.sacred_count > 0 ? '⛩️ ' : ''}${timeStr}
                                        </td>
                                        <td>${r.castle_count}</td>
                                    </tr>
                                `;
                            }).join('')}
                            ${data.rankings.length === 0 ? '<tr><td colspan="5" style="text-align: center; color: #888;">まだ城を占領したプレイヤーがいません</td></tr>' : ''}
                        </tbody>
                    </table>
                </div>
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
        // ランキングタブが表示されている場合はランキングも更新（リアルタイム表示）
        if (currentTab === 'ranking') {
            loadRanking();
        }
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

// 城の偵察を実行
async function reconnaissanceCastle(castleId, castleName, coords) {
    if (!confirm(`${castleName} ${coords}を偵察しますか？\n\n⚠️ 30%の確率で失敗します。\nステルス部隊の数値には25%〜175%の誤差が生じます。`)) return;
    
    try {
        const res = await fetch('civilization_mail_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'reconnaissance_conquest', castle_id: castleId })
        });
        const data = await res.json();
        
        if (data.ok) {
            if (data.success) {
                showNotification(`🔭 偵察成功！結果はメールに送信されました。`);
                // 偵察結果をポップアップで表示
                showReconnaissanceResult(data);
            } else {
                showNotification(`❌ ${data.message}`, true);
            }
            
            // レート制限を表示
            if (data.rate_limit) {
                showNotification(`📊 占領戦偵察: 残り${data.rate_limit.remaining}/${data.rate_limit.limit}回`);
            }
        } else {
            showNotification(data.error || '偵察に失敗しました', true);
        }
    } catch (e) {
        console.error(e);
        showNotification('エラーが発生しました', true);
    }
}

// 偵察結果をポップアップで表示
function showReconnaissanceResult(data) {
    let troopsHtml = '';
    if (data.troops && data.troops.length > 0) {
        data.troops.forEach(t => {
            const approx = t.is_approximate ? '約' : '';
            const stealthNote = t.is_stealth ? '<span style="color: #ffcc00; font-size: 10px;"> (ステルス)</span>' : '';
            troopsHtml += `<div style="padding: 5px; background: rgba(0,0,0,0.3); border-radius: 4px; margin-bottom: 5px;">
                ${t.icon} ${t.name}: ${approx}${t.count}体${stealthNote}
            </div>`;
        });
    } else {
        troopsHtml = '<div style="color: #888;">駐屯部隊はいません</div>';
    }
    
    const ownerInfo = data.owner_civilization 
        ? `${data.owner_civilization}` 
        : 'NPC';
    
    const modalHtml = `
        <div id="reconResultModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: flex; justify-content: center; align-items: center; z-index: 10000;" onclick="if(event.target===this) document.getElementById('reconResultModal').remove()">
            <div style="background: linear-gradient(135deg, #1a0f0a 0%, #2d1810 100%); border: 2px solid #32cd32; border-radius: 16px; padding: 25px; max-width: 400px; width: 90%;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="color: #32cd32; margin: 0;">🔭 偵察報告</h3>
                    <button onclick="document.getElementById('reconResultModal').remove()" style="background: none; border: none; color: #888; font-size: 24px; cursor: pointer;">&times;</button>
                </div>
                <div style="margin-bottom: 15px;">
                    <div style="color: #ffd700; font-size: 16px; margin-bottom: 5px;">🏰 ${data.castle_name || '城'}</div>
                    <div style="color: #888; font-size: 12px;">座標: ${data.castle_coords || ''}</div>
                    <div style="color: #888; font-size: 12px;">所有者: ${ownerInfo}</div>
                </div>
                <div style="margin-bottom: 10px;">
                    <div style="color: #90ee90; font-weight: bold; margin-bottom: 10px;">■ 駐屯部隊:</div>
                    ${troopsHtml}
                </div>
                <div style="color: #ffcc00; font-size: 11px; padding: 10px; background: rgba(0,0,0,0.3); border-radius: 8px;">
                    ⚠️ ステルス部隊の数値には誤差が含まれます
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}
</script>
</body>
</html>
