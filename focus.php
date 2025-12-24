<?php
require_once __DIR__ . '/config.php';

// エラー報告を有効化（デバッグ用）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // ユーザー確認
    $me = user();
    if (!$me) {
        // ユーザーが未ログインの場合はログインページにリダイレクト
        header('Location: /login.php');
        exit;
    }
} catch (Exception $e) {
    // エラーが発生した場合はエラーメッセージを表示
    die('エラーが発生しました: ' . htmlspecialchars($e->getMessage()));
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>集中タイマー - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">

<!-- PWA対応 -->
<link rel="manifest" href="/manifest.json">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<style>
body {
  margin: 0;
  min-height: 100vh;
  background: linear-gradient(135deg, #0d0d0d 0%, #1a1a2e 50%, #16213e 100%);
  overflow-x: hidden;
  position: relative;
  color: #fff;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* 背景アニメーション */
body::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: 
    radial-gradient(circle at 20% 50%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(118, 75, 162, 0.1) 0%, transparent 50%);
  pointer-events: none;
  z-index: 0;
  animation: bgShift 20s ease-in-out infinite;
}

@keyframes bgShift {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 0.8; }
}

canvas#fireCanvas {
  position: fixed;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  pointer-events: none;
  z-index: 1;
  width: 400px;
  height: 400px;
  filter: drop-shadow(0 0 30px rgba(255, 136, 0, 0.5));
}

/* 名言表示 - 改善版 */
#quote {
  position: fixed;
  top: 15%;
  left: 50%;
  transform: translateX(-50%);
  font-size: clamp(1rem, 3vw, 1.5rem);
  font-weight: bold;
  color: #ffdd99;
  text-shadow: 0 0 15px #ff8800, 0 0 25px #ff6600, 0 2px 4px rgba(0,0,0,0.5);
  padding: 16px 24px;
  background: linear-gradient(135deg, rgba(0,0,0,0.6) 0%, rgba(20,20,40,0.8) 100%);
  border-radius: 16px;
  max-width: 85%;
  text-align: center;
  z-index: 10;
  border: 2px solid rgba(255, 221, 153, 0.3);
  box-shadow: 0 4px 20px rgba(0,0,0,0.5), inset 0 1px 1px rgba(255,255,255,0.1);
  animation: quoteFade 1s ease-in-out;
}

@keyframes quoteFade {
  from { opacity: 0; transform: translateX(-50%) translateY(-10px); }
  to { opacity: 1; transform: translateX(-50%) translateY(0); }
}

/* カード全体の改善 */
.focus-card {
  background: linear-gradient(135deg, rgba(30, 30, 50, 0.95) 0%, rgba(20, 20, 35, 0.95) 100%);
  border-radius: 24px;
  padding: 32px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.6), 0 0 1px rgba(102, 126, 234, 0.5);
  border: 1px solid rgba(102, 126, 234, 0.2);
  backdrop-filter: blur(10px);
  position: relative;
  z-index: 2;
  max-width: 600px;
  margin: 0 auto;
  animation: cardSlideIn 0.6s ease-out;
}

@keyframes cardSlideIn {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.focus-card h3 {
  font-size: clamp(1.5rem, 4vw, 2rem);
  margin: 0 0 24px 0;
  text-align: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-weight: bold;
  text-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
}

/* ティア表示の改善 */
.tier-display {
  text-align: center;
  padding: 12px 20px;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%);
  border-radius: 12px;
  margin-bottom: 24px;
  border: 1px solid rgba(102, 126, 234, 0.3);
  font-size: 1.1rem;
  font-weight: 600;
}

#currentTier {
  color: #ffd700;
  font-weight: bold;
  font-size: 1.2rem;
  text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
}

/* フォーム要素の改善 */
.focus-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 0.95rem;
  color: #a0a0c0;
  font-weight: 500;
}

.focus-form input[type="text"],
.focus-form input[type="number"] {
  padding: 12px 16px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(102, 126, 234, 0.3);
  border-radius: 12px;
  color: #fff;
  font-size: 1rem;
  transition: all 0.3s ease;
  outline: none;
}

