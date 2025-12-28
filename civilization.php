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
</style>
</head>
<body>
<div class="civ-container">
    <a href="./" class="back-link">← フィードに戻る</a>
    
    <div id="app">
        <div class="loading">データを読み込み中...</div>
    </div>
</div>

<script>
let civData = null;

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
            <button class="tab-btn active" data-tab="buildings">🏠 建物</button>
            <button class="tab-btn" data-tab="research">📚 研究</button>
            <button class="tab-btn" data-tab="war">⚔️ 戦争</button>
        </div>
        
        <!-- 建物タブ -->
        <div class="tab-content active" id="tab-buildings">
            <h3 style="color: #ffd700; margin-bottom: 20px;">🏗️ 建設可能な建物</h3>
            <div class="buildings-grid">
                ${renderBuildingsGrid(availableBuildings, buildings, resources)}
            </div>
        </div>
        
        <!-- 研究タブ -->
        <div class="tab-content" id="tab-research">
            <h3 style="color: #87ceeb; margin-bottom: 20px;">🔬 研究ツリー</h3>
            <div class="research-tree">
                ${renderResearchTree()}
            </div>
        </div>
        
        <!-- 戦争タブ -->
        <div class="tab-content" id="tab-war">
            <div class="war-section">
                <h3>⚔️ 他の文明を攻撃</h3>
                <p style="color: #c0a080; margin-bottom: 20px;">軍事施設を建設して軍事力を上げ、他の文明から資源を略奪しましょう！</p>
                <div class="targets-list" id="targetsList">
                    <div class="loading">攻撃対象を読み込み中...</div>
                </div>
            </div>
        </div>
    `;
    
    // タブ切り替え
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
            
            // 戦争タブの場合、攻撃対象を読み込む
            if (btn.dataset.tab === 'war') {
                loadTargets();
            }
        });
    });
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
        if (constructing) {
            statusClass = 'constructing';
            const remaining = Math.max(0, Math.ceil((new Date(constructing.construction_completes_at) - new Date()) / 1000));
            statusText = `建設中... ${formatTime(remaining)}`;
        } else if (ownedCount > 0) {
            statusClass = 'owned';
        }
        
        return `
            <div class="building-card ${statusClass}">
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
                ${statusText ? `<div style="color: #ffa500; margin-bottom: 10px;">${statusText}</div>` : ''}
                <button class="build-btn" onclick="buildBuilding(${bt.id})" ${constructing ? 'disabled' : ''}>
                    建設する
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
        if (isCompleted) {
            statusClass = 'completed';
            statusText = '✅ 完了';
        } else if (isResearching) {
            statusClass = 'researching';
            const remaining = Math.max(0, Math.ceil((new Date(userResearch.research_completes_at) - new Date()) / 1000));
            statusText = `研究中... ${formatTime(remaining)}`;
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

// 攻撃対象を読み込む
async function loadTargets() {
    try {
        const res = await fetch('civilization_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_targets'})
        });
        const data = await res.json();
        
        if (data.ok && data.targets.length > 0) {
            document.getElementById('targetsList').innerHTML = data.targets.map(t => `
                <div class="target-card">
                    <div class="target-header">
                        <span class="target-name">${escapeHtml(t.civilization_name)}</span>
                        <span class="target-power">⚔️ ${t.military_power || 0}</span>
                    </div>
                    <div style="color: #888; font-size: 13px; margin-bottom: 10px;">
                        @${t.handle} | 👥 ${t.population}人
                    </div>
                    <button class="attack-btn" onclick="attack(${t.user_id})">
                        攻撃する
                    </button>
                </div>
            `).join('');
        } else {
            document.getElementById('targetsList').innerHTML = '<p style="color: #888;">攻撃可能な文明がありません</p>';
        }
    } catch (e) {
        console.error(e);
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
    if (seconds < 60) return `${seconds}秒`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}分`;
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

// 定期的にデータを更新
setInterval(() => {
    // 完了した研究を確認
    fetch('civilization_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'complete_researches'})
    }).then(res => res.json()).then(data => {
        if (data.ok && data.count > 0) {
            showNotification(`研究完了: ${data.completed.join(', ')}`);
            loadData();
        }
    });
}, 30000); // 30秒ごと

// 初期読み込み
loadData();
</script>
</body>
</html>
