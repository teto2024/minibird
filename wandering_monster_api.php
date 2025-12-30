<?php
// ===============================================
// wandering_monster_api.php
// 放浪モンスターシステムAPI
// ===============================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/battle_engine.php';
require_once __DIR__ . '/quest_helpers.php';

// 放浪モンスター定数
define('MONSTER_ENCOUNTER_COOLDOWN_MINUTES', 30);   // 次の遭遇までのクールダウン（分）
define('MONSTER_BATTLE_COST_STAMINA', 10);          // 戦闘に必要なスタミナ（未実装の場合は無視）
define('MONSTER_DAMAGE_VARIANCE', 0.2);             // ダメージの乱数幅（±20%）
define('MONSTER_CRITICAL_CHANCE', 10);              // クリティカル率（%）
define('MONSTER_CRITICAL_MULTIPLIER', 1.5);         // クリティカルダメージ倍率
define('MONSTER_DEATH_RATE', 0.1);                  // 戦死率（10%）
define('MONSTER_WOUNDED_RATE', 0.3);                // 負傷兵発生率（30%）

// 出撃兵士数上限システム定数
define('MONSTER_BASE_TROOP_DEPLOYMENT_LIMIT', 100); // 基本出撃兵士数上限

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
 * ユーザーのレベルを取得（経験値ベースまたはユーザーテーブルから）
 */
function getUserLevel($pdo, $userId) {
    // usersテーブルのuser_levelを確認
    $stmt = $pdo->prepare("SELECT COALESCE(user_level, 1) as user_level FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? (int)$result['user_level'] : 1;
}

/**
 * 出撃兵士数上限を計算するヘルパー関数
 * 
 * @param PDO $pdo データベース接続
 * @param int $userId ユーザーID
 * @return array ['base_limit' => int, 'building_bonus' => int, 'total_limit' => int]
 */
function calculateMonsterTroopDeploymentLimit($pdo, $userId) {
    // 軍事建物からの出撃上限ボーナスを取得
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(COALESCE(bt.troop_deployment_bonus, 0) * ucb.level), 0) as total_bonus
        FROM user_civilization_buildings ucb
        JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
        WHERE ucb.user_id = ? 
          AND ucb.is_constructing = FALSE
          AND COALESCE(bt.troop_deployment_bonus, 0) > 0
    ");
    $stmt->execute([$userId]);
    $buildingBonus = (int)$stmt->fetchColumn();
    
    $baseLimit = MONSTER_BASE_TROOP_DEPLOYMENT_LIMIT;
    $totalLimit = $baseLimit + $buildingBonus;
    
    return [
        'base_limit' => $baseLimit,
        'building_bonus' => $buildingBonus,
        'total_limit' => $totalLimit
    ];
}

/**
 * モンスターのステータスをユーザーレベルに応じて計算
 */
function calculateMonsterStats($monster, $userLevel) {
    $levelDiff = max(0, $userLevel - $monster['min_level']);
    $scalingFactor = pow((float)$monster['level_scaling'], $levelDiff);
    
    return [
        'attack' => (int)floor($monster['base_attack'] * $scalingFactor),
        'defense' => (int)floor($monster['base_defense'] * $scalingFactor),
        'health' => (int)floor($monster['base_health'] * $scalingFactor),
        'monster_level' => $monster['min_level'] + $levelDiff
    ];
}

/**
 * ドロップ報酬を計算
 */