.focus-form input[type="text"]:focus,
.focus-form input[type="number"]:focus {
  background: rgba(255, 255, 255, 0.08);
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
  transform: translateY(-2px);
}

.focus-form input[type="text"]::placeholder,
.focus-form input[type="number"]::placeholder {
  color: #606080;
}

/* チェックボックスの改善 */
.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  padding: 10px;
  border-radius: 8px;
  transition: background 0.2s;
  font-size: 0.9rem;
  color: #b0b0d0;
}

.checkbox-label:hover {
  background: rgba(255, 255, 255, 0.03);
}

.checkbox-label input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
}

/* 開始ボタンの改善 */
#start {
  padding: 16px 32px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 16px;
  font-size: 1.2rem;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
  margin-top: 8px;
  position: relative;
  overflow: hidden;
}

#start::before {
  content: '🔥';
  position: absolute;
  left: 20px;
  font-size: 1.3rem;
  animation: fireFlicker 2s ease-in-out infinite;
}

@keyframes fireFlicker {
  0%, 100% { opacity: 0.8; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.2); }
}

#start:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.6);
}

#start:active {
  transform: translateY(-1px);
}

#start:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* タイマー表示の改善 */
#timer {
  font-size: clamp(3rem, 10vw, 5rem);
  margin-top: 24px;
  text-align: center;
  font-weight: bold;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  text-shadow: 0 4px 20px rgba(102, 126, 234, 0.5);
  letter-spacing: 0.05em;
  font-family: 'Courier New', monospace;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
  .focus-card {
    padding: 24px;
    margin: 20px;
  }
  
  #quote {
    max-width: 90%;
    padding: 12px 16px;
  }
  
  canvas#fireCanvas {
    width: 300px;
    height: 300px;
  }
}

/* ローディングアニメーション */
.loading {
  display: inline-block;
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 0.6; }
  50% { opacity: 1; }
}
</style>
</head>
<body>
<header class="topbar">
  <div class="logo"><a class="link" href="./">← 戻る</a></div>
</header>

<!-- 焚火Canvas -->
<canvas id="fireCanvas"></canvas>

<!-- 名言 -->
<div id="quote" style="display:none;"></div>

<!-- 完了モーダル -->
<div id="completionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; justify-content: center; align-items: center; overflow-y: auto; padding: 20px 0;">
  <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; padding: 40px; max-width: 600px; width: 90%; color: white; box-shadow: 0 10px 40px rgba(0,0,0,0.5); margin: auto; max-height: 90vh; overflow-y: auto;">
    <h2 id="modalTitle" style="text-align: center; font-size: clamp(20px, 5vw, 32px); margin-bottom: 20px;"></h2>
    <div id="modalContent" style="font-size: clamp(14px, 3vw, 18px); line-height: 1.8;"></div>
    <button onclick="closeCompletionModal()" style="width: 100%; margin-top: 30px; padding: 15px; background: white; color: #667eea; border: none; border-radius: 10px; font-size: clamp(14px, 3vw, 18px); font-weight: bold; cursor: pointer;">閉じる</button>
  </div>
</div>

<main class="layout">
<section class="center">
  <div class="card focus-card">
    <h3>🔥 集中モード 🔥</h3>

    <div class="tier-display">
      現在のティア: <span id="currentTier" class="loading">読み込み中...</span>
    </div>

    <form class="focus-form" onsubmit="return false;">
      <div class="form-group">
        <label for="task">📝 やること</label>
        <input id="task" type="text" placeholder="例: 勉強、読書、プログラミング...">
      </div>
      
      <div class="form-group">
        <label for="mins">⏱️ 時間（分）</label>
        <input id="mins" type="number" min="1" max="<?= FOCUS_MAX_MINUTES ?>" value="25">
        <small style="color: #a0a0c0; font-size: 0.85rem; margin-top: 4px; display: block;">最大<?= FOCUS_MAX_MINUTES ?>分まで設定可能（チート防止）</small>
      </div>
      
      <div class="form-group">
        <label for="tagHandle">👥 タッグ（友達のハンドル）</label>
        <input id="tagHandle" type="text" placeholder="例: friend123">
      </div>
      
      <label class="checkbox-label">
        <input type="checkbox" id="disablePenalty">
        <span>画面離脱ペナルティを無効化する</span>
      </label>
      
      <button id="start">集中開始！</button>
      <button id="abort" style="display: none; background: linear-gradient(135deg, #f56565 0%, #c53030 100%); margin-top: 12px;">
        ⏸️ 中断する（失敗扱い・進捗報酬あり）
      </button>
    </form>

    <div id="timer"></div>
  </div>
