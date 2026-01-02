<?php
// ===============================================
// civilization_guide.php
// 文明育成ゲーム攻略書・説明書
// ===============================================

require_once __DIR__ . '/config.php';

$me = user();
if (!$me) {
    header('Location: ./login.php');
    exit;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>文明育成攻略書 - MiniBird</title>
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

.guide-container {
    max-width: 900px;
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
    transition: all 0.3s;
}

.back-link:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-2px);
}

.guide-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px;
    background: linear-gradient(135deg, rgba(139, 69, 19, 0.6) 0%, rgba(101, 67, 33, 0.6) 100%);
    border: 3px solid #8b4513;
    border-radius: 20px;
}

.guide-header h1 {
    font-size: 2.5em;
    color: #ffd700;
    margin: 0 0 10px 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.guide-header p {
    color: #c0a080;
    font-size: 1.1em;
    margin: 0;
}

.toc {
    background: rgba(0,0,0,0.4);
    border: 2px solid #4a3728;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
}

.toc h2 {
    color: #ffd700;
    margin-top: 0;
    border-bottom: 2px solid #4a3728;
    padding-bottom: 10px;
}

.toc ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.toc li {
    margin: 8px 0;
}

.toc a {
    color: #87ceeb;
    text-decoration: none;
    display: block;
    padding: 8px 15px;
    border-radius: 8px;
    transition: all 0.2s;
}

.toc a:hover {
    background: rgba(135, 206, 235, 0.1);
    padding-left: 25px;
}

.section {
    background: rgba(0,0,0,0.3);
    border: 2px solid #4a3728;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
}

.section h2 {
    color: #ffd700;
    border-bottom: 2px solid #4a3728;
    padding-bottom: 10px;
    margin-top: 0;
}

.section h3 {
    color: #87ceeb;
    margin-top: 20px;
}

.section p {
    line-height: 1.8;
    margin: 15px 0;
}

.section ul, .section ol {
    margin: 15px 0;
    padding-left: 25px;
}

.section li {
    margin: 8px 0;
    line-height: 1.6;
}

.tip-box {
    background: linear-gradient(135deg, rgba(72, 187, 120, 0.2) 0%, rgba(56, 161, 105, 0.2) 100%);
    border: 2px solid #48bb78;
    border-radius: 10px;
    padding: 15px 20px;
    margin: 20px 0;
}

.tip-box h4 {
    color: #48bb78;
    margin: 0 0 10px 0;
}

.warning-box {
    background: linear-gradient(135deg, rgba(245, 101, 101, 0.2) 0%, rgba(229, 62, 62, 0.2) 100%);
    border: 2px solid #f56565;
    border-radius: 10px;
    padding: 15px 20px;
    margin: 20px 0;
}

.warning-box h4 {
    color: #f56565;
    margin: 0 0 10px 0;
}

.info-box {
    background: linear-gradient(135deg, rgba(66, 153, 225, 0.2) 0%, rgba(49, 130, 206, 0.2) 100%);
    border: 2px solid #4299e1;
    border-radius: 10px;
    padding: 15px 20px;
    margin: 20px 0;
}

.info-box h4 {
    color: #4299e1;
    margin: 0 0 10px 0;
}

.resource-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.resource-table th,
.resource-table td {
    padding: 12px 15px;
    text-align: left;
    border: 1px solid #4a3728;
}

.resource-table th {
    background: rgba(139, 69, 19, 0.4);
    color: #ffd700;
}

.resource-table tr:nth-child(even) {
    background: rgba(0,0,0,0.2);
}

.emoji-icon {
    font-size: 1.5em;
    margin-right: 8px;
}

@media (max-width: 768px) {
    .guide-header h1 {
        font-size: 1.8em;
    }
    
    .section {
        padding: 15px;
    }
}
</style>
</head>
<body>
<div class="guide-container">
    <a href="index.php" class="back-link">← ホームに戻る</a>
    <a href="civilization.php" class="back-link" style="margin-left: 10px;">🏛️ 文明育成をプレイ</a>
    
    <div class="guide-header">
        <h1>📖 文明育成攻略書</h1>
        <p>石器時代から始めて、あなただけの文明を発展させよう！</p>
    </div>
    
    <!-- 目次 -->
    <div class="toc">
        <h2>📋 目次</h2>
        <ul>
            <li><a href="#basics">1. ゲームの基本</a></li>
            <li><a href="#resources">2. 資源システム</a></li>
            <li><a href="#buildings">3. 建物</a></li>
            <li><a href="#research">4. 研究</a></li>
            <li><a href="#troops">5. 兵士と軍事</a></li>
            <li><a href="#war">6. 戦争システム</a></li>
            <li><a href="#conquest">7. 占領戦</a></li>
            <li><a href="#monsters">8. モンスター討伐</a></li>
            <li><a href="#quests">9. クエスト</a></li>
            <li><a href="#leaderboard">10. リーダーボード</a></li>
            <li><a href="#tips">11. 攻略のコツ</a></li>
            <li><a href="#events">12. イベントシステム（新機能）</a></li>
            <li><a href="#new-resources">13. 新資源（新機能）</a></li>
            <li><a href="#heroes">14. ヒーローシステム</a></li>
            <li><a href="#new-eras">15. 新時代（原子力時代〜宇宙時代）</a></li>
            <li><a href="#market">16. 市場システム</a></li>
            <li><a href="#bank">17. 銀行システム</a></li>
        </ul>
    </div>
    
    <!-- 1. ゲームの基本 -->
    <div class="section" id="basics">
        <h2><span class="emoji-icon">🎮</span>1. ゲームの基本</h2>
        <p>文明育成は、自分だけの文明を石器時代から発展させていくシミュレーションゲームです。建物を建設し、資源を集め、兵士を訓練して、他のプレイヤーと競い合いましょう。</p>
        
        <h3>ゲームの流れ</h3>
        <ol>
            <li><strong>コインを投資</strong>して文明を発展させる資金を得ます</li>
            <li><strong>建物を建設</strong>して資源生産や軍事力を強化</li>
            <li><strong>資源を収集</strong>して建物の建設・研究・兵士訓練に使用</li>
            <li><strong>研究を進め</strong>て新しい技術や建物をアンロック</li>
            <li><strong>兵士を訓練</strong>して軍事力を高める</li>
            <li><strong>他プレイヤーと戦争</strong>して資源を略奪</li>
            <li><strong>時代を進化</strong>させて新しい可能性を開く</li>
        </ol>
        
        <div class="info-box">
            <h4>💡 自動更新について</h4>
            <p>ゲーム画面は10秒ごとに自動更新されます。建設中の建物や訓練中の兵士の進捗がリアルタイムで確認できます。</p>
        </div>
    </div>
    
    <!-- 2. 資源システム -->
    <div class="section" id="resources">
        <h2><span class="emoji-icon">📦</span>2. 資源システム</h2>
        <p>文明を発展させるためには様々な資源が必要です。資源は時代によってアンロックされ、対応する生産施設を建設することで生産できます。</p>
        
        <table class="resource-table">
            <tr>
                <th>資源</th>
                <th>生産施設</th>
                <th>用途</th>
            </tr>
            <tr>
                <td>🌾 食料</td>
                <td>畑、農場</td>
                <td>人口維持、兵士訓練</td>
            </tr>
            <tr>
                <td>🪵 木材</td>
                <td>伐採場</td>
                <td>建物建設、研究</td>
            </tr>
            <tr>
                <td>🪨 石材</td>
                <td>採石場</td>
                <td>上位建物、防衛施設</td>
            </tr>
            <tr>
                <td>🔩 鉄</td>
                <td>鉄鉱山</td>
                <td>兵器、上位兵士</td>
            </tr>
            <tr>
                <td>🥉 青銅</td>
                <td>青銅精錬所</td>
                <td>武器、防具</td>
            </tr>
            <tr>
                <td>🪙 金</td>
                <td>金鉱山</td>
                <td>研究、高級建物</td>
            </tr>
            <tr>
                <td>💎 宝石</td>
                <td>宝石鉱山</td>
                <td>特殊アイテム</td>
            </tr>
        </table>
        
        <div class="tip-box">
            <h4>💡 資源収集のコツ</h4>
            <p>資源は自動で生産されますが、定期的にログインして「収集」することで貯まります。生産施設のレベルを上げると生産量が増加します。</p>
        </div>
    </div>
    
    <!-- 3. 建物 -->
    <div class="section" id="buildings">
        <h2><span class="emoji-icon">🏠</span>3. 建物</h2>
        <p>建物は文明の基盤です。それぞれの建物には異なる役割があり、バランスよく建設することが重要です。</p>
        
        <h3>建物の種類</h3>
        <ul>
            <li><strong>🌾 生産施設</strong>：畑、伐採場、鉱山など。資源を生産します。</li>
            <li><strong>🏠 住居</strong>：住居、アパートなど。人口上限を増やします。</li>
            <li><strong>⚔️ 軍事施設</strong>：兵舎、射撃場など。軍事力を上げ、兵士訓練を可能にします。</li>
            <li><strong>🏥 医療施設</strong>：病院、野戦病院。負傷兵を治療します。</li>
            <li><strong>🔬 研究施設</strong>：図書館、大学など。研究速度を上げます。</li>
            <li><strong>🏛️ 特殊施設</strong>：市場、銀行など。特殊な効果を提供します。</li>
        </ul>
        
        <h3>建物のレベルアップ</h3>
        <p>既存の建物をレベルアップすることで効果が強化されます。生産施設のレベルを上げると生産量が増加し、軍事施設のレベルを上げると軍事力が上がります。</p>
        
        <div class="warning-box">
            <h4>⚠️ 建設には時間がかかります</h4>
            <p>建物の建設やレベルアップには時間がかかります。複数の建物を同時に建設することはできません。計画的に建設しましょう。</p>
        </div>
    </div>
    
    <!-- 4. 研究 -->
    <div class="section" id="research">
        <h2><span class="emoji-icon">📚</span>4. 研究</h2>
        <p>研究を進めることで新しい技術や建物、兵種をアンロックできます。研究にはコストと時間がかかりますが、文明の発展に欠かせません。</p>
        
        <h3>研究の種類</h3>
        <ul>
            <li><strong>生産技術</strong>：資源生産効率を上げる</li>
            <li><strong>軍事技術</strong>：兵士の能力を強化</li>
            <li><strong>建築技術</strong>：新しい建物をアンロック</li>
            <li><strong>時代技術</strong>：次の時代への進化条件</li>
        </ul>
        
        <div class="tip-box">
            <h4>💡 研究の優先順位</h4>
            <p>序盤は生産効率を上げる研究を優先しましょう。資源が安定してから軍事研究に投資することをお勧めします。</p>
        </div>
    </div>
    
    <!-- 5. 兵士と軍事 -->
    <div class="section" id="troops">
        <h2><span class="emoji-icon">🎖️</span>5. 兵士と軍事</h2>
        <p>兵士を訓練して軍事力を高めましょう。異なる兵種には特徴があり、戦術的な編成が勝利の鍵となります。</p>
        
        <h3>兵種カテゴリ</h3>
        <ul>
            <li><strong>🗡️ 歩兵（Infantry）</strong>：バランス型。騎兵に強い。</li>
            <li><strong>🏹 射撃（Ranged）</strong>：遠距離攻撃。歩兵に強い。</li>
            <li><strong>🐎 騎兵（Cavalry）</strong>：高速機動。射撃に強い。</li>
            <li><strong>🛡️ 攻城（Siege）</strong>：防御施設破壊に特化。</li>
        </ul>
        
        <h3>兵種相性</h3>
        <p>三すくみの関係があります：</p>
        <ul>
            <li>歩兵 → 騎兵に強い（+15%ダメージ）</li>
            <li>騎兵 → 射撃に強い（+15%ダメージ）</li>
            <li>射撃 → 歩兵に強い（+15%ダメージ）</li>
        </ul>
        
        <div class="info-box">
            <h4>💡 軍事力の計算</h4>
            <p>軍事力 = (攻撃力 + 防御力/2 + 体力/比率) × 兵数 + 建物パワー + 装備パワー</p>
        </div>
    </div>
    
    <!-- 6. 戦争システム -->
    <div class="section" id="war">
        <h2><span class="emoji-icon">⚔️</span>6. 戦争システム</h2>
        <p>他のプレイヤーの文明を攻撃して資源を略奪できます。ただし、負けると逆に資源を奪われる可能性があります。</p>
        
        <h3>戦争の流れ</h3>
        <ol>
            <li>「⚔️ 戦争」タブで攻撃対象を選択</li>
            <li>相手の軍事力を確認</li>
            <li>「攻撃」ボタンで戦闘開始</li>
            <li>勝利すると資源を略奪</li>
            <li>敗北すると兵士が負傷</li>
        </ol>
        
        <h3>略奪と防衛</h3>
        <ul>
            <li><strong>勝利時</strong>：相手のコインと資源の一部を略奪</li>
            <li><strong>敗北時</strong>：兵士が負傷（病院で治療可能）</li>
        </ul>
        
        <div class="warning-box">
            <h4>⚠️ 戦争のリスク</h4>
            <p>軍事力が相手より低い場合は負ける可能性が高いです。戦争ログで過去の結果を確認し、慎重に攻撃対象を選びましょう。</p>
        </div>
    </div>
    
    <!-- 7. 占領戦 -->
    <div class="section" id="conquest">
        <h2><span class="emoji-icon">🏰</span>7. 占領戦</h2>
        <p>占領戦は複数のプレイヤーが拠点を奪い合うイベントです。シーズン終了時に最も多くの拠点を占領しているプレイヤーが勝者となります。</p>
        
        <h3>占領戦の基本</h3>
        <ul>
            <li>マップ上の城を攻撃して占領</li>
            <li>占領した城は防衛する必要あり</li>
            <li>城にはNPC守備兵がいる場合も</li>
            <li>シーズン終了時に占領数で順位決定</li>
        </ul>
        
        <h3>報酬</h3>
        <ul>
            <li>シーズン優勝者：特別報酬</li>
            <li>上位ランカー：順位に応じた報酬</li>
            <li>参加報酬：占領回数に応じたボーナス</li>
        </ul>
        
        <div class="tip-box">
            <h4>💡 占領戦のコツ</h4>
            <p>序盤は無防備な城を狙い、徐々に勢力を拡大しましょう。他のプレイヤーと同盟を組むことで有利に進められます。</p>
        </div>
    </div>
    
    <!-- 8. モンスター討伐 -->
    <div class="section" id="monsters">
        <h2><span class="emoji-icon">🐉</span>8. モンスター討伐</h2>
        <p>マップ上に出現するモンスターを討伐して報酬を獲得できます。</p>
        
        <h3>モンスターの種類</h3>
        <ul>
            <li><strong>放浪モンスター</strong>：個人で討伐。経験値と資源獲得。</li>
            <li><strong>ワールドボス</strong>：全プレイヤーで協力して討伐。与ダメージに応じた報酬。</li>
        </ul>
        
        <div class="info-box">
            <h4>💡 ワールドボス攻略</h4>
            <p>ワールドボスには定期的に攻撃して与ダメージを稼ぎましょう。討伐成功時のランキング上位者には豪華報酬があります。</p>
        </div>
    </div>
    
    <!-- 9. クエスト -->
    <div class="section" id="quests">
        <h2><span class="emoji-icon">📋</span>9. クエスト</h2>
        <p>クエストをクリアすることでコイン、クリスタル、ダイヤモンドなどの報酬を獲得できます。</p>
        
        <h3>クエストの種類</h3>
        <ul>
            <li><strong>建築クエスト</strong>：建物を建設・レベルアップ</li>
            <li><strong>訓練クエスト</strong>：兵士を訓練</li>
            <li><strong>資源クエスト</strong>：資源を収集</li>
            <li><strong>戦闘クエスト</strong>：モンスター討伐、戦争勝利</li>
            <li><strong>占領クエスト</strong>：占領戦で拠点を占領</li>
        </ul>
        
        <div class="tip-box">
            <h4>💡 クエストの進め方</h4>
            <p>クエストタブに赤いバッジが表示されている場合は、報酬受け取り待ちのクエストがあります。忘れずに報酬を受け取りましょう！</p>
        </div>
    </div>
    
    <!-- 10. リーダーボード -->
    <div class="section" id="leaderboard">
        <h2><span class="emoji-icon">🏆</span>10. リーダーボード</h2>
        <p>他のプレイヤーとランキングで競い合いましょう。様々なカテゴリでトップを目指せます。</p>
        
        <h3>ランキングカテゴリ</h3>
        <ul>
            <li><strong>👥 人口</strong>：文明の総人口</li>
            <li><strong>⚔️ 軍事力</strong>：総合軍事力</li>
            <li><strong>🎖️ 総兵士数</strong>：保有する兵士の合計</li>
            <li><strong>🏠 総建築物数</strong>：建設した建物の数</li>
            <li><strong>🏆 占領戦優勝回数</strong>：シーズン優勝した回数</li>
            <li><strong>🏰 拠点占領回数</strong>：占領した城の累計</li>
            <li><strong>📦 資源別</strong>：各資源の保有量</li>
        </ul>
    </div>
    
    <!-- 11. 攻略のコツ -->
    <div class="section" id="tips">
        <h2><span class="emoji-icon">💡</span>11. 攻略のコツ</h2>
        
        <h3>序盤の進め方</h3>
        <ol>
            <li>まずは住居を建てて人口上限を増やす</li>
            <li>畑・伐採場を建てて基本資源を確保</li>
            <li>定期的にログインして資源を収集</li>
            <li>研究で生産効率を上げる</li>
            <li>兵舎を建てて兵士を少しずつ訓練</li>
        </ol>
        
        <h3>中盤の進め方</h3>
        <ol>
            <li>軍事施設を充実させて軍事力を上げる</li>
            <li>弱い相手を見つけて資源略奪</li>
            <li>病院を建てて負傷兵を治療</li>
            <li>占領戦に参加して報酬を獲得</li>
            <li>時代進化の準備を始める</li>
        </ol>
        
        <h3>終盤の進め方</h3>
        <ol>
            <li>全施設のレベルを上げる</li>
            <li>強力な兵種を揃える</li>
            <li>占領戦でトップを目指す</li>
            <li>リーダーボードで上位を狙う</li>
        </ol>
        
        <div class="tip-box">
            <h4>💡 最重要ポイント</h4>
            <ul>
                <li>毎日ログインして資源収集とクエスト報酬を受け取る</li>
                <li>建設・訓練キューは常に動かしておく</li>
                <li>戦争は軍事力が上回る相手のみを攻撃</li>
                <li>負傷兵は早めに治療する</li>
            </ul>
        </div>
    </div>
    
    <!-- 12. イベントシステム（新機能） -->
    <div class="section" id="events">
        <h2><span class="emoji-icon">🎉</span>12. イベントシステム（新機能）</h2>
        <p>イベントタブでは3種類のイベントに参加できます。</p>
        
        <h3>デイリーイベント</h3>
        <p>毎日更新されるタスク形式のイベントです。</p>
        <ul>
            <li>ログイン、資源収集、建設、兵士訓練などのタスクをクリア</li>
            <li>タスク完了でコイン、クリスタル、経験値などを獲得</li>
            <li>毎日0時にリセットされます</li>
        </ul>
        
        <h3>スペシャルイベント</h3>
        <p>正月イベントなど季節限定のイベントです。</p>
        <ul>
            <li>期間限定で開催される特別イベント</li>
            <li>放浪モンスター、ワールドボス、占領戦などで限定アイテムがドロップ</li>
            <li>限定アイテムは交換所でコイン、クリスタル、ダイヤモンドなどと交換可能</li>
            <li><strong>ポータル機能</strong>：3時間ごとに限定ボスを攻撃可能！限定アイテムの大量ドロップが見込めます</li>
        </ul>
        
        <h3>ヒーローイベント</h3>
        <p>1週間ごとに開催される特定ヒーローをテーマにしたイベントです。</p>
        <ul>
            <li>テーマヒーローの欠片の排出率が大幅アップ</li>
            <li>限定ガチャでテーマヒーローを狙いやすい</li>
            <li>タスククエストでポイントを集め、指定ポイント達成で豪華報酬を獲得</li>
            <li>報酬例：ヒーローの欠片、コイン、クリスタル、ダイヤモンド、各種資源など</li>
        </ul>
    </div>
    
    <!-- 13. 新資源（新機能） -->
    <div class="section" id="new-resources">
        <h2><span class="emoji-icon">📦</span>13. 新資源（新機能）</h2>
        <p>新たに追加された資源と用途です。</p>
        
        <table class="resource-table">
            <tr>
                <th>資源</th>
                <th>用途</th>
            </tr>
            <tr>
                <td>🩹 包帯</td>
                <td>負傷者の治療効率向上</td>
            </tr>
            <tr>
                <td>⚫ ゴム</td>
                <td>工業製品、近代兵器の製造</td>
            </tr>
            <tr>
                <td>🔷 チタン</td>
                <td>高強度の武器・防具製造</td>
            </tr>
            <tr>
                <td>🏛️ 大理石</td>
                <td>高級建材、特殊建物の建設</td>
            </tr>
            <tr>
                <td>🔩 鋼鉄</td>
                <td>近代的な武器と建物</td>
            </tr>
            <tr>
                <td>🌶️ 香辛料</td>
                <td>貿易価値が高い、特殊効果</td>
            </tr>
            <tr>
                <td>💥 火薬</td>
                <td>銃火器、爆発物の製造</td>
            </tr>
            <tr>
                <td>🧂 火薬資源</td>
                <td>火薬の原料</td>
            </tr>
            <tr>
                <td>✨ マナ</td>
                <td>魔法効果、特殊スキル発動</td>
            </tr>
        </table>
    </div>
    
    <!-- 14. ヒーローシステム（詳細） -->
    <div class="section" id="heroes">
        <h2><span class="emoji-icon">🦸</span>14. ヒーローシステム</h2>
        <p>ヒーローは戦闘で強力なスキルを発動し、内政でも様々なボーナスを提供します。</p>
        
        <h3>ヒーローのスターレベル効果</h3>
        <p>ヒーローの星を上げることで以下の効果が得られます：</p>
        <ul>
            <li><strong>スキル発動率UP</strong>：基本15% + 星レベル×2%（最大8★で31%）</li>
            <li><strong>スキル効果UP</strong>：星レベル毎に+5%（8★で+35%効果増加）</li>
            <li><strong>攻撃力ボーナス</strong>：星レベル×5（8★で+40攻撃力）</li>
            <li><strong>防御力ボーナス</strong>：星レベル×3（8★で+24防御力）</li>
            <li><strong>体力ボーナス</strong>：星レベル×50（8★で+400体力）</li>
        </ul>
        
        <div class="tip-box">
            <h4>💡 スターアップのコツ</h4>
            <p>ガチャで同じヒーローの欠片を集めてスターアップしましょう。スターレベルが高いほど戦闘での勝率が大幅に向上します！</p>
        </div>
        
        <h3>新ヒーロー紹介</h3>
        <ul>
            <li><strong>🛡️ アイアンフォートレス</strong>：鉄壁の守護者。味方全体のアーマーを大幅に上昇させ、敵の攻撃を引き付けます。</li>
            <li><strong>💨 ウィンドダンサー</strong>：疾風の踊り子。5連続攻撃で敵を圧倒する高速アタッカー。</li>
            <li><strong>💚 ライフウィーバー</strong>：命の紡ぎ手。味方全体を50%回復し、継続回復も付与する最強ヒーラー。</li>
            <li><strong>☠️ プレイグドクター</strong>：疫病の医師。敵全体に毒と攻撃力デバフを付与するデバッファー。</li>
            <li><strong>💰 トレジャーハンター</strong>：財宝の狩人。戦闘報酬50%アップ、全資源生産量15%アップの資源収集特化。</li>
        </ul>
        
        <div class="info-box">
            <h4>💡 ヒーロースキルについて</h4>
            <p>ヒーローのバトルスキルは戦争、防衛、占領戦、ワールドボス、放浪モンスター、ポータルボスなど全ての戦闘で発動します。スキル発動率はヒーローの星レベルに応じて上昇します。</p>
        </div>
    </div>
    
    <!-- 15. 新時代（原子力時代〜宇宙時代） -->
    <div class="section" id="new-eras">
        <h2><span class="emoji-icon">🚀</span>15. 新時代（原子力時代〜宇宙時代）</h2>
        <p>現代より先の未来の時代が追加されました。各時代には新しい建物、兵士、資源が用意されています。</p>
        
        <h3>追加された時代</h3>
        <table class="resource-table">
            <tr>
                <th>時代</th>
                <th>アイコン</th>
                <th>必要人口</th>
                <th>必要研究P</th>
                <th>特徴</th>
            </tr>
            <tr>
                <td>原子力時代</td>
                <td>☢️</td>
                <td>8,000</td>
                <td>50,000</td>
                <td>核エネルギーの発見</td>
            </tr>
            <tr>
                <td>現代Ⅱ</td>
                <td>🌐</td>
                <td>12,000</td>
                <td>80,000</td>
                <td>インターネットの時代</td>
            </tr>
            <tr>
                <td>現代Ⅲ</td>
                <td>📱</td>
                <td>18,000</td>
                <td>120,000</td>
                <td>スマートフォンとSNS</td>
            </tr>
            <tr>
                <td>量子革命時代</td>
                <td>⚛️</td>
                <td>25,000</td>
                <td>180,000</td>
                <td>量子コンピューター実用化</td>
            </tr>
            <tr>
                <td>現代Ⅳ</td>
                <td>🤖</td>
                <td>35,000</td>
                <td>250,000</td>
                <td>AI革命の時代</td>
            </tr>
            <tr>
                <td>現代Ⅴ</td>
                <td>🧬</td>
                <td>50,000</td>
                <td>350,000</td>
                <td>バイオテクノロジー</td>
            </tr>
            <tr>
                <td>宇宙時代</td>
                <td>🚀</td>
                <td>75,000</td>
                <td>500,000</td>
                <td>宇宙への進出</td>
            </tr>
        </table>
        
        <h3>新資源</h3>
        <ul>
            <li><strong>☢️ プルトニウム</strong>：核兵器と原子力発電に必要</li>
            <li><strong>🔲 シリコン</strong>：半導体と電子機器に必要</li>
            <li><strong>💫 レアアース</strong>：ハイテク機器に必要な希少資源</li>
            <li><strong>🔮 量子結晶</strong>：量子コンピューターに必要な特殊資源</li>
            <li><strong>🧠 AIコア</strong>：AIシステムの中核となる処理装置</li>
            <li><strong>🧬 遺伝子サンプル</strong>：バイオテクノロジー研究に必要</li>
            <li><strong>🌌 ダークマター</strong>：宇宙技術に必要な謎の物質</li>
            <li><strong>💥 反物質</strong>：宇宙船の燃料となる究極のエネルギー源</li>
        </ul>
        
        <h3>新ユニット（陸・海・空カテゴリ）</h3>
        <p>兵種に陸軍・海軍・空軍のカテゴリが追加されました：</p>
        <ul>
            <li><strong>陸軍</strong>：従来の歩兵、騎兵など地上戦闘ユニット</li>
            <li><strong>海軍</strong>：潜水艦、空母など海上戦闘ユニット</li>
            <li><strong>空軍</strong>：戦闘機、爆撃機など航空戦闘ユニット</li>
        </ul>
        
        <div class="info-box">
            <h4>💡 未来時代の攻略</h4>
            <p>未来時代では資源の種類が増え、建設・訓練コストも高額になります。効率的な資源管理と市場での資源交換を活用しましょう。ダイソン球計画などの究極建造物の建設を目指そう！</p>
        </div>
    </div>
    
    <!-- 16. 市場システム（詳細） -->
    <div class="section" id="market">
        <h2><span class="emoji-icon">🏪</span>16. 市場システム</h2>
        <p>市場を建設すると、資源同士を交換できるようになります。</p>
        
        <h3>交換制限</h3>
        <ul>
            <li>交換元の資源には1時間ごとの制限があります</li>
            <li><strong>制限量</strong>：10,000 × 市場建築数（1時間ごと）</li>
            <li>交換先の資源には制限がありません</li>
            <li>市場を多く建てると交換レートも改善されます</li>
        </ul>
        
        <div class="tip-box">
            <h4>💡 市場のコツ</h4>
            <p>余っている資源を不足している資源に変換しましょう。市場を複数建てることで交換レートが改善され、1時間あたりの交換上限も増加します。</p>
        </div>
    </div>
    
    <!-- 17. 銀行システム（詳細） -->
    <div class="section" id="bank">
        <h2><span class="emoji-icon">🏦</span>17. 銀行システム</h2>
        <p>銀行を建設すると、時間経過でコインを自動生成できます。</p>
        
        <h3>銀行の効果</h3>
        <ul>
            <li>銀行1レベルあたり1時間に10コインを生産</li>
            <li>資源収集時にコインも一緒に受け取れます</li>
            <li>銀行のレベルを上げると生産量が増加</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin-top: 40px; padding: 20px;">
        <a href="civilization.php" class="back-link" style="font-size: 1.2em; padding: 15px 30px;">🏛️ 文明育成をプレイする</a>
    </div>
</div>
</body>
</html>
