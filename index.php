<?php
require_once __DIR__ . '/config.php';
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
  <div class="logo">🐦 MiniBird</div>
  <div class="search"><input id="q" placeholder="検索 (ハッシュ/ユーザー)"></div>
  <div class="user">
    <?php if ($me): ?>
      <span>@<?=htmlspecialchars($me['handle'])?></span>
      <button id="logoutBtn">ログアウト</button>
    <?php else: ?>
      <button id="showAuth">ログイン / 登録</button>
    <?php endif; ?>
  </div>
</header>

<main class="layout">
  <aside class="left">
    <nav>
      <button class="tabBtn" data-tab="recommended">おすすめ</button>
      <button class="tabBtn" data-tab="global" aria-selected="true">全体</button>
      <?php if ($me): ?>
        <button class="tabBtn" data-tab="following">フォロー中</button>
        <button class="tabBtn" data-tab="bookmarks">ブックマーク</button>
        <button class="tabBtn" data-tab="communities">コミュニティ</button>
        <a href="focus.php" class="tabBtn block">集中</a>
        <a href="shop.php" class="tabBtn block">フレーム・ショップ</a>
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
      <h2>@example_user</h2>
  　　<button id="follow-btn" data-user-id="123">フォローする</button>
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
