<?php
require_once __DIR__ . '/config.php';

$me = user();
if (!$me){ header('Location: ./login.php'); exit; }
$pdo = db();

// レアリティ定義
$RARITIES = [
    'normal' => ['name' => 'ノーマル', 'color' => '#808080', 'icon' => '⚪', 'buff_count' => 1, 'fail_rate' => 0, 'token_col' => 'normal_tokens'],
    'rare' => ['name' => 'レア', 'color' => '#00cc00', 'icon' => '🟢', 'buff_count' => 2, 'fail_rate' => 10, 'token_col' => 'rare_tokens'],
    'unique' => ['name' => 'ユニーク', 'color' => '#0080ff', 'icon' => '🔵', 'buff_count' => 3, 'fail_rate' => 20, 'token_col' => 'unique_tokens'],
    'legend' => ['name' => 'レジェンド', 'color' => '#ffcc00', 'icon' => '🟡', 'buff_count' => 4, 'fail_rate' => 30, 'token_col' => 'legend_tokens'],
    'epic' => ['name' => 'エピック', 'color' => '#cc00ff', 'icon' => '🟣', 'buff_count' => 5, 'fail_rate' => 40, 'token_col' => 'epic_tokens'],
    'hero' => ['name' => 'ヒーロー', 'color' => '#ff0000', 'icon' => '🔴', 'buff_count' => 6, 'fail_rate' => 50, 'token_col' => 'hero_tokens'],
    'mythic' => ['name' => 'ミシック', 'color' => 'rainbow', 'icon' => '🌈', 'buff_count' => 7, 'fail_rate' => 60, 'token_col' => 'mythic_tokens']
];

// 装備部位定義
$SLOTS = [
    'weapon' => ['name' => '武器', 'icon' => '⚔️'],
    'helm' => ['name' => 'ヘルム', 'icon' => '🪖'],
    'body' => ['name' => 'ボディ', 'icon' => '🛡️'],
    'shoulder' => ['name' => 'ショルダー', 'icon' => '🎽'],
    'arm' => ['name' => 'アーム', 'icon' => '🧤'],
    'leg' => ['name' => 'レッグ', 'icon' => '👢']
];

// バフ種類定義
$BUFF_TYPES = [
    'attack' => ['name' => '攻撃力', 'icon' => '⚔️', 'min' => 1, 'max_normal' => 10, 'max_mythic' => 100],
    'armor' => ['name' => 'アーマー', 'icon' => '🛡️', 'min' => 1, 'max_normal' => 10, 'max_mythic' => 100],
    'health' => ['name' => '体力', 'icon' => '❤️', 'min' => 5, 'max_normal' => 50, 'max_mythic' => 500],
    'coin_drop' => ['name' => 'コインドロップ', 'icon' => '🪙', 'min' => 1, 'max_normal' => 5, 'max_mythic' => 50, 'unit' => '%'],
    'crystal_drop' => ['name' => 'クリスタルドロップ', 'icon' => '💎', 'min' => 1, 'max_normal' => 3, 'max_mythic' => 30, 'unit' => '%'],
    'token_normal_drop' => ['name' => 'ノーマルトークンドロップ', 'icon' => '⚪', 'min' => 1, 'max_normal' => 5, 'max_mythic' => 50, 'unit' => '%'],
    'token_rare_drop' => ['name' => 'レアトークンドロップ', 'icon' => '🟢', 'min' => 1, 'max_normal' => 4, 'max_mythic' => 40, 'unit' => '%']
];

$CRAFT_COST_COINS = 10000;