</section>
</main>

<script>
// 報酬計算用の定数
const REWARD_CONFIG = {
  BASE_COINS: 10,
  BASE_CRYSTALS: 2,
  COINS_EXP_RATE: 1.04,
  CRYSTALS_EXP_RATE: 1.015,
  MAX_MINUTES: <?= FOCUS_MAX_MINUTES ?>
};

let lock=false, t=null, end=0, quoteInterval=null;
const isiOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
let startTime=null;

// 報酬計算関数（bounds checking付き）
function calculateRewards(mins) {
  // 最大値チェック（安全のため）
  const safeMins = Math.min(mins, REWARD_CONFIG.MAX_MINUTES);
  const coins = Math.floor(REWARD_CONFIG.BASE_COINS * Math.pow(REWARD_CONFIG.COINS_EXP_RATE, safeMins));
  const crystals = Math.floor(REWARD_CONFIG.BASE_CRYSTALS * Math.pow(REWARD_CONFIG.CRYSTALS_EXP_RATE, safeMins));
  return { coins, crystals };
}

// 名言リスト
const quotes = [
  "成功の秘訣は、やる気が出るまで待たずに始めること。",
  "今日の努力が未来の自分を作る。",
  "小さな一歩が、大きな成果に繋がる。",
  "やり抜く力が才能を超える。",
  "できるかできないかじゃない、やるかやらないかだ。",
  "集中力は習慣から生まれる。",
  "諦めるな、あと少しで成果が見える。",
  "努力の積み重ねが明日を変える。",
  "成長は快適ゾーンの外にある。",
  "努力は必ず報われる、時を選ばずに。",
  "毎日の小さな集中が、大きな結果を生む。",
  "失敗は成功のための学びである。",
  "今日できることを全力でやる、それが未来を変える。",
  "集中力を高めることが、人生の質を上げる。",
  "努力のない天才は存在しない。",
  "限界を決めるのは自分自身だ。",
  "挑戦することでしか、新しい自分は見えない。",
  "今日の集中が明日の自信を作る。",
  "努力は裏切らないが、怠け心は裏切る。",
  "夢は努力の上にしか成り立たない。",
  "自分を信じて、一歩踏み出せ。",
  "小さな成功を積み重ねることが大きな勝利に繋がる。",
  "集中力は練習で鍛えられる筋肉だ。",
  "苦しい時こそ成長のチャンス。",
  "やるべきことを後回しにするな。",
  "今日の努力は昨日の自分への投資だ。",
  "挑戦なくして成長なし。",
  "一流は努力の量で決まる。",
  "成功は偶然ではなく、必然の積み重ねだ。",
  "努力は裏切らない、諦めが裏切るだけ。",
  "自分を信じることが全ての始まり。",
  "毎日の小さな習慣が未来の大きな結果を作る。",
  "諦めずに続ける力こそが本物の力。",
  "集中している時間は人生の質を決める。",
  "行動しない限り、何も変わらない。",
  "失敗を恐れず挑戦し続けろ。",
  "努力を楽しめば、成果は自然とついてくる。",
  "自分の限界は自分で決めるな。",
  "今日の小さな努力が、明日の大きな自信になる。",
  "習慣こそが人を作る。",
  "集中は勝利への最短距離だ。",
  "諦めなければ必ず道は開ける。",
  "失敗は成功の母。",
  "努力の先にしか、本物の喜びはない。",
  "一歩踏み出す勇気が人生を変える。",
  "自分のペースで前に進め。",
  "挑戦は自分を試す最高の方法だ。",
  "努力は才能を追い越す力になる。",
  "集中力は自分を支える最強の武器。",
  "成功は小さな努力の積み重ねから生まれる。",
  "今日の自分を超えることを目指せ。",
  "失敗しても立ち上がる勇気が未来を作る。",
  "習慣が人生を決める。",
  "努力することでしか夢は現実にならない。",
  "一日一歩、三日で三歩、千里の道も一歩から。",
  "集中している時間が人生を変える。",
  "やる気がなくても行動することが大切だ。",
  "挑戦なくして成長なし。",
  "自分に負けなければ誰にも負けない。",
  "努力は裏切らない、続けることが全て。",
  "今日できることを明日に延ばすな。",
  "一歩踏み出すことで世界は変わる。",
  "限界は自分の中にしかない。",
  "習慣を制する者が人生を制す。",
  "努力を楽しむ心が成功を呼ぶ。",
  "小さな進歩を喜べ、それが大きな変化につながる。",
  "集中力は磨けば磨くほど光る。",
  "諦めたらそこで試合終了だ。",
  "挑戦は自分を強くする最高の薬。",
  "今日の努力が明日の自分を作る。",
  "集中力がある人は時間を味方につける。",
  "努力は未来への種だ。",
  "行動する者にのみチャンスは訪れる。",
  "失敗は恐れず、学びに変えろ。",
  "毎日の積み重ねが人生を変える。",
  "集中力は習慣から生まれる。",
  "限界を決めるのは他人ではなく自分だ。",
  "努力なくして成長なし。",
  "自分の努力が自分を裏切ることはない。",
  "今日の一歩が明日の大きな成果に繋がる。",
  "挑戦する心が人生を豊かにする。",
  "集中して取り組むことが成功の鍵。",
  "努力を続ける者が最終的に勝つ。",
  "小さな成功を積み重ねろ。",
  "自分を信じて行動し続けろ。",
  "諦めなければ道は必ず開ける。",
  "努力は必ず報われる。",
  "挑戦することに価値がある。",
  "集中力は人生の質を決める。",
  "毎日の努力が未来を作る。",
  "成功は努力の先にしかない。",
  "今日の自分に全力を尽くせ。",
  "失敗しても挑戦を続けろ。",
  "努力を楽しむ心を持て。",
  "集中力を高めることが成長への近道。",
  "諦めずに続けることで未来が変わる。",
  "努力は習慣となり力となる。",
  "自分を信じることが全ての始まり。",
  "挑戦なくして人生に彩りはない。",
  "集中は力、努力は道。",
  "小さな努力が大きな成果を生む。",
  "今日の集中が明日の自信に繋がる。",
  "限界を超えようとする心が強さを生む。",
  "行動することでしか未来は変わらない。",
  "失敗を恐れず挑戦し続けることが成長の鍵。",
  "努力の積み重ねが人生を作る。",
  "集中して取り組む時間が成功を生む。",
  "今日の小さな努力を大切に。",
  "挑戦することが人生を豊かにする。",
  "努力は未来への最大の投資。",
  "集中力を高めることが結果を変える。",
  "諦めずに続ける力が本物の力となる。",
  "毎日の努力が未来の自分を輝かせる。",
  "成功は継続する者に訪れる。",
  "小さな一歩を積み重ね続けろ。"
];


