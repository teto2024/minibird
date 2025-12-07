<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/darkroom_engine.php';

// ====== 設定 ======
$TICK_INTERVAL = 1; // 秒
$WOOD_PER_GATHER = 4;
$FOOD_PER_HUNT = 2;
$COIN_REWARD_RATE = 1; // 1アクションごとに1コイン
$CRYSTAL_REWARD_MILESTONE = 100; // 100アクション毎に1クリスタル
// ==================

$me = user();
$uid = $me['id'] ?? $_SESSION['uid'] ?? null;

if (!$uid) {
    header('Location: ./');
    exit;
}

$pdo = db();

// ====== ゲームエンジン初期化 ======
$engine = new DarkroomEngine($pdo, $uid);

// ====== ゲームステート初期化 ======
if (!isset($_SESSION['darkroom_state'])) {
    $_SESSION['darkroom_state'] = [
        'fire_level' => 0,
        'fire_stoked' => 0,
        'wood' => 0,
        'food' => 0,
        'traps' => 0,
        'huts' => 0,
        'population' => 0,
        'builders' => 0,
        'gatherers' => 0,
        'hunters' => 0,
        'total_actions' => 0,
        'unlocked_gather' => false,
        'unlocked_trap' => false,
        'unlocked_hut' => false,
        'story_stage' => 0,
        'last_tick' => time()
    ];
}

$state = &$_SESSION['darkroom_state'];

// ====== リセット処理 ======
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    unset($_SESSION['darkroom_state']);
    header('Location: darkroom.php');
    exit;
}

// ====== AJAX処理 ======
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=UTF-8');
    
    $ajaxAction = $_GET['ajax'];
    
    switch ($ajaxAction) {
        case '1': // ティック処理
            $now = time();
            $elapsed = $now - $state['last_tick'];
            if ($elapsed > 0) {
                // 罠から食料を自動収集
                if ($state['traps'] > 0) {
                    $state['food'] += $state['traps'] * $elapsed * 0.1;
                }
                // 火が消えないようにする（木材を消費）
                if ($state['fire_level'] > 0) {
                    $state['fire_stoked'] -= $elapsed;
                    if ($state['fire_stoked'] <= 0) {
                        $state['fire_level'] = max(0, $state['fire_level'] - 1);
                        $state['fire_stoked'] = 0;
                    }
                }
                $state['last_tick'] = $now;
            }
            
            echo json_encode([
                'ok' => true,
                'state' => $state
            ]);
            break;
            
        case 'player_stats':
            echo json_encode($engine->getPlayerStats());
            break;
            
        case 'inventory':
            echo json_encode($engine->getInventory());
            break;
            
        case 'recipes':
            echo json_encode($engine->getAvailableRecipes());
            break;
            
        case 'quests':
            echo json_encode($engine->getQuests());
            break;
            
        case 'enemies':
            echo json_encode($engine->getAvailableEnemies());
            break;
            
        default:
            echo json_encode(['error' => 'Unknown AJAX action']);
    }
    exit;
}

