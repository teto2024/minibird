<?php
// ===============================================
// civilization.php
// 文明育成システム（Home Quest風）
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
<title>文明育成 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
* {
    box-sizing: border-box;
}

body {
    background: linear-gradient(180deg, #1a0f0a 0%, #2d1810 50%, #1a0f0a 100%);
    min-height: 100vh;
    margin: 0;
    color: #f5deb3;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.civ-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 20px;
    background: rgba(255,255,255,0.1);
    color: #f5deb3;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}

.back-link:hover {
    background: #d4a574;
    color: #1a0f0a;
}

/* ヘッダー */
.civ-header {
    background: linear-gradient(135deg, rgba(139, 69, 19, 0.8) 0%, rgba(101, 67, 33, 0.8) 100%);
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
    border: 2px solid #d4a574;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.civ-title {
    display: flex;
    align-items: center;
    gap: 15px;
}

.civ-name {
    font-size: 28px;
    font-weight: bold;
    color: #ffd700;
    text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
    cursor: pointer;
}

.civ-era {
    font-size: 18px;
    color: #f5deb3;
    background: rgba(0,0,0,0.3);
    padding: 8px 16px;
    border-radius: 8px;
}

.civ-stats {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-box {
    background: rgba(0,0,0,0.3);
    padding: 12px 20px;
    border-radius: 10px;
    text-align: center;
    min-width: 100px;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #ffd700;
}

.stat-label {
    font-size: 12px;
    color: #c0a080;
}

/* 資源バー */
.resources-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 25px;
    background: rgba(0,0,0,0.3);
    padding: 15px;
    border-radius: 12px;
}

.resource-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.05);
    padding: 8px 14px;
    border-radius: 8px;
    min-width: 100px;
}

.resource-icon {
    font-size: 20px;
}

.resource-amount {
    font-weight: bold;
    color: #ffd700;
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
    border: 2px solid #8b4513;
    border-radius: 10px;
    background: rgba(0,0,0,0.3);
    color: #c0a080;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s;
}

.tab-btn.active {
    background: linear-gradient(135deg, #8b4513 0%, #d4a574 100%);
    color: #fff;
    border-color: #ffd700;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* 投資セクション */
.invest-section {
    background: linear-gradient(135deg, rgba(139, 69, 19, 0.5) 0%, rgba(101, 67, 33, 0.5) 100%);
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
    border: 2px solid #8b4513;
}

.invest-section h3 {
    margin: 0 0 20px 0;
    color: #ffd700;
    font-size: 20px;
}

.invest-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.invest-input {
    padding: 12px 16px;
    font-size: 18px;
    background: rgba(0,0,0,0.3);
    border: 2px solid #8b4513;
    border-radius: 10px;
    color: #f5deb3;
    width: 150px;
}

.invest-input:focus {
    border-color: #ffd700;
    outline: none;
}

.invest-btn {
    padding: 12px 24px;
    background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
    color: #1a0f0a;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.invest-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
}

.quick-invest-btns {
    display: flex;
    gap: 10px;
}

.quick-invest-btn {
    padding: 8px 16px;
    background: rgba(255,255,255,0.1);
    border: 1px solid #8b4513;
    border-radius: 8px;
    color: #f5deb3;
    cursor: pointer;
    transition: all 0.3s;
}

.quick-invest-btn:hover {
    background: #8b4513;
}

/* 建物グリッド */
.buildings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.building-card {
    background: linear-gradient(135deg, rgba(50, 30, 15, 0.9) 0%, rgba(80, 50, 25, 0.9) 100%);
    border-radius: 12px;
    padding: 20px;
    border: 2px solid #8b4513;
    transition: all 0.3s;
}

.building-card:hover {
    border-color: #ffd700;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
}

.building-card.owned {
    border-color: #228b22;
    background: linear-gradient(135deg, rgba(34, 70, 34, 0.5) 0%, rgba(50, 80, 50, 0.5) 100%);
}

.building-card.constructing {
    border-color: #ffa500;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.building-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.building-icon {
    font-size: 32px;
}

.building-name {
    font-size: 18px;
    font-weight: bold;
    color: #ffd700;
}

.building-level {
    background: rgba(0,0,0,0.3);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 14px;
}

.building-desc {
    color: #c0a080;
    font-size: 14px;
    margin-bottom: 15px;
}

.building-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.building-stat {
    background: rgba(0,0,0,0.2);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
}

.building-cost {
    background: rgba(139, 69, 19, 0.3);
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 12px;
    font-size: 13px;
}

.build-btn, .upgrade-btn {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.build-btn {
    background: linear-gradient(135deg, #228b22 0%, #32cd32 100%);
    color: #fff;
}

.upgrade-btn {
    background: linear-gradient(135deg, #4169e1 0%, #6495ed 100%);
    color: #fff;
}

.build-btn:hover, .upgrade-btn:hover {
    transform: translateY(-2px);
}

.build-btn:disabled, .upgrade-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* 研究ツリー */
.research-tree {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.research-card {
    background: linear-gradient(135deg, rgba(30, 30, 60, 0.9) 0%, rgba(60, 60, 100, 0.9) 100%);
    border-radius: 12px;
    padding: 18px;
    border: 2px solid #4169e1;
    transition: all 0.3s;
}

.research-card.completed {
    border-color: #228b22;
    background: linear-gradient(135deg, rgba(34, 70, 34, 0.5) 0%, rgba(50, 80, 50, 0.5) 100%);
}

.research-card.researching {
    border-color: #ffa500;
    animation: pulse 2s ease-in-out infinite;
}

.research-card.locked {
    opacity: 0.5;
    border-color: #444;
}

.research-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.research-icon {
    font-size: 28px;
}

.research-name {
    font-size: 16px;
    font-weight: bold;
    color: #87ceeb;
}

.research-desc {
    color: #a0a0c0;
    font-size: 13px;
    margin-bottom: 12px;
}

.research-cost {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(0,0,0,0.2);
    padding: 8px 12px;
    border-radius: 6px;
    margin-bottom: 12px;
    font-size: 13px;
}

.research-btn {
    width: 100%;
    padding: 10px;
    background: linear-gradient(135deg, #4169e1 0%, #6495ed 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.research-btn:hover {
    transform: translateY(-2px);
}

.research-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* 戦争タブ */
.war-section {
    background: linear-gradient(135deg, rgba(100, 20, 20, 0.5) 0%, rgba(50, 10, 10, 0.5) 100%);
    border-radius: 16px;
    padding: 25px;
    border: 2px solid #8b0000;
}

.war-section h3 {
    color: #ff6b6b;
    margin: 0 0 20px 0;
}

.targets-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
}

.target-card {
    background: rgba(0,0,0,0.3);
    border-radius: 10px;
    padding: 15px;
    border: 1px solid #8b0000;
}

.target-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.target-name {
    font-weight: bold;
    color: #ff6b6b;
}

.target-power {
    background: rgba(255,0,0,0.2);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 13px;
}

.attack-btn {
    width: 100%;
    padding: 10px;
    background: linear-gradient(135deg, #8b0000 0%, #dc143c 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.attack-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(220, 20, 60, 0.4);
}

/* 時代進化 */
.era-progress {
    background: rgba(0,0,0,0.3);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
}

.era-progress h3 {
    color: #ffd700;
    margin: 0 0 15px 0;
}

.era-requirements {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.era-req {
    background: rgba(255,255,255,0.05);
    padding: 10px 16px;
    border-radius: 8px;
}

.req-label {
    font-size: 12px;
    color: #888;
}

.req-value {
    font-weight: bold;
}

.req-value.met {
    color: #32cd32;
}

.req-value.unmet {
    color: #ff6b6b;
}

.advance-era-btn {
    padding: 14px 28px;
    background: linear-gradient(135deg, #9932cc 0%, #da70d6 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.advance-era-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(153, 50, 204, 0.5);
}

.advance-era-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* 通知 */
.notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: linear-gradient(135deg, #228b22 0%, #32cd32 100%);
    color: #fff;
    padding: 15px 25px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
    z-index: 1000;
    animation: slideIn 0.3s ease-out;
}

.notification.error {
    background: linear-gradient(135deg, #8b0000 0%, #dc143c 100%);
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

/* レスポンシブ */
@media (max-width: 768px) {
    .civ-header {
        flex-direction: column;
        text-align: center;
    }
    
    .civ-stats {
        justify-content: center;
    }
    
    .invest-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .invest-input {
        width: 100%;
    }
    
    .tabs {
        justify-content: center;
    }
    
    .buildings-grid, .research-tree, .targets-list {
        grid-template-columns: 1fr;
    }
}

/* ローディング */
.loading {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 50px;
    color: #c0a080;
}

.loading::after {
    content: '';
    width: 40px;
    height: 40px;
    border: 4px solid #8b4513;
    border-top-color: #ffd700;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 15px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* 攻撃モーダル */
.attack-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.85);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.attack-modal-overlay.active {
    display: flex;
}

.attack-modal {
    background: linear-gradient(135deg, #1a0f0a 0%, #2d1810 100%);
    border-radius: 16px;
    padding: 25px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    border: 2px solid #8b4513;
}

.attack-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.attack-modal-title {
    font-size: 20px;
    font-weight: bold;
    color: #ffd700;
}

.attack-modal-close {
    background: none;
    border: none;
    color: #c0a080;
    font-size: 24px;
    cursor: pointer;
}

.troop-select-row {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.05);
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 10px;
}

.troop-select-info {
    flex: 1;
    min-width: 120px;
}

.troop-select-icon {
    font-size: 20px;
}

.troop-select-name {
    color: #f5deb3;
    font-weight: bold;
    font-size: 14px;
}

.troop-select-stats {
    font-size: 11px;
    color: #a08060;
}

.troop-select-slider {
    width: 100px;
    -webkit-appearance: none;
    height: 8px;
    border-radius: 4px;
    background: #8b4513;
}

.troop-select-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #ffd700;
    cursor: pointer;
}

.troop-select-slider::-moz-range-thumb {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #ffd700;
    cursor: pointer;
    border: none;
}

.troop-select-count {
    width: 60px;
    padding: 8px;
    background: rgba(0,0,0,0.3);
    border: 1px solid #8b4513;
    border-radius: 6px;
    color: #f5deb3;
    text-align: center;
}

.troop-select-count:focus {
    border-color: #ffd700;
    outline: none;
}

.troop-select-max {
    font-size: 11px;
    color: #888;
    min-width: 50px;
    text-align: right;
}

.attack-power-display {
    background: rgba(0,0,0,0.3);
    padding: 15px;
    border-radius: 10px;
    margin: 15px 0;
    text-align: center;
}

.attack-power-value {
    font-size: 24px;
    font-weight: bold;
    color: #ff6b6b;
}

.attack-confirm-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #8b0000 0%, #dc143c 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.attack-confirm-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(220, 20, 60, 0.5);
}

.attack-confirm-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}
</style>
</head>
<body>
<div class="civ-container">
    <a href="./" class="back-link">← フィードに戻る</a>
    
    <div id="app">
        <div class="loading">データを読み込み中...</div>
    </div>
</div>

<!-- 攻撃部隊選択モーダル -->
<div class="attack-modal-overlay" id="attackModal">
    <div class="attack-modal">
        <div class="attack-modal-header">
            <h3 class="attack-modal-title">⚔️ 出撃部隊を選択</h3>
            <button class="attack-modal-close" onclick="closeAttackModal()">×</button>
        </div>
        <div id="attackModalTarget"></div>
        <div id="attackTroopSelector"></div>
        <div class="attack-power-display">
            <div>出撃パワー</div>
            <div class="attack-power-value" id="attackPowerDisplay">0</div>
        </div>
        <button class="attack-confirm-btn" id="confirmAttackBtn" onclick="confirmAttack()">
            ⚔️ 攻撃開始
        </button>
    </div>
</div>

<script>
// 戦闘計算用定数（サーバーサイドと同期）
const CIV_ARMOR_MAX_REDUCTION = 0.5;    // アーマーによる最大ダメージ軽減率（50%）
const CIV_ARMOR_PERCENT_DIVISOR = 100;  // アーマー値を軽減率に変換する除数
const CIV_ADVANTAGE_DISPLAY_THRESHOLD = 0.05; // 相性表示の閾値（±5%）

let civData = null;
let currentTab = 'buildings'; // 現在のアクティブタブを保持
let selectedAttackTarget = null; // 攻撃対象のユーザーID
let userTroops = []; // ユーザーの兵士データ

// 攻撃モーダルを開く
function openAttackModal(targetUserId, targetCivName, targetPower) {
    selectedAttackTarget = targetUserId;
    
    // モーダルを表示
    document.getElementById('attackModal').classList.add('active');
    
    // ターゲット情報を表示
    document.getElementById('attackModalTarget').innerHTML = `
        <div style="background: rgba(139, 0, 0, 0.3); padding: 12px; border-radius: 8px; margin-bottom: 15px;">
            <div style="color: #ff6b6b; font-weight: bold;">攻撃対象: ${escapeHtml(targetCivName)}</div>
            <div style="color: #888; font-size: 12px; margin-top: 5px;">防御力: ⚔️ ${targetPower}</div>
        </div>
    `;
    
    // 兵士データを読み込んで表示
    loadAttackTroops();
}

// 攻撃モーダルを閉じる
function closeAttackModal() {
    document.getElementById('attackModal').classList.remove('active');
    selectedAttackTarget = null;
}

// 攻撃用兵士を読み込む
async function loadAttackTroops() {
    const container = document.getElementById('attackTroopSelector');
    container.innerHTML = '<div class="loading">兵士を読み込み中...</div>';
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_troops'})
        });
        const data = await res.json();
        
        if (data.ok && data.user_troops && data.user_troops.filter(t => t.count > 0).length > 0) {
            userTroops = data.user_troops.filter(t => t.count > 0);
            
            container.innerHTML = userTroops.map(troop => `
                <div class="troop-select-row">
                    <div class="troop-select-info">
                        <span class="troop-select-icon">${troop.icon}</span>
                        <span class="troop-select-name">${troop.name}</span>
                        <div class="troop-select-stats">⚔️${troop.attack_power} 🛡️${troop.defense_power}</div>
                    </div>
                    <input type="range" class="troop-select-slider" 
                           id="attack-slider-${parseInt(troop.troop_type_id)}"
                           min="0" max="${parseInt(troop.count)}" value="0"
                           data-troop-id="${parseInt(troop.troop_type_id)}"
                           data-attack="${parseInt(troop.attack_power)}"
                           data-defense="${parseInt(troop.defense_power)}"
                           oninput="syncAttackTroopInput(${parseInt(troop.troop_type_id)}, this.value)">
                    <input type="number" class="troop-select-count" 
                           id="attack-count-${parseInt(troop.troop_type_id)}"
                           min="0" max="${parseInt(troop.count)}" value="0"
                           data-troop-id="${parseInt(troop.troop_type_id)}"
                           oninput="syncAttackTroopSlider(${parseInt(troop.troop_type_id)}, this.value)">
                    <span class="troop-select-max">/ ${parseInt(troop.count)}</span>
                </div>
            `).join('');
            
            updateAttackPowerDisplay();
        } else {
            container.innerHTML = '<p style="color: #888; text-align: center; padding: 20px;">兵士がいません。兵士タブで兵士を訓練してください。</p>';
        }
    } catch (e) {
        container.innerHTML = '<p style="color: #ff6b6b; text-align: center;">兵士の読み込みに失敗しました</p>';
    }
}

// スライダーと数値入力を同期
function syncAttackTroopInput(troopId, value) {
    const countInput = document.getElementById(`attack-count-${troopId}`);
    if (countInput) {
        countInput.value = value;
    }
    updateAttackPowerDisplay();
}

function syncAttackTroopSlider(troopId, value) {
    const slider = document.getElementById(`attack-slider-${troopId}`);
    if (slider) {
        const max = parseInt(slider.max);
        let val = parseInt(value) || 0;
        val = Math.max(0, Math.min(max, val));
        slider.value = val;
        document.getElementById(`attack-count-${troopId}`).value = val;
    }
    updateAttackPowerDisplay();
}

// 攻撃パワーを計算・表示
function updateAttackPowerDisplay() {
    let totalPower = 0;
    
    document.querySelectorAll('[id^="attack-count-"]').forEach(input => {
        const count = parseInt(input.value) || 0;
        if (count > 0) {
            const slider = document.getElementById(`attack-slider-${input.dataset.troopId}`);
            if (slider) {
                const attack = parseInt(slider.dataset.attack) || 0;
                const defense = parseInt(slider.dataset.defense) || 0;
                totalPower += (attack + Math.floor(defense / 2)) * count;
            }
        }
    });
    
    document.getElementById('attackPowerDisplay').textContent = totalPower;
    document.getElementById('confirmAttackBtn').disabled = totalPower === 0;
}

// 攻撃を実行
async function confirmAttack() {
    if (!selectedAttackTarget) return;
    
    // 選択した部隊を収集
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
    
    if (troops.length === 0) {
        showNotification('出撃部隊を選択してください', true);
        return;
    }
    
    closeAttackModal();
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'attack_with_troops',
                target_user_id: selectedAttackTarget,
                troops: troops
            })
        });
        const data = await res.json();
        
        if (data.ok) {
            const isVictory = data.result === 'victory';
            showNotification(data.message, !isVictory);
            loadData();
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// 攻撃モーダルの外側クリックで閉じる
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('attackModal');
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeAttackModal();
        }
    });
});