// ページロード時に現在ティアを取得（タイムアウト付き）
const tierFetchTimeout = setTimeout(() => {
  const tierEl = document.getElementById('currentTier');
  if (tierEl.classList.contains('loading')) {
    tierEl.classList.remove('loading');
    tierEl.textContent = 'ティア情報取得失敗';
    console.warn('Tier fetch timed out');
  }
}, 5000); // 5秒でタイムアウト

fetch('get_focus_tier.php')
  .then(r=>r.json())
  .then(data=>{
    clearTimeout(tierFetchTimeout);
    const tierEl = document.getElementById('currentTier');
    tierEl.classList.remove('loading');
    tierEl.textContent = data.ok?`ティア${data.tier}`:'不明';
  }).catch((error)=>{
    clearTimeout(tierFetchTimeout);
    const tierEl = document.getElementById('currentTier');
    tierEl.classList.remove('loading');
    tierEl.textContent='ティア情報取得エラー';
    console.error('Tier fetch error:', error);
  });

document.getElementById('start').onclick = async ()=>{
  if(lock) return;

  const mins = parseInt(document.getElementById('mins').value||'25',10);
  
  // 最大時間チェック
  if (mins > REWARD_CONFIG.MAX_MINUTES) {
    alert(`集中時間は最大${REWARD_CONFIG.MAX_MINUTES}分までです。\n入力された値: ${mins}分`);
    return;
  }
  
  const task = document.getElementById('task').value.trim();
  const disablePenalty = document.getElementById('disablePenalty').checked;
  if(!task){alert("タスク名を入力してください");return;}
  startTime=new Date();

  if(!isiOS){
    try{await enterFullscreen(document.documentElement);}catch{alert("フルスクリーン開始に失敗しました"); return;}
  }

  end=Date.now()+mins*60*1000;
  lock=true;
  
  // UIの更新
  document.getElementById('start').style.display = 'none';
  document.getElementById('abort').style.display = 'block';
  
  tick();
  t=setInterval(tick,250);

  // ペナルティが無効化されていない場合のみ、離脱検知を設定
  if (!disablePenalty) {
    window.onblur = fail;
    document.onvisibilitychange = ()=>{if(document.visibilityState!=='visible') fail();};
  }

  // 名言初期表示 & 1分ごとに切り替え
  const quoteEl = document.getElementById("quote");
  quoteEl.style.display="block";
  function showRandomQuote(){
    const q = quotes[Math.floor(Math.random()*quotes.length)];
    quoteEl.textContent = q;
  }
  showRandomQuote();
  quoteInterval = setInterval(showRandomQuote, 60000);

  // 火アニメーション開始
  initFireCanvas();
};

