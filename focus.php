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
</head>
<body>
<header class="topbar">
  <div class="logo"><a class="link" href="./">← 戻る</a></div>
</header>

<main class="layout">
<section class="center">
  <div class="card">
    <h3>集中モード</h3>
    <p>やること: <input id="task" placeholder="例: 勉強"> 
    時間(分): <input id="mins" type="number" min="1" max="240" value="25">
    <button id="start">開始</button></p>
    <div id="timer" style="font-size:48px;margin-top:10px"></div>
  </div>
</section>
</main>

<script>
let lock=false, t=null, end=0;
const isiOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
let startTime = null; // ★開始時刻を記録用

document.getElementById('start').onclick = async () => {
  if (lock) return;

  const mins = parseInt(document.getElementById('mins').value || '25', 10);
  const task = document.getElementById('task').value.trim();
  if (!task) {
    alert("タスク名を入力してください");
    return;
  }

  startTime = new Date(); // ★開始時刻をセット

  // iPhone PWAモード or 通常フルスクリーン
  if (!isiOS) {
    try {
      await enterFullscreen(document.documentElement);
    } catch(e) {
      alert("フルスクリーン開始に失敗しました");
      return;
    }
  }

  end = Date.now() + mins*60*1000;
  lock = true;
  tick();
  t = setInterval(tick, 250);

  // 集中モード中に画面を離れたら失敗
  window.onblur = fail;
  document.onvisibilitychange = () => { if(document.visibilityState !== 'visible') fail(); };
};

function tick() {
  const remain = Math.max(0, end - Date.now());
  const s = Math.floor(remain/1000), m = Math.floor(s/60), ss = ('0'+(s%60)).slice(-2);
  document.getElementById('timer').textContent = `${m}:${ss}`;
  if (remain <= 0) success();
}

function success() {
  clearInterval(t); lock=false;
  exitFullscreen();

  const mins = parseInt(document.getElementById('mins').value || '25', 10);
  const coins = Math.floor(5 * Math.pow(1.1, mins));
  const crystals = Math.floor(1 * Math.pow(1.05, mins));
  const task = document.getElementById('task').value.trim();
  const endTime = new Date();

  alert(`成功！コイン+${coins} / クリスタル+${crystals}`);

  sendFocusLog(task, startTime, endTime, mins, coins, crystals, "success");
}

function fail() {
  if (!lock) return;
  clearInterval(t); lock=false;
  exitFullscreen();
  alert('失敗...');

  const task = document.getElementById('task').value.trim();
  const endTime = new Date();
  const started = startTime ?? endTime; // startTime が null なら現在時刻を代入

  sendFocusLog(task, started, endTime, 0, 0, 0, "fail");
}

// 共通ログ送信関数
function sendFocusLog(task, started_at, ended_at, mins, coins, crystals, status) {
  if (!task) return; // タスク名が空なら送信しない

  fetch('focus_save.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      task,
      started_at: started_at.toISOString().slice(0,19).replace('T',' '),
      ended_at: ended_at.toISOString().slice(0,19).replace('T',' '),
      mins, coins, crystals,
      status
    })
  }).catch(e => console.error("focus_save fetch error:", e));
}

// Safari対応フルスクリーン
function enterFullscreen(elem) {
  if (elem.requestFullscreen) {
    return elem.requestFullscreen();
  } else if (elem.webkitRequestFullscreen) {
    return elem.webkitRequestFullscreen();
  } else {
    return Promise.reject("Fullscreen API not supported");
  }
}

function exitFullscreen() {
  if (document.fullscreenElement || document.webkitFullscreenElement) {
    if (document.exitFullscreen) {
      document.exitFullscreen().catch(()=>{});
    } else if (document.webkitExitFullscreen) {
      document.webkitExitFullscreen();
    }
  }
}
</script>

</body>
</html>