// 初期データ読み込み
async function loadData() {
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_data'})
        });
        civData = await res.json();
        
        if (civData.ok) {
            renderApp();
            
            // 収集された資源を通知
            if (civData.collected_resources && civData.collected_resources.length > 0) {
                const resourcesText = civData.collected_resources.map(r => 
                    `${r.icon} ${r.name}: +${Math.floor(r.amount)}`
                ).join('、');
                showNotification(`資源を収集しました: ${resourcesText}`);
            }
        } else {
            document.getElementById('app').innerHTML = `<div class="loading">エラー: ${civData.error}</div>`;
        }
    } catch (e) {
        console.error(e);
        document.getElementById('app').innerHTML = '<div class="loading">読み込みエラーが発生しました</div>';
    }
}

// メイン描画
function renderApp() {
    const civ = civData.civilization;
    const era = civData.current_era;
    const resources = civData.resources;
    const buildings = civData.buildings;
    const availableBuildings = civData.available_buildings;
    const balance = civData.balance;
    
    // 次の時代を取得
    const nextEra = civData.eras.find(e => e.era_order > era.era_order);
    
    document.getElementById('app').innerHTML = `
        <!-- ヘッダー -->
        <div class="civ-header">
            <div class="civ-title">
                <span class="civ-name" onclick="renameCiv()">${escapeHtml(civ.civilization_name)} ✏️</span>
                <span class="civ-era">${era.icon} ${era.name}</span>
            </div>
            <div class="civ-stats">
                <div class="stat-box">
                    <div class="stat-value">${civ.population}/${civ.max_population}</div>
                    <div class="stat-label">👥 人口</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">${civ.research_points}</div>
                    <div class="stat-label">📚 研究ポイント</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">${civ.military_power || 0}</div>
                    <div class="stat-label">⚔️ 軍事力</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">${Number(balance.coins).toLocaleString()}</div>
                    <div class="stat-label">🪙 コイン</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">${Number(balance.crystals || 0).toLocaleString()}</div>
                    <div class="stat-label">💎 クリスタル</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">${Number(balance.diamonds || 0).toLocaleString()}</div>
                    <div class="stat-label">💠 ダイヤモンド</div>
                </div>
            </div>
        </div>
        
        <!-- 資源バー -->
        <div class="resources-bar">
            ${resources.filter(r => r.unlocked).map(r => `
                <div class="resource-item">
                    <span class="resource-icon">${r.icon}</span>
                    <span class="resource-amount">${Math.floor(r.amount)}</span>
                    <span style="font-size: 12px; color: #888;">${r.name}</span>
                </div>
            `).join('')}
        </div>
        
        <!-- 投資セクション -->
        <div class="invest-section">
            <h3>💰 コインを投資</h3>
            <p style="color: #c0a080; margin-bottom: 15px;">コインを投資して研究ポイントと資源を獲得！（10コイン = 1研究ポイント + 基本資源ボーナス）</p>
            <div class="invest-form">
                <input type="number" id="investAmount" class="invest-input" value="1000" min="100" step="100">
                <button class="invest-btn" onclick="investCoins()">投資する</button>
                <div class="quick-invest-btns">
                    <button class="quick-invest-btn" onclick="setInvestAmount(500)">500</button>
                    <button class="quick-invest-btn" onclick="setInvestAmount(1000)">1000</button>
                    <button class="quick-invest-btn" onclick="setInvestAmount(5000)">5000</button>
                    <button class="quick-invest-btn" onclick="setInvestAmount(10000)">10000</button>
                </div>
            </div>
        </div>
        
        <!-- 時代進化 -->
        ${nextEra ? `
        <div class="era-progress">
            <h3>🌟 次の時代: ${nextEra.icon} ${nextEra.name}</h3>
            <p style="color: #c0a080; margin-bottom: 15px;">${nextEra.description}</p>
            <div class="era-requirements">
                <div class="era-req">
                    <div class="req-label">必要人口</div>
                    <div class="req-value ${civ.population >= nextEra.unlock_population ? 'met' : 'unmet'}">
                        ${civ.population} / ${nextEra.unlock_population}
                    </div>
                </div>
                <div class="era-req">
                    <div class="req-label">必要研究ポイント</div>
                    <div class="req-value ${civ.research_points >= nextEra.unlock_research_points ? 'met' : 'unmet'}">
                        ${civ.research_points} / ${nextEra.unlock_research_points}
                    </div>
                </div>
            </div>
            <button class="advance-era-btn" onclick="advanceEra()" 
                ${civ.population >= nextEra.unlock_population && civ.research_points >= nextEra.unlock_research_points ? '' : 'disabled'}>
                ${nextEra.name}に進化する
            </button>
        </div>
        ` : '<div class="era-progress"><h3>🏆 最高の時代に到達しました！</h3></div>'}
        
        <!-- タブ -->
        <div class="tabs">
            <button class="tab-btn ${currentTab === 'buildings' ? 'active' : ''}" data-tab="buildings">🏠 建物</button>
            <button class="tab-btn ${currentTab === 'research' ? 'active' : ''}" data-tab="research">📚 研究</button>
            <button class="tab-btn ${currentTab === 'market' ? 'active' : ''}" data-tab="market">🏪 市場</button>
            <button class="tab-btn ${currentTab === 'troops' ? 'active' : ''}" data-tab="troops">🎖️ 兵士</button>
            <button class="tab-btn ${currentTab === 'war' ? 'active' : ''}" data-tab="war">⚔️ 戦争</button>
            <button class="tab-btn ${currentTab === 'conquest' ? 'active' : ''}" data-tab="conquest">🏰 占領戦</button>
            <button class="tab-btn ${currentTab === 'shop' ? 'active' : ''}" data-tab="shop">💠 VIPショップ</button>
        </div>
        
        <!-- 建物タブ -->
        <div class="tab-content ${currentTab === 'buildings' ? 'active' : ''}" id="tab-buildings">
            <h3 style="color: #ffd700; margin-bottom: 20px;">🏗️ 建設可能な建物</h3>
            <div class="buildings-grid">
                ${renderBuildingsGrid(availableBuildings, buildings, resources)}
            </div>
        </div>
        
        <!-- 研究タブ -->
        <div class="tab-content ${currentTab === 'research' ? 'active' : ''}" id="tab-research">
            <h3 style="color: #87ceeb; margin-bottom: 20px;">🔬 研究ツリー</h3>
            <div class="research-tree">
                ${renderResearchTree()}
            </div>
        </div>
        
        <!-- 兵士タブ -->
        <div class="tab-content ${currentTab === 'troops' ? 'active' : ''}" id="tab-troops">
            <!-- 訓練キュー -->
            <div class="war-section" style="background: linear-gradient(135deg, rgba(70, 130, 180, 0.5) 0%, rgba(25, 25, 112, 0.5) 100%); border-color: #4682b4; margin-bottom: 20px;">
                <h3 style="color: #87ceeb;">⏳ 訓練キュー</h3>
                <div id="trainingQueueList">
                    <div class="loading">訓練キューを読み込み中...</div>
                </div>
            </div>
            
            <!-- 負傷兵 -->
            <div class="war-section" style="background: linear-gradient(135deg, rgba(220, 20, 60, 0.3) 0%, rgba(139, 0, 0, 0.3) 100%); border-color: #dc143c; margin-bottom: 20px;">
                <h3 style="color: #ff6b6b;">🏥 負傷兵</h3>
                <p style="color: #c0a080; margin-bottom: 10px;">病院または野戦病院を建設して負傷兵を治療しましょう</p>
                <div id="woundedTroopsList">
                    <div class="loading">負傷兵を読み込み中...</div>
                </div>
                <div id="healingQueueList" style="margin-top: 15px;"></div>
            </div>
            
            <!-- 防御設定 -->
            <div class="war-section" style="background: linear-gradient(135deg, rgba(50, 205, 50, 0.3) 0%, rgba(0, 100, 0, 0.3) 100%); border-color: #32cd32; margin-bottom: 20px;">
                <h3 style="color: #90ee90;">🛡️ 防御部隊設定</h3>
                <p style="color: #c0a080; margin-bottom: 10px;">攻撃された時に自動的に防御に使用される兵士を設定します</p>
                <div id="defenseSettingsList">
                    <div class="loading">防御設定を読み込み中...</div>
                </div>
            </div>
            
            <!-- 兵士訓練 -->
            <div class="war-section" style="background: linear-gradient(135deg, rgba(139, 69, 19, 0.5) 0%, rgba(50, 30, 10, 0.5) 100%); border-color: #8b4513;">
                <h3 style="color: #ffd700;">🎖️ 兵士を訓練</h3>
                <p style="color: #c0a080; margin-bottom: 20px;">兵舎や軍事施設を建設すると、より多くの兵士を訓練できます。訓練には時間がかかります。</p>
                <div class="targets-list" id="troopsList">
                    <div class="loading">兵種を読み込み中...</div>
                </div>
            </div>
        </div>
        
        <!-- 占領戦タブ -->
        <div class="tab-content ${currentTab === 'conquest' ? 'active' : ''}" id="tab-conquest">
            <div class="war-section" style="background: linear-gradient(135deg, rgba(153, 50, 204, 0.5) 0%, rgba(75, 0, 130, 0.5) 100%); border-color: #9932cc;">
                <h3 style="color: #da70d6;">🏰 占領戦</h3>
                <p style="color: #c0a080; margin-bottom: 20px;">
                    占領戦は毎週月曜日にリセットされるシーズン制のコンテンツです。<br>
                    マップ上の城を攻め落とし、中央の神城⛩️を目指しましょう！
                </p>
                <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
                    <div class="stat-box" style="background: rgba(0,0,0,0.3);">
                        <div style="font-size: 32px;">🏰</div>
                        <div style="color: #ffd700; font-size: 14px;">城を占領</div>
                    </div>
                    <div class="stat-box" style="background: rgba(0,0,0,0.3);">
                        <div style="font-size: 32px;">🛡️</div>
                        <div style="color: #ffd700; font-size: 14px;">防御部隊配置</div>
                    </div>
                    <div class="stat-box" style="background: rgba(0,0,0,0.3);">
                        <div style="font-size: 32px;">⛩️</div>
                        <div style="color: #ffd700; font-size: 14px;">神城を奪取</div>
                    </div>
                </div>
                <a href="./conquest.php" class="invest-btn" style="display: inline-block; text-decoration: none; padding: 15px 30px; font-size: 18px; background: linear-gradient(135deg, #9932cc 0%, #da70d6 100%);">
                    ⚔️ 占領戦に参加する
                </a>
            </div>
        </div>
        
        <!-- 戦争タブ -->
        <div class="tab-content ${currentTab === 'war' ? 'active' : ''}" id="tab-war">
            <div class="war-section">
                <h3>⚔️ 他の文明を攻撃</h3>
                <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <h4 style="color: #ff6b6b; margin: 0 0 10px 0;">あなたの軍事力</h4>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                        <div>
                            <span style="color: #888;">🏰 建物:</span>
                            <span style="color: #ffd700; font-weight: bold;" id="myBuildingPower">${civ.building_power || 0}</span>
                        </div>
                        <div>
                            <span style="color: #888;">🎖️ 兵士:</span>
                            <span style="color: #ffd700; font-weight: bold;" id="myTroopPower">${civ.troop_power || 0}</span>
                        </div>
                        <div>
                            <span style="color: #888;">⚔️ 装備:</span>
                            <span style="color: #9932cc; font-weight: bold;" id="myEquipmentPower">${civData.military_power_breakdown?.equipment_power || 0}</span>
                        </div>
                        <div>
                            <span style="color: #888;">🛡️ アーマー:</span>
                            <span style="color: #87ceeb; font-weight: bold;" id="myArmor">${Math.floor(civData.military_power_breakdown?.equipment_buffs?.armor || 0)}</span>
                        </div>
                        <div>
                            <span style="color: #888;">❤️ 体力:</span>
                            <span style="color: #ff6b6b; font-weight: bold;" id="myHealth">${Math.floor(civData.military_power_breakdown?.equipment_buffs?.health || 0)}</span>
                        </div>
                        <div>
                            <span style="color: #888;">⚔️ 合計:</span>
                            <span style="color: #ff6b6b; font-weight: bold; font-size: 1.2em;" id="myTotalPower">${civ.military_power || 0}</span>
                        </div>
                    </div>
                    <p style="color: #888; font-size: 11px; margin-top: 10px;">💡 装備のバフ（攻撃力・体力・アーマー）が戦闘力に影響します。アーマーは敵の攻撃を軽減します。</p>
                </div>
                <p style="color: #c0a080; margin-bottom: 20px;">軍事施設を建設して軍事力を上げ、他の文明から資源を略奪しましょう！</p>
                <div class="targets-list" id="targetsList">
                    <div class="loading">攻撃対象を読み込み中...</div>
                </div>
            </div>
            
            <!-- 戦争ログセクション -->
            <div class="war-section" style="margin-top: 20px;">
                <h3>📜 戦争ログ</h3>
                <div id="warLogsList" style="max-height: 400px; overflow-y: auto;">
                    <div class="loading">戦争ログを読み込み中...</div>
                </div>
            </div>
        </div>
        
        <!-- VIPショップタブ -->
        <div class="tab-content ${currentTab === 'shop' ? 'active' : ''}" id="tab-shop">
            <div class="invest-section" style="background: linear-gradient(135deg, rgba(153, 50, 204, 0.5) 0%, rgba(75, 0, 130, 0.5) 100%); border-color: #9932cc;">
                <h3 style="color: #da70d6;">💠 VIPショップ（ダイヤモンド専用）</h3>
                <p style="color: #c0a080; margin-bottom: 20px;">ダイヤモンドを使って特別なブーストやアイテムを購入できます</p>
                <div class="buildings-grid">
                    <div class="building-card" style="border-color: #9932cc;">
                        <div class="building-header">
                            <span class="building-icon">⚡</span>
                            <span class="building-name">資源生産2倍</span>
                        </div>
                        <div class="building-desc">24時間、すべての資源生産量が2倍になります</div>
                        <div class="building-cost">💠 5 ダイヤモンド</div>
                        <button class="build-btn" onclick="buyVipBoost('production_2x')" style="background: linear-gradient(135deg, #9932cc 0%, #da70d6 100%);">
                            購入する
                        </button>
                    </div>
                    <div class="building-card" style="border-color: #9932cc;">
                        <div class="building-header">
                            <span class="building-icon">📚</span>
                            <span class="building-name">研究速度2倍</span>
                        </div>
                        <div class="building-desc">12時間、研究速度が2倍になります</div>
                        <div class="building-cost">💠 3 ダイヤモンド</div>
                        <button class="build-btn" onclick="buyVipBoost('research_speed')" style="background: linear-gradient(135deg, #9932cc 0%, #da70d6 100%);">
                            購入する
                        </button>
                    </div>
                    <div class="building-card" style="border-color: #9932cc;">
                        <div class="building-header">
                            <span class="building-icon">🏗️</span>
                            <span class="building-name">建設速度2倍</span>
                        </div>
                        <div class="building-desc">12時間、建設速度が2倍になります</div>
                        <div class="building-cost">💠 3 ダイヤモンド</div>
                        <button class="build-btn" onclick="buyVipBoost('build_speed')" style="background: linear-gradient(135deg, #9932cc 0%, #da70d6 100%);">
                            購入する
                        </button>
                    </div>
                    <div class="building-card" style="border-color: #9932cc;">
                        <div class="building-header">
                            <span class="building-icon">📦</span>
                            <span class="building-name">資源パック</span>
                        </div>
                        <div class="building-desc">食料、木材、石材を各1000獲得します</div>
                        <div class="building-cost">💠 10 ダイヤモンド</div>
                        <button class="build-btn" onclick="buyVipBoost('resource_pack')" style="background: linear-gradient(135deg, #9932cc 0%, #da70d6 100%);">
                            購入する
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 市場タブ -->
        <div class="tab-content ${currentTab === 'market' ? 'active' : ''}" id="tab-market">
            <div class="invest-section" style="background: linear-gradient(135deg, rgba(139, 115, 85, 0.5) 0%, rgba(100, 80, 60, 0.5) 100%); border-color: #d4a574;">
                <h3 style="color: #ffd700;">🏪 市場 - 資源交換</h3>
                <p style="color: #c0a080; margin-bottom: 20px;">市場を建設していると、資源を他の資源に交換できます。交換レート: 2:1</p>
                ${renderMarketExchange(resources)}
            </div>
        </div>
    `;
    
    // タブ切り替え
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentTab = btn.dataset.tab; // 現在のタブを保存
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
            
            // 戦争タブの場合、攻撃対象を読み込む
            if (btn.dataset.tab === 'war') {
                loadTargets();
            }
            // 兵士タブの場合、兵種・キュー・負傷兵を読み込む
            if (btn.dataset.tab === 'troops') {
                loadTroops();
                loadTrainingQueue();
                loadWoundedTroops();
                loadDefenseSettings();
            }
            // 市場タブの場合、市場を読み込む
            if (btn.dataset.tab === 'market') {
                loadMarketData();
            }
        });
    });
    
    // 市場タブがアクティブな場合、市場データを読み込む
    if (currentTab === 'market') {
        loadMarketData();
    }
    // 戦争タブがアクティブな場合、攻撃対象を読み込む
    if (currentTab === 'war') {
        loadTargets();
    }
    // 兵士タブがアクティブな場合、兵種・キュー・負傷兵を読み込む
    if (currentTab === 'troops') {
        loadTroops();
        loadTrainingQueue();
        loadWoundedTroops();
        loadDefenseSettings();
    }
}