// 中断ボタンのハンドラ
document.getElementById('abort').onclick = ()=>{
  if(!lock) return;
  if(!confirm('中断しますか？\n失敗扱いになりますが、ここまでの進捗に応じた報酬がもらえます。')) return;
  
  // 中断は失敗として扱う
  fail();
};

function tick(){
  const remain=Math.max(0,end-Date.now());
  const s=Math.floor(remain/1000), m=Math.floor(s/60), ss=('0'+(s%60)).slice(-2);
  document.getElementById('timer').textContent = `${m}:${ss}`;
  if(remain<=0) success();
}

function sendFocusLog(task, started_at, ended_at, mins, coins, crystals, status){
  if(!task) return;
  const tagHandle = document.getElementById('tagHandle').value.trim();
  fetch('focus_save.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({
      task,
      started_at:started_at.toISOString().slice(0,19).replace('T',' '),
      ended_at:ended_at.toISOString().slice(0,19).replace('T',' '),
      mins, coins, crystals, status,
      tag_handle: tagHandle
    })
  })
  .then(r => r.json())
  .then(data => {
    if(data.ok && data.tier !== undefined){
      // ティア表示更新
      document.getElementById('currentTier').textContent = `ティア${data.tier}`;

      // 完了モーダルを表示
      showCompletionModal(status, data);
    }
  })
  .catch(e => console.error("focus_save fetch error:", e));
}