// ====== POST(アクション) ハンドラ ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    
    $action = $_POST['action'] ?? '';
    $msg = '';
    $reward_coin = 0;
    $reward_crystal = 0;
    
    switch ($action) {
        // ====== 新機能: レベリング ======
        case 'allocate_stat':
            $stat = $_POST['stat'] ?? '';
            $result = $engine->allocateStatPoint($stat);
            if ($result['success']) {
                $msg = "{$stat} を強化しました！ (+{$result['increment']})";
            } else {
                $msg = $result['error'];
            }
            break;
            
        // ====== 新機能: アイテムクラフト ======
        case 'craft_item':
            $recipeKey = $_POST['recipe_key'] ?? '';
            $result = $engine->craftItem($recipeKey);
            if ($result['success']) {
                $msg = "{$result['item_name']} x{$result['quantity']} を作成しました！";
                if ($result['leveled_up']) {
                    $msg .= " レベルアップ！ Lv.{$result['new_level']}";
                }
                // クエスト進捗更新
                $engine->updateQuestProgress('craft', $recipeKey, 1);
            } else {
                $msg = $result['error'];
            }
            break;
            
        // ====== 新機能: クエスト ======
        case 'start_quest':
            $questKey = $_POST['quest_key'] ?? '';
            $result = $engine->startQuest($questKey);
            if ($result['success']) {
                $msg = "クエスト「{$result['quest_title']}」を開始しました！";
            } else {
                $msg = $result['error'];
            }
            break;
            
        case 'complete_quest':
            $questKey = $_POST['quest_key'] ?? '';
            $result = $engine->completeQuest($questKey);
            if ($result['success']) {
                $msg = "クエスト「{$result['quest_title']}」を完了しました！ " . implode(', ', $result['rewards']);
                if ($result['leveled_up']) {
                    $msg .= " レベルアップ！ Lv.{$result['new_level']}";
                }
            } else {
                $msg = $result['error'];
            }
            break;
            
        // ====== 新機能: 戦闘 ======
        case 'battle':
            $enemyKey = $_POST['enemy_key'] ?? '';
            $result = $engine->battle($enemyKey);
            if ($result['success']) {
                if ($result['result'] === 'victory') {
                    $msg = "勝利！ 経験値 +{$result['experience_gained']}";
                    if (count($result['loot']) > 0) {
                        $lootText = [];
                        foreach ($result['loot'] as $item) {
                            $lootText[] = "{$item['item_key']} x{$item['quantity']}";
                        }
                        $msg .= " | 獲得: " . implode(', ', $lootText);
                    }
                    if ($result['leveled_up']) {
                        $msg .= " | レベルアップ！ Lv.{$result['new_level']}";
                    }
                } elseif ($result['result'] === 'defeat') {
                    $msg = "敗北... HP: {$result['player_health']}";
                } else {
                    $msg = "逃走しました";
                }
            } else {
                $msg = $result['error'];
            }
            break;
            
        // ====== 既存機能 ======
        case 'light_fire':
            if ($state['fire_level'] === 0) {
                $state['fire_level'] = 1;
                $state['fire_stoked'] = 60;
                $state['unlocked_gather'] = true;
                $state['story_stage'] = 1;
                $msg = '火を灯した。暖かさが部屋に広がる。';
            } else {
                $msg = 'すでに火は灯っている。';
            }
            break;
            
        case 'stoke_fire':
            if ($state['wood'] >= 1) {
                $state['wood'] -= 1;
                $state['fire_level'] = min(5, $state['fire_level'] + 1);
                $state['fire_stoked'] += 30;
                $msg = '火をくべた。炎が明るく燃える。';
                if ($state['fire_level'] >= 3 && !$state['unlocked_trap']) {
                    $state['unlocked_trap'] = true;
                    $state['story_stage'] = 2;
                    $msg .= ' 罠の作り方を思い出した。';
                }
            } else {
                $msg = '木材が足りない。';
            }
            break;
            
        case 'gather_wood':
            if ($state['unlocked_gather']) {
                $state['wood'] += $WOOD_PER_GATHER;
                $state['total_actions']++;
                $msg = "木材を " . $WOOD_PER_GATHER . " 集めた。";
                $reward_coin = $COIN_REWARD_RATE;
                
                // アイテムシステムにも追加（拡張機能）
                $engine->addItem('wood', $WOOD_PER_GATHER);
                
                // クエスト進捗更新
                $engine->updateQuestProgress('gather', 'wood', $WOOD_PER_GATHER);
                
                // 経験値獲得
                $expResult = $engine->addExperience(2);
                if ($expResult['leveled_up']) {
                    $msg .= " | レベルアップ！ Lv.{$expResult['new_level']}";
                }
            }
            break;
            
        case 'build_trap':
            if ($state['unlocked_trap'] && $state['wood'] >= 10) {
                $state['wood'] -= 10;
                $state['traps']++;
                $state['total_actions']++;
                $msg = '罠を設置した。食料が自動で集まるようになる。';
                $reward_coin = $COIN_REWARD_RATE * 5;
                if ($state['traps'] >= 3 && !$state['unlocked_hut']) {
                    $state['unlocked_hut'] = true;
                    $state['story_stage'] = 3;
                    $msg .= ' 小屋を建てられるようになった。';
                }
            } else {
                $msg = '木材が10個必要。';
            }
            break;
            
        case 'build_hut':
            if ($state['unlocked_hut'] && $state['wood'] >= 50) {
                $state['wood'] -= 50;
                $state['huts']++;
                $state['population']++;
                $state['total_actions']++;
                $msg = '小屋を建てた。住民が1人増えた。';
                $reward_coin = $COIN_REWARD_RATE * 10;
            } else {
                $msg = '木材が50個必要。';
            }
            break;
            
        case 'assign_gatherer':
            if ($state['population'] > ($state['gatherers'] + $state['hunters'] + $state['builders'])) {
                $state['gatherers']++;
                $msg = '住民を採集者に任命した。';
            } else {
                $msg = '割り当て可能な住民がいない。';
            }
            break;
            
        case 'assign_hunter':
            if ($state['population'] > ($state['gatherers'] + $state['hunters'] + $state['builders'])) {
                $state['hunters']++;
                $msg = '住民を狩人に任命した。';
            } else {
                $msg = '割り当て可能な住民がいない。';
            }
            break;
            
        case 'assign_builder':
            if ($state['population'] > ($state['gatherers'] + $state['hunters'] + $state['builders'])) {
                $state['builders']++;
                $msg = '住民を建築者に任命した。';
            } else {
                $msg = '割り当て可能な住民がいない。';
            }
            break;
    }
    
    // 報酬計算
    if ($state['total_actions'] > 0 && $state['total_actions'] % $CRYSTAL_REWARD_MILESTONE === 0) {
        $reward_crystal = 1;
    }
    
    // DB更新
    if ($reward_coin > 0 || $reward_crystal > 0) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE users SET coins = coins + ?, crystals = crystals + ? WHERE id = ?");
            $stmt->execute([$reward_coin, $reward_crystal, $uid]);
            
            $stmt2 = $pdo->prepare(
                "INSERT INTO reward_events(user_id, kind, amount, meta) VALUES (?, 'darkroom_action', ?, JSON_OBJECT('action', ?))"
            );
            $stmt2->execute([$uid, $reward_coin, $action]);
            
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Darkroom reward error: " . $e->getMessage());
        }
    }
    
    // ゲームステートの保存（DBに永続化）
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO darkroom_saves(user_id, game_state, updated_at) 
             VALUES (?, ?, NOW()) 
             ON DUPLICATE KEY UPDATE game_state = ?, updated_at = NOW()"
        );
        $stmt->execute([$uid, json_encode($state), json_encode($state)]);
    } catch (PDOException $e) {
        error_log("Darkroom save error: " . $e->getMessage());
    }
    
    echo json_encode([
        'ok' => true,
        'msg' => $msg,
        'reward_coin' => $reward_coin,
        'reward_crystal' => $reward_crystal,
        'state' => $state
    ]);
    exit;
}

