<!doctype html>
<html lang="ja"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>集中タイマー - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
</head><body>
<header class="topbar"><div class="logo"><a class="link" href="./">← 戻る</a></div></header>
<main class="layout"><section class="center">
  <div class="card">
    <h3>集中モード</h3>
    <p>やること: <input id="task" placeholder="例: 勉強"> 時間(分): <input id="mins" type="number" min="1" max="240" value="25">
    <button id="start">開始</button></p>
    <div id="timer" style="font-size:48px;margin-top:10px"></div>
  </div>
</section></main>
<script>
let lock=false,t=null,end=0;
document.getElementById('start').onclick=()=>{
  if (lock) return;
  const mins=parseInt(document.getElementById('mins').value||'25',10);
  document.documentElement.requestFullscreen?.();
  end=Date.now()+mins*60*1000; lock=true; tick();
  t=setInterval(tick, 250);
  window.onblur=fail; document.onvisibilitychange=()=>{ if(document.visibilityState!=='visible') fail(); };
};
function tick(){
  const remain=Math.max(0,end-Date.now());
  const s=Math.floor(remain/1000), m=Math.floor(s/60), ss=('0'+(s%60)).slice(-2);
  document.getElementById('timer').textContent=`${m}:${ss}`;
  if (remain<=0){ success(); }
}
function success(){
  clearInterval(t); lock=false; document.exitFullscreen?.();
  alert('成功！コイン+5 / クリスタル+1');
  fetch('focus_api.php',{method:'POST'});
}
function fail(){
  if (!lock) return;
  clearInterval(t); lock=false; document.exitFullscreen?.();
  alert('失敗...'); 
}
</script>
</body></html>
