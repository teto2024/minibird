<?php
require_once __DIR__ . '/config.php'; // db(), user() を提供

$me = user();
$pdo = db();
$nowStr = (new DateTime())->format('Y-m-d H:i:s');


// --- Ajaxでのバフ取得用（必ず最初に処理） ---
if(isset($_GET['fetch_buffs'])){
    header('Content-Type: application/json');
    $nowStr = (new DateTime())->format('Y-m-d H:i:s');

    // 全バフ取得（グローバル）
    $st = $pdo->prepare("
        SELECT type, level, TIMESTAMPDIFF(SECOND,NOW(),end_time) AS remaining_sec, activated_by
        FROM buffs
        WHERE end_time>?
    ");
    $st->execute([$nowStr]);
    $allBuffs = $st->fetchAll(PDO::FETCH_ASSOC);

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

    // typeごとに「レベル最大 → 残り時間最大」を選択
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

    foreach($buffs as &$b){
        $b['remaining_sec'] = isset($b['remaining_sec']) ? (int)$b['remaining_sec'] : 0;
        $b['level'] = isset($b['level']) ? (int)$b['level'] : 1;
        $b['label'] = $LABELS[$b['type']] ?? $b['type'];
        $b['icon']  = $ICONS[$b['type']] ?? '';
        $b['bonus_percent'] = ($b['type']==='task'||$b['type']==='word') ? $b['level']*20 : 0;
        $b['activated_by'] = $b['activated_by'] ?? null;
    }

    echo json_encode(array_values($buffs), JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Ajaxでのバフ発動処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!$me) {
        echo json_encode(['ok' => false, 'error' => 'ログインが必要です']);
        exit;
    }

    $type = $_POST['type'] ?? '';
    $now = new DateTime();

    $COSTS = [
        'task' => ['coin'=>1000,'crystal'=>1],
        'word' => ['coin'=>1000,'crystal'=>1],
        'chat_festival' => ['coin'=>2000,'crystal'=>2],
        'festival_exempt' => ['coin'=>500,'crystal'=>1],
    ];

    if (!isset($COSTS[$type])) {
        echo json_encode(['ok'=>false,'error'=>'不正なバフ種類']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT coins, crystals FROM users WHERE id=? FOR UPDATE");
        $st->execute([$me['id']]);
        $user = $st->fetch();
        if (!$user) throw new Exception("ユーザーが存在しません");

        $costCoin = $COSTS[$type]['coin'];
        $costCrystal = $COSTS[$type]['crystal'];
        if ($user['coins']<$costCoin) throw new Exception("コインが不足しています");
        if ($user['crystals']<$costCrystal) throw new Exception("クリスタルが不足しています");

        $st = $pdo->prepare("UPDATE users SET coins=coins-?, crystals=crystals-? WHERE id=?");
        $st->execute([$costCoin, $costCrystal, $me['id']]);

        $start = $now->format('Y-m-d H:i:s');
        $end = $now->add(new DateInterval('PT20M'))->format('Y-m-d H:i:s');

        if ($type==='festival_exempt') {
            $st = $pdo->prepare("INSERT INTO user_buffs (user_id,type,start_time,end_time) VALUES (?,?,?,?)");
            $st->execute([$me['id'],'festival_exempt',$start,$end]);
        } else {
            $st = $pdo->prepare("SELECT id,level,end_time FROM buffs WHERE type=? AND end_time>NOW() ORDER BY end_time DESC LIMIT 1");
            $st->execute([$type]);
            $buff = $st->fetch();

            if ($type==='chat_festival') {
                $st = $pdo->prepare("INSERT INTO buffs (type,level,activated_by,start_time,end_time) VALUES (?,?,?,?,?)");
                $st->execute([$type,1,$me['id'],$start,$end]);
            } else {
                $level = $buff ? min(10,$buff['level']+1) : 1;
                $st = $pdo->prepare("INSERT INTO buffs (type,level,activated_by,start_time,end_time) VALUES (?,?,?,?,?)");
                $st->execute([$type,$level,$me['id'],$start,$end]);
            }
        }
        // --- 自動投稿（バフ発動通知） ---
$emoji_pool = ['🎊','✨','💪','🔥','🌟']; // ランダムに絵文字を複数入れる
shuffle($emoji_pool);
$emojis = implode('', array_slice($emoji_pool, 0, 3)); // 上位3個を使う

$label = $LABELS[$type] ?? ucfirst($type); // LABELSがなければ type をそのまま
$post_content_md = "{$me['handle']}さんが{$label}バフをアクティベートしました！ {$emojis}";

$post_content_html = htmlspecialchars($post_content_md);

// 発信元 user_id を5に固定
$st = $pdo->prepare("
    INSERT INTO posts (user_id, content_md, content_html)
    VALUES (?, ?, ?)
");
$st->execute([5, $post_content_md, $post_content_html]);

        $pdo->commit();
        echo json_encode(['ok'=>true,'message'=>'バフを発動しました']);
    } catch(Exception $e){
        $pdo->rollBack();
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

// --- ページ表示用 ---
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/style.css">
<title>バフ発動ページ（Ajax版）</title>
</head>
<body>
<h2>バフ発動ページ（Ajax版）</h2>
※説明
タスク報酬→集中タスク報酬20％アップ、20分間持続。重ねがけ可能。
英単語報酬→未実装
チャット祭り→チャットに愉快な絵文字が追加されるだけです。20分間有効。
個人免除バフ→未実装

<div class="buff-status-bar" id="buffBar"></div>

<div class="buff-buttons">
<button class="buff-btn" data-type="task">タスク報酬UP</button>
<button class="buff-btn" data-type="word">英単語報酬UP</button>
<button class="buff-btn" data-type="chat_festival">チャット祭り</button>
<button class="buff-btn" data-type="festival_exempt">個人免除バフ</button>
</div>

<script>
// --- バフバー更新 ---
function updateBuffBar(buffs){
    const bar = document.getElementById('buffBar');
    bar.innerHTML = '';
    buffs.forEach(b=>{
        const remaining = Math.max(0, b.remaining_sec);
        const div = document.createElement('div');
        div.className = 'buff-icon' + (b.activated_by?' personal':'');
        div.dataset.remaining = remaining;
        div.innerHTML = `
            <span>${b.icon}</span>
            ${b.bonus_percent !== 0 ? `<span class="buff-percent">${b.bonus_percent}%</span>` : ''}
            <span class="buff-timer"></span>
        `;
        bar.appendChild(div);
        startTimer(div);
    });
}

// --- タイマー処理 ---
function startTimer(el){
    let remaining=parseInt(el.dataset.remaining);
    const timerEl=el.querySelector('.buff-timer');
    function update(){
        if(remaining<=0){ timerEl.textContent="終了"; return; }
        const m=Math.floor(remaining/60), s=remaining%60;
        timerEl.textContent=m+":"+("0"+s).slice(-2);
        remaining--; setTimeout(update,1000);
    }
    update();
}

// --- 初期表示 ---
async function initBuffBar(){
    const res = await fetch('?fetch_buffs=1');
    const data = await res.json();
    updateBuffBar(data);
}

// --- ボタン処理（確認アラート + 値段表示） ---
const COSTS = {
    'task': {coin:1000, crystal:1},
    'word': {coin:1000, crystal:1},
    'chat_festival': {coin:2000, crystal:2},
    'festival_exempt': {coin:500, crystal:1},
};

document.querySelectorAll('.buff-btn').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
        const type = btn.dataset.type;
        const cost = COSTS[type];
        if(!confirm(`${btn.textContent}バフを発動しますか？\n必要コイン: ${cost.coin} / クリスタル: ${cost.crystal}`)){
            return; // キャンセルしたら中止
        }

        const formData = new FormData();
        formData.append('type', type);
        const res = await fetch('',{method:'POST', body:formData});
        const data = await res.json();
        if(data.ok){
            alert(data.message);
            initBuffBar();
        } else {
            alert('エラー: '+data.error);
        }
    });
});

// --- Ajaxポーリング ---
setInterval(initBuffBar, 5000);
initBuffBar();
</script>

</body>
</html>