// 建物グリッドを描画
function renderBuildingsGrid(availableBuildings, ownedBuildings, resources) {
    return availableBuildings.map(bt => {
        const owned = ownedBuildings.filter(b => b.building_type_id == bt.id);
        const ownedCount = owned.length;
        const constructing = owned.find(b => b.is_constructing);
        
        let costText = `🪙 ${bt.base_build_cost_coins}`;
        if (bt.base_build_cost_resources) {
            const costs = JSON.parse(bt.base_build_cost_resources);
            Object.entries(costs).forEach(([key, val]) => {
                const res = resources.find(r => r.resource_key === key);
                costText += ` | ${res ? res.icon : '❓'} ${val}`;
            });
        }
        
        let statusClass = '';
        let statusText = '';
        let instantCompleteBtn = '';
        if (constructing) {
            statusClass = 'constructing';
            const remaining = Math.max(0, Math.ceil((new Date(constructing.construction_completes_at) - new Date()) / 1000));
            if (remaining <= 0) {
                statusText = `建設完了！データを更新中...`;
            } else {
                statusText = `建設中... ${formatTime(remaining)}`;
                const crystalCost = Math.max(5, Math.ceil(remaining / 60));
                const diamondCost = Math.max(1, Math.ceil(remaining / 120));
                instantCompleteBtn = `
                    <div style="display: flex; gap: 5px; margin-top: 8px;">
                        <button class="instant-btn" onclick="instantCompleteBuilding(${constructing.id})" style="flex: 1; padding: 8px 12px; background: linear-gradient(135deg, #9932cc 0%, #da70d6 100%); color: white; border: none; border-radius: 6px; font-size: 11px; cursor: pointer;">💎 ${crystalCost}</button>
                        <button class="instant-btn" onclick="instantCompleteBuildingDiamond(${constructing.id})" style="flex: 1; padding: 8px 12px; background: linear-gradient(135deg, #00bfff 0%, #1e90ff 100%); color: white; border: none; border-radius: 6px; font-size: 11px; cursor: pointer;">💠 ${diamondCost}</button>
                    </div>`;
            }
        } else if (ownedCount > 0) {
            statusClass = 'owned';
        }
        
        // 前提条件表示
        const canBuild = bt.can_build !== false;
        const missingPrereqs = bt.missing_prerequisites || [];
        const prereqText = missingPrereqs.length > 0 
            ? `<div style="color: #ff6b6b; font-size: 12px; margin-bottom: 10px;">🔒 必要: ${missingPrereqs.join(', ')}</div>` 
            : '';
        
        // 建設不可の場合はスタイルを変更
        const lockedClass = !canBuild ? 'locked' : '';
        
        return `
            <div class="building-card ${statusClass} ${lockedClass}" style="${!canBuild ? 'opacity: 0.7;' : ''}">
                <div class="building-header">
                    <span class="building-icon">${bt.icon}</span>
                    <span class="building-name">${bt.name}</span>
                    ${ownedCount > 0 ? `<span class="building-level">×${ownedCount}</span>` : ''}
                </div>
                <div class="building-desc">${bt.description || ''}</div>
                <div class="building-stats">
                    ${bt.production_rate > 0 ? `<span class="building-stat">⚡ ${bt.production_rate}/h</span>` : ''}
                    ${bt.population_capacity > 0 ? `<span class="building-stat">👥 +${bt.population_capacity}</span>` : ''}
                    ${bt.military_power > 0 ? `<span class="building-stat">⚔️ +${bt.military_power}</span>` : ''}
                </div>
                <div class="building-cost">${costText} | ⏱️ ${formatTime(bt.base_build_time_seconds)}</div>
                ${prereqText}
                ${statusText ? `<div style="color: #ffa500; margin-bottom: 10px;">${statusText}</div>` : ''}
                ${instantCompleteBtn}
                <button class="build-btn" onclick="buildBuilding(${bt.id})" ${constructing || !canBuild ? 'disabled' : ''}>
                    ${!canBuild ? '🔒 ロック中' : '建設する'}
                </button>
            </div>
        `;
    }).join('');
}