function showCompletionModal(status, data) {
  const modal = document.getElementById('completionModal');
  const title = document.getElementById('modalTitle');
  const content = document.getElementById('modalContent');
  
  if (status === 'success') {
    title.innerHTML = '🎉 成功！よく頑張りました！';
  } else {
    title.innerHTML = '😔 惜しい！次は成功しよう！';
  }
  
  // トークンドロップをフォーマット
  let tokenDropsHtml = '';
  if (data.token_drops && Object.keys(data.token_drops).length > 0) {
    const tokenIcons = {
      'normal_tokens': '⚪',
      'rare_tokens': '🟢',
      'unique_tokens': '🔵',
      'legend_tokens': '🟡',
      'epic_tokens': '🟣',
      'hero_tokens': '🔴',
      'mythic_tokens': '🌈'
    };
    const tokenNames = {
      'normal_tokens': 'ノーマル',
      'rare_tokens': 'レア',
      'unique_tokens': 'ユニーク',
      'legend_tokens': 'レジェンド',
      'epic_tokens': 'エピック',
      'hero_tokens': 'ヒーロー',
      'mythic_tokens': 'ミシック'
    };
    
    tokenDropsHtml = Object.entries(data.token_drops).map(([key, val]) => {
      return `<div style="text-align: center; padding: 5px 10px;">
        <div style="font-size: clamp(20px, 5vw, 28px);">${tokenIcons[key] || '🎫'}</div>
        <div style="font-size: clamp(14px, 3vw, 18px); font-weight: bold;">+${val}</div>
        <div style="font-size: clamp(10px, 2vw, 12px); opacity: 0.7;">${tokenNames[key] || key}</div>
      </div>`;
    }).join('');
  }
  
  let html = `
    <div style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: clamp(10px, 3vw, 20px); margin-bottom: clamp(10px, 3vw, 20px);">
      <h3 style="margin: 0 0 15px 0; font-size: clamp(16px, 4vw, 20px);">📊 報酬</h3>
      <div style="display: flex; gap: clamp(10px, 3vw, 20px); justify-content: center; flex-wrap: wrap;">
        <div style="text-align: center;">
          <div style="font-size: clamp(24px, 6vw, 36px);">🪙</div>
          <div style="font-size: clamp(18px, 4vw, 24px); font-weight: bold;">+${data.coins}</div>
        </div>
        <div style="text-align: center;">
          <div style="font-size: clamp(24px, 6vw, 36px);">💎</div>
          <div style="font-size: clamp(18px, 4vw, 24px); font-weight: bold;">+${data.crystals}</div>
        </div>
      </div>
      ${data.tag_bonus_active ? '<div style="margin-top: 15px; text-align: center; font-size: clamp(12px, 3vw, 16px); color: #ffeb3b;">✨ タッグボーナス！報酬2倍 ✨</div>' : ''}
    </div>
  `;
  
  // トークンドロップセクション
  if (tokenDropsHtml) {
    html += `
    <div style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: clamp(10px, 3vw, 20px); margin-bottom: clamp(10px, 3vw, 20px);">
      <h3 style="margin: 0 0 15px 0; font-size: clamp(16px, 4vw, 20px);">🎫 トークンドロップ</h3>
      <div style="display: flex; gap: clamp(8px, 2vw, 15px); justify-content: center; flex-wrap: wrap;">
        ${tokenDropsHtml}
      </div>
    </div>
    `;
  }
  
  if (data.statistics) {
    const stats = data.statistics;
    html += `
      <div style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: clamp(10px, 3vw, 20px); margin-bottom: clamp(10px, 3vw, 20px);">
        <h3 style="margin: 0 0 15px 0; font-size: clamp(16px, 4vw, 20px);">🔥 連続記録</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: clamp(10px, 3vw, 15px);">
          <div style="text-align: center;">
            <div style="font-size: clamp(12px, 2.5vw, 14px); opacity: 0.8;">連続成功</div>
            <div style="font-size: clamp(20px, 5vw, 28px); font-weight: bold;">${stats.consecutive_successes}回</div>
          </div>
          <div style="text-align: center;">
            <div style="font-size: clamp(12px, 2.5vw, 14px); opacity: 0.8;">連続日数</div>
            <div style="font-size: clamp(20px, 5vw, 28px); font-weight: bold;">${stats.current_streak}日</div>
          </div>
        </div>
      </div>
      
      <div style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: clamp(10px, 3vw, 20px);">
        <h3 style="margin: 0 0 15px 0; font-size: clamp(16px, 4vw, 20px);">📈 ランキング（上位%）</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: clamp(8px, 2vw, 15px);">
          <div style="text-align: center;">
            <div style="font-size: clamp(11px, 2.5vw, 14px); opacity: 0.8;">本日</div>
            <div style="font-size: clamp(16px, 4vw, 24px); font-weight: bold;">上位${stats.today_percentile.toFixed(1)}%</div>
            <div style="font-size: clamp(10px, 2vw, 12px); opacity: 0.6;">${stats.today_total}分</div>
          </div>
          <div style="text-align: center;">
            <div style="font-size: clamp(11px, 2.5vw, 14px); opacity: 0.8;">直近1週間</div>
            <div style="font-size: clamp(16px, 4vw, 24px); font-weight: bold;">上位${stats.week_percentile.toFixed(1)}%</div>
            <div style="font-size: clamp(10px, 2vw, 12px); opacity: 0.6;">${stats.week_total}分</div>
          </div>
          <div style="text-align: center;">
            <div style="font-size: clamp(11px, 2.5vw, 14px); opacity: 0.8;">累計</div>
            <div style="font-size: clamp(16px, 4vw, 24px); font-weight: bold;">上位${stats.total_percentile.toFixed(1)}%</div>
            <div style="font-size: clamp(10px, 2vw, 12px); opacity: 0.6;">${stats.total_time}分</div>
          </div>
        </div>
      </div>
    `;
  }
  
  content.innerHTML = html;
  modal.style.display = 'flex';
}

