<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>集中タイマー - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">

<!-- PWA対応 -->
<link rel="manifest" href="/manifest.json">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<style>
body {
  margin: 0;
  height: 100vh;
  background: #0d0d0d;
  overflow: hidden;
  position: relative;
  color: #fff;
}
canvas#fireCanvas {
  position: fixed;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  pointer-events: none;
  z-index: 0;
  width: 400px;
  height: 400px;
}

/* 名言表示 */
#quote {
  position: fixed;
  top: 15%;
  left: 50%;
  transform: translateX(-50%);
  font-size: 1.5em;
  font-weight: bold;
  color: #ffdd99;
  text-shadow: 0 0 10px #ff8800, 0 0 20px #ff6600;
  padding: 12px 20px;
  background: rgba(0,0,0,0.4);
  border-radius: 12px;
  max-width: 80%;
  text-align: center;
  z-index: 1;
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

<main class="layout">
<section class="center">
  <div class="card">
    <h3>集中モード</h3>

    <p>現在のティア: <span id="currentTier">読み込み中...</span></p>

    <p>
      やること: <input id="task" placeholder="例: 勉強"> <br>
      時間(分): <input id="mins" type="number" min="1" max="240" value="25"><br>
      タッグ（友達のハンドル）: <input id="tagHandle" placeholder="例: friend123"><br>
      <button id="start">開始</button>
    </p>

    <div id="timer" style="font-size:48px;margin-top:10px"></div>
  </div>
</section>
</main>

<script>
let lock=false, t=null, end=0, quoteInterval=null;
const isiOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
let startTime=null;

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


// ページロード時に現在ティアを取得
fetch('get_focus_tier.php')
  .then(r=>r.json())
  .then(data=>{
    document.getElementById('currentTier').textContent = data.ok?`ティア${data.tier}`:'不明';
  }).catch(()=>{document.getElementById('currentTier').textContent='不明';});

document.getElementById('start').onclick = async ()=>{
  if(lock) return;

  const mins = parseInt(document.getElementById('mins').value||'25',10);
  const task = document.getElementById('task').value.trim();
  if(!task){alert("タスク名を入力してください");return;}
  startTime=new Date();

  if(!isiOS){
    try{await enterFullscreen(document.documentElement);}catch{alert("フルスクリーン開始に失敗しました"); return;}
  }

  end=Date.now()+mins*60*1000;
  lock=true;
  tick();
  t=setInterval(tick,250);

  window.onblur = fail;
  document.onvisibilitychange = ()=>{if(document.visibilityState!=='visible') fail();};

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

      // 成功・失敗に関わらず報酬表示
      if(status === "success"){
        alert(`成功！コイン+${data.coins} / クリスタル+${data.crystals}`);
      } else {
        alert(`失敗でも報酬！コイン+${data.coins} / クリスタル+${data.crystals}`);
      }

      // タッグボーナス表示
      if(data.tag_bonus_active){
        alert(`タッグボーナス発生！報酬2倍`);
      }
    }
  })
  .catch(e => console.error("focus_save fetch error:", e));
}

function success(){
  clearInterval(t); lock=false;
  clearInterval(quoteInterval);
  exitFullscreen();
  const mins = parseInt(document.getElementById('mins').value || '25', 10);

  // --------------------------
  // 指数関数的報酬計算を JS 側でも統一
  // --------------------------
  const coins = Math.floor(3 * Math.pow(1.025, mins));
  const crystals = Math.floor(1 * Math.pow(1.012, mins));

  const task = document.getElementById('task').value.trim();
  const endTime = new Date();
  
  sendFocusLog(task, startTime, endTime, mins, coins, crystals, "success");
}

function fail(){
  if(!lock) return;
  clearInterval(t); lock=false;
  clearInterval(quoteInterval);
  exitFullscreen();

  const task = document.getElementById('task').value.trim();
  const endTime = new Date();
  const started = startTime ?? endTime;

  const mins = parseInt(document.getElementById('mins').value || '25', 10);

  // --------------------------
  // 失敗時も指数関数的報酬を計算（半分はサーバー側で対応）
  // --------------------------
  const coins = Math.floor(3 * Math.pow(1.025, mins));
  const crystals = Math.floor(1 * Math.pow(1.012, mins));

  sendFocusLog(task, started, endTime, mins, coins, crystals, "fail");
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