// 研究ツリーを描画
function renderResearchTree() {
    const researches = civData.available_researches;
    const userResearches = civData.user_researches;
    
    return researches.map(r => {
        const userResearch = userResearches.find(ur => ur.research_id == r.id);
        const isCompleted = userResearch && userResearch.is_completed;
        const isResearching = userResearch && userResearch.is_researching;
        
        // 前提研究チェック
        let isLocked = false;
        if (r.prerequisite_research_id) {
            const prereq = userResearches.find(ur => ur.research_id == r.prerequisite_research_id);
            isLocked = !prereq || !prereq.is_completed;
        }
        
        let statusClass = '';
        let statusText = '';
        let instantCompleteBtn = '';
        if (isCompleted) {
            statusClass = 'completed';
            statusText = '✅ 完了';
        } else if (isResearching) {
            statusClass = 'researching';
            const remaining = Math.max(0, Math.ceil((new Date(userResearch.research_completes_at) - new Date()) / 1000));
            if (remaining <= 0) {
                statusText = `研究完了！データを更新中...`;
            } else {
                statusText = `研究中... ${formatTime(remaining)}`;
                const crystalCost = Math.max(3, Math.ceil(remaining / 60));
                const diamondCost = Math.max(1, Math.ceil(remaining / 120));
                instantCompleteBtn = `
                    <div style="display: flex; gap: 5px; margin-top: 8px;">
                        <button class="instant-btn" onclick="instantCompleteResearch(${userResearch.id})" style="flex: 1; padding: 8px 12px; background: linear-gradient(135deg, #9932cc 0%, #da70d6 100%); color: white; border: none; border-radius: 6px; font-size: 11px; cursor: pointer;">💎 ${crystalCost}</button>
                        <button class="instant-btn" onclick="instantCompleteResearchDiamond(${userResearch.id})" style="flex: 1; padding: 8px 12px; background: linear-gradient(135deg, #00bfff 0%, #1e90ff 100%); color: white; border: none; border-radius: 6px; font-size: 11px; cursor: pointer;">💠 ${diamondCost}</button>
                    </div>`;
            }
        } else if (isLocked) {
            statusClass = 'locked';
            statusText = '🔒 前提研究が必要';
        }
        
        return `
            <div class="research-card ${statusClass}">
                <div class="research-header">
                    <span class="research-icon">${r.icon}</span>
                    <span class="research-name">${r.name}</span>
                </div>
                <div class="research-desc">${r.description || ''}</div>
                <div class="research-cost">
                    <span>📚 ${r.research_cost_points} ポイント</span>
                    <span>⏱️ ${formatTime(r.research_time_seconds)}</span>
                </div>
                ${statusText ? `<div style="margin-bottom: 10px; font-size: 13px;">${statusText}</div>` : ''}
                ${instantCompleteBtn}
                ${!isCompleted && !isResearching && !isLocked ? `
                    <button class="research-btn" onclick="startResearch(${r.id})" 
                        ${civData.civilization.research_points < r.research_cost_points ? 'disabled' : ''}>
                        研究開始
                    </button>
                ` : ''}
            </div>
        `;
    }).join('');
}

