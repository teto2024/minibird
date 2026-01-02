<?php
// ===============================================
// hero_gacha_api.php
// ヒーローガチャ・管理API
// ===============================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/exp_system.php';

header('Content-Type: application/json');

$me = user();
if (!$me) {
    echo json_encode(['ok' => false, 'error' => 'login_required']);
    exit;
}

$pdo = db();
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

/**
 * ⑤ デイリータスク進捗を更新（gacha）
 */
function updateGachaDailyTaskProgress($pdo, $userId, $amount = 1) {
    $today = date('Y-m-d');
    
    // タスクタイプに該当するタスクを取得
    $stmt = $pdo->prepare("SELECT id, target_count FROM civilization_daily_tasks WHERE task_type = 'gacha' AND is_active = TRUE");
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tasks as $task) {
        // 進捗を更新
        $stmt = $pdo->prepare("
            INSERT INTO user_daily_task_progress (user_id, task_id, task_date, current_progress, is_completed)
            VALUES (?, ?, ?, LEAST(?, ?), LEAST(?, ?) >= ?)
            ON DUPLICATE KEY UPDATE 
                current_progress = LEAST(current_progress + VALUES(current_progress), ?)
        ");
        $stmt->execute([
            $userId, $task['id'], $today, 
            $amount, $task['target_count'],
            $amount, $task['target_count'], $task['target_count'],
            $task['target_count']
        ]);
        
        // is_completedを更新
        $stmt = $pdo->prepare("
            UPDATE user_daily_task_progress 
            SET is_completed = (current_progress >= ?)
            WHERE user_id = ? AND task_id = ? AND task_date = ?
        ");
        $stmt->execute([$task['target_count'], $userId, $task['id'], $today]);
    }
}

// ガチャを引く
if ($action === 'pull') {
    $type = $input['type'] ?? 'normal';
    
    $pdo->beginTransaction();
    try {
        // コスト確認
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$me['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $cost_coins = 0;
        $cost_crystals = 0;
        $cost_diamonds = 0;
        
        if ($type === 'normal') {
            $cost_coins = 1000;
            if ($user['coins'] < $cost_coins) {
                throw new Exception('コインが不足しています');
            }
        } else if ($type === 'crystal') {
            $cost_crystals = 100;
            if ($user['crystals'] < $cost_crystals) {
                throw new Exception('クリスタルが不足しています');
            }
        } else if ($type === 'diamond') {
            $cost_diamonds = 10;
            if ($user['diamonds'] < $cost_diamonds) {
                throw new Exception('ダイヤモンドが不足しています');
            }
        } else {
            throw new Exception('無効なガチャタイプです');
        }
        
        // コスト消費
        $stmt = $pdo->prepare("UPDATE users SET coins = coins - ?, crystals = crystals - ?, diamonds = diamonds - ? WHERE id = ?");
        $stmt->execute([$cost_coins, $cost_crystals, $cost_diamonds, $me['id']]);
        
        // 報酬決定（ダイヤモンドガチャはクリスタルガチャと同じ報酬テーブル）
        $rewardType = $type === 'diamond' ? 'crystal' : $type;
        $reward = determineGachaReward($rewardType, $pdo, $me['id']);
        
        // 報酬付与
        applyGachaReward($reward, $pdo, $me['id']);
        
        // 履歴記録
        $stmt = $pdo->prepare("
            INSERT INTO hero_gacha_history (user_id, gacha_type, reward_type, reward_data, cost_coins, cost_crystals, cost_diamonds)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$me['id'], $type, $reward['type'], json_encode($reward), $cost_coins, $cost_crystals, $cost_diamonds]);
        
        $pdo->commit();
        
        // ⑤ デイリータスク進捗を更新（gacha）
        updateGachaDailyTaskProgress($pdo, $me['id'], 1);
        
        // 更新後の残高を取得
        $stmt = $pdo->prepare("SELECT coins, crystals, diamonds FROM users WHERE id = ?");
        $stmt->execute([$me['id']]);
        $balance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'reward' => $reward,
            'balance' => $balance
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 10連ガチャを引く
if ($action === 'pull_10') {
    $type = $input['type'] ?? 'normal';
    
    $pdo->beginTransaction();
    try {
        // コスト確認（10連分、10%割引）
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$me['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $cost_coins = 0;
        $cost_crystals = 0;
        $cost_diamonds = 0;
        
        if ($type === 'normal') {
            $cost_coins = 9000; // 10連で10%割引（通常10,000）
            if ($user['coins'] < $cost_coins) {
                throw new Exception('コインが不足しています（必要: ' . number_format($cost_coins) . '）');
            }
        } else if ($type === 'crystal') {
            $cost_crystals = 900; // 10連で10%割引（通常1,000）
            if ($user['crystals'] < $cost_crystals) {
                throw new Exception('クリスタルが不足しています（必要: ' . number_format($cost_crystals) . '）');
            }
        } else if ($type === 'diamond') {
            $cost_diamonds = 90; // 10連で10%割引（通常100）
            if ($user['diamonds'] < $cost_diamonds) {
                throw new Exception('ダイヤモンドが不足しています（必要: ' . number_format($cost_diamonds) . '）');
            }
        } else {
            throw new Exception('無効なガチャタイプです');
        }
        
        // コスト消費
        $stmt = $pdo->prepare("UPDATE users SET coins = coins - ?, crystals = crystals - ?, diamonds = diamonds - ? WHERE id = ?");
        $stmt->execute([$cost_coins, $cost_crystals, $cost_diamonds, $me['id']]);
        
        // 10回分の報酬を決定・付与（ダイヤモンドガチャはクリスタルガチャと同じ報酬テーブル）
        $rewardType = $type === 'diamond' ? 'crystal' : $type;
        $rewards = [];
        for ($i = 0; $i < 10; $i++) {
            $reward = determineGachaReward($rewardType, $pdo, $me['id']);
            applyGachaReward($reward, $pdo, $me['id']);
            $rewards[] = $reward;
            
            // 履歴記録
            // 10連ガチャの場合、総コストを各エントリに1/10ずつ記録（分析しやすくするため）
            $stmt = $pdo->prepare("
                INSERT INTO hero_gacha_history (user_id, gacha_type, reward_type, reward_data, cost_coins, cost_crystals, cost_diamonds)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $individual_coins = (int)floor($cost_coins / 10);
            $individual_crystals = (int)floor($cost_crystals / 10);
            $individual_diamonds = (int)floor($cost_diamonds / 10);
            $stmt->execute([$me['id'], $type . '_10', $reward['type'], json_encode($reward), $individual_coins, $individual_crystals, $individual_diamonds]);
        }
        
        $pdo->commit();
        
        // ⑤ デイリータスク進捗を更新（gacha 10回分）
        updateGachaDailyTaskProgress($pdo, $me['id'], 10);
        
        // 更新後の残高を取得
        $stmt = $pdo->prepare("SELECT coins, crystals, diamonds FROM users WHERE id = ?");
        $stmt->execute([$me['id']]);
        $balance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'rewards' => $rewards,
            'balance' => $balance
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ヒーローアンロック
if ($action === 'unlock') {
    $hero_id = (int)($input['hero_id'] ?? 0);
    
    $pdo->beginTransaction();
    try {
        // ヒーロー情報取得
        $stmt = $pdo->prepare("SELECT * FROM heroes WHERE id = ?");
        $stmt->execute([$hero_id]);
        $hero = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$hero) {
            throw new Exception('ヒーローが見つかりません');
        }
        
        // ユーザーのヒーロー状況確認
        $stmt = $pdo->prepare("SELECT * FROM user_heroes WHERE user_id = ? AND hero_id = ?");
        $stmt->execute([$me['id'], $hero_id]);
        $userHero = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userHero && $userHero['star_level'] > 0) {
            throw new Exception('既にアンロック済みです');
        }
        
        $shards = $userHero ? $userHero['shards'] : 0;
        $unlockShards = $hero['unlock_shards'];
        
        if ($shards < $unlockShards) {
            throw new Exception('欠片が不足しています');
        }
        
        // アンロック処理
        if ($userHero) {
            $stmt = $pdo->prepare("
                UPDATE user_heroes 
                SET star_level = 1, shards = shards - ?, unlocked_at = NOW()
                WHERE user_id = ? AND hero_id = ?
            ");
            $stmt->execute([$unlockShards, $me['id'], $hero_id]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO user_heroes (user_id, hero_id, star_level, shards, unlocked_at)
                VALUES (?, ?, 1, 0, NOW())
            ");
            $stmt->execute([$me['id'], $hero_id]);
        }
        
        $pdo->commit();
        
        echo json_encode(['ok' => true, 'message' => 'アンロック成功']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// スターアップ
if ($action === 'star_up') {
    $hero_id = (int)($input['hero_id'] ?? 0);
    
    $pdo->beginTransaction();
    try {
        // ヒーロー情報取得
        $stmt = $pdo->prepare("SELECT * FROM heroes WHERE id = ?");
        $stmt->execute([$hero_id]);
        $hero = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$hero) {
            throw new Exception('ヒーローが見つかりません');
        }
        
        // ユーザーのヒーロー状況確認
        $stmt = $pdo->prepare("SELECT * FROM user_heroes WHERE user_id = ? AND hero_id = ?");
        $stmt->execute([$me['id'], $hero_id]);
        $userHero = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userHero || $userHero['star_level'] == 0) {
            throw new Exception('まずアンロックしてください');
        }
        
        if ($userHero['star_level'] >= 8) {
            throw new Exception('既に最大レベルです');
        }
        
        $starUpShards = json_decode($hero['star_up_shards'], true) ?: [15, 25, 40, 60, 90, 130, 180];
        $requiredShards = $starUpShards[$userHero['star_level'] - 1] ?? 999;
        
        if ($userHero['shards'] < $requiredShards) {
            throw new Exception('欠片が不足しています');
        }
        
        // スターアップ処理
        $newStarLevel = $userHero['star_level'] + 1;
        $stmt = $pdo->prepare("
            UPDATE user_heroes 
            SET star_level = ?, shards = shards - ?
            WHERE user_id = ? AND hero_id = ?
        ");
        $stmt->execute([$newStarLevel, $requiredShards, $me['id'], $hero_id]);
        
        $pdo->commit();
        
        echo json_encode(['ok' => true, 'new_star_level' => $newStarLevel]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'invalid_action']);

// ガチャ報酬決定関数
function determineGachaReward($type, $pdo, $user_id) {
    // 報酬確率（クリスタルガチャの方が良い報酬が出やすい）
    if ($type === 'crystal') {
        $roll = mt_rand(1, 100);
        if ($roll <= 5) {
            // 5%: 装備ドロップ
            return generateEquipmentReward($pdo, $user_id);
        } elseif ($roll <= 30) {
            // 25%: ヒーロー欠片 (多め)
            return generateHeroShardsReward($pdo, mt_rand(3, 5));
        } elseif ($roll <= 55) {
            // 25%: 経験値
            return ['type' => 'exp', 'amount' => mt_rand(100, 500), 'name' => '経験値', 'detail' => mt_rand(100, 500) . ' EXP を獲得！'];
        } elseif ($roll <= 75) {
            // 20%: クリスタル還元
            $amount = mt_rand(10, 50);
            return ['type' => 'crystals', 'amount' => $amount, 'name' => 'クリスタル', 'detail' => "$amount クリスタルを獲得！"];
        } else {
            // 25%: トークン
            return generateTokenReward(true);
        }
    } else {
        // ノーマルガチャ
        $roll = mt_rand(1, 100);
        if ($roll <= 40) {
            // 40%: ヒーロー欠片
            return generateHeroShardsReward($pdo, mt_rand(1, 3));
        } elseif ($roll <= 65) {
            // 25%: 経験値
            $amount = mt_rand(50, 200);
            return ['type' => 'exp', 'amount' => $amount, 'name' => '経験値', 'detail' => "$amount EXP を獲得！"];
        } elseif ($roll <= 85) {
            // 20%: コイン
            $amount = mt_rand(100, 500);
            return ['type' => 'coins', 'amount' => $amount, 'name' => 'コイン', 'detail' => "$amount コインを獲得！"];
        } else {
            // 15%: トークン
            return generateTokenReward(false);
        }
    }
}

// ヒーロー欠片報酬生成
function generateHeroShardsReward($pdo, $shardCount) {
    // ヒーロー一覧を取得してアプリケーション側でランダム選択（小規模データセット向け最適化）
    $stmt = $pdo->prepare("SELECT id, name, icon FROM heroes WHERE generation = 0");
    $stmt->execute();
    $heroes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($heroes)) {
        return ['type' => 'coins', 'amount' => 500, 'name' => 'コイン', 'detail' => '500 コインを獲得！'];
    }
    
    // アプリケーション側でランダム選択
    $hero = $heroes[array_rand($heroes)];
    
    return [
        'type' => 'hero_shards',
        'hero_id' => $hero['id'],
        'hero_name' => $hero['name'],
        'hero_icon' => $hero['icon'],
        'amount' => $shardCount,
        'name' => $hero['name'] . 'の欠片',
        'detail' => "{$hero['icon']} {$hero['name']} の欠片を {$shardCount} 個獲得！"
    ];
}

// トークン報酬生成
function generateTokenReward($isRare) {
    $tokens = $isRare 
        ? ['rare_tokens' => 'レアトークン', 'unique_tokens' => 'ユニークトークン'] 
        : ['normal_tokens' => 'ノーマルトークン', 'rare_tokens' => 'レアトークン'];
    
    $tokenKey = array_rand($tokens);
    $tokenName = $tokens[$tokenKey];
    $amount = $isRare ? mt_rand(1, 3) : mt_rand(1, 2);
    
    return [
        'type' => 'tokens',
        'token_type' => $tokenKey,
        'amount' => $amount,
        'name' => $tokenName,
        'detail' => "{$tokenName} を {$amount} 個獲得！"
    ];
}

// 装備報酬生成
function generateEquipmentReward($pdo, $user_id) {
    $slots = ['weapon', 'helm', 'body', 'shoulder', 'arm', 'leg'];
    $rarities = ['rare', 'unique', 'legend'];
    
    // レアリティごとのバフ数定義
    $RARITY_BUFF_COUNTS = [
        'rare' => 2,
        'unique' => 3,
        'legend' => 4
    ];
    
    // バフ種類定義
    $BUFF_TYPES = [
        'attack' => ['min' => 5, 'max_rare' => 15, 'max_legend' => 30],
        'armor' => ['min' => 3, 'max_rare' => 12, 'max_legend' => 25],
        'health' => ['min' => 10, 'max_rare' => 50, 'max_legend' => 100],
        'coin_drop' => ['min' => 1, 'max_rare' => 5, 'max_legend' => 15],
        'crystal_drop' => ['min' => 1, 'max_rare' => 3, 'max_legend' => 10],
        'exp_bonus' => ['min' => 1, 'max_rare' => 5, 'max_legend' => 15]
    ];
    
    $slot = $slots[array_rand($slots)];
    $rarity = $rarities[array_rand($rarities)];
    
    $SLOTS = [
        'weapon' => ['name' => '武器', 'icon' => '⚔️'],
        'helm' => ['name' => 'ヘルム', 'icon' => '🪖'],
        'body' => ['name' => 'ボディ', 'icon' => '🛡️'],
        'shoulder' => ['name' => 'ショルダー', 'icon' => '🎽'],
        'arm' => ['name' => 'アーム', 'icon' => '🧤'],
        'leg' => ['name' => 'レッグ', 'icon' => '👢']
    ];
    
    $prefixes = ['輝く', '神秘の', '古代の', '伝説の', '英雄の'];
    $name = $prefixes[array_rand($prefixes)] . $SLOTS[$slot]['name'];
    
    // レアリティに応じたバフ数を取得
    $buff_count = $RARITY_BUFF_COUNTS[$rarity] ?? 2;
    
    // バフをランダムに選択して生成
    $buff_keys = array_keys($BUFF_TYPES);
    shuffle($buff_keys);
    $selected_buffs = array_slice($buff_keys, 0, $buff_count);
    
    $buffs = [];
    $rarity_index = array_search($rarity, $rarities);
    $max_rarity_index = max(1, count($rarities) - 1); // 0除算を防止
    
    foreach ($selected_buffs as $buff_key) {
        $buff_info = $BUFF_TYPES[$buff_key];
        // レアリティに応じて最大値を補間
        $max_value = $buff_info['max_rare'] + ($buff_info['max_legend'] - $buff_info['max_rare']) * ($rarity_index / $max_rarity_index);
        $value = mt_rand($buff_info['min'], (int)$max_value);
        $buffs[$buff_key] = $value;
    }
    
    return [
        'type' => 'equipment',
        'slot' => $slot,
        'rarity' => $rarity,
        'name' => $name,
        'buffs' => $buffs,
        'detail' => "⚔️ $rarity 装備「{$name}」を獲得！"
    ];
}

// 報酬適用
function applyGachaReward($reward, $pdo, $user_id) {
    switch ($reward['type']) {
        case 'hero_shards':
            // ヒーロー欠片を追加
            $stmt = $pdo->prepare("
                INSERT INTO user_heroes (user_id, hero_id, shards)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE shards = shards + ?
            ");
            $stmt->execute([$user_id, $reward['hero_id'], $reward['amount'], $reward['amount']]);
            break;
            
        case 'exp':
            // 経験値を直接追加
            $stmt = $pdo->prepare("UPDATE users SET user_exp = user_exp + ? WHERE id = ?");
            $stmt->execute([$reward['amount'], $user_id]);
            break;
            
        case 'coins':
            $stmt = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
            $stmt->execute([$reward['amount'], $user_id]);
            break;
            
        case 'crystals':
            $stmt = $pdo->prepare("UPDATE users SET crystals = crystals + ? WHERE id = ?");
            $stmt->execute([$reward['amount'], $user_id]);
            break;
            
        case 'tokens':
            $tokenCol = $reward['token_type'];
            // ホワイトリスト検証でSQLインジェクションを防止
            $allowed = ['normal_tokens', 'rare_tokens', 'unique_tokens', 'legend_tokens', 'epic_tokens', 'hero_tokens', 'mythic_tokens'];
            if (in_array($tokenCol, $allowed, true)) {
                $stmt = $pdo->prepare("UPDATE users SET {$tokenCol} = {$tokenCol} + ? WHERE id = ?");
                $stmt->execute([$reward['amount'], $user_id]);
            }
            break;
            
        case 'equipment':
            // 装備を作成
            $stmt = $pdo->prepare("
                INSERT INTO user_equipment (user_id, slot, name, rarity, buffs)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $reward['slot'], $reward['name'], $reward['rarity'], json_encode($reward['buffs'])]);
            break;
    }
}
