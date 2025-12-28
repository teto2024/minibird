<?php
// ===============================================
// civilization_api.php
// 文明育成システムAPI
// ===============================================

require_once __DIR__ . '/config.php';

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

// 資源を収集（時間経過分）
function collectResources($pdo, $userId) {
    $civ = getUserCivilization($pdo, $userId);
    $lastCollection = strtotime($civ['last_resource_collection']);
    $now = time();
    $hoursPassed = ($now - $lastCollection) / 3600;
    
    if ($hoursPassed < 0.01) { // 約36秒未満なら収集しない
        return [];
    }
    
    // 完了した建設を確認
    $stmt = $pdo->prepare("
        UPDATE user_civilization_buildings 
        SET is_constructing = FALSE 
        WHERE user_id = ? AND is_constructing = TRUE AND construction_completes_at <= NOW()
    ");
    $stmt->execute([$userId]);
    
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
            $stmt = $pdo->prepare("
                UPDATE user_civilization_resources 
                SET amount = amount + ? 
                WHERE user_id = ? AND resource_type_id = ?
            ");
            $stmt->execute([$produced, $userId, $prod['produces_resource_id']]);
            
            // 資源名を取得
            $stmt = $pdo->prepare("SELECT name, icon FROM civilization_resource_types WHERE id = ?");
            $stmt->execute([$prod['produces_resource_id']]);
            $resInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $collectedResources[] = [
                'resource_id' => $prod['produces_resource_id'],
                'name' => $resInfo['name'],
                'icon' => $resInfo['icon'],
                'amount' => round($produced, 2)
            ];
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
        
        // ユーザーのコイン・クリスタル残高
        $stmt = $pdo->prepare("SELECT coins, crystals FROM users WHERE id = ?");
        $stmt->execute([$me['id']]);
        $balance = $stmt->fetch(PDO::FETCH_ASSOC);
        
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
            'balance' => $balance
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
        $researchPointsGained = (int)floor($amount / 10); // 10コイン=1研究ポイント
        $stmt->execute([$amount, $researchPointsGained, $me['id']]);
        
        // 資源をボーナスとして追加（投資額の10%相当の食料・木材・石材）
        $resourceBonus = (int)floor($amount / 10);
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
        
        // 軍事力を計算
        $stmt = $pdo->prepare("
            SELECT SUM(bt.military_power * ucb.level) as total_power
            FROM user_civilization_buildings ucb
            JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
            WHERE ucb.user_id = ? AND ucb.is_constructing = FALSE
        ");
        $stmt->execute([$me['id']]);
        $myPower = (int)$stmt->fetchColumn() ?: 0;
        
        $stmt->execute([$targetUserId]);
        $targetPower = (int)$stmt->fetchColumn() ?: 0;
        
        if ($myPower <= 0) {
            throw new Exception('軍事力がありません。兵舎や軍事施設を建設してください。');
        }
        
        // 戦闘判定（攻撃側ボーナス +10%）
        $myRoll = mt_rand(1, 100) + ($myPower * 1.1);
        $targetRoll = mt_rand(1, 100) + $targetPower;
        
        $winnerId = ($myRoll > $targetRoll) ? $me['id'] : $targetUserId;
        $loserId = ($winnerId === $me['id']) ? $targetUserId : $me['id'];
        
        // 略奪
        $lootCoins = 0;
        $lootResources = [];
        
        if ($winnerId === $me['id']) {
            // 勝利時：相手の資源の10%を略奪
            $stmt = $pdo->prepare("
                SELECT ucr.resource_type_id, ucr.amount, rt.resource_key
                FROM user_civilization_resources ucr
                JOIN civilization_resource_types rt ON ucr.resource_type_id = rt.id
                WHERE ucr.user_id = ?
            ");
            $stmt->execute([$targetUserId]);
            $targetResources = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($targetResources as $res) {
                $loot = floor($res['amount'] * 0.1);
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
            
            // コインも略奪（相手の5%）
            $stmt = $pdo->prepare("SELECT coins FROM users WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $targetCoins = (int)$stmt->fetchColumn();
            $lootCoins = (int)floor($targetCoins * 0.05);
            
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
                   (SELECT SUM(bt.military_power * ucb.level) 
                    FROM user_civilization_buildings ucb
                    JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
                    WHERE ucb.user_id = uc.user_id AND ucb.is_constructing = FALSE) as military_power
            FROM user_civilizations uc
            JOIN users u ON uc.user_id = u.id
            WHERE uc.user_id != ?
            ORDER BY uc.population DESC
            LIMIT 20
        ");
        $stmt->execute([$me['id']]);
        $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['ok' => true, 'targets' => $targets]);
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

echo json_encode(['ok' => false, 'error' => 'invalid_action']);
