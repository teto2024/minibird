<?php
// ===============================================
// civilization_api.php
// 文明育成システムAPI
// ===============================================

require_once __DIR__ . '/config.php';

// 文明システム設定定数
define('CIV_COINS_TO_RESEARCH_RATIO', 10);     // 研究ポイント1あたりのコイン
define('CIV_RESOURCE_BONUS_RATIO', 10);        // 資源ボーナス1あたりのコイン
define('CIV_ATTACKER_BONUS', 1.1);             // 攻撃側のボーナス倍率
define('CIV_LOOT_RESOURCE_RATE', 0.1);         // 略奪時の資源比率（10%）
define('CIV_LOOT_COINS_RATE', 0.05);           // 略奪時のコイン比率（5%）
define('CIV_INSTANT_BUILDING_MIN_COST', 5);    // 建物即完了の最低クリスタルコスト
define('CIV_INSTANT_RESEARCH_MIN_COST', 3);    // 研究即完了の最低クリスタルコスト
define('CIV_INSTANT_SECONDS_PER_CRYSTAL', 60); // クリスタル1個あたりの秒数

// 資源価値の定義（市場交換レート計算用）
// 値が高いほど価値が高い資源
$RESOURCE_VALUES = [
    'food' => 1.0,       // 基本資源
    'wood' => 1.0,       // 基本資源
    'stone' => 1.2,      // やや希少
    'bronze' => 1.5,     // 中程度の価値
    'iron' => 2.0,       // 価値が高い
    'gold' => 3.0,       // 高価値
    'knowledge' => 2.5,  // 価値が高い
    'oil' => 3.5,        // かなり高価値
    'crystal' => 4.0,    // 非常に高価値
    'mana' => 4.5,       // 非常に高価値
    'uranium' => 5.0,    // 最高価値
    'diamond' => 6.0,    // 最高価値
    // 追加資源
    'sulfur' => 2.0,
    'gems' => 4.0,
    'cloth' => 1.5,
    'marble' => 2.5,
    'horses' => 2.0,
    'coal' => 2.0,
    'glass' => 2.5,
    'spices' => 3.0
];

header('Content-Type: application/json');

$me = user();
if (!$me) {
    echo json_encode(['ok' => false, 'error' => 'login_required']);
    exit;
}

$pdo = db();
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

// ユーザーの文明を取得または作成
function getUserCivilization($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM user_civilizations WHERE user_id = ?");
    $stmt->execute([$userId]);
    $civ = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$civ) {
        // 新規文明作成
        $stmt = $pdo->prepare("
            INSERT INTO user_civilizations (user_id, civilization_name, current_era_id, population, max_population)
            VALUES (?, '新しい文明', 1, 0, 10)
        ");
        $stmt->execute([$userId]);
        
        // 初期資源をアンロック（food, wood, stone）
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO user_civilization_resources (user_id, resource_type_id, amount, unlocked, unlocked_at)
            SELECT ?, id, 100, TRUE, NOW()
            FROM civilization_resource_types 
            WHERE unlock_order = 0
        ");
        $stmt->execute([$userId]);
        
        // 再取得
        $stmt = $pdo->prepare("SELECT * FROM user_civilizations WHERE user_id = ?");
        $stmt->execute([$userId]);
        $civ = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return $civ;
}

// 総合軍事力を計算するヘルパー関数
function calculateTotalMilitaryPower($pdo, $userId) {
    // 建物からの軍事力
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(bt.military_power * ucb.level), 0) as building_power
        FROM user_civilization_buildings ucb
        JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
        WHERE ucb.user_id = ? AND ucb.is_constructing = FALSE
    ");
    $stmt->execute([$userId]);
    $buildingPower = (int)$stmt->fetchColumn();
    
    // 兵士からの軍事力（攻撃力 + 防御力の半分）
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM((tt.attack_power + FLOOR(tt.defense_power / 2)) * uct.count), 0) as troop_power
        FROM user_civilization_troops uct
        JOIN civilization_troop_types tt ON uct.troop_type_id = tt.id
        WHERE uct.user_id = ?
    ");
    $stmt->execute([$userId]);
    $troopPower = (int)$stmt->fetchColumn();
    
    return [
        'building_power' => $buildingPower,
        'troop_power' => $troopPower,
        'total_power' => $buildingPower + $troopPower
    ];
}