function calculateDropRewards($pdo, $monsterId, $userLevel) {
    $rewards = [
        'resources' => [],
        'troops' => []
    ];
    
    // ドロップ品を取得
    $stmt = $pdo->prepare("
        SELECT wmd.*, rt.resource_key, rt.name as resource_name, rt.icon as resource_icon,
               tt.troop_key, tt.name as troop_name, tt.icon as troop_icon
        FROM wandering_monster_drops wmd
        LEFT JOIN civilization_resource_types rt ON wmd.resource_type_id = rt.id
        LEFT JOIN civilization_troop_types tt ON wmd.troop_type_id = tt.id
        WHERE wmd.monster_id = ?
    ");
    $stmt->execute([$monsterId]);
    $drops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($drops as $drop) {
        // 確率判定
        if (mt_rand(1, 10000) > $drop['drop_chance'] * 100) {
            continue;
        }
        
        // ドロップ量を計算（レベルに応じてボーナス）
        $levelBonus = 1 + ($userLevel / 100);
        $amount = mt_rand($drop['amount_min'], $drop['amount_max']);
        $amount = (int)floor($amount * $levelBonus);
        
        if ($drop['resource_type_id']) {
            $rewards['resources'][] = [
                'resource_type_id' => $drop['resource_type_id'],
                'resource_key' => $drop['resource_key'],
                'name' => $drop['resource_name'],
                'icon' => $drop['resource_icon'],
                'amount' => $amount
            ];
        } elseif ($drop['troop_type_id']) {
            $rewards['troops'][] = [
                'troop_type_id' => $drop['troop_type_id'],
                'troop_key' => $drop['troop_key'],
                'name' => $drop['troop_name'],
                'icon' => $drop['troop_icon'],
                'count' => $amount
            ];
        }
    }
    
    return $rewards;
}

/**
 * コイン・クリスタル・ダイヤモンド報酬を計算
 */
function calculateCurrencyRewards($monster, $userLevel) {
    $levelBonus = 1 + ($userLevel / 50);
    
    return [
        'coins' => (int)floor(mt_rand($monster['reward_coins_min'], $monster['reward_coins_max']) * $levelBonus),
        'crystals' => (int)floor(mt_rand($monster['reward_crystals_min'], $monster['reward_crystals_max']) * $levelBonus),
        'diamonds' => (int)floor(mt_rand($monster['reward_diamonds_min'], $monster['reward_diamonds_max']) * $levelBonus)
    ];
}

// ===============================================
// 放浪モンスター一覧を取得
// ===============================================
if ($action === 'get_monsters') {
    try {
        $userLevel = getUserLevel($pdo, $me['id']);
        
        $stmt = $pdo->prepare("
            SELECT * FROM wandering_monsters 
            WHERE min_level <= ? AND max_level >= ?
            ORDER BY min_level ASC
        ");
        $stmt->execute([$userLevel, $userLevel]);
        $monsters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 各モンスターにレベル調整されたステータスを追加
        foreach ($monsters as &$monster) {
            $stats = calculateMonsterStats($monster, $userLevel);
            $monster['scaled_attack'] = $stats['attack'];
            $monster['scaled_defense'] = $stats['defense'];
            $monster['scaled_health'] = $stats['health'];
            $monster['monster_level'] = $stats['monster_level'];
        }
        
        echo json_encode([
            'ok' => true,
            'monsters' => $monsters,
            'user_level' => $userLevel
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// モンスターに遭遇する（新しい遭遇を作成）
// ===============================================
if ($action === 'encounter_monster') {
    $monsterId = (int)($input['monster_id'] ?? 0);
    
    if ($monsterId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'モンスターIDが不正です']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        $userLevel = getUserLevel($pdo, $me['id']);
        
        // モンスターを取得
        $stmt = $pdo->prepare("SELECT * FROM wandering_monsters WHERE id = ?");
        $stmt->execute([$monsterId]);
        $monster = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$monster) {
            throw new Exception('モンスターが見つかりません');
        }
        
        // レベル範囲をチェック
        if ($userLevel < $monster['min_level'] || $userLevel > $monster['max_level']) {
            throw new Exception('このモンスターはあなたのレベルでは遭遇できません');
        }
        
        // 既にアクティブな遭遇がないかチェック
        $stmt = $pdo->prepare("
            SELECT id FROM user_wandering_monster_encounters 
            WHERE user_id = ? AND is_active = TRUE
        ");
        $stmt->execute([$me['id']]);
        if ($stmt->fetch()) {
            throw new Exception('すでにモンスターと遭遇中です。先に倒すか撤退してください。');
        }
        
        // ステータスを計算
        $stats = calculateMonsterStats($monster, $userLevel);
        
        // 遭遇を作成
        $stmt = $pdo->prepare("
            INSERT INTO user_wandering_monster_encounters 
            (user_id, monster_id, monster_level, current_health, max_health, attack_power, defense_power)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $me['id'],
            $monsterId,
            $stats['monster_level'],
            $stats['health'],
            $stats['health'],
            $stats['attack'],
            $stats['defense']
        ]);
        $encounterId = $pdo->lastInsertId();
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => "{$monster['icon']} {$monster['name']}に遭遇しました！",
            'encounter_id' => $encounterId,
            'monster' => [
                'id' => $monster['id'],
                'name' => $monster['name'],
                'icon' => $monster['icon'],
                'level' => $stats['monster_level'],
                'attack' => $stats['attack'],
                'defense' => $stats['defense'],
                'health' => $stats['health'],
                'max_health' => $stats['health']
            ]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// アクティブな遭遇を取得
// ===============================================
if ($action === 'get_active_encounter') {
    try {
        $stmt = $pdo->prepare("
            SELECT uwme.*, wm.name, wm.icon, wm.description,
                   wm.reward_coins_min, wm.reward_coins_max,
                   wm.reward_crystals_min, wm.reward_crystals_max,
                   wm.reward_diamonds_min, wm.reward_diamonds_max
            FROM user_wandering_monster_encounters uwme
            JOIN wandering_monsters wm ON uwme.monster_id = wm.id
            WHERE uwme.user_id = ? AND uwme.is_active = TRUE
            LIMIT 1
        ");
        $stmt->execute([$me['id']]);
        $encounter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'has_encounter' => $encounter !== false,
            'encounter' => $encounter ?: null
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// モンスターと戦闘する
// ===============================================
if ($action === 'attack_monster') {
    $encounterId = (int)($input['encounter_id'] ?? 0);
    $troops = $input['troops'] ?? []; // [{troop_type_id, count}, ...]
    
    if ($encounterId <= 0) {
        echo json_encode(['ok' => false, 'error' => '遭遇IDが不正です']);
        exit;
    }
    
    if (empty($troops)) {
        echo json_encode(['ok' => false, 'error' => '攻撃部隊を選択してください']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        // 遭遇を取得
        $stmt = $pdo->prepare("
            SELECT uwme.*, wm.name, wm.icon, wm.monster_key, wm.soldier_drop_chance,
                   wm.reward_coins_min, wm.reward_coins_max,
                   wm.reward_crystals_min, wm.reward_crystals_max,
                   wm.reward_diamonds_min, wm.reward_diamonds_max
            FROM user_wandering_monster_encounters uwme
            JOIN wandering_monsters wm ON uwme.monster_id = wm.id
            WHERE uwme.id = ? AND uwme.user_id = ? AND uwme.is_active = TRUE
        ");
        $stmt->execute([$encounterId, $me['id']]);
        $encounter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$encounter) {
            throw new Exception('遭遇が見つかりません');
        }
        
        $userLevel = getUserLevel($pdo, $me['id']);
        
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
        
        // 出撃兵士数上限チェック
        $totalTroopCount = 0;
        foreach ($attackerTroops as $troop) {
            $totalTroopCount += $troop['count'];
        }
        $deploymentLimit = calculateMonsterTroopDeploymentLimit($pdo, $me['id']);
        if ($totalTroopCount > $deploymentLimit['total_limit']) {
            throw new Exception('出撃兵士数の上限（' . $deploymentLimit['total_limit'] . '人）を超えています。司令部や軍事センターを建設すると上限が増加します。');
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
        
        // 攻撃側にヒーロースキルを適用
        $attackerHero = getUserBattleHero($pdo, $me['id'], 'wandering_monster');
        if ($attackerHero) {
            $skillType1 = (int)($attackerHero['skill_1_type'] ?? 1);
            $skillType2 = isset($attackerHero['skill_2_type']) ? (int)$attackerHero['skill_2_type'] : null;
            $attackerUnit = applyHeroSkillsToUnit($attackerUnit, $attackerHero, $skillType1, $skillType2);
        }
        
        // モンスターユニットを準備
        $monsterUnit = [
            'attack' => (int)$encounter['attack_power'],
            'armor' => (int)$encounter['defense_power'],
            'max_health' => (int)$encounter['max_health'],
            'current_health' => (int)$encounter['current_health'],
            'troops' => [
                [
                    'troop_type_id' => 0,
                    'name' => $encounter['name'],
                    'icon' => $encounter['icon'],
                    'count' => 1,
                    'attack' => (int)$encounter['attack_power'],
                    'defense' => (int)$encounter['defense_power'],
                    'health' => (int)$encounter['current_health'],
                    'category' => 'infantry'
                ]
            ],
            'skills' => [],
            'equipment_buffs' => ['attack' => 0, 'armor' => 0, 'health' => 0],
            'active_effects' => [],
            'is_frozen' => false,
            'is_stunned' => false,
            'extra_attacks' => 0
        ];
        
        // バトル実行
        $battleResult = executeTurnBattle($attackerUnit, $monsterUnit);
        $playerWins = $battleResult['attacker_wins'];
        
        // 攻撃側の損失を計算
        $attackerLosses = [];
        $attackerWounded = [];
        $attackerHpLossRate = 1 - ($battleResult['attacker_final_hp'] / max(1, $battleResult['attacker_max_hp']));
        
        foreach ($attackerUnit['troops'] as $troop) {
            $troopTypeId = $troop['troop_type_id'];
            $count = $troop['count'];
            
            $totalLossCount = (int)floor($count * $attackerHpLossRate);
            $deaths = (int)floor($totalLossCount * MONSTER_DEATH_RATE);
            $wounded = $totalLossCount - $deaths;
            
            if ($deaths > 0) {
                $attackerLosses[$troopTypeId] = $deaths;
                $stmt = $pdo->prepare("
                    UPDATE user_civilization_troops SET count = count - ?
                    WHERE user_id = ? AND troop_type_id = ?
                ");
                $stmt->execute([$deaths, $me['id'], $troopTypeId]);
            }
            if ($wounded > 0) {
                $attackerWounded[$troopTypeId] = $wounded;
                $stmt = $pdo->prepare("
                    INSERT INTO user_civilization_wounded_troops (user_id, troop_type_id, count)
                    VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE count = count + ?
                ");
                $stmt->execute([$me['id'], $troopTypeId, $wounded, $wounded]);
                
                // 兵士から減少
                $stmt = $pdo->prepare("
                    UPDATE user_civilization_troops SET count = count - ?
                    WHERE user_id = ? AND troop_type_id = ?
                ");
                $stmt->execute([$wounded, $me['id'], $troopTypeId]);
            }
        }
        
        // 報酬の初期化
        $rewardCoins = 0;
        $rewardCrystals = 0;
        $rewardDiamonds = 0;
        $rewardResources = [];
        $rewardTroops = [];
        $isDefeated = false;
        $damageDealt = max(0, (int)$encounter['current_health'] - $battleResult['defender_final_hp']);
        
        if ($playerWins) {
            // モンスターを倒した
            $isDefeated = true;
            
            // 通貨報酬を計算
            $currencyRewards = calculateCurrencyRewards($encounter, $userLevel);
            $rewardCoins = $currencyRewards['coins'];
            $rewardCrystals = $currencyRewards['crystals'];
            $rewardDiamonds = $currencyRewards['diamonds'];
            
            // ドロップ報酬を計算
            $dropRewards = calculateDropRewards($pdo, $encounter['monster_id'], $userLevel);
            $rewardResources = $dropRewards['resources'];
            $rewardTroops = $dropRewards['troops'];
            
            // 報酬を付与
            $stmt = $pdo->prepare("
                UPDATE users SET coins = coins + ?, crystals = crystals + ?, diamonds = diamonds + ?
                WHERE id = ?
            ");
            $stmt->execute([$rewardCoins, $rewardCrystals, $rewardDiamonds, $me['id']]);
            
            // 資源報酬を付与
            foreach ($rewardResources as $res) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_civilization_resources (user_id, resource_type_id, amount, unlocked, unlocked_at)
                    VALUES (?, ?, ?, TRUE, NOW())
                    ON DUPLICATE KEY UPDATE amount = amount + ?
                ");
                $stmt->execute([$me['id'], $res['resource_type_id'], $res['amount'], $res['amount']]);
            }
            
            // 兵士報酬を付与
            foreach ($rewardTroops as $trp) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_civilization_troops (user_id, troop_type_id, count)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE count = count + ?
                ");
                $stmt->execute([$me['id'], $trp['troop_type_id'], $trp['count'], $trp['count']]);
            }
            
            // 遭遇を終了
            $stmt = $pdo->prepare("
                UPDATE user_wandering_monster_encounters 
                SET is_active = FALSE, defeated_at = NOW(), current_health = 0
                WHERE id = ?
            ");
            $stmt->execute([$encounterId]);
        } else {
            // モンスターのHPを更新
            $newHealth = max(0, $battleResult['defender_final_hp']);
            $stmt = $pdo->prepare("
                UPDATE user_wandering_monster_encounters SET current_health = ?
                WHERE id = ?
            ");
            $stmt->execute([$newHealth, $encounterId]);
        }
        
        // バトルログを記録
        $stmt = $pdo->prepare("
            INSERT INTO wandering_monster_battle_logs 
            (user_id, encounter_id, monster_id, damage_dealt, is_defeated, 
             reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $me['id'],
            $encounterId,
            $encounter['monster_id'],
            $damageDealt,
            $isDefeated ? 1 : 0,
            $rewardCoins,
            $rewardCrystals,
            $rewardDiamonds,
            json_encode($rewardResources),
            json_encode($rewardTroops)
        ]);
        $battleLogId = $pdo->lastInsertId();
        
        // 詳細なバトルターンログを保存
        if (!empty($battleResult['turn_logs'])) {
            saveWanderingMonsterBattleTurnLogs($pdo, $battleLogId, $battleResult['turn_logs']);
        }
        
        // モンスター討伐時にクエスト進捗を更新
        if ($isDefeated) {
            $monsterKey = $encounter['monster_key'] ?? null;
            updateCivilizationQuestProgressHelper($pdo, $me['id'], 'defeat_monster', $monsterKey, 1);
        }
        
        $pdo->commit();
        
        $message = $isDefeated 
            ? "{$encounter['icon']} {$encounter['name']}を倒しました！" 
            : "{$encounter['icon']} {$encounter['name']}に{$damageDealt}ダメージを与えました！";
        
        echo json_encode([
            'ok' => true,
            'result' => $isDefeated ? 'victory' : 'continue',
            'message' => $message,
            'is_defeated' => $isDefeated,
            'damage_dealt' => $damageDealt,
            'monster_remaining_health' => $isDefeated ? 0 : $battleResult['defender_final_hp'],
            'battle_result' => [
                'total_turns' => $battleResult['total_turns'],
                'attacker_final_hp' => $battleResult['attacker_final_hp'],
                'defender_final_hp' => $battleResult['defender_final_hp'],
                'attacker_max_hp' => $battleResult['attacker_max_hp'],
                'defender_max_hp' => $battleResult['defender_max_hp']
            ],
            'turn_logs' => $battleResult['turn_logs'] ?? [],
            'rewards' => [
                'coins' => $rewardCoins,
                'crystals' => $rewardCrystals,
                'diamonds' => $rewardDiamonds,
                'resources' => $rewardResources,
                'troops' => $rewardTroops
            ],
            'losses' => $attackerLosses,
            'wounded' => $attackerWounded
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// 遭遇から撤退
// ===============================================
if ($action === 'retreat') {
    $encounterId = (int)($input['encounter_id'] ?? 0);
    
    $pdo->beginTransaction();
    try {
        // 遭遇を取得
        $stmt = $pdo->prepare("
            SELECT * FROM user_wandering_monster_encounters 
            WHERE id = ? AND user_id = ? AND is_active = TRUE
        ");
        $stmt->execute([$encounterId, $me['id']]);
        $encounter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$encounter) {
            throw new Exception('遭遇が見つかりません');
        }
        
        // 遭遇を終了
        $stmt = $pdo->prepare("
            UPDATE user_wandering_monster_encounters 
            SET is_active = FALSE
            WHERE id = ?
        ");
        $stmt->execute([$encounterId]);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => '撤退しました'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// 討伐履歴を取得
// ===============================================
if ($action === 'get_battle_history') {
    try {
        $stmt = $pdo->prepare("
            SELECT wmbl.*, wm.name, wm.icon
            FROM wandering_monster_battle_logs wmbl
            JOIN wandering_monsters wm ON wmbl.monster_id = wm.id
            WHERE wmbl.user_id = ?
            ORDER BY wmbl.battle_at DESC
            LIMIT 50
        ");
        $stmt->execute([$me['id']]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'battle_history' => $history
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// 特定のバトルログの詳細を取得（ターンログ含む）
// ===============================================
if ($action === 'get_battle_detail') {
    $battleLogId = (int)($input['battle_log_id'] ?? 0);
    
    if ($battleLogId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'バトルログIDが不正です']);
        exit;
    }
    
    try {
        // バトルログ本体を取得
        $stmt = $pdo->prepare("
            SELECT wmbl.*, wm.name, wm.icon, wm.description
            FROM wandering_monster_battle_logs wmbl
            JOIN wandering_monsters wm ON wmbl.monster_id = wm.id
            WHERE wmbl.id = ? AND wmbl.user_id = ?
        ");
        $stmt->execute([$battleLogId, $me['id']]);
        $battleLog = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$battleLog) {
            throw new Exception('バトルログが見つかりません');
        }
        
        // ターンログを取得
        $stmt = $pdo->prepare("
            SELECT * FROM wandering_monster_turn_logs
            WHERE battle_log_id = ?
            ORDER BY turn_number ASC
        ");
        $stmt->execute([$battleLogId]);
        $turnLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'battle_log' => $battleLog,
            'turn_logs' => $turnLogs
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'invalid_action']);