// 市場交換UIを描画
function renderMarketExchange(resources) {
    // 市場建物を持っているか確認
    const markets = civData.buildings.filter(b => b.building_key === 'market' && !b.is_constructing);
    const marketCount = markets.length;
    const totalMarketLevel = markets.reduce((sum, m) => sum + (parseInt(m.level) || 1), 0);
    
    if (marketCount === 0) {
        return `
            <div style="text-align: center; padding: 40px; color: #c0a080;">
                <p style="font-size: 24px; margin-bottom: 15px;">🏪</p>
                <p>市場を建設すると、資源を交換できるようになります。</p>
                <p style="font-size: 13px; margin-top: 10px;">建物タブから「市場」を建設してください。</p>
            </div>
        `;
    }
    
    const unlockedResources = resources.filter(r => r.unlocked);
    
    if (unlockedResources.length < 2) {
        return `
            <div style="text-align: center; padding: 40px; color: #c0a080;">
                <p>交換できる資源が足りません。資源を2種類以上アンロックしてください。</p>
            </div>
        `;
    }
    
    // 市場ボーナスを計算
    const marketBonus = Math.min(50, (marketCount * 5) + (totalMarketLevel * 2));
    
    return `
        <div class="buildings-grid">
            <div class="building-card" style="border-color: #d4a574; grid-column: span 2;">
                <div class="building-header">
                    <span class="building-icon">🔄</span>
                    <span class="building-name">資源交換</span>
                </div>
                <div class="building-desc">資源を他の資源に交換します。レートは資源の価値により変動します。</div>
                
                <div style="background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px; margin-top: 10px;">
                    <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <span style="color: #888;">🏪 市場数:</span>
                            <span style="color: #ffd700; font-weight: bold;">${marketCount}</span>
                        </div>
                        <div>
                            <span style="color: #888;">📈 合計レベル:</span>
                            <span style="color: #ffd700; font-weight: bold;">${totalMarketLevel}</span>
                        </div>
                        <div>
                            <span style="color: #888;">✨ レートボーナス:</span>
                            <span style="color: #32cd32; font-weight: bold;">+${marketBonus}%</span>
                        </div>
                    </div>
                    <p style="color: #888; font-size: 12px; margin-top: 8px;">💡 市場を増やすとレートが改善されます（市場1つ:+5%, レベル:+2%, 最大+50%）</p>
                </div>
                
                <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center; margin-top: 15px;">
                    <div style="flex: 1; min-width: 150px;">
                        <label style="display: block; margin-bottom: 8px; color: #c0a080; font-size: 13px;">交換する資源</label>
                        <select id="fromResource" class="invest-input" style="width: 100%;">
                            ${unlockedResources.map(r => `<option value="${r.resource_type_id}" data-key="${r.resource_key}" data-amount="${r.amount}">${r.icon} ${r.name} (${Math.floor(r.amount)})</option>`).join('')}
                        </select>
                    </div>
                    <div style="flex: 0; padding-top: 25px; font-size: 24px;">→</div>
                    <div style="flex: 1; min-width: 150px;">
                        <label style="display: block; margin-bottom: 8px; color: #c0a080; font-size: 13px;">受け取る資源</label>
                        <select id="toResource" class="invest-input" style="width: 100%;">
                            ${unlockedResources.map(r => `<option value="${r.resource_type_id}" data-key="${r.resource_key}">${r.icon} ${r.name}</option>`).join('')}
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 15px;">
                    <label style="display: block; margin-bottom: 8px; color: #c0a080; font-size: 13px;">交換する量</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="number" id="exchangeAmount" class="invest-input" value="100" min="1" step="1" style="flex: 1;">
                        <div class="quick-invest-btns">
                            <button class="quick-invest-btn" onclick="setExchangeAmount(10)">10</button>
                            <button class="quick-invest-btn" onclick="setExchangeAmount(50)">50</button>
                            <button class="quick-invest-btn" onclick="setExchangeAmount(100)">100</button>
                            <button class="quick-invest-btn" onclick="setExchangeAmount(500)">500</button>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 15px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                    <span style="color: #c0a080;">交換結果: </span>
                    <span id="exchangeResult" style="color: #ffd700;">--</span>
                </div>
                
                <button class="build-btn" onclick="exchangeResources()" style="margin-top: 15px; background: linear-gradient(135deg, #d4a574 0%, #8b4513 100%);">
                    交換する
                </button>
            </div>
        </div>
    `;
}

// 市場データを読み込む
function loadMarketData() {
    // 交換結果の計算をセットアップ
    const fromSelect = document.getElementById('fromResource');
    const toSelect = document.getElementById('toResource');
    const amountInput = document.getElementById('exchangeAmount');
    
    if (!fromSelect || !toSelect || !amountInput) {
        return; // 市場が建設されていない場合などは要素が存在しない
    }
    
    // 市場情報をサーバーから取得して資源価値を使用
    fetch('civilization_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'get_market_info'})
    })
    .then(res => res.json())
    .then(marketInfo => {
        // サーバーから資源価値を取得（フォールバック用にデフォルト値も設定）
        const resourceValues = marketInfo.ok ? marketInfo.resource_values : {
            'food': 1.0, 'wood': 1.0, 'stone': 1.2, 'bronze': 1.5, 'iron': 2.0,
            'gold': 3.0, 'knowledge': 2.5, 'oil': 3.5, 'crystal': 4.0,
            'mana': 4.5, 'uranium': 5.0, 'diamond': 6.0
        };
        
        // 市場ボーナスを計算
        const markets = civData.buildings.filter(b => b.building_key === 'market' && !b.is_constructing);
        const marketCount = markets.length;
        const totalMarketLevel = markets.reduce((sum, m) => sum + (parseInt(m.level) || 1), 0);
        const marketBonus = Math.min(0.5, (marketCount * 0.05) + (totalMarketLevel * 0.02));
        
        const updateResult = () => {
            const resultElement = document.getElementById('exchangeResult');
            if (!resultElement) return;
            
            const fromId = fromSelect.value;
            const toId = toSelect.value;
            const amount = parseInt(amountInput.value) || 0;
            
            if (fromId === toId) {
                resultElement.textContent = '同じ資源は交換できません';
                return;
            }
            
            const fromOption = fromSelect.options[fromSelect.selectedIndex];
            const toOption = toSelect.options[toSelect.selectedIndex];
            const fromName = fromOption.textContent.split('(')[0].trim();
            const toName = toOption.textContent.split('(')[0].trim();
            const fromKey = fromOption.dataset.key || 'food';
            const toKey = toOption.dataset.key || 'food';
            
            // 交換レートを計算
            const fromValue = resourceValues[fromKey] || 1.0;
            const toValue = resourceValues[toKey] || 1.0;
            const baseRate = fromValue / toValue;
            const finalRate = baseRate * (1 + marketBonus);
            
            const received = Math.floor(amount * finalRate);
            const ratePercent = Math.round(finalRate * 100);
            resultElement.innerHTML = `${amount} ${fromName} → <strong style="color: #32cd32;">${received}</strong> ${toName} <span style="color: #888; font-size: 12px;">(レート: ${ratePercent}%)</span>`;
        };
        
        fromSelect.addEventListener('change', updateResult);
        toSelect.addEventListener('change', updateResult);
        amountInput.addEventListener('input', updateResult);
        
        updateResult();
    })
    .catch(err => {
        console.error('Failed to load market info:', err);
    });
}

// 交換量をセット
function setExchangeAmount(amount) {
    document.getElementById('exchangeAmount').value = amount;
    // 結果を更新
    document.getElementById('exchangeAmount').dispatchEvent(new Event('input'));
}