// 資源を収集（時間経過分）
function collectResources($pdo, $userId) {
    $civ = getUserCivilization($pdo, $userId);
    $lastCollection = strtotime($civ['last_resource_collection']);
    $now = time();
    $hoursPassed = ($now - $lastCollection) / 3600;
    
    // 完了した建設を確認し、住宅の場合は人口も増やす
    $stmt = $pdo->prepare("
        SELECT ucb.id, bt.population_capacity, bt.name, ucb.level
        FROM user_civilization_buildings ucb
        JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
        WHERE ucb.user_id = ? AND ucb.is_constructing = TRUE AND ucb.construction_completes_at <= NOW()
    ");
    $stmt->execute([$userId]);
    $completedBuildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $populationIncrease = 0;
    foreach ($completedBuildings as $building) {
        // 住宅建物の場合、人口増加
        if ($building['population_capacity'] > 0) {
            $populationIncrease += $building['population_capacity'] * $building['level'];
        }
    }
    
    // 建設完了をマーク
    $stmt = $pdo->prepare("
        UPDATE user_civilization_buildings 
        SET is_constructing = FALSE 
        WHERE user_id = ? AND is_constructing = TRUE AND construction_completes_at <= NOW()
    ");
    $stmt->execute([$userId]);
    
    // 人口を増加
    if ($populationIncrease > 0) {
        $stmt = $pdo->prepare("
            UPDATE user_civilizations 
            SET population = population + ?,
                max_population = max_population + ?
            WHERE user_id = ?
        ");
        $stmt->execute([$populationIncrease, $populationIncrease, $userId]);
    }
    
    // 時間経過が少なすぎる場合は資源収集をスキップ（約36秒未満）
    if ($hoursPassed < 0.01) {
        return [];
    }
    
    // 生産建物からの資源を計算
    $stmt = $pdo->prepare("
        SELECT bt.produces_resource_id, bt.production_rate, SUM(ucb.level) as total_level, COUNT(*) as building_count
        FROM user_civilization_buildings ucb
        JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
        WHERE ucb.user_id = ? AND ucb.is_constructing = FALSE AND bt.produces_resource_id IS NOT NULL
        GROUP BY bt.produces_resource_id, bt.production_rate
    ");
    $stmt->execute([$userId]);
    $productions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $collectedResources = [];
    
    foreach ($productions as $prod) {
        $rate = $prod['production_rate'] * $prod['total_level'];
        $produced = $rate * $hoursPassed;
        
        if ($produced > 0) {
            // 資源がまだアンロックされていない場合はアンロックする
            $stmt = $pdo->prepare("
                INSERT INTO user_civilization_resources (user_id, resource_type_id, amount, unlocked, unlocked_at)
                VALUES (?, ?, ?, TRUE, NOW())
                ON DUPLICATE KEY UPDATE amount = amount + ?, unlocked = TRUE
            ");
            $stmt->execute([$userId, $prod['produces_resource_id'], $produced, $produced]);
            
            // 資源名を取得
            $stmt = $pdo->prepare("SELECT name, icon FROM civilization_resource_types WHERE id = ?");
            $stmt->execute([$prod['produces_resource_id']]);
            $resInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resInfo) {
                $collectedResources[] = [
                    'resource_id' => $prod['produces_resource_id'],
                    'name' => $resInfo['name'],
                    'icon' => $resInfo['icon'],
                    'amount' => round($produced, 2)
                ];
            }
        }
    }
    
    // 最終収集時刻を更新
    $stmt = $pdo->prepare("UPDATE user_civilizations SET last_resource_collection = NOW() WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    return $collectedResources;
}

// 文明データを取得
if ($action === 'get_data') {
    try {
        $civ = getUserCivilization($pdo, $me['id']);
        $collected = collectResources($pdo, $me['id']);
        
        // 時代情報
        $stmt = $pdo->prepare("SELECT * FROM civilization_eras ORDER BY era_order");
        $stmt->execute();
        $eras = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 現在の時代
        $stmt = $pdo->prepare("SELECT * FROM civilization_eras WHERE id = ?");
        $stmt->execute([$civ['current_era_id']]);
        $currentEra = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // ユーザーの資源
        $stmt = $pdo->prepare("
            SELECT ucr.*, rt.resource_key, rt.name, rt.icon, rt.color
            FROM user_civilization_resources ucr
            JOIN civilization_resource_types rt ON ucr.resource_type_id = rt.id
            WHERE ucr.user_id = ?
        ");
        $stmt->execute([$me['id']]);
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ユーザーの建物
        $stmt = $pdo->prepare("
            SELECT ucb.*, bt.building_key, bt.name, bt.icon, bt.description, bt.category, 
                   bt.produces_resource_id, bt.production_rate, bt.max_level, bt.population_capacity, bt.military_power
            FROM user_civilization_buildings ucb
            JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
            WHERE ucb.user_id = ?
        ");
        $stmt->execute([$me['id']]);
        $buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 利用可能な建物タイプ（現在の時代まで）
        $stmt = $pdo->prepare("
            SELECT bt.*, e.name as era_name
            FROM civilization_building_types bt
            LEFT JOIN civilization_eras e ON bt.unlock_era_id = e.id
            WHERE bt.unlock_era_id IS NULL OR bt.unlock_era_id <= ?
            ORDER BY bt.unlock_era_id, bt.id
        ");
        $stmt->execute([$civ['current_era_id']]);
        $availableBuildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 研究進捗
        $stmt = $pdo->prepare("
            SELECT ucr.*, r.research_key, r.name, r.icon, r.description, r.era_id, 
                   r.unlock_building_id, r.unlock_resource_id, r.research_cost_points, r.research_time_seconds
            FROM user_civilization_researches ucr
            JOIN civilization_researches r ON ucr.research_id = r.id
            WHERE ucr.user_id = ?
        ");
        $stmt->execute([$me['id']]);
        $userResearches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 利用可能な研究
        $stmt = $pdo->prepare("
            SELECT r.*, e.name as era_name
            FROM civilization_researches r
            JOIN civilization_eras e ON r.era_id = e.id
            WHERE r.era_id <= ?
            ORDER BY r.era_id, r.id
        ");
        $stmt->execute([$civ['current_era_id']]);
        $availableResearches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ユーザーのコイン・クリスタル・ダイヤモンド残高
        $stmt = $pdo->prepare("SELECT coins, crystals, diamonds FROM users WHERE id = ?");
        $stmt->execute([$me['id']]);
        $balance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 総合軍事力を計算
        $militaryPowerData = calculateTotalMilitaryPower($pdo, $me['id']);
        
        // 文明データに軍事力を追加
        $civ['military_power'] = $militaryPowerData['total_power'];
        $civ['building_power'] = $militaryPowerData['building_power'];
        $civ['troop_power'] = $militaryPowerData['troop_power'];
        
        echo json_encode([
            'ok' => true,
            'civilization' => $civ,
            'current_era' => $currentEra,
            'eras' => $eras,
            'resources' => $resources,
            'buildings' => $buildings,
            'available_buildings' => $availableBuildings,
            'user_researches' => $userResearches,
            'available_researches' => $availableResearches,
            'collected_resources' => $collected,
            'balance' => $balance,
            'military_power_breakdown' => $militaryPowerData
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// コインを投資
if ($action === 'invest_coins') {
    $amount = (int)($input['amount'] ?? 0);
    
    if ($amount < 100) {
        echo json_encode(['ok' => false, 'error' => '最低投資額は100コインです']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        // ユーザーのコインを確認
        $stmt = $pdo->prepare("SELECT coins FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$me['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user['coins'] < $amount) {
            throw new Exception('コインが不足しています');
        }
        
        // コインを消費
        $stmt = $pdo->prepare("UPDATE users SET coins = coins - ? WHERE id = ?");
        $stmt->execute([$amount, $me['id']]);
        
        // 文明データ更新
        $civ = getUserCivilization($pdo, $me['id']);
        $stmt = $pdo->prepare("
            UPDATE user_civilizations 
            SET total_invested_coins = total_invested_coins + ?,
                research_points = research_points + ?
            WHERE user_id = ?
        ");
        $researchPointsGained = (int)floor($amount / CIV_COINS_TO_RESEARCH_RATIO);
        $stmt->execute([$amount, $researchPointsGained, $me['id']]);
        
        // 資源をボーナスとして追加（投資額に応じた食料・木材・石材）
        $resourceBonus = (int)floor($amount / CIV_RESOURCE_BONUS_RATIO);
        $stmt = $pdo->prepare("
            UPDATE user_civilization_resources 
            SET amount = amount + ?
            WHERE user_id = ? AND resource_type_id IN (1, 2, 3)
        ");
        $stmt->execute([$resourceBonus, $me['id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => "投資成功！研究ポイント +{$researchPointsGained}、基本資源 +{$resourceBonus}",
            'research_points_gained' => $researchPointsGained,
            'resource_bonus' => $resourceBonus
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 建物を建設
if ($action === 'build') {
    $buildingTypeId = (int)($input['building_type_id'] ?? 0);
    
    $pdo->beginTransaction();
    try {
        // 建物タイプを確認
        $stmt = $pdo->prepare("SELECT * FROM civilization_building_types WHERE id = ?");
        $stmt->execute([$buildingTypeId]);
        $buildingType = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$buildingType) {
            throw new Exception('建物タイプが見つかりません');
        }
        
        // 文明データを確認
        $civ = getUserCivilization($pdo, $me['id']);
        
        // 時代制限チェック
        if ($buildingType['unlock_era_id'] && $buildingType['unlock_era_id'] > $civ['current_era_id']) {
            throw new Exception('この建物はまだ利用できません');
        }
        
        // コストを確認
        $stmt = $pdo->prepare("SELECT coins FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$me['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user['coins'] < $buildingType['base_build_cost_coins']) {
            throw new Exception('コインが不足しています');
        }
        
        // 資源コストを確認
        $resourceCosts = json_decode($buildingType['base_build_cost_resources'], true) ?: [];
        foreach ($resourceCosts as $resourceKey => $required) {
            $stmt = $pdo->prepare("
                SELECT ucr.amount 
                FROM user_civilization_resources ucr
                JOIN civilization_resource_types rt ON ucr.resource_type_id = rt.id
                WHERE ucr.user_id = ? AND rt.resource_key = ?
            ");
            $stmt->execute([$me['id'], $resourceKey]);
            $currentAmount = (float)$stmt->fetchColumn();
            
            if ($currentAmount < $required) {
                throw new Exception("{$resourceKey}が不足しています（必要: {$required}、所持: " . round($currentAmount) . "）");
            }
        }
        
        // コストを消費
        $stmt = $pdo->prepare("UPDATE users SET coins = coins - ? WHERE id = ?");
        $stmt->execute([$buildingType['base_build_cost_coins'], $me['id']]);
        
        foreach ($resourceCosts as $resourceKey => $required) {
            $stmt = $pdo->prepare("
                UPDATE user_civilization_resources ucr
                JOIN civilization_resource_types rt ON ucr.resource_type_id = rt.id
                SET ucr.amount = ucr.amount - ?
                WHERE ucr.user_id = ? AND rt.resource_key = ?
            ");
            $stmt->execute([$required, $me['id'], $resourceKey]);
        }
        
        // 建物を作成（建設中）
        $completesAt = date('Y-m-d H:i:s', time() + $buildingType['base_build_time_seconds']);
        $stmt = $pdo->prepare("
            INSERT INTO user_civilization_buildings 
            (user_id, building_type_id, level, is_constructing, construction_started_at, construction_completes_at)
            VALUES (?, ?, 1, TRUE, NOW(), ?)
        ");
        $stmt->execute([$me['id'], $buildingTypeId, $completesAt]);
        $buildingId = $pdo->lastInsertId();
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => "{$buildingType['name']}の建設を開始しました",
            'building_id' => $buildingId,
            'completes_at' => $completesAt
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 研究を開始
if ($action === 'research') {
    $researchId = (int)($input['research_id'] ?? 0);
    
    $pdo->beginTransaction();
    try {
        // 研究情報を確認
        $stmt = $pdo->prepare("SELECT * FROM civilization_researches WHERE id = ?");
        $stmt->execute([$researchId]);
        $research = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$research) {
            throw new Exception('研究が見つかりません');
        }
        
        // 文明データを確認
        $civ = getUserCivilization($pdo, $me['id']);
        
        // 時代制限チェック
        if ($research['era_id'] > $civ['current_era_id']) {
            throw new Exception('この研究はまだ利用できません');
        }
        
        // 研究ポイントを確認
        if ($civ['research_points'] < $research['research_cost_points']) {
            throw new Exception('研究ポイントが不足しています');
        }
        
        // 既に研究済みか確認
        $stmt = $pdo->prepare("SELECT * FROM user_civilization_researches WHERE user_id = ? AND research_id = ?");
        $stmt->execute([$me['id'], $researchId]);
        $existingResearch = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingResearch && $existingResearch['is_completed']) {
            throw new Exception('この研究は既に完了しています');
        }
        
        if ($existingResearch && $existingResearch['is_researching']) {
            throw new Exception('この研究は既に進行中です');
        }
        
        // 前提研究チェック
        if ($research['prerequisite_research_id']) {
            $stmt = $pdo->prepare("
                SELECT is_completed 
                FROM user_civilization_researches 
                WHERE user_id = ? AND research_id = ?
            ");
            $stmt->execute([$me['id'], $research['prerequisite_research_id']]);
            $prereq = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$prereq || !$prereq['is_completed']) {
                throw new Exception('前提研究が完了していません');
            }
        }
        
        // 研究ポイントを消費
        $stmt = $pdo->prepare("
            UPDATE user_civilizations 
            SET research_points = research_points - ? 
            WHERE user_id = ?
        ");
        $stmt->execute([$research['research_cost_points'], $me['id']]);
        
        // 研究を開始
        $completesAt = date('Y-m-d H:i:s', time() + $research['research_time_seconds']);
        $stmt = $pdo->prepare("
            INSERT INTO user_civilization_researches 
            (user_id, research_id, is_researching, research_started_at, research_completes_at)
            VALUES (?, ?, TRUE, NOW(), ?)
            ON DUPLICATE KEY UPDATE 
                is_researching = TRUE, 
                research_started_at = NOW(), 
                research_completes_at = ?
        ");
        $stmt->execute([$me['id'], $researchId, $completesAt, $completesAt]);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => "{$research['name']}の研究を開始しました",
            'completes_at' => $completesAt
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 完了した研究を確認
if ($action === 'complete_researches') {
    $pdo->beginTransaction();
    try {
        // 完了した研究を取得
        $stmt = $pdo->prepare("
            SELECT ucr.*, r.name, r.unlock_building_id, r.unlock_resource_id
            FROM user_civilization_researches ucr
            JOIN civilization_researches r ON ucr.research_id = r.id
            WHERE ucr.user_id = ? AND ucr.is_researching = TRUE AND ucr.research_completes_at <= NOW()
        ");
        $stmt->execute([$me['id']]);
        $completedResearches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $completedNames = [];
        
        foreach ($completedResearches as $research) {
            // 研究を完了
            $stmt = $pdo->prepare("
                UPDATE user_civilization_researches 
                SET is_researching = FALSE, is_completed = TRUE, completed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$research['id']]);
            
            // 資源アンロック
            if ($research['unlock_resource_id']) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_civilization_resources (user_id, resource_type_id, amount, unlocked, unlocked_at)
                    VALUES (?, ?, 0, TRUE, NOW())
                    ON DUPLICATE KEY UPDATE unlocked = TRUE, unlocked_at = NOW()
                ");
                $stmt->execute([$me['id'], $research['unlock_resource_id']]);
            }
            
            $completedNames[] = $research['name'];
        }
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'completed' => $completedNames,
            'count' => count($completedNames)
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 時代を進化
if ($action === 'advance_era') {
    $pdo->beginTransaction();
    try {
        $civ = getUserCivilization($pdo, $me['id']);
        
        // 次の時代を取得
        $stmt = $pdo->prepare("
            SELECT * FROM civilization_eras 
            WHERE era_order > (SELECT era_order FROM civilization_eras WHERE id = ?)
            ORDER BY era_order ASC LIMIT 1
        ");
        $stmt->execute([$civ['current_era_id']]);
        $nextEra = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$nextEra) {
            throw new Exception('既に最高の時代に達しています');
        }
        
        // 条件チェック
        if ($civ['population'] < $nextEra['unlock_population']) {
            throw new Exception("人口が不足しています（必要: {$nextEra['unlock_population']}、現在: {$civ['population']}）");
        }
        
        if ($civ['research_points'] < $nextEra['unlock_research_points']) {
            throw new Exception("研究ポイントが不足しています（必要: {$nextEra['unlock_research_points']}、現在: {$civ['research_points']}）");
        }
        
        // 時代を進化
        $stmt = $pdo->prepare("
            UPDATE user_civilizations 
            SET current_era_id = ?, 
                research_points = research_points - ?
            WHERE user_id = ?
        ");
        $stmt->execute([$nextEra['id'], $nextEra['unlock_research_points'], $me['id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => "{$nextEra['name']}に進化しました！",
            'new_era' => $nextEra
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 戦争（他プレイヤーを攻撃）
if ($action === 'attack') {
    $targetUserId = (int)($input['target_user_id'] ?? 0);
    
    if ($targetUserId === $me['id']) {
        echo json_encode(['ok' => false, 'error' => '自分を攻撃することはできません']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        // 攻撃者の文明
        $myCiv = getUserCivilization($pdo, $me['id']);
        
        // 防御者の文明
        $stmt = $pdo->prepare("SELECT * FROM user_civilizations WHERE user_id = ?");
        $stmt->execute([$targetUserId]);
        $targetCiv = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$targetCiv) {
            throw new Exception('相手の文明が見つかりません');
        }
        
        // 軍事力を計算（建物 + 兵士）
        $myPowerData = calculateTotalMilitaryPower($pdo, $me['id']);
        $myPower = $myPowerData['total_power'];
        
        $targetPowerData = calculateTotalMilitaryPower($pdo, $targetUserId);
        $targetPower = $targetPowerData['total_power'];
        
        if ($myPower <= 0) {
            throw new Exception('軍事力がありません。兵舎や軍事施設を建設するか、兵士を訓練してください。');
        }
        
        // 戦闘判定（攻撃側ボーナス適用）
        $myRoll = mt_rand(1, 100) + ($myPower * CIV_ATTACKER_BONUS);
        $targetRoll = mt_rand(1, 100) + $targetPower;
        
        $winnerId = ($myRoll > $targetRoll) ? $me['id'] : $targetUserId;
        $loserId = ($winnerId === $me['id']) ? $targetUserId : $me['id'];
        
        // 略奪
        $lootCoins = 0;
        $lootResources = [];
        
        if ($winnerId === $me['id']) {
            // 勝利時：相手の資源を略奪
            $stmt = $pdo->prepare("
                SELECT ucr.resource_type_id, ucr.amount, rt.resource_key
                FROM user_civilization_resources ucr
                JOIN civilization_resource_types rt ON ucr.resource_type_id = rt.id
                WHERE ucr.user_id = ?
            ");
            $stmt->execute([$targetUserId]);
            $targetResources = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($targetResources as $res) {
                $loot = floor($res['amount'] * CIV_LOOT_RESOURCE_RATE);
                if ($loot > 0) {
                    $lootResources[$res['resource_key']] = $loot;
                    
                    // 資源を移動
                    $stmt = $pdo->prepare("
                        UPDATE user_civilization_resources 
                        SET amount = amount - ? 
                        WHERE user_id = ? AND resource_type_id = ?
                    ");
                    $stmt->execute([$loot, $targetUserId, $res['resource_type_id']]);
                    
                    $stmt = $pdo->prepare("
                        UPDATE user_civilization_resources 
                        SET amount = amount + ? 
                        WHERE user_id = ? AND resource_type_id = ?
                    ");
                    $stmt->execute([$loot, $me['id'], $res['resource_type_id']]);
                }
            }
            
            // コインも略奪
            $stmt = $pdo->prepare("SELECT coins FROM users WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $targetCoins = (int)$stmt->fetchColumn();
            $lootCoins = (int)floor($targetCoins * CIV_LOOT_COINS_RATE);
            
            if ($lootCoins > 0) {
                $stmt = $pdo->prepare("UPDATE users SET coins = coins - ? WHERE id = ?");
                $stmt->execute([$lootCoins, $targetUserId]);
                
                $stmt = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
                $stmt->execute([$lootCoins, $me['id']]);
            }
        }
        
        // 戦争ログを記録
        $stmt = $pdo->prepare("
            INSERT INTO civilization_war_logs 
            (attacker_user_id, defender_user_id, attacker_power, defender_power, winner_user_id, loot_coins, loot_resources)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $me['id'], $targetUserId, $myPower, $targetPower, $winnerId, $lootCoins, json_encode($lootResources)
        ]);
        
        $pdo->commit();
        
        $result = ($winnerId === $me['id']) ? 'victory' : 'defeat';
        $message = ($result === 'victory') 
            ? "勝利！{$lootCoins}コインと資源を略奪しました！" 
            : "敗北...相手の防御が強すぎました。";
        
        echo json_encode([
            'ok' => true,
            'result' => $result,
            'message' => $message,
            'my_power' => $myPower,
            'target_power' => $targetPower,
            'loot_coins' => $lootCoins,
            'loot_resources' => $lootResources
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 攻撃可能なプレイヤー一覧
if ($action === 'get_targets') {
    try {
        $stmt = $pdo->prepare("
            SELECT uc.user_id, uc.civilization_name, uc.population, u.handle, u.display_name,
                   (SELECT COALESCE(SUM(bt.military_power * ucb.level), 0) 
                    FROM user_civilization_buildings ucb
                    JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
                    WHERE ucb.user_id = uc.user_id AND ucb.is_constructing = FALSE) +
                   (SELECT COALESCE(SUM((tt.attack_power + FLOOR(tt.defense_power / 2)) * uct.count), 0)
                    FROM user_civilization_troops uct
                    JOIN civilization_troop_types tt ON uct.troop_type_id = tt.id
                    WHERE uct.user_id = uc.user_id) as military_power
            FROM user_civilizations uc
            JOIN users u ON uc.user_id = u.id
            WHERE uc.user_id != ?
            ORDER BY uc.population DESC
            LIMIT 20
        ");
        $stmt->execute([$me['id']]);
        $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 自分の軍事力も取得して返す
        $myPowerData = calculateTotalMilitaryPower($pdo, $me['id']);
        
        echo json_encode([
            'ok' => true, 
            'targets' => $targets,
            'my_military_power' => $myPowerData
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 文明名を変更
if ($action === 'rename') {
    $newName = trim($input['name'] ?? '');
    
    if (mb_strlen($newName) < 1 || mb_strlen($newName) > 50) {
        echo json_encode(['ok' => false, 'error' => '文明名は1〜50文字で入力してください']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE user_civilizations SET civilization_name = ? WHERE user_id = ?");
        $stmt->execute([$newName, $me['id']]);
        
        echo json_encode(['ok' => true, 'message' => '文明名を変更しました']);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 完了した建物を確認
if ($action === 'complete_buildings') {
    $pdo->beginTransaction();
    try {
        // 完了した建設を取得
        $stmt = $pdo->prepare("
            SELECT ucb.id, ucb.level, bt.name, bt.population_capacity
            FROM user_civilization_buildings ucb
            JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
            WHERE ucb.user_id = ? AND ucb.is_constructing = TRUE AND ucb.construction_completes_at <= NOW()
        ");
        $stmt->execute([$me['id']]);
        $completedBuildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $completedNames = [];
        $populationIncrease = 0;
        
        foreach ($completedBuildings as $building) {
            // 建設を完了
            $stmt = $pdo->prepare("
                UPDATE user_civilization_buildings 
                SET is_constructing = FALSE 
                WHERE id = ?
            ");
            $stmt->execute([$building['id']]);
            
            // 住宅の場合は人口を増やす
            if ($building['population_capacity'] > 0) {
                $populationIncrease += $building['population_capacity'] * $building['level'];
            }
            
            $completedNames[] = $building['name'];
        }
        
        // 人口を増加
        if ($populationIncrease > 0) {
            $stmt = $pdo->prepare("
                UPDATE user_civilizations 
                SET population = population + ?,
                    max_population = max_population + ?
                WHERE user_id = ?
            ");
            $stmt->execute([$populationIncrease, $populationIncrease, $me['id']]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'completed' => $completedNames,
            'count' => count($completedNames),
            'population_increase' => $populationIncrease
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 建物を即座に完成（クリスタル消費）
if ($action === 'instant_complete_building') {
    $buildingId = (int)($input['building_id'] ?? 0);
    
    $pdo->beginTransaction();
    try {
        // 建設中の建物を確認
        $stmt = $pdo->prepare("
            SELECT ucb.*, bt.name, bt.population_capacity
            FROM user_civilization_buildings ucb
            JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
            WHERE ucb.id = ? AND ucb.user_id = ? AND ucb.is_constructing = TRUE
        ");
        $stmt->execute([$buildingId, $me['id']]);
        $building = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$building) {
            throw new Exception('建設中の建物が見つかりません');
        }
        
        // 残り時間に応じたクリスタルコストを計算
        $remainingSeconds = max(0, strtotime($building['construction_completes_at']) - time());
        $crystalCost = max(CIV_INSTANT_BUILDING_MIN_COST, (int)ceil($remainingSeconds / CIV_INSTANT_SECONDS_PER_CRYSTAL));
        
        // クリスタルを確認
        $stmt = $pdo->prepare("SELECT crystals FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$me['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user['crystals'] < $crystalCost) {
            throw new Exception("クリスタルが不足しています（必要: {$crystalCost}、所持: {$user['crystals']}）");
        }
        
        // クリスタルを消費
        $stmt = $pdo->prepare("UPDATE users SET crystals = crystals - ? WHERE id = ?");
        $stmt->execute([$crystalCost, $me['id']]);
        
        // 建設を完了
        $stmt = $pdo->prepare("
            UPDATE user_civilization_buildings 
            SET is_constructing = FALSE, construction_completes_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$buildingId]);
        
        // 住宅の場合は人口を増やす
        $populationIncrease = 0;
        if ($building['population_capacity'] > 0) {
            $populationIncrease = $building['population_capacity'] * $building['level'];
            $stmt = $pdo->prepare("
                UPDATE user_civilizations 
                SET population = population + ?,
                    max_population = max_population + ?
                WHERE user_id = ?
            ");
            $stmt->execute([$populationIncrease, $populationIncrease, $me['id']]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => "{$building['name']}の建設が完了しました！",
            'crystals_spent' => $crystalCost,
            'population_increase' => $populationIncrease
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 研究を即座に完成（クリスタル消費）
if ($action === 'instant_complete_research') {
    $researchId = (int)($input['user_research_id'] ?? 0);
    
    $pdo->beginTransaction();
    try {
        // 研究中の研究を確認
        $stmt = $pdo->prepare("
            SELECT ucr.*, r.name, r.unlock_resource_id
            FROM user_civilization_researches ucr
            JOIN civilization_researches r ON ucr.research_id = r.id
            WHERE ucr.id = ? AND ucr.user_id = ? AND ucr.is_researching = TRUE
        ");
        $stmt->execute([$researchId, $me['id']]);
        $research = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$research) {
            throw new Exception('研究中の研究が見つかりません');
        }
        
        // 残り時間に応じたクリスタルコストを計算
        $remainingSeconds = max(0, strtotime($research['research_completes_at']) - time());
        $crystalCost = max(CIV_INSTANT_RESEARCH_MIN_COST, (int)ceil($remainingSeconds / CIV_INSTANT_SECONDS_PER_CRYSTAL));
        
        // クリスタルを確認
        $stmt = $pdo->prepare("SELECT crystals FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$me['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user['crystals'] < $crystalCost) {
            throw new Exception("クリスタルが不足しています（必要: {$crystalCost}、所持: {$user['crystals']}）");
        }
        
        // クリスタルを消費
        $stmt = $pdo->prepare("UPDATE users SET crystals = crystals - ? WHERE id = ?");
        $stmt->execute([$crystalCost, $me['id']]);
        
        // 研究を完了
        $stmt = $pdo->prepare("
            UPDATE user_civilization_researches 
            SET is_researching = FALSE, is_completed = TRUE, completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$researchId]);
        
        // 資源アンロック
        if ($research['unlock_resource_id']) {
            $stmt = $pdo->prepare("
                INSERT INTO user_civilization_resources (user_id, resource_type_id, amount, unlocked, unlocked_at)
                VALUES (?, ?, 0, TRUE, NOW())
                ON DUPLICATE KEY UPDATE unlocked = TRUE, unlocked_at = NOW()
            ");
            $stmt->execute([$me['id'], $research['unlock_resource_id']]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => "{$research['name']}の研究が完了しました！",
            'crystals_spent' => $crystalCost
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 兵士を訓練
if ($action === 'train_troops') {
    $troopTypeId = (int)($input['troop_type_id'] ?? 0);
    $count = (int)($input['count'] ?? 1);
    
    if ($count < 1 || $count > 100) {
        echo json_encode(['ok' => false, 'error' => '訓練数は1〜100の範囲で指定してください']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        // 兵種を確認
        $stmt = $pdo->prepare("SELECT * FROM civilization_troop_types WHERE id = ?");
        $stmt->execute([$troopTypeId]);
        $troopType = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$troopType) {
            throw new Exception('兵種が見つかりません');
        }
        
        // 文明データを確認
        $civ = getUserCivilization($pdo, $me['id']);
        
        // 時代制限チェック
        if ($troopType['unlock_era_id'] && $troopType['unlock_era_id'] > $civ['current_era_id']) {
            throw new Exception('この兵種はまだ利用できません');
        }
        
        // コストを計算
        $totalCoinCost = $troopType['train_cost_coins'] * $count;
        $resourceCosts = json_decode($troopType['train_cost_resources'], true) ?: [];
        
        // コインを確認
        $stmt = $pdo->prepare("SELECT coins FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$me['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user['coins'] < $totalCoinCost) {
            throw new Exception('コインが不足しています');
        }
        
        // 資源コストを確認・消費
        foreach ($resourceCosts as $resourceKey => $required) {
            $totalRequired = $required * $count;
            $stmt = $pdo->prepare("
                SELECT ucr.amount 
                FROM user_civilization_resources ucr
                JOIN civilization_resource_types rt ON ucr.resource_type_id = rt.id
                WHERE ucr.user_id = ? AND rt.resource_key = ?
            ");
            $stmt->execute([$me['id'], $resourceKey]);
            $currentAmount = (float)$stmt->fetchColumn();
            
            if ($currentAmount < $totalRequired) {
                throw new Exception("{$resourceKey}が不足しています");
            }
            
            $stmt = $pdo->prepare("
                UPDATE user_civilization_resources ucr
                JOIN civilization_resource_types rt ON ucr.resource_type_id = rt.id
                SET ucr.amount = ucr.amount - ?
                WHERE ucr.user_id = ? AND rt.resource_key = ?
            ");
            $stmt->execute([$totalRequired, $me['id'], $resourceKey]);
        }
        
        // コインを消費
        $stmt = $pdo->prepare("UPDATE users SET coins = coins - ? WHERE id = ?");
        $stmt->execute([$totalCoinCost, $me['id']]);
        
        // 兵士を追加または更新
        $stmt = $pdo->prepare("
            INSERT INTO user_civilization_troops (user_id, troop_type_id, count)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE count = count + ?
        ");
        $stmt->execute([$me['id'], $troopTypeId, $count, $count]);
        
        // 軍事力を更新
        $totalMilitaryPower = $troopType['attack_power'] * $count;
        $stmt = $pdo->prepare("
            UPDATE user_civilizations 
            SET military_power = military_power + ?
            WHERE user_id = ?
        ");
        $stmt->execute([$totalMilitaryPower, $me['id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => "{$troopType['name']} ×{$count} を訓練しました！",
            'military_power_increase' => $totalMilitaryPower
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 兵種一覧を取得
if ($action === 'get_troops') {
    try {
        $civ = getUserCivilization($pdo, $me['id']);
        
        // 利用可能な兵種
        $stmt = $pdo->prepare("
            SELECT tt.*, e.name as era_name
            FROM civilization_troop_types tt
            LEFT JOIN civilization_eras e ON tt.unlock_era_id = e.id
            WHERE tt.unlock_era_id IS NULL OR tt.unlock_era_id <= ?
            ORDER BY tt.unlock_era_id, tt.id
        ");
        $stmt->execute([$civ['current_era_id']]);
        $availableTroops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ユーザーの兵士
        $stmt = $pdo->prepare("
            SELECT uct.*, tt.troop_key, tt.name, tt.icon, tt.attack_power, tt.defense_power
            FROM user_civilization_troops uct
            JOIN civilization_troop_types tt ON uct.troop_type_id = tt.id
            WHERE uct.user_id = ?
        ");
        $stmt->execute([$me['id']]);
        $userTroops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'available_troops' => $availableTroops,
            'user_troops' => $userTroops
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ダイヤモンドでVIPブースト購入
if ($action === 'buy_vip_boost') {
    $boostType = $input['boost_type'] ?? '';
    
    $boostCosts = [
        'production_2x' => ['diamonds' => 5, 'duration_hours' => 24, 'description' => '資源生産2倍（24時間）'],
        'research_speed' => ['diamonds' => 3, 'duration_hours' => 12, 'description' => '研究速度2倍（12時間）'],
        'build_speed' => ['diamonds' => 3, 'duration_hours' => 12, 'description' => '建設速度2倍（12時間）'],
        'resource_pack' => ['diamonds' => 10, 'resources' => ['food' => 1000, 'wood' => 1000, 'stone' => 1000], 'description' => '資源パック']
    ];
    
    if (!isset($boostCosts[$boostType])) {
        echo json_encode(['ok' => false, 'error' => '無効なブーストタイプです']);
        exit;
    }
    
    $boost = $boostCosts[$boostType];
    
    $pdo->beginTransaction();
    try {
        // ダイヤモンドを確認
        $stmt = $pdo->prepare("SELECT diamonds FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$me['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user['diamonds'] < $boost['diamonds']) {
            throw new Exception("ダイヤモンドが不足しています（必要: {$boost['diamonds']}、所持: {$user['diamonds']}）");
        }
        
        // ダイヤモンドを消費
        $stmt = $pdo->prepare("UPDATE users SET diamonds = diamonds - ? WHERE id = ?");
        $stmt->execute([$boost['diamonds'], $me['id']]);
        
        // ブースト適用
        if ($boostType === 'resource_pack') {
            // 資源を追加
            foreach ($boost['resources'] as $resourceKey => $amount) {
                $stmt = $pdo->prepare("
                    UPDATE user_civilization_resources ucr
                    JOIN civilization_resource_types rt ON ucr.resource_type_id = rt.id
                    SET ucr.amount = ucr.amount + ?
                    WHERE ucr.user_id = ? AND rt.resource_key = ?
                ");
                $stmt->execute([$amount, $me['id'], $resourceKey]);
            }
        } else {
            // ブースト記録
            $expiresAt = date('Y-m-d H:i:s', time() + ($boost['duration_hours'] * 3600));
            $stmt = $pdo->prepare("
                INSERT INTO civilization_boosts (user_id, boost_type, multiplier, expires_at)
                VALUES (?, ?, 2.0, ?)
                ON DUPLICATE KEY UPDATE expires_at = ?, multiplier = 2.0
            ");
            $stmt->execute([$me['id'], $boostType, $expiresAt, $expiresAt]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => $boost['description'] . 'を購入しました！',
            'diamonds_spent' => $boost['diamonds']
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 資源交換（市場機能）
if ($action === 'exchange_resources') {
    $fromResourceId = (int)($input['from_resource_id'] ?? 0);
    $toResourceId = (int)($input['to_resource_id'] ?? 0);
    $amount = (int)($input['amount'] ?? 0);
    
    if ($fromResourceId === $toResourceId) {
        echo json_encode(['ok' => false, 'error' => '同じ資源は交換できません']);
        exit;
    }
    
    if ($amount < 2) {
        echo json_encode(['ok' => false, 'error' => '最低交換量は2です']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        // 市場建物の数を確認（建設数に応じてレートが改善される）
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as market_count, SUM(ucb.level) as total_level
            FROM user_civilization_buildings ucb
            JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
            WHERE ucb.user_id = ? AND bt.building_key = 'market' AND ucb.is_constructing = FALSE
        ");
        $stmt->execute([$me['id']]);
        $marketData = $stmt->fetch(PDO::FETCH_ASSOC);
        $marketCount = (int)($marketData['market_count'] ?? 0);
        $totalMarketLevel = (int)($marketData['total_level'] ?? 0);
        
        if ($marketCount === 0) {
            throw new Exception('市場を建設してから交換してください');
        }
        
        // 資源を確認
        $stmt = $pdo->prepare("
            SELECT ucr.amount, rt.name as from_name, rt.icon as from_icon, rt.resource_key as from_key
            FROM user_civilization_resources ucr
            JOIN civilization_resource_types rt ON ucr.resource_type_id = rt.id
            WHERE ucr.user_id = ? AND ucr.resource_type_id = ?
        ");
        $stmt->execute([$me['id'], $fromResourceId]);
        $fromResource = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$fromResource) {
            throw new Exception('交換元の資源がアンロックされていません');
        }
        
        if ((float)$fromResource['amount'] < $amount) {
            throw new Exception("資源が不足しています（必要: {$amount}、所持: " . round($fromResource['amount']) . "）");
        }
        
        // 交換先の資源を確認
        $stmt = $pdo->prepare("
            SELECT rt.name as to_name, rt.icon as to_icon, rt.resource_key as to_key
            FROM user_civilization_resources ucr
            JOIN civilization_resource_types rt ON ucr.resource_type_id = rt.id
            WHERE ucr.user_id = ? AND ucr.resource_type_id = ?
        ");
        $stmt->execute([$me['id'], $toResourceId]);
        $toResource = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$toResource) {
            throw new Exception('交換先の資源がアンロックされていません');
        }
        
        // グローバルの資源価値定義を使用
        global $RESOURCE_VALUES;
        
        $fromValue = $RESOURCE_VALUES[$fromResource['from_key']] ?? 1.0;
        $toValue = $RESOURCE_VALUES[$toResource['to_key']] ?? 1.0;
        
        // 基本交換レート（価値の比率）
        $baseRate = $fromValue / $toValue;
        
        // 市場建設数によるボーナス（市場1つあたり5%改善、最大50%まで）
        // 市場レベルも加味（レベル合計 * 2%）
        $marketBonus = min(0.5, ($marketCount * 0.05) + ($totalMarketLevel * 0.02));
        
        // 最終交換レート（市場ボーナス適用）
        $finalRate = $baseRate * (1 + $marketBonus);
        
        // 受け取り量を計算
        $received = (int)floor($amount * $finalRate);
        
        if ($received < 1) {
            throw new Exception('交換量が少なすぎます。もう少し多くの量を指定してください。');
        }
        
        // 資源を消費
        $stmt = $pdo->prepare("
            UPDATE user_civilization_resources 
            SET amount = amount - ? 
            WHERE user_id = ? AND resource_type_id = ?
        ");
        $stmt->execute([$amount, $me['id'], $fromResourceId]);
        
        // 資源を追加
        $stmt = $pdo->prepare("
            UPDATE user_civilization_resources 
            SET amount = amount + ? 
            WHERE user_id = ? AND resource_type_id = ?
        ");
        $stmt->execute([$received, $me['id'], $toResourceId]);
        
        $pdo->commit();
        
        $ratePercent = round($finalRate * 100);
        echo json_encode([
            'ok' => true,
            'message' => "{$fromResource['from_icon']} {$amount} → {$toResource['to_icon']} {$received} に交換しました！（レート: {$ratePercent}%）",
            'from_amount' => $amount,
            'to_amount' => $received,
            'exchange_rate' => $finalRate,
            'market_count' => $marketCount,
            'market_bonus' => $marketBonus
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 市場情報を取得（交換レート計算用）
if ($action === 'get_market_info') {
    try {
        // 市場建物の数を確認
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as market_count, COALESCE(SUM(ucb.level), 0) as total_level
            FROM user_civilization_buildings ucb
            JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
            WHERE ucb.user_id = ? AND bt.building_key = 'market' AND ucb.is_constructing = FALSE
        ");
        $stmt->execute([$me['id']]);
        $marketData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $marketCount = (int)($marketData['market_count'] ?? 0);
        $totalMarketLevel = (int)($marketData['total_level'] ?? 0);
        $marketBonus = min(0.5, ($marketCount * 0.05) + ($totalMarketLevel * 0.02));
        
        // グローバルの資源価値定義を使用
        global $RESOURCE_VALUES;
        
        echo json_encode([
            'ok' => true,
            'market_count' => $marketCount,
            'total_market_level' => $totalMarketLevel,
            'market_bonus' => $marketBonus,
            'resource_values' => $RESOURCE_VALUES
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 戦争ログを取得
if ($action === 'get_war_logs') {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                wl.*,
                attacker.handle as attacker_handle,
                attacker.display_name as attacker_name,
                defender.handle as defender_handle,
                defender.display_name as defender_name,
                ac.civilization_name as attacker_civ_name,
                dc.civilization_name as defender_civ_name
            FROM civilization_war_logs wl
            JOIN users attacker ON wl.attacker_user_id = attacker.id
            JOIN users defender ON wl.defender_user_id = defender.id
            LEFT JOIN user_civilizations ac ON wl.attacker_user_id = ac.user_id
            LEFT JOIN user_civilizations dc ON wl.defender_user_id = dc.user_id
            WHERE wl.attacker_user_id = ? OR wl.defender_user_id = ?
            ORDER BY wl.battle_at DESC
            LIMIT 50
        ");
        $stmt->execute([$me['id'], $me['id']]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'war_logs' => $logs,
            'my_user_id' => $me['id']
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 総合軍事力を計算（建物 + 兵士）
if ($action === 'get_military_power') {
    try {
        // 建物からの軍事力
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(bt.military_power * ucb.level), 0) as building_power
            FROM user_civilization_buildings ucb
            JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
            WHERE ucb.user_id = ? AND ucb.is_constructing = FALSE
        ");
        $stmt->execute([$me['id']]);
        $buildingPower = (int)$stmt->fetchColumn();
        
        // 兵士からの軍事力（攻撃力 + 防御力の半分）
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM((tt.attack_power + FLOOR(tt.defense_power / 2)) * uct.count), 0) as troop_power
            FROM user_civilization_troops uct
            JOIN civilization_troop_types tt ON uct.troop_type_id = tt.id
            WHERE uct.user_id = ?
        ");
        $stmt->execute([$me['id']]);
        $troopPower = (int)$stmt->fetchColumn();
        
        $totalPower = $buildingPower + $troopPower;
        
        echo json_encode([
            'ok' => true,
            'building_power' => $buildingPower,
            'troop_power' => $troopPower,
            'total_power' => $totalPower
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'invalid_action']);
