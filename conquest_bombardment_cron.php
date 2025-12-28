<?php
// ===============================================
// conquest_bombardment_cron.php
// 占領戦砲撃処理用Cronジョブ
// 30分おきに実行推奨: */30 * * * * php conquest_bombardment_cron.php
// ===============================================

require_once __DIR__ . '/config.php';

$pdo = db();

// conquest_api.phpから砲撃定数と関数を読み込む
// 定数はすでに定義されている場合はスキップ
if (!defined('CONQUEST_BOMBARDMENT_INTERVAL_MINUTES')) {
    define('CONQUEST_BOMBARDMENT_INTERVAL_MINUTES', 30);
}
if (!defined('CONQUEST_BOMBARDMENT_BASE_RATE')) {
    define('CONQUEST_BOMBARDMENT_BASE_RATE', 0.05);
}
if (!defined('CONQUEST_BOMBARDMENT_COST_FACTOR')) {
    define('CONQUEST_BOMBARDMENT_COST_FACTOR', 0.0001);
}
if (!defined('CONQUEST_BOMBARDMENT_MAX_COST_REDUCTION')) {
    define('CONQUEST_BOMBARDMENT_MAX_COST_REDUCTION', 0.04);
}
if (!defined('CONQUEST_BOMBARDMENT_MIN_LOSS_RATE')) {
    define('CONQUEST_BOMBARDMENT_MIN_LOSS_RATE', 0.01);
}
if (!defined('CONQUEST_BOMBARDMENT_VARIANCE_RANGE')) {
    define('CONQUEST_BOMBARDMENT_VARIANCE_RANGE', 20);
}

/**
 * アクティブなシーズンを取得
 */
