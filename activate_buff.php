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
        'task'=>'タスク報酬UP',
        'chat_festival'=>'チャット祭',
        'word_master_reward'=>'英単語報酬UP'
    ];
    $ICONS = [
        'task'=>'✏',
        'chat_festival'=>'🎊',
        'word_master_reward'=>'📚'
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
        $b['bonus_percent'] = ($b['type']==='task'||$b['type']==='word'||$b['type']==='word_master_reward') ? $b['level']*20 : 0;
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
        'task' => ['coin'=>500,'crystal'=>1],
        'chat_festival' => ['coin'=>1000,'crystal'=>2],
        'word_master_reward' => ['coin'=>800,'crystal'=>2],
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

        // 既存のバフレベルを確認
        $st = $pdo->prepare("SELECT id,level,end_time FROM buffs WHERE type=? AND end_time>NOW() ORDER BY end_time DESC LIMIT 1");
        $st->execute([$type]);
        $buff = $st->fetch();

        if ($type==='chat_festival') {
            // チャット祭は重ねがけなし
            $st = $pdo->prepare("INSERT INTO buffs (type,level,activated_by,start_time,end_time) VALUES (?,?,?,?,?)");
            $st->execute([$type,1,$me['id'],$start,$end]);
        } elseif ($type==='word_master_reward') {
            // 英単語マスター報酬UPは個人バフ（user_buffs）、最大Lv10まで重ねがけ可能
            $st = $pdo->prepare("SELECT id,level,end_time FROM user_buffs WHERE user_id=? AND type=? AND end_time>NOW() ORDER BY end_time DESC LIMIT 1");
            $st->execute([$me['id'], $type]);
            $userBuff = $st->fetch();
            
            $level = $userBuff ? min(10, $userBuff['level'] + 1) : 1;
            $st = $pdo->prepare("INSERT INTO user_buffs (user_id,type,level,start_time,end_time) VALUES (?,?,?,?,?)");
            $st->execute([$me['id'],$type,$level,$start,$end]);
        } else {
            // タスク報酬UPは重ねがけ可能（最大Lv10）
            $level = $buff ? min(10,$buff['level']+1) : 1;
            $st = $pdo->prepare("INSERT INTO buffs (type,level,activated_by,start_time,end_time) VALUES (?,?,?,?,?)");
            $st->execute([$type,$level,$me['id'],$start,$end]);
        }
        // --- 自動投稿（バフ発動通知） ---
$emoji_pool = ['🎊','✨','💪','🔥','🌟']; // ランダムに絵文字を複数入れる
shuffle($emoji_pool);
$emojis = implode('', array_slice($emoji_pool, 0, 3)); // 上位3個を使う

$label = $LABELS[$type] ?? ucfirst($type); // LABELSがなければ type をそのまま
// 表示名を使用（未設定の場合はハンドルをフォールバック）
$display_name = !empty($me['display_name']) ? $me['display_name'] : $me['handle'];
$post_content_md = "{$display_name}さんが{$label}バフをアクティベートしました！ {$emojis}";

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
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<title>バフショップ - MiniBird</title>
<style>
.buff-shop-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.buff-shop-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
}

.buff-shop-header h1 {
    margin: 0 0 10px 0;
    font-size: 28px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.buff-shop-header p {
    margin: 0;
    opacity: 0.9;
}

.buff-card {
    background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%);
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    border: 1px solid rgba(255,255,255,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.buff-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.4);
}

.buff-card-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.buff-card-icon {
    font-size: 48px;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
}

.buff-card-title {
    font-size: 22px;
    font-weight: bold;
    color: #fff;
    margin: 0;
}

.buff-card-description {
    color: #a0a0c0;
    line-height: 1.8;
    margin-bottom: 20px;
    padding: 15px;
    background: rgba(0,0,0,0.2);
    border-radius: 10px;
    font-size: 14px;
}

.buff-card-description ul {
    margin: 10px 0 0 0;
    padding-left: 20px;
}

.buff-card-description li {
    margin: 8px 0;
}

.buff-card-price {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    padding: 15px;
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
}

.price-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 18px;
    font-weight: bold;
    color: #ffd700;
}

