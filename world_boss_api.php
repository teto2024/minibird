<?php
// ===============================================
// world_boss_api.php
// ワールドボスシステムAPI
// みんなで倒す強敵、ダメージランキングで報酬分配
// ===============================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/battle_engine.php';

// ワールドボス定数
define('WORLD_BOSS_ATTACK_COOLDOWN_SECONDS', 60);   // 攻撃クールダウン（秒）
define('WORLD_BOSS_DAMAGE_VARIANCE', 0.2);          // ダメージの乱数幅（±20%）
define('WORLD_BOSS_MAX_PARTICIPANTS_REWARD', 100);   // 報酬対象の最大人数

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
 * ユーザーのレベルを取得
 */
function getWorldBossUserLevel($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COALESCE(level, 1) as level FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? (int)$result['level'] : 1;
}

/**
 * 期限切れのワールドボスを処理し、報酬を配布
 */
function processExpiredWorldBosses($pdo) {
    // 期限切れで未処理のボスを取得
    $stmt = $pdo->query("
        SELECT * FROM world_boss_instances 
        WHERE is_active = TRUE AND ends_at < NOW()
    ");
    $expiredBosses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($expiredBosses as $instance) {
        // ボスを終了
        $stmt = $pdo->prepare("UPDATE world_boss_instances SET is_active = FALSE WHERE id = ?");
        $stmt->execute([$instance['id']]);
        
        // 報酬配布
        distributeWorldBossRewards($pdo, $instance['id'], false);
    }
    
    return count($expiredBosses);
}

/**
 * ワールドボスの報酬を配布
 */
function distributeWorldBossRewards($pdo, $instanceId, $isDefeated) {
    // 既に配布済みかチェック
    $stmt = $pdo->prepare("SELECT rewards_distributed FROM world_boss_instances WHERE id = ?");
    $stmt->execute([$instanceId]);
    $instance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instance || $instance['rewards_distributed']) {
        return;
    }
    
    // ボス情報を取得
    $stmt = $pdo->prepare("
        SELECT wbi.*, wb.boss_key, wb.name as boss_name
        FROM world_boss_instances wbi
        JOIN world_bosses wb ON wbi.boss_id = wb.id
        WHERE wbi.id = ?
    ");
    $stmt->execute([$instanceId]);
    $bossInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bossInfo) {
        return;
    }
    
    // ダメージランキングを取得
    $stmt = $pdo->prepare("
        SELECT wbdl.*, u.handle, u.display_name
        FROM world_boss_damage_logs wbdl
        JOIN users u ON wbdl.user_id = u.id
        WHERE wbdl.instance_id = ?
        ORDER BY wbdl.damage_dealt DESC
        LIMIT ?
    ");
    $stmt->execute([$instanceId, WORLD_BOSS_MAX_PARTICIPANTS_REWARD]);
    $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 報酬設定を取得
    $stmt = $pdo->prepare("
        SELECT * FROM world_boss_rewards 
        WHERE boss_id = ?
        ORDER BY rank_start ASC
    ");
    $stmt->execute([$bossInfo['boss_id']]);
    $rewardConfigs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 各プレイヤーに報酬を配布
    foreach ($rankings as $rank => $player) {
        $rankNum = $rank + 1;
        
        // 該当する報酬設定を探す
        $reward = null;
        foreach ($rewardConfigs as $config) {
            if ($rankNum >= $config['rank_start'] && $rankNum <= $config['rank_end']) {
                $reward = $config;
                break;
            }
        }
        
        if (!$reward) {
            continue;
        }
        
        // 討伐できなかった場合は報酬を半減
        $rewardMultiplier = $isDefeated ? 1.0 : 0.5;
        
        $coins = (int)floor($reward['reward_coins'] * $rewardMultiplier);
        $crystals = (int)floor($reward['reward_crystals'] * $rewardMultiplier);
        $diamonds = (int)floor($reward['reward_diamonds'] * $rewardMultiplier);
        
        // 報酬を付与
        $stmt = $pdo->prepare("
            UPDATE users SET coins = coins + ?, crystals = crystals + ?, diamonds = diamonds + ?
            WHERE id = ?
        ");
        $stmt->execute([$coins, $crystals, $diamonds, $player['user_id']]);
        
        // 資源報酬を付与
        $resources = json_decode($reward['reward_resources'], true) ?: [];
        foreach ($resources as $res) {
            $amount = (int)floor($res['amount'] * $rewardMultiplier);
            $stmt = $pdo->prepare("
                INSERT INTO user_civilization_resources (user_id, resource_type_id, amount, unlocked, unlocked_at)
                VALUES (?, ?, ?, TRUE, NOW())
                ON DUPLICATE KEY UPDATE amount = amount + ?
            ");
            $stmt->execute([$player['user_id'], $res['resource_type_id'], $amount, $amount]);
        }
        
        // 兵士報酬を付与
        $troops = json_decode($reward['reward_troops'], true) ?: [];
        foreach ($troops as $trp) {
            $count = (int)floor($trp['count'] * $rewardMultiplier);
            $stmt = $pdo->prepare("
                INSERT INTO user_civilization_troops (user_id, troop_type_id, count)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE count = count + ?
            ");
            $stmt->execute([$player['user_id'], $trp['troop_type_id'], $count, $count]);
        }
        
        // 報酬ログを記録
        $stmt = $pdo->prepare("
            INSERT INTO world_boss_reward_logs 
            (instance_id, user_id, rank_position, total_damage, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $instanceId,
            $player['user_id'],
            $rankNum,
            $player['damage_dealt'],
            $coins,
            $crystals,
            $diamonds,
            json_encode($resources),
            json_encode($troops)
        ]);
    }
    
    // 配布済みフラグを設定
    $stmt = $pdo->prepare("UPDATE world_boss_instances SET rewards_distributed = TRUE WHERE id = ?");
    $stmt->execute([$instanceId]);
}

// ===============================================
// 召喚可能なワールドボス一覧を取得
// ===============================================
if ($action === 'get_bosses') {
    try {
        $userLevel = getWorldBossUserLevel($pdo, $me['id']);
        
        // 期限切れのボスを処理
        processExpiredWorldBosses($pdo);
        
        // 召喚可能なボスを取得（ユーザーレベル以下のボス）
        $stmt = $pdo->prepare("
            SELECT * FROM world_bosses 
            WHERE min_user_level <= ?
            ORDER BY boss_level ASC
        ");
        $stmt->execute([$userLevel]);
        $bosses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // アクティブなインスタンスを取得
        $stmt = $pdo->query("
            SELECT wbi.*, wb.name as boss_name, wb.icon as boss_icon, wb.boss_level,
                   u.handle as summoner_handle,
                   TIMESTAMPDIFF(SECOND, NOW(), wbi.ends_at) as seconds_remaining
            FROM world_boss_instances wbi
            JOIN world_bosses wb ON wbi.boss_id = wb.id
            JOIN users u ON wbi.summoner_user_id = u.id
            WHERE wbi.is_active = TRUE
            ORDER BY wbi.started_at DESC
        ");
        $activeInstances = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'bosses' => $bosses,
            'active_instances' => $activeInstances,
            'user_level' => $userLevel
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// ワールドボスを召喚
// ===============================================
if ($action === 'summon_boss') {
    $bossId = (int)($input['boss_id'] ?? 0);
    
    if ($bossId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ボスIDが不正です']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        $userLevel = getWorldBossUserLevel($pdo, $me['id']);
        
        // ボス情報を取得
        $stmt = $pdo->prepare("SELECT * FROM world_bosses WHERE id = ?");
        $stmt->execute([$bossId]);
        $boss = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$boss) {
            throw new Exception('ボスが見つかりません');
        }
        
        // レベル制限をチェック
        if ($userLevel < $boss['min_user_level']) {
            throw new Exception("このボスを召喚するにはレベル{$boss['min_user_level']}以上が必要です");
        }
        
        // このボスのアクティブインスタンスがあるかチェック
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM world_boss_instances 
            WHERE boss_id = ? AND is_active = TRUE
        ");
        $stmt->execute([$bossId]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new Exception('このボスは既に召喚されています');
        }
        
        // ダイヤモンドをチェック
        $stmt = $pdo->prepare("SELECT diamonds FROM users WHERE id = ?");
        $stmt->execute([$me['id']]);
        $userDiamonds = (int)$stmt->fetchColumn();
        
        if ($userDiamonds < $boss['summon_cost_diamonds']) {
            throw new Exception("ダイヤモンドが不足しています（必要: {$boss['summon_cost_diamonds']}）");
        }
        
        // ダイヤモンドを消費
        $stmt = $pdo->prepare("UPDATE users SET diamonds = diamonds - ? WHERE id = ?");
        $stmt->execute([$boss['summon_cost_diamonds'], $me['id']]);
        
        // ボスインスタンスを作成
        $endsAt = date('Y-m-d H:i:s', time() + ($boss['time_limit_hours'] * 3600));
        $stmt = $pdo->prepare("
            INSERT INTO world_boss_instances 
            (boss_id, summoner_user_id, current_health, max_health, ends_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $bossId,
            $me['id'],
            $boss['base_health'],
            $boss['base_health'],
            $endsAt
        ]);
        $instanceId = $pdo->lastInsertId();
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => "{$boss['icon']} {$boss['name']}を召喚しました！みんなで討伐しましょう！",
            'instance_id' => $instanceId,
            'boss' => [
                'id' => $boss['id'],
                'name' => $boss['name'],
                'icon' => $boss['icon'],
                'level' => $boss['boss_level'],
                'health' => $boss['base_health'],
                'attack' => $boss['base_attack'],
                'defense' => $boss['base_defense'],
                'time_limit_hours' => $boss['time_limit_hours'],
                'ends_at' => $endsAt
            ]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// ワールドボスの詳細を取得
// ===============================================
if ($action === 'get_boss_detail') {
    $instanceId = (int)($input['instance_id'] ?? 0);
    
    if ($instanceId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'インスタンスIDが不正です']);
        exit;
    }
    
    try {
        // インスタンス情報を取得
        $stmt = $pdo->prepare("
            SELECT wbi.*, wb.name as boss_name, wb.icon as boss_icon, wb.boss_level,
                   wb.base_attack, wb.base_defense, wb.description,
                   u.handle as summoner_handle, u.display_name as summoner_name,
                   TIMESTAMPDIFF(SECOND, NOW(), wbi.ends_at) as seconds_remaining
            FROM world_boss_instances wbi
            JOIN world_bosses wb ON wbi.boss_id = wb.id
            JOIN users u ON wbi.summoner_user_id = u.id
            WHERE wbi.id = ?
        ");
        $stmt->execute([$instanceId]);
        $instance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$instance) {
            throw new Exception('ボスインスタンスが見つかりません');
        }
        
        // ダメージランキングを取得
        $stmt = $pdo->prepare("
            SELECT wbdl.*, u.handle, u.display_name,
                   RANK() OVER (ORDER BY wbdl.damage_dealt DESC) as rank_position
            FROM world_boss_damage_logs wbdl
            JOIN users u ON wbdl.user_id = u.id
            WHERE wbdl.instance_id = ?
            ORDER BY wbdl.damage_dealt DESC
            LIMIT 50
        ");
        $stmt->execute([$instanceId]);
        $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 自分の情報を取得
        $stmt = $pdo->prepare("
            SELECT * FROM world_boss_damage_logs 
            WHERE instance_id = ? AND user_id = ?
        ");
        $stmt->execute([$instanceId, $me['id']]);
        $myStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 報酬情報を取得
        $stmt = $pdo->prepare("
            SELECT * FROM world_boss_rewards 
            WHERE boss_id = ?
            ORDER BY rank_start ASC
        ");
        $stmt->execute([$instance['boss_id']]);
        $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'instance' => $instance,
            'rankings' => $rankings,
            'my_stats' => $myStats,
            'rewards' => $rewards
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// ワールドボスを攻撃
// ===============================================
if ($action === 'attack_boss') {
    $instanceId = (int)($input['instance_id'] ?? 0);
    $troops = $input['troops'] ?? [];
    
    if ($instanceId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'インスタンスIDが不正です']);
        exit;
    }
    
    if (empty($troops)) {
        echo json_encode(['ok' => false, 'error' => '攻撃部隊を選択してください']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        // インスタンスを取得
        $stmt = $pdo->prepare("
            SELECT wbi.*, wb.name as boss_name, wb.icon as boss_icon, 
                   wb.base_attack, wb.base_defense
            FROM world_boss_instances wbi
            JOIN world_bosses wb ON wbi.boss_id = wb.id
            WHERE wbi.id = ? AND wbi.is_active = TRUE
            FOR UPDATE
        ");
        $stmt->execute([$instanceId]);
        $instance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$instance) {
            throw new Exception('アクティブなボスインスタンスが見つかりません');
        }
        
        // 期限切れチェック
        if (strtotime($instance['ends_at']) < time()) {
            // ボスを終了
            $stmt = $pdo->prepare("UPDATE world_boss_instances SET is_active = FALSE WHERE id = ?");
            $stmt->execute([$instanceId]);
            distributeWorldBossRewards($pdo, $instanceId, false);
            throw new Exception('討伐時間が終了しました');
        }
        
        // クールダウンチェック
        $stmt = $pdo->prepare("
            SELECT last_attack_at FROM world_boss_damage_logs 
            WHERE instance_id = ? AND user_id = ?
        ");
        $stmt->execute([$instanceId, $me['id']]);
        $lastAttack = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastAttack) {
            $lastAttackTime = strtotime($lastAttack['last_attack_at']);
            $cooldownRemaining = WORLD_BOSS_ATTACK_COOLDOWN_SECONDS - (time() - $lastAttackTime);
            if ($cooldownRemaining > 0) {
                throw new Exception("クールダウン中です（残り{$cooldownRemaining}秒）");
            }
        }
        
        // 攻撃部隊を検証
        $attackerTroops = [];
        foreach ($troops as $troop) {
            $troopTypeId = (int)$troop['troop_type_id'];
            $count = (int)$troop['count'];
            
            if ($count <= 0) continue;
            
            // 所有兵士数を確認
            $stmt = $pdo->prepare("
                SELECT count FROM user_civilization_troops
                WHERE user_id = ? AND troop_type_id = ?
            ");
            $stmt->execute([$me['id'], $troopTypeId]);
            $ownedCount = (int)$stmt->fetchColumn();
            
            if ($ownedCount < $count) {
                throw new Exception('兵士が不足しています');
            }
            
            $attackerTroops[] = [
                'troop_type_id' => $troopTypeId,
                'count' => $count
            ];
        }
        
        if (empty($attackerTroops)) {
            throw new Exception('攻撃部隊を選択してください');
        }
        
        // 装備バフを取得
        $equipmentBuffs = ['attack' => 0, 'armor' => 0, 'health' => 0];
        $stmt = $pdo->prepare("
            SELECT buffs FROM user_equipment 
            WHERE user_id = ? AND is_equipped = 1
        ");
        $stmt->execute([$me['id']]);
        $equippedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($equippedItems as $item) {
            $buffs = json_decode($item['buffs'], true) ?: [];
            foreach ($equipmentBuffs as $key => $value) {
                if (isset($buffs[$key])) {
                    $equipmentBuffs[$key] += (float)$buffs[$key];
                }
            }
        }
        
        // バトルユニットを準備
        $attackerUnit = prepareBattleUnit($attackerTroops, $equipmentBuffs, $pdo);
        
        // ワールドボスへのダメージ計算（乱数変動あり）
        $baseDamage = $attackerUnit['attack'];
        $bossDefense = (int)$instance['base_defense'];
        
        // 防御によるダメージ軽減
        $defenseReduction = min(0.75, $bossDefense / 200);
        $damage = (int)floor($baseDamage * (1 - $defenseReduction));
        
        // 乱数変動を適用
        $variance = 1 + (mt_rand(-100, 100) / 100) * WORLD_BOSS_DAMAGE_VARIANCE;
        $damage = (int)floor($damage * $variance);
        
        // クリティカル判定
        $isCritical = mt_rand(1, 100) <= 10;
        if ($isCritical) {
            $damage = (int)floor($damage * 1.5);
        }
        
        $damage = max(1, $damage);
        
        // ボスのHPを減少
        $newHealth = max(0, $instance['current_health'] - $damage);
        $stmt = $pdo->prepare("UPDATE world_boss_instances SET current_health = ? WHERE id = ?");
        $stmt->execute([$newHealth, $instanceId]);
        
        $isDefeated = $newHealth <= 0;
        
        // ダメージログを更新
        $stmt = $pdo->prepare("
            INSERT INTO world_boss_damage_logs (instance_id, user_id, damage_dealt, attack_count, last_attack_at)
            VALUES (?, ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE 
                damage_dealt = damage_dealt + VALUES(damage_dealt),
                attack_count = attack_count + 1,
                last_attack_at = NOW()
        ");
        $stmt->execute([$instanceId, $me['id'], $damage]);
        
        // 討伐完了時の処理
        if ($isDefeated) {
            $stmt = $pdo->prepare("
                UPDATE world_boss_instances 
                SET is_active = FALSE, defeated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$instanceId]);
            
            // 報酬配布
            distributeWorldBossRewards($pdo, $instanceId, true);
        }
        
        $pdo->commit();
        
        $critText = $isCritical ? 'クリティカル！' : '';
        $message = $isDefeated 
            ? "{$instance['boss_icon']} {$instance['boss_name']}を討伐しました！報酬が配布されます！"
            : "{$critText}{$instance['boss_icon']} {$instance['boss_name']}に{$damage}ダメージ！";
        
        echo json_encode([
            'ok' => true,
            'result' => $isDefeated ? 'defeated' : 'hit',
            'message' => $message,
            'damage' => $damage,
            'is_critical' => $isCritical,
            'is_defeated' => $isDefeated,
            'boss_remaining_health' => $newHealth,
            'boss_max_health' => $instance['max_health'],
            'health_percentage' => $instance['max_health'] > 0 ? round($newHealth / $instance['max_health'] * 100, 2) : 0
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// 自分の報酬履歴を取得
// ===============================================
if ($action === 'get_my_rewards') {
    try {
        $stmt = $pdo->prepare("
            SELECT wbrl.*, wb.name as boss_name, wb.icon as boss_icon
            FROM world_boss_reward_logs wbrl
            JOIN world_boss_instances wbi ON wbrl.instance_id = wbi.id
            JOIN world_bosses wb ON wbi.boss_id = wb.id
            WHERE wbrl.user_id = ?
            ORDER BY wbrl.distributed_at DESC
            LIMIT 50
        ");
        $stmt->execute([$me['id']]);
        $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'rewards' => $rewards
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'invalid_action']);