// 装備作成のAPI処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    
    if ($action === 'craft') {
        $slot = $_POST['slot'] ?? '';
        $rarity = $_POST['rarity'] ?? '';
        
        if (!isset($SLOTS[$slot]) || !isset($RARITIES[$rarity])) {
            echo json_encode(['ok' => false, 'error' => '不正なパラメータです']);
            exit;
        }
        
        $rarity_info = $RARITIES[$rarity];
        $token_col = $rarity_info['token_col'];
        
        // 許可されたカラム名のホワイトリスト
        $allowed_token_columns = ['normal_tokens', 'rare_tokens', 'unique_tokens', 'legend_tokens', 'epic_tokens', 'hero_tokens', 'mythic_tokens'];
        if (!in_array($token_col, $allowed_token_columns)) {
            echo json_encode(['ok' => false, 'error' => '不正なトークンカラムです']);
            exit;
        }
        
        $pdo->beginTransaction();
        try {
            // ユーザー情報を取得
            $st = $pdo->prepare("SELECT * FROM users WHERE id=? FOR UPDATE");
            $st->execute([$me['id']]);
            $user = $st->fetch();
            
            // トークンとコインをチェック
            if (($user[$token_col] ?? 0) < 1) {
                throw new Exception($rarity_info['name'] . 'トークンが不足しています');
            }
            if ($user['coins'] < $CRAFT_COST_COINS) {
                throw new Exception('コインが不足しています（必要: ' . number_format($CRAFT_COST_COINS) . '）');
            }
            
            // 素材を消費（ホワイトリスト検証済みのカラム名を使用）
            $st = $pdo->prepare("UPDATE users SET {$token_col} = {$token_col} - 1, coins = coins - ? WHERE id = ?");
            $st->execute([$CRAFT_COST_COINS, $me['id']]);
            
            // 失敗判定
            $fail_roll = mt_rand(1, 100);
            $success = $fail_roll > $rarity_info['fail_rate'];
            
            $equipment_id = null;
            
            if ($success) {
                // バフを生成
                $buff_count = $rarity_info['buff_count'];
                $buff_keys = array_keys($BUFF_TYPES);
                shuffle($buff_keys);
                $selected_buffs = array_slice($buff_keys, 0, $buff_count);
                
                $buffs = [];
                $rarity_index = array_search($rarity, array_keys($RARITIES));
                $max_rarity_index = count($RARITIES) - 1;
                
                foreach ($selected_buffs as $buff_key) {
                    $buff_info = $BUFF_TYPES[$buff_key];
                    // レアリティに応じて最大値を補間
                    $max_value = $buff_info['max_normal'] + ($buff_info['max_mythic'] - $buff_info['max_normal']) * ($rarity_index / $max_rarity_index);
                    $value = round($buff_info['min'] + (mt_rand(0, 100) / 100) * ($max_value - $buff_info['min']), 2);
                    $buffs[$buff_key] = $value;
                }
                
                // 装備名を生成
                $prefixes = ['輝く', '神秘の', '古代の', '伝説の', '英雄の', '神の', '究極の'];
                $name = $prefixes[array_rand($prefixes)] . $SLOTS[$slot]['name'];
                
                // 装備を保存
                $st = $pdo->prepare("
                    INSERT INTO user_equipment (user_id, slot, name, rarity, buffs)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $st->execute([$me['id'], $slot, $name, $rarity, json_encode($buffs)]);
                $equipment_id = $pdo->lastInsertId();
            }
            
            // 履歴を記録
            $st = $pdo->prepare("
                INSERT INTO equipment_craft_history (user_id, equipment_id, rarity, success, token_used, coins_used)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $st->execute([$me['id'], $equipment_id, $rarity, $success ? 1 : 0, $token_col, $CRAFT_COST_COINS]);
            
            $pdo->commit();
            
            if ($success) {
                echo json_encode([
                    'ok' => true,
                    'success' => true,
                    'message' => '装備の作成に成功しました！',
                    'equipment' => [
                        'id' => $equipment_id,
                        'name' => $name,
                        'rarity' => $rarity,
                        'buffs' => $buffs
                    ]
                ]);
            } else {
                echo json_encode([
                    'ok' => true,
                    'success' => false,
                    'message' => '装備の作成に失敗しました...素材は消費されました。',
                    'fail_rate' => $rarity_info['fail_rate']
                ]);
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'equip') {
        $equipment_id = (int)($_POST['equipment_id'] ?? 0);
        
        try {
            // 装備の所有確認
            $st = $pdo->prepare("SELECT * FROM user_equipment WHERE id = ? AND user_id = ?");
            $st->execute([$equipment_id, $me['id']]);
            $equipment = $st->fetch();
            
            if (!$equipment) {
                throw new Exception('装備が見つかりません');
            }
            
            $pdo->beginTransaction();
            
            // 同じ部位の装備を外す
            $st = $pdo->prepare("UPDATE user_equipment SET is_equipped = 0 WHERE user_id = ? AND slot = ?");
            $st->execute([$me['id'], $equipment['slot']]);
            
            // 装備する
            $st = $pdo->prepare("UPDATE user_equipment SET is_equipped = 1 WHERE id = ?");
            $st->execute([$equipment_id]);
            
            $pdo->commit();
            
            echo json_encode(['ok' => true, 'message' => '装備しました']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'unequip') {
        $equipment_id = (int)($_POST['equipment_id'] ?? 0);
        
        try {
            $st = $pdo->prepare("UPDATE user_equipment SET is_equipped = 0 WHERE id = ? AND user_id = ?");
            $st->execute([$equipment_id, $me['id']]);
            
            echo json_encode(['ok' => true, 'message' => '装備を外しました']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'upgrade') {
        $equipment_id = (int)($_POST['equipment_id'] ?? 0);
        
        if ($equipment_id <= 0) {
            echo json_encode(['ok' => false, 'error' => '不正な装備IDです']);
            exit;
        }
        
        $pdo->beginTransaction();
        try {
            // 装備の所有確認と現在の状態を取得
            $st = $pdo->prepare("SELECT * FROM user_equipment WHERE id = ? AND user_id = ? FOR UPDATE");
            $st->execute([$equipment_id, $me['id']]);
            $equipment = $st->fetch();
            
            if (!$equipment) {
                throw new Exception('装備が見つかりません');
            }
            
            $current_level = (int)($equipment['upgrade_level'] ?? 0);
            $rarity = $equipment['rarity'];
            $rarity_info = $RARITIES[$rarity];
            $token_col = $rarity_info['token_col'];
            
            // 許可されたカラム名のホワイトリスト
            $allowed_token_columns = ['normal_tokens', 'rare_tokens', 'unique_tokens', 'legend_tokens', 'epic_tokens', 'hero_tokens', 'mythic_tokens'];
            if (!in_array($token_col, $allowed_token_columns)) {
                throw new Exception('不正なトークンカラムです');
            }
            
            // 必要トークン数を計算（アップグレードレベル + 1）
            $required_tokens = $current_level + 1;
            
            // ユーザー情報を取得
            $st = $pdo->prepare("SELECT * FROM users WHERE id=? FOR UPDATE");
            $st->execute([$me['id']]);
            $user = $st->fetch();
            
            // トークンをチェック
            if (($user[$token_col] ?? 0) < $required_tokens) {
                throw new Exception($rarity_info['name'] . 'トークンが不足しています（必要: ' . $required_tokens . '個）');
            }
            
            // トークンを消費（ホワイトリスト検証済みのカラム名を使用）
            $st = $pdo->prepare("UPDATE users SET {$token_col} = {$token_col} - ? WHERE id = ?");
            $st->execute([$required_tokens, $me['id']]);
            
            // バフを上昇させる（各バフを10%上昇）
            $buffs = json_decode($equipment['buffs'], true) ?: [];
            $buff_increase = [];
            foreach ($buffs as $buff_key => $value) {
                $increase = round($value * 0.1, 2);  // 10%上昇
                $buff_increase[$buff_key] = $increase;
                $buffs[$buff_key] = round($value + $increase, 2);
            }
            
            $new_level = $current_level + 1;
            
            // 装備を更新
            $st = $pdo->prepare("UPDATE user_equipment SET buffs = ?, upgrade_level = ? WHERE id = ?");
            $st->execute([json_encode($buffs), $new_level, $equipment_id]);
            
            // 履歴を記録
            $st = $pdo->prepare("
                INSERT INTO equipment_upgrade_history (user_id, equipment_id, from_level, to_level, token_used, token_amount, buff_increase)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $st->execute([$me['id'], $equipment_id, $current_level, $new_level, $token_col, $required_tokens, json_encode($buff_increase)]);
            
            $pdo->commit();
            
            echo json_encode([
                'ok' => true,
                'message' => '装備をアップグレードしました！（+' . $new_level . '）',
                'new_level' => $new_level,
                'new_buffs' => $buffs,
                'buff_increase' => $buff_increase
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

// ユーザーの装備一覧を取得
$st = $pdo->prepare("SELECT * FROM user_equipment WHERE user_id = ? ORDER BY is_equipped DESC, rarity DESC, created_at DESC");
$st->execute([$me['id']]);
$equipments = $st->fetchAll();

// 現在のトークン残高を取得
$st = $pdo->prepare("SELECT * FROM users WHERE id=?");
$st->execute([$me['id']]);
$user = $st->fetch();
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>装備システム - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
.equipment-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.equipment-header {
    background: linear-gradient(135deg, #6b5b95 0%, #8b4b8b 100%);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 8px 16px rgba(107, 91, 149, 0.3);
}

.equipment-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
}

.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    background: #2d2d44;
    color: #888;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s;
}

.tab-btn.active {
    background: linear-gradient(135deg, #6b5b95 0%, #8b4b8b 100%);
    color: white;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* 作成セクション */
.craft-section {
    background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%);
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 20px;
}

.craft-section h3 {
    margin: 0 0 20px 0;
    font-size: 20px;
    color: #fff;
}

.slot-selector, .rarity-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.slot-btn, .rarity-btn {
    padding: 12px 20px;
    border: 2px solid #444;
    border-radius: 10px;
    background: #2d2d44;
    color: #fff;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.slot-btn.selected, .rarity-btn.selected {
    border-color: #6b5b95;
    background: rgba(107, 91, 149, 0.3);
}

.rarity-btn .icon {
    font-size: 20px;
}

.craft-info {
    background: rgba(0,0,0,0.2);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}

.craft-info p {
    margin: 8px 0;
    color: #aaa;
}

.craft-info .warning {
    color: #ff6b6b;
}

.craft-btn {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #6b5b95 0%, #8b4b8b 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.craft-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(107, 91, 149, 0.4);
}

.craft-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* 装備一覧 */
.equipment-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
}

.equipment-card {
    background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%);
    border-radius: 12px;
    padding: 20px;
    border: 2px solid #333;
    transition: all 0.3s;
}

.equipment-card.equipped {
    border-color: #6b5b95;
    box-shadow: 0 0 15px rgba(107, 91, 149, 0.4);
}

.equipment-card.rarity-normal { border-left: 4px solid #808080; }
.equipment-card.rarity-rare { border-left: 4px solid #00cc00; }
.equipment-card.rarity-unique { border-left: 4px solid #0080ff; }
.equipment-card.rarity-legend { border-left: 4px solid #ffcc00; }
.equipment-card.rarity-epic { border-left: 4px solid #cc00ff; }
.equipment-card.rarity-hero { border-left: 4px solid #ff0000; }
.equipment-card.rarity-mythic { 
    border-left: 4px solid transparent;
    border-image: linear-gradient(180deg, red, orange, yellow, green, blue, indigo, violet) 1;
}

.equipment-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.equipment-name {
    font-size: 18px;
    font-weight: bold;
    color: #fff;
}

.equipment-rarity {
    font-size: 14px;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: bold;
}

.equipment-buffs {
    margin-bottom: 15px;
}

.buff-item {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    font-size: 14px;
}

.buff-item:last-child {
    border-bottom: none;
}

.buff-name {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #aaa;
}

.buff-value {
    color: #00ff88;
    font-weight: bold;
}

.equipment-actions {
    display: flex;
    gap: 10px;
}

.equip-btn, .unequip-btn {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.equip-btn {
    background: linear-gradient(135deg, #6b5b95 0%, #8b4b8b 100%);
    color: white;
}

.unequip-btn {
    background: #444;
    color: #fff;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 20px;
    background: rgba(255,255,255,0.1);
    color: #6b5b95;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}

.back-link:hover {
    background: #6b5b95;
    color: white;
}

.token-display {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    background: rgba(0,0,0,0.2);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.token-item {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    font-size: 14px;
}

.no-equipment {
    text-align: center;
    padding: 40px;
    color: #666;
}

.upgrade-info {
    margin-bottom: 10px;
    padding: 8px;
    background: rgba(255, 215, 0, 0.1);
    border-radius: 6px;
    text-align: center;
}

.upgrade-cost {
    font-size: 12px;
    color: #ffcc00;
}

.upgrade-btn {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
    color: #333;
}

.upgrade-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
}
</style>
</head>
<body>
<div class="equipment-container">
    <a href="./" class="back-link">← フィードに戻る</a>
    
    <div class="equipment-header">
        <h1>⚔️ 装備システム</h1>
        <p>トークンを使って装備を作成し、バフを獲得しよう！</p>
    </div>
    
    <div class="tabs">
        <button class="tab-btn active" data-tab="craft">🔨 装備作成</button>
        <button class="tab-btn" data-tab="inventory">📦 所持装備</button>
    </div>
    
    <!-- 装備作成タブ -->
    <div class="tab-content active" id="tab-craft">
        <div class="craft-section">
            <h3>トークン残高</h3>
            <div class="token-display">
                <div class="token-item"><span>🪙</span> <?= number_format($user['coins']) ?></div>
                <div class="token-item"><span>⚪</span> <?= $user['normal_tokens'] ?? 0 ?></div>
                <div class="token-item"><span>🟢</span> <?= $user['rare_tokens'] ?? 0 ?></div>
                <div class="token-item"><span>🔵</span> <?= $user['unique_tokens'] ?? 0 ?></div>
                <div class="token-item"><span>🟡</span> <?= $user['legend_tokens'] ?? 0 ?></div>
                <div class="token-item"><span>🟣</span> <?= $user['epic_tokens'] ?? 0 ?></div>
                <div class="token-item"><span>🔴</span> <?= $user['hero_tokens'] ?? 0 ?></div>
                <div class="token-item"><span>🌈</span> <?= $user['mythic_tokens'] ?? 0 ?></div>
            </div>
        </div>
        
        <div class="craft-section">
            <h3>1. 部位を選択</h3>
            <div class="slot-selector">
                <?php foreach ($SLOTS as $key => $slot): ?>
                <button class="slot-btn" data-slot="<?= $key ?>">
                    <span><?= $slot['icon'] ?></span>
                    <span><?= $slot['name'] ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="craft-section">
            <h3>2. レアリティを選択</h3>
            <div class="rarity-selector">
                <?php foreach ($RARITIES as $key => $rarity): ?>
                <button class="rarity-btn" data-rarity="<?= $key ?>">
                    <span class="icon"><?= $rarity['icon'] ?></span>
                    <span><?= $rarity['name'] ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="craft-section">
            <h3>3. 作成情報</h3>
            <div class="craft-info" id="craftInfo">
                <p>部位とレアリティを選択してください</p>
            </div>
            <button class="craft-btn" id="craftBtn" disabled>装備を作成する</button>
        </div>
    </div>
    
    <!-- 所持装備タブ -->
    <div class="tab-content" id="tab-inventory">
        <?php if (empty($equipments)): ?>
        <div class="no-equipment">
            <p>装備がありません。<br>「装備作成」タブから作成しましょう！</p>
        </div>
        <?php else: ?>
        <div class="equipment-grid">
            <?php foreach ($equipments as $eq): 
                $buffs = json_decode($eq['buffs'], true) ?: [];
                $rarity_info = $RARITIES[$eq['rarity']];
                $upgrade_level = (int)($eq['upgrade_level'] ?? 0);
                $upgrade_display = $upgrade_level > 0 ? ' +' . $upgrade_level : '';
                $required_tokens = $upgrade_level + 1;
            ?>
            <div class="equipment-card <?= $eq['is_equipped'] ? 'equipped' : '' ?> rarity-<?= $eq['rarity'] ?>">
                <div class="equipment-card-header">
                    <span class="equipment-name"><?= htmlspecialchars($eq['name']) ?><?= $upgrade_display ?></span>
                    <span class="equipment-rarity" style="background: <?= $rarity_info['color'] === 'rainbow' ? 'linear-gradient(90deg, red, orange, yellow, green, blue, violet)' : $rarity_info['color'] ?>;">
                        <?= $rarity_info['name'] ?>
                    </span>
                </div>
                <div class="equipment-buffs">
                    <?php foreach ($buffs as $buff_key => $value): 
                        $buff_info = $BUFF_TYPES[$buff_key] ?? ['name' => $buff_key, 'icon' => '❓', 'unit' => ''];
                    ?>
                    <div class="buff-item">
                        <span class="buff-name">
                            <span><?= $buff_info['icon'] ?></span>
                            <?= $buff_info['name'] ?>
                        </span>
                        <span class="buff-value">+<?= $value ?><?= $buff_info['unit'] ?? '' ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="upgrade-info">
                    <span class="upgrade-cost"><?= $rarity_info['icon'] ?> ×<?= $required_tokens ?> で強化</span>
                </div>
                <div class="equipment-actions">
                    <?php if ($eq['is_equipped']): ?>
                    <button class="unequip-btn" data-id="<?= $eq['id'] ?>">外す</button>
                    <?php else: ?>
                    <button class="equip-btn" data-id="<?= $eq['id'] ?>">装備する</button>
                    <?php endif; ?>
                    <button class="upgrade-btn" data-id="<?= $eq['id'] ?>" data-rarity="<?= $eq['rarity'] ?>" data-level="<?= $upgrade_level ?>" data-name="<?= htmlspecialchars($eq['name']) ?>">⬆️ 強化</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
const RARITIES = <?= json_encode($RARITIES) ?>;
const CRAFT_COST = <?= $CRAFT_COST_COINS ?>;

let selectedSlot = null;
let selectedRarity = null;

// タブ切り替え
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
});

// 部位選択
document.querySelectorAll('.slot-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        selectedSlot = btn.dataset.slot;
        updateCraftInfo();
    });
});

// レアリティ選択
document.querySelectorAll('.rarity-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.rarity-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        selectedRarity = btn.dataset.rarity;
        updateCraftInfo();
    });
});

function updateCraftInfo() {
    const info = document.getElementById('craftInfo');
    const craftBtn = document.getElementById('craftBtn');
    
    if (!selectedSlot || !selectedRarity) {
        info.innerHTML = '<p>部位とレアリティを選択してください</p>';
        craftBtn.disabled = true;
        return;
    }
    
    const rarity = RARITIES[selectedRarity];
    info.innerHTML = `
        <p>📦 必要素材: ${rarity.icon} ${rarity.name}トークン ×1</p>
        <p>🪙 必要コイン: ${CRAFT_COST.toLocaleString()}</p>
        <p>✨ バフ数: ${rarity.buff_count}個</p>
        <p class="warning">⚠️ 失敗率: ${rarity.fail_rate}% ${rarity.fail_rate > 0 ? '（失敗すると素材は消費されます）' : ''}</p>
    `;
    craftBtn.disabled = false;
}

// 装備作成
document.getElementById('craftBtn').addEventListener('click', async () => {
    if (!selectedSlot || !selectedRarity) return;
    
    const rarity = RARITIES[selectedRarity];
    if (!confirm(`${rarity.name}の装備を作成しますか？\n\n必要: ${rarity.name}トークン×1 + ${CRAFT_COST.toLocaleString()}コイン\n失敗率: ${rarity.fail_rate}%`)) {
        return;
    }
    
    const btn = document.getElementById('craftBtn');
    btn.disabled = true;
    btn.textContent = '作成中...';
    
    try {
        const formData = new FormData();
        formData.append('action', 'craft');
        formData.append('slot', selectedSlot);
        formData.append('rarity', selectedRarity);
        
        const res = await fetch('', {method: 'POST', body: formData});
        const data = await res.json();
        
        if (data.ok) {
            if (data.success) {
                alert(`✅ ${data.message}\n\n作成された装備: ${data.equipment.name}`);
                location.reload();
            } else {
                alert(`❌ ${data.message}`);
            }
        } else {
            alert('❌ ' + data.error);
        }
    } catch (e) {
        alert('❌ 通信エラーが発生しました');
    }
    
    btn.disabled = false;
    btn.textContent = '装備を作成する';
});

// 装備/外す
document.querySelectorAll('.equip-btn, .unequip-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const action = btn.classList.contains('equip-btn') ? 'equip' : 'unequip';
        
        const formData = new FormData();
        formData.append('action', action);
        formData.append('equipment_id', id);
        
        try {
            const res = await fetch('', {method: 'POST', body: formData});
            const data = await res.json();
            
            if (data.ok) {
                location.reload();
            } else {
                alert('❌ ' + data.error);
            }
        } catch (e) {
            alert('❌ 通信エラーが発生しました');
        }
    });
});

// アップグレード
document.querySelectorAll('.upgrade-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const rarity = btn.dataset.rarity;
        const level = parseInt(btn.dataset.level) || 0;
        const name = btn.dataset.name;
        const requiredTokens = level + 1;
        const rarityInfo = RARITIES[rarity];
        
        if (!confirm(`「${name}${level > 0 ? ' +' + level : ''}」をアップグレードしますか？\n\n必要: ${rarityInfo.icon} ${rarityInfo.name}トークン ×${requiredTokens}\n効果: 全バフが10%上昇`)) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'upgrade');
        formData.append('equipment_id', id);
        
        try {
            const res = await fetch('', {method: 'POST', body: formData});
            const data = await res.json();
            
            if (data.ok) {
                alert(`✅ ${data.message}`);
                location.reload();
            } else {
                alert('❌ ' + data.error);
            }
        } catch (e) {
            alert('❌ 通信エラーが発生しました');
        }
    });
});
</script>
</body>
</html>