function getActiveSeason($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM conquest_seasons WHERE is_active = TRUE ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 砲撃を処理する関数
 * conquest_api.phpと同じロジックを使用
 */
function processBombardmentCron($pdo, $castleId, $seasonId) {
    // 城の情報を取得
    $stmt = $pdo->prepare("
        SELECT cc.*, 
               COALESCE(cc.last_bombardment_at, DATE_SUB(NOW(), INTERVAL 1 HOUR)) as effective_last_bombardment
        FROM conquest_castles cc 
        WHERE cc.id = ? AND cc.season_id = ?
    ");
    $stmt->execute([$castleId, $seasonId]);
    $castle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$castle || !$castle['owner_user_id']) {
        return ['ok' => false, 'message' => 'NPC城は砲撃対象外'];
    }
    
    // 最後の砲撃から30分経過しているか確認
    $lastBombardment = strtotime($castle['effective_last_bombardment']);
    $bombardmentInterval = CONQUEST_BOMBARDMENT_INTERVAL_MINUTES * 60;
    
    if (time() - $lastBombardment < $bombardmentInterval) {
        return ['ok' => false, 'message' => '砲撃間隔未経過'];
    }
    
    // 城に配置されている防御部隊を取得
    $stmt = $pdo->prepare("
        SELECT ccd.*, tt.name, tt.icon, tt.train_cost_coins, tt.attack_power, tt.defense_power,
               COALESCE(tt.health_points, 100) as health_points
        FROM conquest_castle_defense ccd
        JOIN civilization_troop_types tt ON ccd.troop_type_id = tt.id
        WHERE ccd.castle_id = ? AND ccd.count > 0
    ");
    $stmt->execute([$castleId]);
    $defenseTroops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($defenseTroops)) {
        return ['ok' => false, 'message' => '防御部隊なし'];
    }
    
    $woundedTroops = [];
    $totalWounded = 0;
    $logMessages = ["💥 砲撃発生！ ({$castle['name']})"];
    
    foreach ($defenseTroops as $troop) {
        // コストに基づく損失率計算
        $costFactor = min(CONQUEST_BOMBARDMENT_MAX_COST_REDUCTION, $troop['train_cost_coins'] * CONQUEST_BOMBARDMENT_COST_FACTOR);
        $lossRate = max(CONQUEST_BOMBARDMENT_MIN_LOSS_RATE, CONQUEST_BOMBARDMENT_BASE_RATE - $costFactor);
        
        // 負傷兵数を計算
        $baseWounded = (int)floor($troop['count'] * $lossRate);
        $varianceRange = CONQUEST_BOMBARDMENT_VARIANCE_RANGE;
        $randomVariance = mt_rand(-$varianceRange, $varianceRange) / 100;
        $wounded = max(1, (int)floor($baseWounded * (1 + $randomVariance)));
        $wounded = min($wounded, $troop['count']);
        
        if ($wounded > 0) {
            $woundedTroops[] = [
                'troop_type_id' => $troop['troop_type_id'],
                'count' => $wounded,
                'name' => $troop['name'],
                'icon' => $troop['icon'],
                'cost' => $troop['train_cost_coins']
            ];
            $totalWounded += $wounded;
            $logMessages[] = "{$troop['icon']} {$troop['name']}: {$wounded}体が負傷";
            
            // 防御部隊から減少
            $stmt = $pdo->prepare("
                UPDATE conquest_castle_defense
                SET count = count - ?
                WHERE castle_id = ? AND troop_type_id = ? AND user_id = ?
            ");
            $stmt->execute([$wounded, $castleId, $troop['troop_type_id'], $troop['user_id']]);
            
            // 負傷兵として追加
            $stmt = $pdo->prepare("
                INSERT INTO user_civilization_wounded_troops (user_id, troop_type_id, count)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE count = count + ?
            ");
            $stmt->execute([$troop['user_id'], $troop['troop_type_id'], $wounded, $wounded]);
        }
    }
    
    if ($totalWounded == 0) {
        return ['ok' => false, 'message' => '砲撃被害なし'];
    }
    
    // 砲撃ログを記録
    $stmt = $pdo->prepare("
        INSERT INTO conquest_bombardment_logs 
        (season_id, castle_id, user_id, bombardment_at, troops_wounded, total_wounded, log_message)
        VALUES (?, ?, ?, NOW(), ?, ?, ?)
    ");
    $stmt->execute([
        $seasonId,
        $castleId,
        $castle['owner_user_id'],
        json_encode($woundedTroops),
        $totalWounded,
        implode("\n", $logMessages)
    ]);
    
    // 城の最終砲撃時刻を更新
    $stmt = $pdo->prepare("UPDATE conquest_castles SET last_bombardment_at = NOW() WHERE id = ?");
    $stmt->execute([$castleId]);
    
    // 戦闘ログにも砲撃ログを記録
    $stmt = $pdo->prepare("
        INSERT INTO conquest_battle_logs 
        (log_type, season_id, castle_id, attacker_user_id, defender_user_id,
         attacker_troops, defender_troops, attacker_power, defender_power,
         attacker_losses, defender_losses, attacker_wounded, defender_wounded,
         winner_user_id, castle_captured, total_turns, battle_log_summary)
        VALUES ('bombardment', ?, ?, ?, ?, '[]', ?, 0, 0, '{}', ?, '{}', ?, NULL, 0, 1, ?)
    ");
    $stmt->execute([
        $seasonId,
        $castleId,
        $castle['owner_user_id'],
        $castle['owner_user_id'],
        json_encode($woundedTroops),
        json_encode(array_column($woundedTroops, 'count', 'troop_type_id')),
        json_encode(array_column($woundedTroops, 'count', 'troop_type_id')),
        implode("\n", $logMessages)
    ]);
    $battleLogId = $pdo->lastInsertId();
    
    // ターンログにも砲撃ログを記録
    $stmt = $pdo->prepare("
        INSERT INTO conquest_battle_turn_logs 
        (battle_id, turn_number, actor_side, action_type, 
         damage_dealt, log_message, attacker_hp_after, defender_hp_after)
        VALUES (?, 1, 'attacker', 'bombardment', ?, ?, 0, 0)
    ");
    $stmt->execute([
        $battleLogId,
        $totalWounded,
        implode("\n", $logMessages)
    ]);
    
    return [
        'ok' => true,
        'castle_id' => $castleId,
        'castle_name' => $castle['name'],
        'total_wounded' => $totalWounded
    ];
}

// メイン処理
echo "[" . date('Y-m-d H:i:s') . "] 砲撃処理開始\n";

$season = getActiveSeason($pdo);

if (!$season) {
    echo "アクティブなシーズンがありません\n";
    exit;
}

echo "シーズン {$season['season_number']} の砲撃処理\n";

// 砲撃対象の城を取得
$bombardmentInterval = CONQUEST_BOMBARDMENT_INTERVAL_MINUTES;
$stmt = $pdo->prepare("
    SELECT id, name FROM conquest_castles 
    WHERE season_id = ? 
      AND owner_user_id IS NOT NULL
      AND (last_bombardment_at IS NULL OR last_bombardment_at < DATE_SUB(NOW(), INTERVAL ? MINUTE))
");
$stmt->execute([$season['id'], $bombardmentInterval]);
$castles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "砲撃対象城数: " . count($castles) . "\n";

$pdo->beginTransaction();
try {
    $processedCount = 0;
    $totalWounded = 0;
    
    foreach ($castles as $castle) {
        $result = processBombardmentCron($pdo, $castle['id'], $season['id']);
        if ($result['ok']) {
            $processedCount++;
            $totalWounded += $result['total_wounded'];
            echo "  - {$castle['name']}: {$result['total_wounded']}体負傷\n";
        }
    }
    
    $pdo->commit();
    
    echo "処理完了: {$processedCount}城、総負傷兵 {$totalWounded}体\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "エラー: " . $e->getMessage() . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] 砲撃処理終了\n";