// 資源を交換
async function exchangeResources() {
    const fromResourceId = parseInt(document.getElementById('fromResource').value);
    const toResourceId = parseInt(document.getElementById('toResource').value);
    const amount = parseInt(document.getElementById('exchangeAmount').value) || 0;
    
    if (fromResourceId === toResourceId) {
        showNotification('同じ資源は交換できません', true);
        return;
    }
    
    if (amount < 1) {
        showNotification('最低交換量は1です', true);
        return;
    }
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'exchange_resources',
                from_resource_id: fromResourceId,
                to_resource_id: toResourceId,
                amount: amount
            })
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

// 攻撃対象を読み込む
async function loadTargets() {
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_targets'})
        });
        const data = await res.json();
        
        // 自分の軍事力を更新
        if (data.my_military_power) {
            const myBuildingPower = document.getElementById('myBuildingPower');
            const myTroopPower = document.getElementById('myTroopPower');
            const myEquipmentPower = document.getElementById('myEquipmentPower');
            const myTotalPower = document.getElementById('myTotalPower');
            if (myBuildingPower) myBuildingPower.textContent = data.my_military_power.building_power || 0;
            if (myTroopPower) myTroopPower.textContent = data.my_military_power.troop_power || 0;
            if (myEquipmentPower) myEquipmentPower.textContent = data.my_military_power.equipment_power || 0;
            if (myTotalPower) myTotalPower.textContent = data.my_military_power.total_power || 0;
            
            // 装備バフを更新
            const myArmor = document.getElementById('myArmor');
            if (myArmor && data.my_military_power.equipment_buffs) {
                myArmor.textContent = Math.floor(data.my_military_power.equipment_buffs.armor || 0);
            }
            const myHealth = document.getElementById('myHealth');
            if (myHealth && data.my_military_power.equipment_buffs) {
                myHealth.textContent = Math.floor(data.my_military_power.equipment_buffs.health || 0);
            }
        }
        
        if (data.ok && data.targets.length > 0) {
            const myPower = data.my_military_power?.total_power || 0;
            const myArmor = data.my_military_power?.equipment_buffs?.armor || 0;
            
            document.getElementById('targetsList').innerHTML = data.targets.map(t => {
                const targetPower = t.military_power || 0;
                const targetArmor = t.equipment_buffs?.armor || 0;
                
                // アーマーによる軽減を考慮した有利不利計算
                // 自分のアーマーは相手の攻撃を軽減、相手のアーマーは自分の攻撃を軽減
                const myArmorReduction = Math.min(CIV_ARMOR_MAX_REDUCTION, myArmor / CIV_ARMOR_PERCENT_DIVISOR);
                const targetArmorReduction = Math.min(CIV_ARMOR_MAX_REDUCTION, targetArmor / CIV_ARMOR_PERCENT_DIVISOR);
                
                // 相性ボーナスを考慮
                const troopAdvantage = t.troop_advantage_multiplier || 1.0;
                
                const myEffectivePower = myPower * (1 - targetArmorReduction) * troopAdvantage;
                const targetEffectivePower = targetPower * (1 - myArmorReduction);
                
                const powerDiff = myEffectivePower - targetEffectivePower;
                const powerClass = powerDiff > 0 ? 'color: #32cd32;' : (powerDiff < 0 ? 'color: #ff6b6b;' : 'color: #ffd700;');
                const powerIndicator = powerDiff > 0 ? '✅ 有利' : (powerDiff < 0 ? '⚠️ 不利' : '⚖️ 互角');
                
                // 相性ボーナス表示
                const advantageThresholdHigh = 1.0 + CIV_ADVANTAGE_DISPLAY_THRESHOLD;
                const advantageThresholdLow = 1.0 - CIV_ADVANTAGE_DISPLAY_THRESHOLD;
                let advantageText = '';
                if (troopAdvantage > advantageThresholdHigh) {
                    const bonusPercent = Math.round((troopAdvantage - 1) * 100);
                    advantageText = `<div style="color: #32cd32; font-size: 11px; margin-bottom: 5px;">🎯 相性有利 +${bonusPercent}%</div>`;
                } else if (troopAdvantage < advantageThresholdLow) {
                    const penaltyPercent = Math.round((1 - troopAdvantage) * 100);
                    advantageText = `<div style="color: #ff6b6b; font-size: 11px; margin-bottom: 5px;">⚠️ 相性不利 -${penaltyPercent}%</div>`;
                }
                
                // 装備バフ表示
                const equipBuffs = t.equipment_buffs || {};
                const hasEquipBuffs = (equipBuffs.attack > 0 || equipBuffs.armor > 0 || equipBuffs.health > 0);
                const equipBuffText = hasEquipBuffs ? `<div style="color: #9932cc; font-size: 11px; margin-bottom: 5px;">⚔️${Math.floor(equipBuffs.attack || 0)} 🛡️${Math.floor(equipBuffs.armor || 0)} ❤️${Math.floor(equipBuffs.health || 0)}</div>` : '';
                
                // 兵種構成表示
                const troopComp = t.troop_composition || {};
                let troopCompText = '';
                const categories = ['infantry', 'cavalry', 'ranged', 'siege'];
                const categoryIcons = {'infantry': '🗡️', 'cavalry': '🐴', 'ranged': '🏹', 'siege': '💣'};
                const hasAnyTroops = categories.some(c => (troopComp[c]?.count || 0) > 0);
                if (hasAnyTroops) {
                    troopCompText = '<div style="display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 5px;">';
                    categories.forEach(c => {
                        const count = troopComp[c]?.count || 0;
                        if (count > 0) {
                            troopCompText += `<span style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 3px; font-size: 10px;">${categoryIcons[c]} ${count}</span>`;
                        }
                    });
                    troopCompText += '</div>';
                }
                
                return `
                <div class="target-card">
                    <div class="target-header">
                        <span class="target-name">${escapeHtml(t.civilization_name)}</span>
                        <span class="target-power" style="${powerClass}">⚔️ ${targetPower}</span>
                    </div>
                    <div style="color: #888; font-size: 13px; margin-bottom: 5px;">
                        @${escapeHtml(t.handle)} | 👥 ${t.population}人
                    </div>
                    ${troopCompText}
                    ${equipBuffText}
                    ${advantageText}
                    <div style="font-size: 12px; margin-bottom: 10px; ${powerClass}">
                        ${powerIndicator}
                    </div>
                    <button class="attack-btn" onclick="openAttackModal(${t.user_id}, '${escapeHtml(t.civilization_name).replace(/'/g, "\\'")}', ${targetPower})">
                        ⚔️ 攻撃する
                    </button>
                </div>
            `}).join('');
        } else {
            document.getElementById('targetsList').innerHTML = '<p style="color: #888;">攻撃可能な文明がありません</p>';
        }
        
        // 戦争ログも読み込む
        loadWarLogs();
    } catch (e) {
        console.error(e);
    }
}

// 戦争ログを読み込む
async function loadWarLogs() {
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_war_logs'})
        });
        const data = await res.json();
        
        const warLogsList = document.getElementById('warLogsList');
        if (!warLogsList) return;
        
        if (data.ok && data.war_logs.length > 0) {
            const myUserId = data.my_user_id;
            warLogsList.innerHTML = data.war_logs.map(log => {
                const isAttacker = log.attacker_user_id == myUserId;
                const isWinner = log.winner_user_id == myUserId;
                const resultText = isWinner ? '勝利' : '敗北';
                const resultClass = isWinner ? 'color: #32cd32;' : 'color: #ff6b6b;';
                const actionText = isAttacker ? '攻撃' : '防衛';
                
                // 相手の文明名とユーザーハンドルを取得
                const opponentCivName = isAttacker ? log.defender_civ_name : log.attacker_civ_name;
                const opponentHandle = isAttacker ? log.defender_handle : log.attacker_handle;
                const opponentDisplayName = isAttacker ? log.defender_name : log.attacker_name;
                const battleTime = new Date(log.battle_at).toLocaleString('ja-JP');
                
                let lootText = '';
                if (isWinner && (log.loot_coins > 0 || (log.loot_resources && Object.keys(JSON.parse(log.loot_resources || '{}')).length > 0))) {
                    const lootResources = JSON.parse(log.loot_resources || '{}');
                    lootText = `<div style="font-size: 11px; color: #32cd32; margin-top: 5px;">💰 ${log.loot_coins}コイン`;
                    for (const [key, val] of Object.entries(lootResources)) {
                        lootText += ` | ${key}: +${val}`;
                    }
                    lootText += '</div>';
                }
                
                return `
                <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; margin-bottom: 10px; border-left: 3px solid ${isWinner ? '#32cd32' : '#ff6b6b'};">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span style="font-weight: bold; ${resultClass}">${resultText}</span>
                            <span style="color: #888;"> - ${actionText}</span>
                        </div>
                        <span style="color: #888; font-size: 11px;">${battleTime}</span>
                    </div>
                    <div style="margin-top: 5px; font-size: 13px;">
                        <span style="color: #c0a080;">vs</span> 
                        <span style="color: #ffd700;">${escapeHtml(opponentCivName || '不明の文明')}</span>
                        <span style="color: #87ceeb; font-size: 12px;">(@${escapeHtml(opponentHandle || '?')})</span>
                    </div>
                    <div style="margin-top: 5px; font-size: 12px; color: #888;">
                        ⚔️ ${log.attacker_power} vs 🛡️ ${log.defender_power}
                    </div>
                    ${lootText}
                </div>
            `}).join('');
        } else {
            warLogsList.innerHTML = '<p style="color: #888;">戦争ログがありません</p>';
        }
    } catch (e) {
        console.error(e);
        document.getElementById('warLogsList').innerHTML = '<p style="color: #888;">戦争ログの読み込みに失敗しました</p>';
    }
}

// コイン投資
async function investCoins() {
    const amount = parseInt(document.getElementById('investAmount').value) || 0;
    
    if (amount < 100) {
        showNotification('最低投資額は100コインです', true);
        return;
    }
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'invest_coins', amount: amount})
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

function setInvestAmount(amount) {
    document.getElementById('investAmount').value = amount;
}

// 建物建設
async function buildBuilding(buildingTypeId) {
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'build', building_type_id: buildingTypeId})
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

// 研究開始
async function startResearch(researchId) {
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'research', research_id: researchId})
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

