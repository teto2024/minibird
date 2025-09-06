<?php
require_once __DIR__ . '/config.php';
$pdo = db();
$me = user();

function linkify_handles($content) {
    return preg_replace(
        '/@([a-zA-Z0-9_]+)/',
        '<a href="/profile.php?handle=$1">@$1</a>',
        htmlspecialchars($content)
    );
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <button class="menu-toggle">☰</button>
  <div class="logo">🐦 MiniBird</div>
  <?php if ($me): ?>
<div class="buff-status-bar" id="buffBar">
<?php
$nowStr = (new DateTime())->format('Y-m-d H:i:s');

// --- 全バフ取得（グローバル・個人まとめて） ---
$st = $pdo->prepare("
    SELECT type, level, TIMESTAMPDIFF(SECOND,NOW(),end_time) AS remaining_sec, activated_by
    FROM buffs
    WHERE end_time>?
       OR (activated_by=? AND end_time>?)
");
$st->execute([$nowStr, $me['id'], $nowStr]);
$allBuffs = $st->fetchAll(PDO::FETCH_ASSOC);

// --- 表示用ラベル・アイコン ---
$LABELS = [
    'task'=>'タスク増額',
    'word'=>'英単語報酬UP',
    'chat_festival'=>'チャット祭り',
    'festival_exempt'=>'個人免除バフ'
];
$ICONS = [
    'task'=>'✏',
    'word'=>'🔤',
    'chat_festival'=>'🎊',
    'festival_exempt'=>'⚡'
];

// --- typeごとに「レベル最大 → 残り時間最大」を選択 ---
$buffs = [];
foreach($allBuffs as $b){
    $type = $b['type'];
    $level = (int)$b['level'];
    $remaining = (int)$b['remaining_sec'];

    if(!isset($buffs[$type]) || 
       $level > $buffs[$type]['level'] ||
       ($level === $buffs[$type]['level'] && $remaining > $buffs[$type]['remaining_sec'])) {
        $buffs[$type] = $b;
    }
}

// --- 表示 ---
foreach($buffs as $b){
    $b['remaining_sec'] = (int)($b['remaining_sec'] ?? 0);
    $b['level'] = (int)($b['level'] ?? 1);
    $b['label'] = $LABELS[$b['type']] ?? $b['type'];
    $b['icon'] = $ICONS[$b['type']] ?? '';
    $bonus_percent = ($b['type']==='task'||$b['type']==='word') ? $b['level']*20 : 0;
    $class = 'buff-icon' . ($b['activated_by']?' personal':'');

    echo '<div class="'.$class.'" data-remaining="'.$b['remaining_sec'].'" title="'.htmlspecialchars($b['label']).'">';
    echo '<span class="buff-emoji">'.htmlspecialchars($b['icon']).'</span>';
    if($bonus_percent) echo '<span class="buff-percent">'.$bonus_percent.'%</span>';
    echo '<span class="buff-timer"></span>';
    echo '</div>';
}
?>
</div>

<script>
// JSで残り時間をカウントダウン表示（mm:ss形式）
document.querySelectorAll('.buff-icon').forEach(function(el){
    let remaining = parseInt(el.dataset.remaining);
    const timerEl = el.querySelector('.buff-timer');
    function update(){
        if(remaining<=0){ timerEl.textContent="00:00"; return; }
        let m = Math.floor(remaining / 60);
        let s = remaining % 60;
        timerEl.textContent = ("0"+m).slice(-2) + ":" + ("0"+s).slice(-2);
        remaining--;
        setTimeout(update,1000);
    }
    update();
});
</script>

<?php endif; ?>


  <form method="get" action="/search.php">
  <input name="q" placeholder="検索..." value="<?=htmlspecialchars($q)?>">
  <button>検索</button>
</form>
  <div class="user">
    <?php if ($me): ?>
      <span>@<?=htmlspecialchars($me['handle'])?></span>
      <button id="notificationBtn">
  🔔 <span id="notification-badge" class="badge"></span>
</button>

<!-- ポップアップ -->
<div id="notificationPopup" class="hidden">
  <ul id="notificationList"></ul>
  <a href="/notifications.php">すべてを見る</a>
</div>

      <button id="logoutBtn">ログアウト</button>
    <?php else: ?>
      <button id="showAuth">ログイン / 登録</button>
    <?php endif; ?>
  </div>
</header>

<main class="layout">
  <aside class="left">
  <button class="close-menu" aria-label="メニューを閉じる">✕</button>
    <nav>
  <button class="tabBtn" data-tab="recommended">おすすめ</button>
  <button class="tabBtn" data-tab="global" aria-selected="true">全体</button>
  <?php if ($me): ?>
    <button class="tabBtn" data-tab="following">フォロー中</button>
    <button class="tabBtn" data-tab="bookmarks">ブックマーク</button>
    <button class="tabBtn" data-tab="communities">コミュニティ</button>
    <!-- 通知タブを a タグに変更 -->
    <a href="notifications.php" class="tabBtn block">通知</a>
    <a href="focus.php" class="tabBtn block">集中タスク</a>
    <a href="focus_history.php" class="tabBtn block">集中タスク履歴</a>
    <a href="quiz.php" class="tabBtn block">英単語マスター</a>
    <a href="ranking.php" class="tabBtn block">集中ランキング</a>
    <a href="shop.php" class="tabBtn block">フレーム・ショップ</a>
    <a href="activate_buff.php" class="tabBtn block">バフショップ</a>
    <a href="admin.php" class="tabBtn block">管理者用</a>
  <?php endif; ?>
</nav>


  </aside>

  <section class="center">
    <div id="composer">
      <?php if ($me): ?>
        <textarea id="postText" maxlength="1024" placeholder="いまどうしてる？（Markdown可、1024文字まで）"></textarea>
        <div class="row">
          <label><input type="checkbox" id="nsfw"> NSFW</label>
          <input type="file" id="media" accept="image/*,video/*">
          <button id="submitPost">ポスト</button>
        </div>
      <?php else: ?>
        <div class="notice">ポストするにはログインしてください。</div>
      <?php endif; ?>
    </div>

    <div class="feed-tabs">
      <button class="feedTab" data-feed="recommended">おすすめ</button>
      <button class="feedTab active" data-feed="global">全体</button>
      <?php if ($me): ?><button class="feedTab" data-feed="following">フォロー中</button><?php endif; ?>
    </div>

    <div id="feed" data-feed="global"></div>
    <div id="loading">読み込み中...</div>
  </section>
  <aside class="right">
    <div class="card">
      <h3>プロフィール</h3>
      <?php if ($me): ?>
      <h2>@<?=$me['handle']?></h2>

      <button id="follow-btn" data-user-id="<?=$me['id']?>">フォローする</button>
      <div>ユーザーハッシュ: <button id="revealHash">表示</button> <code id="userHash" class="hidden"></code></div>
      <div>コイン: <span id="coins"><?=$me['coins']?></span> / クリスタル: <span id="crystals"><?=$me['crystals']?></span></div>
      <div class="row"><input id="newPass" type="password" placeholder="新パスワード"><button id="changePass">変更</button></div>
      <div class="row"><input id="inviteHandle" placeholder="招待者のハンドル（登録時のみ有効）" disabled></div>
      <?php else: ?>
      <p>ログインすると詳細が表示されます。</p>
      <?php endif; ?>
    </div>
    <div class="card">
      <h3>トレンド</h3>
      <div id="trends"></div>
    </div>
</aside>

</main>

<!-- Auth Modal -->
<div id="authModal" class="modal hidden">
  <div class="modal-content">
    <h3>ログイン / 登録</h3>
    <input id="handle" placeholder="ハンドル（英数字と_ 3-16）" maxlength="16">
    <input id="password" type="password" placeholder="パスワード">
    <input id="invited_by" placeholder="招待者のハンドル（任意）">
    <div class="row">
      <button id="loginBtn">ログイン</button>
      <button id="registerBtn">新規登録</button>
      <button id="closeAuth">閉じる</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="assets/app.js"></script>
</body>
</html>