function closeCompletionModal() {
  document.getElementById('completionModal').style.display = 'none';
}

function success(){
  clearInterval(t); lock=false;
  clearInterval(quoteInterval);
  exitFullscreen();
  
  // UIをリセット
  document.getElementById('start').style.display = 'block';
  document.getElementById('abort').style.display = 'none';
  
  const mins = parseInt(document.getElementById('mins').value || '25', 10);
  const rewards = calculateRewards(mins);

  const task = document.getElementById('task').value.trim();
  const endTime = new Date();
  
  sendFocusLog(task, startTime, endTime, mins, rewards.coins, rewards.crystals, "success");
}

function fail(){
  if(!lock) return;
  clearInterval(t); lock=false;
  clearInterval(quoteInterval);
  exitFullscreen();
  
  // UIをリセット
  document.getElementById('start').style.display = 'block';
  document.getElementById('abort').style.display = 'none';

  const task = document.getElementById('task').value.trim();
  const endTime = new Date();
  const started = startTime ?? endTime;

  const mins = parseInt(document.getElementById('mins').value || '25', 10);
  const rewards = calculateRewards(mins);

  sendFocusLog(task, started, endTime, mins, rewards.coins, rewards.crystals, "fail");
}

function enterFullscreen(elem){
  if(elem.requestFullscreen) return elem.requestFullscreen();
  if(elem.webkitRequestFullscreen) return elem.webkitRequestFullscreen();
  return Promise.reject("Fullscreen API not supported");
}

function exitFullscreen(){
  if(document.fullscreenElement||document.webkitFullscreenElement){
    if(document.exitFullscreen) document.exitFullscreen().catch(()=>{});
    else if(document.webkitExitFullscreen) document.webkitExitFullscreen();
  }
}