// 時代進化
async function advanceEra() {
    if (!confirm('次の時代に進化しますか？\n研究ポイントを消費します。')) return;
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'advance_era'})
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

// 兵種を読み込む
async function loadTroops() {
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_troops'})
        });
        const data = await res.json();
        
        if (data.ok) {
            const troopsList = document.getElementById('troopsList');
            
            // 兵種カテゴリの相性情報
            const advantageInfo = data.troop_advantage_info || {
                'infantry': {name: '歩兵', icon: '🗡️', strong_against: 'ranged', weak_against: 'cavalry'},
                'cavalry': {name: '騎兵', icon: '🐴', strong_against: 'infantry', weak_against: 'ranged'},
                'ranged': {name: '遠距離', icon: '🏹', strong_against: 'cavalry', weak_against: 'infantry'},
                'siege': {name: '攻城', icon: '💣', strong_against: 'infantry', weak_against: 'cavalry'}
            };
            
            if (data.available_troops && data.available_troops.length > 0) {
                // 相性説明を先頭に追加
                let advantageHtml = `
                    <div class="target-card" style="border-color: #ffd700; background: rgba(255, 215, 0, 0.1); grid-column: span 2;">
                        <div class="target-header">
                            <span class="target-name">⚔️ 兵種相性システム</span>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px;">
                            <div style="flex: 1; min-width: 200px;">
                                <div style="color: #ffd700; font-weight: bold; margin-bottom: 5px;">🗡️ 歩兵</div>
                                <div style="color: #32cd32; font-size: 12px;">✓ 遠距離に強い</div>
                                <div style="color: #ff6b6b; font-size: 12px;">✗ 騎兵に弱い</div>
                            </div>
                            <div style="flex: 1; min-width: 200px;">
                                <div style="color: #ffd700; font-weight: bold; margin-bottom: 5px;">🐴 騎兵</div>
                                <div style="color: #32cd32; font-size: 12px;">✓ 歩兵に強い</div>
                                <div style="color: #ff6b6b; font-size: 12px;">✗ 遠距離に弱い</div>
                            </div>
                            <div style="flex: 1; min-width: 200px;">
                                <div style="color: #ffd700; font-weight: bold; margin-bottom: 5px;">🏹 遠距離</div>
                                <div style="color: #32cd32; font-size: 12px;">✓ 騎兵に強い</div>
                                <div style="color: #ff6b6b; font-size: 12px;">✗ 歩兵に弱い</div>
                            </div>
                            <div style="flex: 1; min-width: 200px;">
                                <div style="color: #ffd700; font-weight: bold; margin-bottom: 5px;">💣 攻城</div>
                                <div style="color: #32cd32; font-size: 12px;">✓ 歩兵に強い</div>
                                <div style="color: #ff6b6b; font-size: 12px;">✗ 騎兵に弱い</div>
                            </div>
                        </div>
                    </div>
                `;
                
                troopsList.innerHTML = advantageHtml + data.available_troops.map(t => {
                    const owned = data.user_troops.find(ut => ut.troop_type_id == t.id);
                    const ownedCount = owned ? owned.count : 0;
                    
                    let costText = `🪙 ${t.train_cost_coins}`;
                    if (t.train_cost_resources) {
                        const costs = JSON.parse(t.train_cost_resources);
                        Object.entries(costs).forEach(([key, val]) => {
                            costText += ` | ${key}: ${val}`;
                        });
                    }
                    
                    // 前提条件表示
                    const canTrain = t.can_train !== false;
                    const missingPrereqs = t.missing_prerequisites || [];
                    const prereqText = missingPrereqs.length > 0 
                        ? `<div style="color: #ff6b6b; font-size: 12px; margin-bottom: 10px;">🔒 必要: ${missingPrereqs.join(', ')}</div>` 
                        : '';
                    
                    // 兵種カテゴリと相性を表示
                    const category = t.troop_category || 'infantry';
                    const categoryInfo = advantageInfo[category] || advantageInfo['infantry'];
                    const healthPoints = t.health_points || 100;
                    
                    return `
                        <div class="target-card" style="border-color: #8b4513; ${!canTrain ? 'opacity: 0.7;' : ''}">
                            <div class="target-header">
                                <span class="target-name">${t.icon} ${t.name}</span>
                                <span class="target-power">×${ownedCount}</span>
                            </div>
                            <div style="color: #888; font-size: 13px; margin-bottom: 5px;">
                                ${t.description || ''}
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
                                <span style="background: rgba(139, 69, 19, 0.5); padding: 3px 8px; border-radius: 4px; font-size: 11px;">
                                    ${categoryInfo.icon} ${categoryInfo.name}
                                </span>
                                <span style="background: rgba(220, 20, 60, 0.3); padding: 3px 8px; border-radius: 4px; font-size: 11px;">
                                    ⚔️ ${t.attack_power}
                                </span>
                                <span style="background: rgba(70, 130, 180, 0.3); padding: 3px 8px; border-radius: 4px; font-size: 11px;">
                                    🛡️ ${t.defense_power}
                                </span>
                                <span style="background: rgba(50, 205, 50, 0.3); padding: 3px 8px; border-radius: 4px; font-size: 11px;">
                                    ❤️ ${healthPoints}
                                </span>
                            </div>
                            <div style="color: #c0a080; font-size: 12px; margin-bottom: 10px;">
                                ${costText}
                            </div>
                            ${prereqText}
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="number" id="troop-count-${t.id}" value="1" min="1" max="100" style="width: 60px; padding: 8px; background: rgba(0,0,0,0.3); border: 1px solid #8b4513; border-radius: 4px; color: #f5deb3;" ${!canTrain ? 'disabled' : ''}>
                                <button class="attack-btn" onclick="trainTroops(${t.id})" style="background: linear-gradient(135deg, #8b4513 0%, #d4a574 100%); flex: 1;" ${!canTrain ? 'disabled' : ''}>
                                    ${!canTrain ? '🔒 ロック中' : '訓練する'}
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                troopsList.innerHTML = '<p style="color: #888;">利用可能な兵種がありません。時代を進めてください。</p>';
            }
        }
    } catch (e) {
        console.error(e);
        document.getElementById('troopsList').innerHTML = '<p style="color: #888;">兵種の読み込みに失敗しました</p>';
    }
}

// 兵士を訓練
async function trainTroops(troopTypeId) {
    const countInput = document.getElementById(`troop-count-${troopTypeId}`);
    const count = parseInt(countInput ? countInput.value : 1);
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'queue_training', troop_type_id: troopTypeId, count: count})
        });
        const data = await res.json();
        
        if (data.ok) {
            showNotification(data.message);
            loadData();
            loadTroops();
            loadTrainingQueue();
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// 訓練キューを読み込む
async function loadTrainingQueue() {
    try {
        // 訓練完了をチェック
        await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'complete_training'})
        });
        
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_training_queue'})
        });
        const data = await res.json();
        
        const container = document.getElementById('trainingQueueList');
        if (!container) return;
        
        if (data.ok && data.training_queue && data.training_queue.length > 0) {
            container.innerHTML = data.training_queue.map(q => {
                const completesAt = new Date(q.training_completes_at);
                const remaining = Math.max(0, Math.floor((completesAt - Date.now()) / 1000));
                const remainingText = formatTime(remaining);
                
                return `
                    <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 8px; margin-bottom: 8px;">
                        <div>
                            <span>${q.icon} ${q.name} ×${q.count}</span>
                            <span style="color: #87ceeb; margin-left: 10px;">⏱️ ${remainingText}</span>
                        </div>
                        <div style="display: flex; gap: 5px;">
                            <button class="quick-invest-btn" onclick="instantCompleteQueue('training', ${q.id}, 'crystal')" style="font-size: 11px;">💎 即完了</button>
                            <button class="quick-invest-btn" onclick="instantCompleteQueue('training', ${q.id}, 'diamond')" style="font-size: 11px;">💠 即完了</button>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = '<p style="color: #888;">訓練中の兵士はいません</p>';
        }
    } catch (e) {
        console.error(e);
    }
}

// 負傷兵と治療キューを読み込む
async function loadWoundedTroops() {
    try {
        // 治療完了をチェック
        await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'complete_healing'})
        });
        
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_wounded_troops'})
        });
        const data = await res.json();
        
        const woundedContainer = document.getElementById('woundedTroopsList');
        const healingContainer = document.getElementById('healingQueueList');
        
        if (!woundedContainer) return;
        
        if (data.ok) {
            // 負傷兵リスト
            if (data.wounded_troops && data.wounded_troops.length > 0) {
                woundedContainer.innerHTML = `
                    <div style="margin-bottom: 10px; color: #888; font-size: 12px;">病院容量: ${data.hospital_capacity}床</div>
                    ${data.wounded_troops.map(w => `
                        <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 8px; margin-bottom: 8px;">
                            <div>
                                <span>${w.icon} ${w.name} ×${w.count}</span>
                                <span style="color: #888; font-size: 11px; margin-left: 10px;">治療: ${w.heal_time_seconds}秒/体 🪙${w.heal_cost_coins}/体</span>
                            </div>
                            <div style="display: flex; gap: 5px; align-items: center;">
                                <input type="number" id="heal-count-${w.troop_type_id}" value="1" min="1" max="${w.count}" style="width: 50px; padding: 5px; background: rgba(0,0,0,0.3); border: 1px solid #dc143c; border-radius: 4px; color: #f5deb3;">
                                <button class="quick-invest-btn" onclick="healTroops(${w.troop_type_id})" style="background: linear-gradient(135deg, #32cd32 0%, #228b22 100%); color: #fff;">🏥 治療</button>
                            </div>
                        </div>
                    `).join('')}
                `;
            } else {
                woundedContainer.innerHTML = '<p style="color: #888;">負傷兵はいません</p>';
            }
            
            // 治療キュー
            if (healingContainer && data.healing_queue && data.healing_queue.length > 0) {
                healingContainer.innerHTML = `
                    <h4 style="color: #90ee90; margin-bottom: 10px;">💉 治療中</h4>
                    ${data.healing_queue.map(h => {
                        const completesAt = new Date(h.healing_completes_at);
                        const remaining = Math.max(0, Math.floor((completesAt - Date.now()) / 1000));
                        const remainingText = formatTime(remaining);
                        
                        return `
                            <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(50, 205, 50, 0.2); padding: 10px; border-radius: 8px; margin-bottom: 8px;">
                                <div>
                                    <span>${h.icon} ${h.name} ×${h.count}</span>
                                    <span style="color: #90ee90; margin-left: 10px;">⏱️ ${remainingText}</span>
                                </div>
                                <div style="display: flex; gap: 5px;">
                                    <button class="quick-invest-btn" onclick="instantCompleteQueue('healing', ${h.id}, 'crystal')" style="font-size: 11px;">💎 即完了</button>
                                    <button class="quick-invest-btn" onclick="instantCompleteQueue('healing', ${h.id}, 'diamond')" style="font-size: 11px;">💠 即完了</button>
                                </div>
                            </div>
                        `;
                    }).join('')}
                `;
            } else if (healingContainer) {
                healingContainer.innerHTML = '';
            }
        }
    } catch (e) {
        console.error(e);
    }
}