.buff-card-button {
    width: 100%;
    padding: 15px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.buff-card-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
}

.buff-card-button:active {
    transform: translateY(0);
}

.buff-card.task-buff {
    border-left: 4px solid #00ff88;
}

.buff-card.chat-buff {
    border-left: 4px solid #ff6b6b;
}

.active-buffs-section {
    margin-top: 40px;
}

.active-buffs-title {
    font-size: 20px;
    color: #fff;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.buff-status-bar-enhanced {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.buff-status-item {
    background: linear-gradient(135deg, #2d2d44 0%, #3d3d5c 100%);
    border-radius: 12px;
    padding: 15px;
    text-align: center;
    border: 1px solid rgba(255,255,255,0.1);
}

.buff-status-item .icon {
    font-size: 32px;
    margin-bottom: 8px;
}

.buff-status-item .label {
    font-size: 14px;
    color: #a0a0c0;
    margin-bottom: 5px;
}

.buff-status-item .timer {
    font-size: 20px;
    font-weight: bold;
    color: #00ff88;
    font-family: monospace;
}

.buff-status-item .bonus {
    font-size: 16px;
    color: #ffd700;
    font-weight: bold;
    margin-top: 5px;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 20px;
    background: rgba(255,255,255,0.1);
    color: #667eea;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}

.back-link:hover {
    background: #667eea;
    color: white;
}
</style>
</head>
<body>
<div class="buff-shop-container">
    <a href="./" class="back-link">← フィードに戻る</a>

    <div class="buff-shop-header">
        <h1>⚡ バフショップ</h1>
        <p>バフを発動してゲームプレイを強化しよう！</p>
    </div>

    <!-- タスク報酬UPバフ -->
    <div class="buff-card task-buff">
        <div class="buff-card-header">
            <span class="buff-card-icon">✏️</span>
            <h2 class="buff-card-title">タスク報酬UP</h2>
        </div>
        <div class="buff-card-description">
            <strong>集中タスクの報酬が20%アップします！</strong>
            <ul>
                <li>📈 集中タイマー完了時のコイン・クリスタル報酬が20%増加</li>
                <li>⏱️ 効果時間：20分間</li>
                <li>🔄 重ねがけ可能（最大Lv10で200%UP）</li>
                <li>💡 集中タイマーを始める前に発動するのがオススメ！</li>
            </ul>
        </div>
        <div class="buff-card-price">
            <div class="price-item">
                <span>🪙</span>
                <span>500 コイン</span>
            </div>
            <div class="price-item">
                <span>💎</span>
                <span>1 クリスタル</span>
            </div>
        </div>
        <button class="buff-card-button buff-btn" data-type="task">
            🚀 タスク報酬UPを発動する
        </button>
    </div>

    <!-- チャット祭バフ -->
    <div class="buff-card chat-buff">
        <div class="buff-card-header">
            <span class="buff-card-icon">🎊</span>
            <h2 class="buff-card-title">チャット祭</h2>
        </div>
        <div class="buff-card-description">
            <strong>みんなでチャットを盛り上げよう！</strong>
            <ul>
                <li>🎉 チャットに愉快な絵文字エフェクトが追加されます</li>
                <li>⏱️ 効果時間：20分間</li>
                <li>👥 発動すると全ユーザーに通知が送られます</li>
                <li>🌟 パーティーを開催して交流を深めましょう！</li>
            </ul>
        </div>
        <div class="buff-card-price">
            <div class="price-item">
                <span>🪙</span>
                <span>1000 コイン</span>
            </div>
            <div class="price-item">
                <span>💎</span>
                <span>2 クリスタル</span>
            </div>
        </div>
        <button class="buff-card-button buff-btn" data-type="chat_festival">
            🎉 チャット祭を発動する
        </button>
    </div>

    <!-- 英単語マスター報酬UPバフ -->
    <div class="buff-card" style="border-left: 4px solid #ffd700;">
        <div class="buff-card-header">
            <span class="buff-card-icon">📚</span>
            <h2 class="buff-card-title">英単語マスター報酬UP</h2>
        </div>
        <div class="buff-card-description">
            <strong>英単語マスターの報酬が20%アップします！</strong>
            <ul>
                <li>📈 英単語マスター完了時のコイン・クリスタル報酬が20%増加</li>
                <li>⏱️ 効果時間：20分間</li>
                <li>🔄 重ねがけ可能（最大Lv10で200%UP）</li>
                <li>💡 英単語マスターを始める前に発動するのがオススメ！</li>
            </ul>
        </div>
        <div class="buff-card-price">
            <div class="price-item">
                <span>🪙</span>
                <span>800 コイン</span>
            </div>
            <div class="price-item">
                <span>💎</span>
                <span>2 クリスタル</span>
            </div>
        </div>
        <button class="buff-card-button buff-btn" data-type="word_master_reward">
            📚 英単語報酬UPを発動する
        </button>
    </div>

    <!-- アクティブなバフ表示 -->
    <div class="active-buffs-section">
        <h3 class="active-buffs-title">
            <span>⚡</span>
            <span>現在アクティブなバフ</span>
        </h3>
        <div class="buff-status-bar-enhanced" id="buffBar">
            <!-- バフがここに動的に追加されます -->
        </div>
    </div>
</div>

<script>
// --- バフバー更新 ---
function updateBuffBar(buffs){
    const bar = document.getElementById('buffBar');
    if(buffs.length === 0){
        bar.innerHTML = '<div style="color: #666; text-align: center; grid-column: 1/-1; padding: 20px;">現在アクティブなバフはありません</div>';
        return;
    }
    bar.innerHTML = '';
    buffs.forEach(b=>{
        const remaining = Math.max(0, b.remaining_sec);
        const div = document.createElement('div');
        div.className = 'buff-status-item' + (b.activated_by?' personal':'');
        div.dataset.remaining = remaining;
        div.innerHTML = `
            <div class="icon">${b.icon}</div>
            <div class="label">${b.label}</div>
            ${b.bonus_percent !== 0 ? `<div class="bonus">+${b.bonus_percent}%</div>` : ''}
            <div class="timer"></div>
        `;
        bar.appendChild(div);
        startTimer(div);
    });
}

// --- タイマー処理 ---
function startTimer(el){
    let remaining=parseInt(el.dataset.remaining);
    const timerEl=el.querySelector('.timer');
    function update(){
        if(remaining<=0){ timerEl.textContent="終了"; return; }
        const m=Math.floor(remaining/60), s=remaining%60;
        timerEl.textContent=("0"+m).slice(-2)+":"+("0"+s).slice(-2);
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
    'task': {coin:500, crystal:1},
    'chat_festival': {coin:1000, crystal:2},
    'word_master_reward': {coin:800, crystal:2},
};

const LABELS = {
    'task': 'タスク報酬UP',
    'chat_festival': 'チャット祭',
    'word_master_reward': '英単語マスター報酬UP',
};

document.querySelectorAll('.buff-btn').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
        const type = btn.dataset.type;
        const cost = COSTS[type];
        const label = LABELS[type];
        if(!confirm(`${label}バフを発動しますか？\n\n必要コイン: ${cost.coin}\n必要クリスタル: ${cost.crystal}`)){
            return; // キャンセルしたら中止
        }

        btn.disabled = true;
        btn.textContent = '発動中...';

        const formData = new FormData();
        formData.append('type', type);
        try {
            const res = await fetch('',{method:'POST', body:formData});
            const data = await res.json();
            if(data.ok){
                alert('✅ ' + data.message);
                initBuffBar();
            } else {
                alert('❌ エラー: '+data.error);
            }
        } catch(e) {
            alert('❌ 通信エラーが発生しました');
        }

        btn.disabled = false;
        const buttonTexts = {
            'task': '🚀 タスク報酬UPを発動する',
            'chat_festival': '🎉 チャット祭を発動する',
            'word_master_reward': '📚 英単語報酬UPを発動する'
        };
        btn.textContent = buttonTexts[type] || 'バフを発動する';
    });
});

// --- Ajaxポーリング ---
setInterval(initBuffBar, 5000);
initBuffBar();
</script>

</body>
</html>
