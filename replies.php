<?php
require_once __DIR__ . '/config.php';
$me = user();
$post_id = (int)($_GET['post_id'] ?? 0);
if ($post_id<=0){ http_response_code(400); echo "bad post_id"; exit; }
$pdo = db();
$st = $pdo->prepare("SELECT p.*, u.handle FROM posts p JOIN users u ON u.id=p.user_id WHERE p.id=?");
$st->execute([$post_id]);
$p = $st->fetch();
if (!$p){ http_response_code(404); echo "not found"; exit; }
?>
<!doctype html>
<html lang="ja"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>返信 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
</head><body>
<header class="topbar"><div class="logo"><a class="link" href="./">← 戻る</a></div></header>
<main class="layout">
<section class="center">
  <div class="card">
    <div><strong>@<?=$p['handle']?></strong> / <?=$p['created_at']?></div>
    <div><?=$p['content_html']?></div>
  </div>

  <div class="card">
    <h3>返信を投稿</h3>
    <?php if ($me): ?>
      <textarea id="replyText" maxlength="1024" placeholder="返信（Markdown可）"></textarea>
      <div class="row"><label><input type="checkbox" id="nsfw"> NSFW</label><button id="sendReply">送信</button></div>
    <?php else: ?>
      <div class="notice">ログインしてください</div>
    <?php endif; ?>
  </div>

  <div id="replyList"></div>
</section>
</main>
<script>
async function api(path, data){
  const r = await fetch(path, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)});
  return r.json();
}
const post_id = <?=$post_id?>;
async function load(){
  const r = await api('replies_api.php', {action:'list', post_id});
  if (r.ok){
    const wrap = document.getElementById('replyList'); wrap.innerHTML='';
    r.items.forEach(x=>{
      const div = document.createElement('div'); div.className='post'; 
      div.innerHTML = `<div class="avatar">${x.handle[0].toUpperCase()}</div><div><div class="meta">@${x.handle} ・ ${x.created_at}</div><div>${x.content_html}</div></div>`;
      wrap.append(div);
    });
  }
}
document.getElementById('sendReply')?.addEventListener('click', async ()=>{
  const r = await api('replies_api.php', {action:'create', post_id, content: document.getElementById('replyText').value, nsfw: document.getElementById('nsfw').checked?1:0});
  if (r.ok){ document.getElementById('replyText').value=''; load(); } else alert('失敗: '+r.error);
});
setInterval(load, 3000);
load();
</script>
</body></html>