// 負傷兵を治療
async function healTroops(troopTypeId) {
    const countInput = document.getElementById(`heal-count-${troopTypeId}`);
    const count = parseInt(countInput ? countInput.value : 1);
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'heal_troops', troop_type_id: troopTypeId, count: count})
        });
        const data = await res.json();
        
        if (data.ok) {
            showNotification(data.message);
            loadWoundedTroops();
            loadData();
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// 訓練・治療を即完了
async function instantCompleteQueue(queueType, queueId, currency) {
    const currencyName = currency === 'crystal' ? 'クリスタル' : 'ダイヤモンド';
    if (!confirm(`${currencyName}を使用して即座に完了しますか？`)) return;
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'instant_complete_queue', queue_type: queueType, queue_id: queueId, currency: currency})
        });
        const data = await res.json();
        
        if (data.ok) {
            showNotification(data.message);
            loadData();
            loadTrainingQueue();
            loadWoundedTroops();
            loadTroops();
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// 防御設定を読み込む
async function loadDefenseSettings() {
    try {
        // 利用可能な兵士を取得
        const troopsRes = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_troops'})
        });
        const troopsData = await troopsRes.json();
        
        // 現在の防御設定を取得
        const defenseRes = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_defense_troops'})
        });
        const defenseData = await defenseRes.json();
        
        const container = document.getElementById('defenseSettingsList');
        if (!container) return;
        
        if (troopsData.ok && troopsData.user_troops && troopsData.user_troops.length > 0) {
            const defenseTroops = defenseData.ok ? (defenseData.defense_troops || []) : [];
            
            container.innerHTML = `
                <div style="margin-bottom: 15px;">
                    ${troopsData.user_troops.filter(t => t.count > 0).map(t => {
                        const assigned = defenseTroops.find(d => d.troop_type_id == t.troop_type_id);
                        const assignedCount = assigned ? assigned.assigned_count : 0;
                        
                        return `
                            <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 8px; margin-bottom: 8px;">
                                <div>
                                    <span>${t.icon} ${t.name}</span>
                                    <span style="color: #888; margin-left: 10px;">所持: ${t.count}</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="range" id="defense-slider-${t.troop_type_id}" 
                                           min="0" max="${t.count}" value="${assignedCount}"
                                           style="width: 100px;"
                                           oninput="document.getElementById('defense-count-${t.troop_type_id}').value = this.value">
                                    <input type="number" id="defense-count-${t.troop_type_id}" 
                                           min="0" max="${t.count}" value="${assignedCount}"
                                           style="width: 60px; padding: 5px; background: rgba(0,0,0,0.3); border: 1px solid #32cd32; border-radius: 4px; color: #f5deb3;"
                                           oninput="document.getElementById('defense-slider-${t.troop_type_id}').value = this.value">
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
                <button class="invest-btn" onclick="saveDefenseSettings()" style="background: linear-gradient(135deg, #32cd32 0%, #228b22 100%);">
                    🛡️ 防御設定を保存
                </button>
            `;
        } else {
            container.innerHTML = '<p style="color: #888;">兵士がいません。まず兵士を訓練してください。</p>';
        }
    } catch (e) {
        console.error(e);
    }
}

// 防御設定を保存
async function saveDefenseSettings() {
    const troops = [];
    document.querySelectorAll('[id^="defense-count-"]').forEach(input => {
        const troopTypeId = parseInt(input.id.replace('defense-count-', ''));
        const count = parseInt(input.value) || 0;
        if (count > 0) {
            troops.push({troop_type_id: troopTypeId, count: count});
        }
    });
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'set_defense_troops', troops: troops})
        });
        const data = await res.json();
        
        if (data.ok) {
            showNotification(data.message);
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// 時間をフォーマット
function formatTime(seconds) {
    if (seconds <= 0) return '完了';
    const hours = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    if (hours > 0) return `${hours}時間${mins}分`;
    if (mins > 0) return `${mins}分${secs}秒`;
    return `${secs}秒`;
}

// VIPブーストを購入
async function buyVipBoost(boostType) {
    if (!confirm('ダイヤモンドを消費してブーストを購入しますか？')) return;
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'buy_vip_boost', boost_type: boostType})
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

// 攻撃
async function attack(targetUserId) {
    if (!confirm('この文明を攻撃しますか？')) return;
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'attack', target_user_id: targetUserId})
        });
        const data = await res.json();
        
        if (data.ok) {
            const isVictory = data.result === 'victory';
            showNotification(data.message, !isVictory);
            if (isVictory) {
                loadData();
            }
        } else {
            showNotification(data.error, true);
        }
    } catch (e) {
        showNotification('エラーが発生しました', true);
    }
}

// 文明名変更
async function renameCiv() {
    const newName = prompt('新しい文明名を入力してください:', civData.civilization.civilization_name);
    if (!newName || newName === civData.civilization.civilization_name) return;
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'rename', name: newName})
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

// ユーティリティ関数
function formatTime(seconds) {
    if (seconds <= 0) return '完了';
    if (seconds < 60) return `${seconds}秒`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}分${seconds % 60}秒`;
    return `${Math.floor(seconds / 3600)}時間${Math.floor((seconds % 3600) / 60)}分`;
}

function escapeHtml(text) {
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

// 建物を即完了
async function instantCompleteBuilding(buildingId) {
    if (!confirm('クリスタルを消費して建設を即完了しますか？')) return;
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'instant_complete_building', building_id: buildingId})
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

// 建物をダイヤモンドで即完了
async function instantCompleteBuildingDiamond(buildingId) {
    if (!confirm('ダイヤモンドを消費して建設を即完了しますか？')) return;
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'instant_complete_building_diamond', building_id: buildingId})
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

// 研究を即完了
async function instantCompleteResearch(userResearchId) {
    if (!confirm('クリスタルを消費して研究を即完了しますか？')) return;
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'instant_complete_research', user_research_id: userResearchId})
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

// 研究をダイヤモンドで即完了
async function instantCompleteResearchDiamond(userResearchId) {
    if (!confirm('ダイヤモンドを消費して研究を即完了しますか？')) return;
    
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'instant_complete_research_diamond', user_research_id: userResearchId})
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

// 完了チェックとUIリフレッシュ
async function checkCompletions() {
    let needsRefresh = false;
    
    // 完了した建物を確認
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'complete_buildings'})
        });
        const data = await res.json();
        if (data.ok && data.count > 0) {
            let message = `建設完了: ${data.completed.join(', ')}`;
            if (data.population_increase > 0) {
                message += ` (+${data.population_increase}人口)`;
            }
            showNotification(message);
            needsRefresh = true;
        }
    } catch (e) {
        console.error('Building check error:', e);
    }
    
    // 完了した研究を確認
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'complete_researches'})
        });
        const data = await res.json();
        if (data.ok && data.count > 0) {
            showNotification(`研究完了: ${data.completed.join(', ')}`);
            needsRefresh = true;
        }
    } catch (e) {
        console.error('Research check error:', e);
    }
    
    if (needsRefresh) {
        loadData();
    }
}

// 定期的にデータを更新（10秒ごとにカウントダウンを更新し、完了チェック）
let updateInterval = null;
let isUserInteracting = false;
let interactionTimeout = null;

// ユーザー操作検出用のフラグを設定
function setUserInteracting() {
    isUserInteracting = true;
    
    // 既存のタイムアウトをクリア
    if (interactionTimeout) {
        clearTimeout(interactionTimeout);
    }
    
    // 2秒間操作がなければフラグを解除
    interactionTimeout = setTimeout(() => {
        isUserInteracting = false;
    }, 2000);
}

// ユーザー操作イベントを監視
function setupInteractionListeners() {
    // 入力フィールドのフォーカスと入力
    document.addEventListener('focusin', (e) => {
        if (e.target.matches('input, select, textarea')) {
            setUserInteracting();
        }
    });
    
    document.addEventListener('input', (e) => {
        if (e.target.matches('input, select, textarea')) {
            setUserInteracting();
        }
    });
    
    // スクロール操作
    document.addEventListener('scroll', () => {
        setUserInteracting();
    }, true);
    
    // スライダー操作
    document.addEventListener('mousedown', (e) => {
        if (e.target.matches('input[type="range"]')) {
            setUserInteracting();
        }
    });
    
    document.addEventListener('touchstart', (e) => {
        if (e.target.matches('input[type="range"], input[type="number"]')) {
            setUserInteracting();
        }
    }, { passive: true });
}

function startUpdateTimer() {
    if (updateInterval) clearInterval(updateInterval);
    
    updateInterval = setInterval(() => {
        // ユーザー操作中は更新をスキップ
        if (isUserInteracting) {
            return;
        }
        
        // 完了チェック
        checkCompletions();
        
        // カウントダウンを更新するため、全体を再描画
        if (civData) {
            renderApp();
        }
    }, 10000); // 10秒ごと
}

// 初期読み込み
loadData();
startUpdateTimer();
setupInteractionListeners();
</script>
</body>
</html>