// ====== ゲームステートの読み込み（初回アクセス時） ======
try {
    $stmt = $pdo->prepare("SELECT game_state FROM darkroom_saves WHERE user_id = ?");
    $stmt->execute([$uid]);
    $saved = $stmt->fetch();
    if ($saved && !empty($saved['game_state'])) {
        $loaded = json_decode($saved['game_state'], true);
        if ($loaded) {
            $_SESSION['darkroom_state'] = $loaded;
            $state = &$_SESSION['darkroom_state'];
        }
    }
} catch (PDOException $e) {
    error_log("Darkroom load error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>暗い部屋 - MiniBird</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Courier New', monospace;
    background: #000;
    color: #aaa;
    padding: 20px;
    line-height: 1.6;
}
.container {
    max-width: 800px;
    margin: 0 auto;
    background: #111;
    border: 2px solid #333;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 0 30px rgba(255,255,255,.05);
}
h1 {
    color: #fff;
    text-align: center;
    margin-bottom: 20px;
    text-shadow: 0 0 10px #fff;
}
.header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #333;
}
.back-link {
    color: #888;
    text-decoration: none;
    padding: 5px 10px;
    border: 1px solid #444;
    border-radius: 4px;
    transition: all 0.3s;
}
.back-link:hover {
    color: #fff;
    border-color: #fff;
}
.reset-link {
    color: #c44;
    text-decoration: none;
    padding: 5px 10px;
    border: 1px solid #c44;
    border-radius: 4px;
    transition: all 0.3s;
}
.reset-link:hover {
    color: #fff;
    background: #c44;
}
#story {
    background: #0a0a0a;
    padding: 15px;
    margin-bottom: 20px;
    border-left: 3px solid #666;
    min-height: 60px;
    font-style: italic;
}
#resources {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}
.resource {
    background: #1a1a1a;
    padding: 10px;
    border-radius: 4px;
    text-align: center;
    border: 1px solid #333;
}
.resource-label {
    font-size: 12px;
    color: #888;
    text-transform: uppercase;
}
.resource-value {
    font-size: 20px;
    color: #fff;
    font-weight: bold;
}
.fire-indicator {
    margin-bottom: 20px;
    text-align: center;
}
.fire-level {
    display: inline-block;
    font-size: 32px;
    margin: 10px 0;
}
.actions {
    margin-bottom: 20px;
}
.action-section {
    margin-bottom: 20px;
    padding: 15px;
    background: #0a0a0a;
    border-radius: 6px;
    border: 1px solid #222;
}
.action-section h3 {
    color: #999;
    margin-bottom: 10px;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.btn {
    display: inline-block;
    margin: 5px 5px 5px 0;
    padding: 10px 20px;
    background: #222;
    color: #aaa;
    border: 1px solid #444;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
    font-family: 'Courier New', monospace;
    font-size: 14px;
}
.btn:hover:not(:disabled) {
    background: #333;
    color: #fff;
    border-color: #666;
    transform: translateY(-1px);
}
.btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}
.btn.primary {
    background: #1a4d2e;
    border-color: #2d7a4f;
    color: #9dd;
}
.btn.primary:hover:not(:disabled) {
    background: #2d7a4f;
    color: #fff;
    border-color: #4a9;
}
#message {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #1a1a1a;
    color: #fff;
    padding: 15px 20px;
    border-radius: 6px;
    border: 1px solid #444;
    box-shadow: 0 5px 20px rgba(0,0,0,.5);
    opacity: 0;
    transition: opacity 0.3s;
    max-width: 300px;
    z-index: 1000;
}
#message.show {
    opacity: 1;
}
.village {
    margin-top: 20px;
    padding: 15px;
    background: #0a0a0a;
    border-radius: 6px;
    border: 1px solid #222;
}
.village h3 {
    color: #999;
    margin-bottom: 10px;
}
.villager-assignment {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}
.assignment-item {
    background: #1a1a1a;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #333;
}
.hidden {
    display: none;
}
.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid #333;
}
.tab {
    padding: 10px 20px;
    background: #1a1a1a;
    border: 1px solid #333;
    border-bottom: none;
    border-radius: 6px 6px 0 0;
    cursor: pointer;
    color: #888;
    transition: all 0.3s;
}
.tab:hover {
    background: #222;
    color: #aaa;
}
.tab.active {
    background: #0a0a0a;
    color: #fff;
    border-color: #666;
}
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}
.player-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}
.stat-item {
    background: #1a1a1a;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #333;
}
.stat-label {
    font-size: 11px;
    color: #888;
    text-transform: uppercase;
}
.stat-value {
    font-size: 18px;
    color: #fff;
    font-weight: bold;
}
.item-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 10px;
}
.item-card {
    background: #1a1a1a;
    padding: 12px;
    border-radius: 4px;
    border: 1px solid #333;
}
.item-card.common { border-left: 3px solid #999; }
.item-card.uncommon { border-left: 3px solid #4a9; }
.item-card.rare { border-left: 3px solid #49f; }
.item-card.epic { border-left: 3px solid #a4f; }
.item-card.legendary { border-left: 3px solid #fa0; }
.item-name {
    font-size: 14px;
    font-weight: bold;
    color: #fff;
    margin-bottom: 5px;
}
.item-desc {
    font-size: 12px;
    color: #aaa;
    margin-bottom: 8px;
}
.item-stats {
    font-size: 11px;
    color: #4a9;
    margin-bottom: 5px;
}
.quest-card {
    background: #1a1a1a;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #333;
    margin-bottom: 10px;
}
.quest-card.active {
    border-left: 3px solid #4a9;
}
.quest-card.completed {
    border-left: 3px solid #666;
    opacity: 0.6;
}
.quest-title {
    font-size: 16px;
    font-weight: bold;
    color: #fff;
    margin-bottom: 5px;
}
.quest-desc {
    font-size: 13px;
    color: #aaa;
    margin-bottom: 10px;
}
.quest-progress {
    font-size: 12px;
    color: #4a9;
    margin-bottom: 5px;
}
.enemy-card {
    background: #1a1a1a;
    padding: 12px;
    border-radius: 4px;
    border: 1px solid #333;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.enemy-card.boss {
    border: 2px solid #f44;
}
.enemy-info {
    flex: 1;
}
.enemy-name {
    font-size: 14px;
    font-weight: bold;
    color: #fff;
}
.enemy-stats {
    font-size: 11px;
    color: #888;
}
.btn.small {
    padding: 5px 10px;
    font-size: 12px;
}
.btn.danger {
    background: #4a1a1a;
    border-color: #c44;
    color: #faa;
}
.btn.danger:hover:not(:disabled) {
    background: #6a2a2a;
    border-color: #f66;
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="./" class="back-link">← ホームに戻る</a>
        <a href="?reset=1" class="reset-link" onclick="return confirm('ゲームをリセットしますか？')">リセット</a>
    </div>
    
    <h1>暗い部屋</h1>
    
    <!-- タブナビゲーション -->
    <div class="tabs">
        <div class="tab active" onclick="switchTab('tab-village')">村</div>
        <div class="tab" onclick="switchTab('tab-character')">キャラクター</div>
        <div class="tab" onclick="switchTab('tab-inventory')">インベントリ</div>
        <div class="tab" onclick="switchTab('tab-craft')">クラフト</div>
        <div class="tab" onclick="switchTab('tab-quests')">クエスト</div>
        <div class="tab" onclick="switchTab('tab-battle')">戦闘</div>
    </div>
    
    <!-- 村タブ（既存機能） -->
    <div id="tab-village" class="tab-content active">
    <div id="story">
        <?php
        $stories = [
            0 => '目を覚ますと、そこは暗く冷たい部屋だった。',
            1 => '火が灯り、部屋が見えるようになってきた。外には木がたくさんある。',
            2 => '火が大きくなり、遠くまで見えるようになった。罠を作れば食料が手に入るかもしれない。',
            3 => '罠が機能している。もっと多くの人が住めるように小屋を建てよう。'
        ];
        echo $stories[$state['story_stage']] ?? $stories[0];
        ?>
    </div>
    
    <?php if ($state['fire_level'] > 0): ?>
    <div class="fire-indicator">
        <div class="fire-level" id="fireLevel">
            <?php echo str_repeat('🔥', $state['fire_level']); ?>
        </div>
        <div style="color: #888; font-size: 12px;">
            火の強さ: <span id="fireStokedTime"><?php echo max(0, (int)$state['fire_stoked']); ?></span>秒
        </div>
    </div>
    <?php endif; ?>
    
    <div id="resources">
        <div class="resource">
            <div class="resource-label">木材</div>
            <div class="resource-value" id="wood"><?php echo (int)$state['wood']; ?></div>
        </div>
        <div class="resource">
            <div class="resource-label">食料</div>
            <div class="resource-value" id="food"><?php echo (int)$state['food']; ?></div>
        </div>
        <?php if ($state['traps'] > 0): ?>
        <div class="resource">
            <div class="resource-label">罠</div>
            <div class="resource-value" id="traps"><?php echo $state['traps']; ?></div>
        </div>
        <?php endif; ?>
        <?php if ($state['huts'] > 0): ?>
        <div class="resource">
            <div class="resource-label">小屋</div>
            <div class="resource-value" id="huts"><?php echo $state['huts']; ?></div>
        </div>
        <div class="resource">
            <div class="resource-label">人口</div>
            <div class="resource-value" id="population"><?php echo $state['population']; ?></div>
        </div>
        <?php endif; ?>
        <div class="resource">
            <div class="resource-label">総アクション</div>
            <div class="resource-value" id="totalActions"><?php echo $state['total_actions']; ?></div>
        </div>
    </div>
    
    <div class="actions">
        <?php if ($state['fire_level'] === 0): ?>
        <div class="action-section">
            <h3>最初の一歩</h3>
            <button class="btn primary" onclick="performAction('light_fire')">火を灯す</button>
        </div>
        <?php else: ?>
        <div class="action-section">
            <h3>火の管理</h3>
            <button class="btn primary" onclick="performAction('stoke_fire')" id="btnStoke">
                火をくべる (木材 1)
            </button>
        </div>
        <?php endif; ?>
        
        <?php if ($state['unlocked_gather']): ?>
        <div class="action-section">
            <h3>資源採集</h3>
            <button class="btn" onclick="performAction('gather_wood')">
                木材を集める (+<?php echo $WOOD_PER_GATHER; ?>)
            </button>
        </div>
        <?php endif; ?>
        
        <?php if ($state['unlocked_trap']): ?>
        <div class="action-section">
            <h3>建設</h3>
            <button class="btn" onclick="performAction('build_trap')" id="btnTrap">
                罠を作る (木材 10)
            </button>
            <?php if ($state['unlocked_hut']): ?>
            <button class="btn" onclick="performAction('build_hut')" id="btnHut">
                小屋を建てる (木材 50)
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($state['population'] > 0): ?>
    <div class="village">
        <h3>村の管理</h3>
        <p style="margin-bottom: 10px; color: #888;">
            空き住民: <span id="availableVillagers">
                <?php echo $state['population'] - ($state['gatherers'] + $state['hunters'] + $state['builders']); ?>
            </span>
        </p>
        <div class="villager-assignment">
            <div class="assignment-item">
                <div>採集者: <span id="gatherers"><?php echo $state['gatherers']; ?></span></div>
                <button class="btn" onclick="performAction('assign_gatherer')">任命</button>
            </div>
            <div class="assignment-item">
                <div>狩人: <span id="hunters"><?php echo $state['hunters']; ?></span></div>
                <button class="btn" onclick="performAction('assign_hunter')">任命</button>
            </div>
            <div class="assignment-item">
                <div>建築者: <span id="builders"><?php echo $state['builders']; ?></span></div>
                <button class="btn" onclick="performAction('assign_builder')">任命</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    </div><!-- tab-village終了 -->
    
    <!-- キャラクタータブ -->
    <div id="tab-character" class="tab-content">
        <h2 style="color: #fff; margin-bottom: 15px;">キャラクターステータス</h2>
        <div class="player-stats" id="playerStats"></div>
        <div class="action-section">
            <h3>ステータス強化（ポイント: <span id="statPoints">0</span>）</h3>
            <button class="btn" onclick="allocateStat('max_health')">最大HP +10</button>
            <button class="btn" onclick="allocateStat('attack')">攻撃力 +1</button>
            <button class="btn" onclick="allocateStat('defense')">防御力 +1</button>
            <button class="btn" onclick="allocateStat('agility')">敏捷性 +1</button>
        </div>
    </div>
    
    <!-- インベントリタブ -->
    <div id="tab-inventory" class="tab-content">
        <h2 style="color: #fff; margin-bottom: 15px;">インベントリ</h2>
        <div class="item-grid" id="inventoryGrid"></div>
    </div>
    
    <!-- クラフトタブ -->
    <div id="tab-craft" class="tab-content">
        <h2 style="color: #fff; margin-bottom: 15px;">アイテムクラフト</h2>
        <div class="item-grid" id="recipesGrid"></div>
    </div>
    
    <!-- クエストタブ -->
    <div id="tab-quests" class="tab-content">
        <h2 style="color: #fff; margin-bottom: 15px;">クエスト</h2>
        <div id="questsList"></div>
    </div>
    
    <!-- 戦闘タブ -->
    <div id="tab-battle" class="tab-content">
        <h2 style="color: #fff; margin-bottom: 15px;">戦闘</h2>
        <div id="enemiesList"></div>
    </div>
    
</div><!-- container終了 -->

<div id="message"></div>

<script>
let state = <?php echo json_encode($state); ?>;

function showMessage(text) {
    const msg = document.getElementById('message');
    msg.textContent = text;
    msg.classList.add('show');
    setTimeout(() => {
        msg.classList.remove('show');
    }, 3000);
}

async function performAction(action) {
    try {
        const res = await fetch('darkroom.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: new URLSearchParams({action: action}),
            credentials: 'same-origin'
        });
        const data = await res.json();
        
        if (data.ok) {
            state = data.state;
            updateUI();
            let msg = data.msg;
            if (data.reward_coin > 0) {
                msg += ` +${data.reward_coin}コイン`;
            }
            if (data.reward_crystal > 0) {
                msg += ` +${data.reward_crystal}クリスタル`;
            }
            showMessage(msg);
        } else {
            showMessage('エラーが発生しました');
        }
    } catch (e) {
        showMessage('通信エラー');
        console.error(e);
    }
}

function updateUI() {
    // リソース更新
    document.getElementById('wood').textContent = Math.floor(state.wood);
    document.getElementById('food').textContent = Math.floor(state.food);
    if (document.getElementById('traps')) {
        document.getElementById('traps').textContent = state.traps;
    }
    if (document.getElementById('huts')) {
        document.getElementById('huts').textContent = state.huts;
    }
    if (document.getElementById('population')) {
        document.getElementById('population').textContent = state.population;
    }
    if (document.getElementById('totalActions')) {
        document.getElementById('totalActions').textContent = state.total_actions;
    }
    
    // 火のレベル
    if (document.getElementById('fireLevel')) {
        document.getElementById('fireLevel').textContent = '🔥'.repeat(state.fire_level);
    }
    if (document.getElementById('fireStokedTime')) {
        document.getElementById('fireStokedTime').textContent = Math.max(0, Math.floor(state.fire_stoked));
    }
    
    // ボタンの有効/無効
    if (document.getElementById('btnStoke')) {
        document.getElementById('btnStoke').disabled = state.wood < 1;
    }
    if (document.getElementById('btnTrap')) {
        document.getElementById('btnTrap').disabled = state.wood < 10;
    }
    if (document.getElementById('btnHut')) {
        document.getElementById('btnHut').disabled = state.wood < 50;
    }
    
    // 村の管理
    if (document.getElementById('availableVillagers')) {
        const available = state.population - (state.gatherers + state.hunters + state.builders);
        document.getElementById('availableVillagers').textContent = available;
    }
    if (document.getElementById('gatherers')) {
        document.getElementById('gatherers').textContent = state.gatherers;
    }
    if (document.getElementById('hunters')) {
        document.getElementById('hunters').textContent = state.hunters;
    }
    if (document.getElementById('builders')) {
        document.getElementById('builders').textContent = state.builders;
    }
}

// 定期的にサーバーから状態を取得（自動収集のため）
setInterval(async () => {
    try {
        const res = await fetch('darkroom.php?ajax=1', {
            method: 'GET',
            credentials: 'same-origin'
        });
        const data = await res.json();
        if (data.ok) {
            state = data.state;
            updateUI();
        }
    } catch (e) {
        console.error('Tick error:', e);
    }
}, 2000);

// 初期UI更新
updateUI();

// ====== 新機能のJavaScript ======

// タブ切り替え
function switchTab(tabId) {
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById(tabId).classList.add('active');
    
    // タブが開かれたときにデータを読み込む
    if (tabId === 'tab-character') loadPlayerStats();
    if (tabId === 'tab-inventory') loadInventory();
    if (tabId === 'tab-craft') loadRecipes();
    if (tabId === 'tab-quests') loadQuests();
    if (tabId === 'tab-battle') loadEnemies();
}

// プレイヤーステータス読み込み
async function loadPlayerStats() {
    const res = await fetch('darkroom.php?ajax=player_stats');
    const stats = await res.json();
    
    const html = `
        <div class="stat-item">
            <div class="stat-label">レベル</div>
            <div class="stat-value">${stats.level || 1}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">経験値</div>
            <div class="stat-value">${stats.experience || 0} / ${stats.level * 100}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">HP</div>
            <div class="stat-value">${stats.health || 100} / ${stats.max_health || 100}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">攻撃力</div>
            <div class="stat-value">${stats.attack || 10}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">防御力</div>
            <div class="stat-value">${stats.defense || 5}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">敏捷性</div>
            <div class="stat-value">${stats.agility || 5}</div>
        </div>
    `;
    
    document.getElementById('playerStats').innerHTML = html;
    document.getElementById('statPoints').textContent = stats.stat_points || 0;
}

// ステータス割り振り
async function allocateStat(stat) {
    const res = await fetch('darkroom.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action: 'allocate_stat', stat: stat})
    });
    const data = await res.json();
    
    if (data.ok) {
        showMessage(data.msg);
        loadPlayerStats();
    }
}

// インベントリ読み込み
async function loadInventory() {
    const res = await fetch('darkroom.php?ajax=inventory');
    const items = await res.json();
    
    let html = '';
    items.forEach(item => {
        const stats = item.stats ? JSON.parse(item.stats) : null;
        const statsText = stats ? Object.entries(stats).map(([k,v]) => `${k}+${v}`).join(', ') : '';
        
        html += `
            <div class="item-card ${item.rarity}">
                <div class="item-name">${item.name} x${item.quantity}</div>
                <div class="item-desc">${item.description || ''}</div>
                ${statsText ? `<div class="item-stats">${statsText}</div>` : ''}
                <div style="font-size: 11px; color: #666;">${item.type} | ${item.rarity}</div>
            </div>
        `;
    });
    
    document.getElementById('inventoryGrid').innerHTML = html || '<p style="color: #888;">アイテムがありません</p>';
}

// レシピ読み込み
async function loadRecipes() {
    const res = await fetch('darkroom.php?ajax=recipes');
    const recipes = await res.json();
    
    let html = '';
    recipes.forEach(recipe => {
        const materials = JSON.parse(recipe.materials);
        const matText = materials.map(m => `${m.item_key} x${m.quantity}`).join(', ');
        
        html += `
            <div class="item-card">
                <div class="item-name">${recipe.result_name}</div>
                <div class="item-desc">必要素材: ${matText}</div>
                <div style="font-size: 11px; color: #888; margin-bottom: 8px;">
                    必要レベル: ${recipe.required_level} | 経験値: +${recipe.experience_reward}
                </div>
                <button class="btn small" onclick="craftItem('${recipe.recipe_key}')">作成</button>
            </div>
        `;
    });
    
    document.getElementById('recipesGrid').innerHTML = html || '<p style="color: #888;">利用可能なレシピがありません</p>';
}

// クラフト実行
async function craftItem(recipeKey) {
    const res = await fetch('darkroom.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action: 'craft_item', recipe_key: recipeKey})
    });
    const data = await res.json();
    
    showMessage(data.msg);
    if (data.ok) {
        loadRecipes();
        loadInventory();
    }
}

// クエスト読み込み
async function loadQuests() {
    const res = await fetch('darkroom.php?ajax=quests');
    const quests = await res.json();
    
    let html = '';
    quests.forEach(quest => {
        const objectives = JSON.parse(quest.objectives);
        const progress = quest.progress ? JSON.parse(quest.progress) : {};
        
        let progressHtml = '';
        objectives.forEach((obj, idx) => {
            const current = progress[idx] || 0;
            progressHtml += `<div class="quest-progress">・${obj.type}: ${current}/${obj.count}</div>`;
        });
        
        html += `
            <div class="quest-card ${quest.player_status}">
                <div class="quest-title">${quest.title} [${quest.type}]</div>
                <div class="quest-desc">${quest.description}</div>
                ${progressHtml}
                <div style="font-size: 11px; color: #666; margin-top: 5px;">
                    必要レベル: ${quest.required_level}
                </div>
                ${quest.player_status === 'available' ? 
                    `<button class="btn small" onclick="startQuest('${quest.quest_key}')">開始</button>` : ''}
                ${quest.player_status === 'active' ? 
                    `<button class="btn small primary" onclick="completeQuest('${quest.quest_key}')">完了</button>` : ''}
                ${quest.player_status === 'completed' ? 
                    '<span style="color: #4a9;">完了済み</span>' : ''}
            </div>
        `;
    });
    
    document.getElementById('questsList').innerHTML = html || '<p style="color: #888;">クエストがありません</p>';
}

// クエスト開始
async function startQuest(questKey) {
    const res = await fetch('darkroom.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action: 'start_quest', quest_key: questKey})
    });
    const data = await res.json();
    
    showMessage(data.msg);
    if (data.ok) loadQuests();
}

// クエスト完了
async function completeQuest(questKey) {
    const res = await fetch('darkroom.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action: 'complete_quest', quest_key: questKey})
    });
    const data = await res.json();
    
    showMessage(data.msg);
    if (data.ok) loadQuests();
}

// 敵一覧読み込み
async function loadEnemies() {
    const res = await fetch('darkroom.php?ajax=enemies');
    const enemies = await res.json();
    
    let html = '';
    enemies.forEach(enemy => {
        html += `
            <div class="enemy-card ${enemy.is_boss ? 'boss' : ''}">
                <div class="enemy-info">
                    <div class="enemy-name">${enemy.name} ${enemy.is_boss ? '👑' : ''}</div>
                    <div class="enemy-stats">
                        Lv.${enemy.level} | HP: ${enemy.health} | 攻: ${enemy.attack} | 防: ${enemy.defense} | 経験値: ${enemy.experience_reward}
                    </div>
                    <div class="item-desc">${enemy.description || ''}</div>
                </div>
                <button class="btn danger small" onclick="startBattle('${enemy.enemy_key}')">戦闘</button>
            </div>
        `;
    });
    
    document.getElementById('enemiesList').innerHTML = html || '<p style="color: #888;">敵がいません</p>';
}

// 戦闘開始
async function startBattle(enemyKey) {
    if (!confirm('戦闘を開始しますか？')) return;
    
    showMessage('戦闘中...');
    
    const res = await fetch('darkroom.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action: 'battle', enemy_key: enemyKey})
    });
    const data = await res.json();
    
    if (data.ok) {
        showMessage(data.msg);
        loadPlayerStats();
        loadEnemies();
    }
}

// 初回ロード
loadPlayerStats();
</script>
</body>
</html>