// ==================== 焚火Canvas ====================
function initFireCanvas(){
  const canvas = document.getElementById('fireCanvas');
  const ctx = canvas.getContext('2d');
  canvas.width = 400;
  canvas.height = 400;

  const flameParticles = [];
  const smokeParticles = [];
  const sparks = [];

  // 炎粒子
  for(let i=0;i<300;i++){
    flameParticles.push({
      x:200,
      y:350,
      vx:(Math.random()-0.5)*2,       // 炎全体を少し大きく
      vy:-Math.random()*3-1.5,
      alpha:Math.random(),
      size:Math.random()*8+6,          // 根元大きめ
      gradient: createFlameGradient(ctx),
      shrinkRate: Math.random()*0.02 + 0.01 // 徐々に小さくなる
    });
  }

  // 煙粒子（小さめ）
  for(let i=0;i<50;i++){
    smokeParticles.push({
      x:200 + (Math.random()-0.5)*30,
      y:350,
      vx:(Math.random()-0.3)*0.5,
      vy:-Math.random()*1.5-0.5,
      alpha:Math.random()*0.3+0.2,
      size:Math.random()*8+5
    });
  }

  // 火花粒子
  function spawnSpark(){
    sparks.push({
      x:200 + (Math.random()-0.5)*20,
      y:350,
      vx:(Math.random()-0.5)*2,
      vy:-Math.random()*3-2,
      alpha:1,
      size:Math.random()*2+1,
      color:'#fffacd'
    });
  }

  // 炎用グラデーション作成
  function createFlameGradient(ctx){
    const grad = ctx.createRadialGradient(0,0,0,0,0,1);
    grad.addColorStop(0,'rgba(255,255,180,1)'); // 中心黄色
    grad.addColorStop(0.5,'rgba(255,140,0,1)'); // オレンジ
    grad.addColorStop(1,'rgba(255,0,0,0)');     // 外側赤透明
    return grad;
  }

  function animate(){
    ctx.clearRect(0,0,canvas.width,canvas.height);

    // 火の芯チラチラ光
    const flicker = Math.random()*0.3 + 0.7;
    const gradient = ctx.createRadialGradient(200, 350, 5, 200, 350, 150);
    gradient.addColorStop(0,`rgba(255,220,120,${0.4*flicker})`);
    gradient.addColorStop(1,'rgba(0,0,0,0)');
    ctx.fillStyle = gradient;
    ctx.fillRect(0,0,canvas.width,canvas.height);

    // 炎粒子描画
    flameParticles.forEach(p=>{
      p.x += p.vx;
      p.y += p.vy + Math.sin(Date.now()/200 + p.x)*0.3;
      p.vx += (Math.random()-0.5)*0.1;
      p.vy += (Math.random()-0.5)*0.05;

      // 粒子を徐々に小さく
      p.size -= p.shrinkRate;
      p.alpha -= 0.008;

      if(p.alpha <= 0 || p.size <= 1){
        p.x = 200;
        p.y = 350;
        p.vx = (Math.random()-0.5)*2;
        p.vy = -Math.random()*3-1.5;
        p.alpha = 1;
        p.size = Math.random()*8+6;       // 根元大きめ
        p.shrinkRate = Math.random()*0.02 + 0.01;
      }

      ctx.save();
      ctx.translate(p.x, p.y);
      ctx.rotate(Math.random()*0.3);
      ctx.beginPath();
      ctx.ellipse(0,0,p.size*0.6,p.size*1.5,0,0,Math.PI*2);
      ctx.fillStyle = p.gradient;
      ctx.globalAlpha = p.alpha;
      ctx.fill();
      ctx.restore();

      if(Math.random() < 0.02) spawnSpark();
    });
    ctx.globalAlpha = 1;

    // 煙粒子描画
    smokeParticles.forEach(s=>{
      s.x += s.vx;
      s.y += s.vy;
      s.alpha -= 0.002;
      if(s.alpha <= 0){
        s.x = 200 + (Math.random()-0.5)*30;
        s.y = 350;
        s.vx = (Math.random()-0.3)*0.5;
        s.vy = -Math.random()*1.5-0.5;
        s.alpha = Math.random()*0.3+0.2;
        s.size = Math.random()*8+5;
      }
      ctx.beginPath();
      ctx.arc(s.x,s.y,s.size,0,Math.PI*2);
      ctx.fillStyle = `rgba(200,200,200,${s.alpha})`;
      ctx.fill();
    });

    // 火花描画
    for(let i=sparks.length-1;i>=0;i--){
      const sp = sparks[i];
      sp.x += sp.vx;
      sp.y += sp.vy;
      sp.alpha -= 0.02;
      sp.vy += 0.05;
      ctx.beginPath();
      ctx.arc(sp.x,sp.y,sp.size,0,Math.PI*2);
      ctx.fillStyle = `rgba(255,255,200,${sp.alpha})`;
      ctx.fill();
      if(sp.alpha <= 0) sparks.splice(i,1);
    }

    requestAnimationFrame(animate);
  }

  animate();
}


</script>
</body>
</html>
