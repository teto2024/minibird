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

// ガチャを引く
if ($action === 'pull') {
    $type = $input['type'] ?? 'normal';
    
    $pdo->beginTransaction();
    try {
        // コスト確認
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$me['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($type === 'normal') {
            $cost_coins = 1000;
            $cost_crystals = 0;
            if ($user['coins'] < $cost_coins) {
                throw new Exception('コインが不足しています');
            }
        } else {
            $cost_coins = 0;
            $cost_crystals = 100;
            if ($user['crystals'] < $cost_crystals) {
                throw new Exception('クリスタルが不足しています');
            }
        }
        
        // コスト消費
        $stmt = $pdo->prepare("UPDATE users SET coins = coins - ?, crystals = crystals - ? WHERE id = ?");
        $stmt->execute([$cost_coins, $cost_crystals, $me['id']]);
        
        // 報酬決定
        $reward = determineGachaReward($type, $pdo, $me['id']);
        
        // 報酬付与
        applyGachaReward($reward, $pdo, $me['id']);
        
        // 履歴記録
        $stmt = $pdo->prepare("
            INSERT INTO hero_gacha_history (user_id, gacha_type, reward_type, reward_data, cost_coins, cost_crystals)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$me['id'], $type, $reward['type'], json_encode($reward), $cost_coins, $cost_crystals]);
        
        $pdo->commit();
        
        // 更新後の残高を取得
        $stmt = $pdo->prepare("SELECT coins, crystals FROM users WHERE id = ?");
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
        
        if ($type === 'normal') {
            $cost_coins = 9000; // 10連で10%割引（通常10,000）
            $cost_crystals = 0;
            if ($user['coins'] < $cost_coins) {
                throw new Exception('コインが不足しています（必要: ' . number_format($cost_coins) . '）');
            }
        } else {
            $cost_coins = 0;
            $cost_crystals = 900; // 10連で10%割引（通常1,000）
            if ($user['crystals'] < $cost_crystals) {
                throw new Exception('クリスタルが不足しています（必要: ' . number_format($cost_crystals) . '）');
            }
        }
        
        // コスト消費
        $stmt = $pdo->prepare("UPDATE users SET coins = coins - ?, crystals = crystals - ? WHERE id = ?");
        $stmt->execute([$cost_coins, $cost_crystals, $me['id']]);
        
        // 10回分の報酬を決定・付与
        $rewards = [];
        for ($i = 0; $i < 10; $i++) {
            $reward = determineGachaReward($type, $pdo, $me['id']);
            applyGachaReward($reward, $pdo, $me['id']);
            $rewards[] = $reward;
            
            // 履歴記録
            // 10連ガチャの場合、総コストを各エントリに1/10ずつ記録（分析しやすくするため）
            $stmt = $pdo->prepare("
                INSERT INTO hero_gacha_history (user_id, gacha_type, reward_type, reward_data, cost_coins, cost_crystals)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $individual_coins = (int)floor($cost_coins / 10);
            $individual_crystals = (int)floor($cost_crystals / 10);
            $stmt->execute([$me['id'], $type . '_10', $reward['type'], json_encode($reward), $individual_coins, $individual_crystals]);
        }
        
        $pdo->commit();
        
        // 更新後の残高を取得
        $stmt = $pdo->prepare("SELECT coins, crystals FROM users WHERE id = ?");
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
    
    // 簡易バフ生成
    $buffs = ['attack' => mt_rand(5, 20), 'armor' => mt_rand(3, 15)];
    
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
