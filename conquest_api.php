<?php
// ===============================================
// conquest_api.php
// 占領戦システムAPI
// ===============================================

require_once __DIR__ . '/config.php';

// 占領戦システム定数
define('CONQUEST_SEASON_DURATION_DAYS', 7);           // シーズン期間（日）
define('CONQUEST_MAP_SIZE', 5);                        // マップサイズ（5x5）
define('CONQUEST_NPC_BASE_POWER', 100);               // NPC基本パワー
define('CONQUEST_NPC_POWER_MULTIPLIER_INNER', 3);     // 内周のNPCパワー倍率
define('CONQUEST_NPC_POWER_MULTIPLIER_MIDDLE', 2);    // 中間のNPCパワー倍率
define('CONQUEST_SACRED_NPC_POWER', 5000);            // 神城のNPCパワー
define('CONQUEST_WOUNDED_RATE', 0.3);                 // 負傷兵発生率（30%）
define('CONQUEST_DEATH_RATE', 0.1);                   // 戦死率（10%）
define('CONQUEST_ATTACKER_BONUS', 1.1);               // 攻撃側ボーナス

// シーズン報酬定数
// 順位に応じた報酬 [coins, crystals, diamonds]
define('CONQUEST_REWARD_RANK_1', [10000, 100, 50]);   // 1位報酬
define('CONQUEST_REWARD_RANK_2', [5000, 50, 20]);     // 2位報酬
define('CONQUEST_REWARD_RANK_3', [3000, 30, 10]);     // 3位報酬
define('CONQUEST_REWARD_RANK_4_10', [1000, 10, 5]);   // 4-10位報酬
define('CONQUEST_REWARD_PARTICIPANT', [500, 5, 1]);   // 参加報酬（11位以下）

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
 * シーズン終了時に報酬を配布する
 * @param PDO $pdo
 * @param int $seasonId
 */
function distributeSeasonRewards($pdo, $seasonId) {
    // 既に報酬配布済みかチェック
    $stmt = $pdo->prepare("SELECT rewards_distributed FROM conquest_seasons WHERE id = ?");
    $stmt->execute([$seasonId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['rewards_distributed']) {
        return; // 既に配布済み
    }
    
    // ランキングを取得（城の数でソート、神城所有者が1位）
    $stmt = $pdo->prepare("
        SELECT cc.owner_user_id, 
               COUNT(*) as castle_count,
               SUM(CASE WHEN cc.is_sacred THEN 1 ELSE 0 END) as sacred_count
        FROM conquest_castles cc
        WHERE cc.season_id = ? AND cc.owner_user_id IS NOT NULL
        GROUP BY cc.owner_user_id
        ORDER BY sacred_count DESC, castle_count DESC
    ");
    $stmt->execute([$seasonId]);
    $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 報酬配布
    foreach ($rankings as $rank => $player) {
        $userId = $player['owner_user_id'];
        $rankNum = $rank + 1; // 1-indexed
        
        // 順位に応じた報酬を決定
        if ($rankNum == 1) {
            $reward = CONQUEST_REWARD_RANK_1;
        } elseif ($rankNum == 2) {
            $reward = CONQUEST_REWARD_RANK_2;
        } elseif ($rankNum == 3) {
            $reward = CONQUEST_REWARD_RANK_3;
        } elseif ($rankNum <= 10) {
            $reward = CONQUEST_REWARD_RANK_4_10;
        } else {
            $reward = CONQUEST_REWARD_PARTICIPANT;
        }
        
        $coins = $reward[0];
        $crystals = $reward[1];
        $diamonds = $reward[2];
        
        // ユーザーに報酬を付与
        $stmt = $pdo->prepare("
            UPDATE users 
            SET coins = coins + ?, crystals = crystals + ?, diamonds = diamonds + ?
            WHERE id = ?
        ");
        $stmt->execute([$coins, $crystals, $diamonds, $userId]);
        
        // 報酬ログを記録
        $stmt = $pdo->prepare("
            INSERT INTO conquest_season_rewards (season_id, user_id, rank_position, coins_reward, crystals_reward, diamonds_reward, castle_count)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$seasonId, $userId, $rankNum, $coins, $crystals, $diamonds, $player['castle_count']]);
    }
    
    // 報酬配布済みフラグを設定
    $stmt = $pdo->prepare("UPDATE conquest_seasons SET rewards_distributed = TRUE WHERE id = ?");
    $stmt->execute([$seasonId]);
}

/**
 * 現在のアクティブシーズンを取得（なければ新規作成）
 */
function getOrCreateActiveSeason($pdo) {
    // アクティブシーズンを確認
    $stmt = $pdo->prepare("SELECT * FROM conquest_seasons WHERE is_active = TRUE ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $season = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // シーズンがない、または終了している場合は新規作成
    if (!$season || strtotime($season['ends_at']) < time()) {
        // 古いシーズンを終了
        if ($season) {
            // 報酬を配布（シーズン終了時）
            distributeSeasonRewards($pdo, $season['id']);
            
            $stmt = $pdo->prepare("UPDATE conquest_seasons SET is_active = FALSE WHERE id = ?");
            $stmt->execute([$season['id']]);
            
            // 神城を持っていたユーザーを勝者として記録
            $stmt = $pdo->prepare("
                SELECT cc.owner_user_id, uc.civilization_name 
                FROM conquest_castles cc
                LEFT JOIN user_civilizations uc ON cc.owner_user_id = uc.user_id
                WHERE cc.season_id = ? AND cc.is_sacred = TRUE
            ");
            $stmt->execute([$season['id']]);
            $winner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($winner && $winner['owner_user_id']) {
                $stmt = $pdo->prepare("UPDATE conquest_seasons SET winner_user_id = ?, winner_civilization_name = ? WHERE id = ?");
                $stmt->execute([$winner['owner_user_id'], $winner['civilization_name'], $season['id']]);
            }
        }
        
        // 新しいシーズンを作成
        $season = createNewSeason($pdo);
    }
    
    return $season;
}

/**
 * 新しいシーズンを作成
 */
function createNewSeason($pdo) {
    $stmt = $pdo->prepare("SELECT MAX(season_number) FROM conquest_seasons");
    $stmt->execute();
    $lastSeasonNumber = (int)$stmt->fetchColumn();
    $newSeasonNumber = $lastSeasonNumber + 1;
    
    // 今週または先週の月曜日を起点とする（より明確なロジック）
    $today = strtotime('today');
    $dayOfWeek = date('N', $today); // 1=月曜, 7=日曜
    // 現在の週の月曜日を計算
    $monday = strtotime('-' . ($dayOfWeek - 1) . ' days', $today);
    $startedAt = date('Y-m-d 00:00:00', $monday);
    $endsAt = date('Y-m-d 23:59:59', strtotime('+' . (CONQUEST_SEASON_DURATION_DAYS - 1) . ' days', $monday));
    
    $stmt = $pdo->prepare("
        INSERT INTO conquest_seasons (season_number, started_at, ends_at, is_active)
        VALUES (?, ?, ?, TRUE)
    ");
    $stmt->execute([$newSeasonNumber, $startedAt, $endsAt]);
    $seasonId = $pdo->lastInsertId();
    
    // マップを生成
    generateConquestMap($pdo, $seasonId);
    
    $stmt = $pdo->prepare("SELECT * FROM conquest_seasons WHERE id = ?");
    $stmt->execute([$seasonId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 占領戦マップを生成
 */
function generateConquestMap($pdo, $seasonId) {
    $size = CONQUEST_MAP_SIZE;
    $center = floor($size / 2);
    $maxDistance = $center; // 中心から端までの最大距離
    
    $castleData = [];
    $castleKeys = [];
    
    // マップを生成（同心円状）
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            // 中心からの距離
            $distance = max(abs($x - $center), abs($y - $center));
            
            // 城の種類を決定
            $castleType = 'outer';
            $isSacred = false;
            $npcPower = CONQUEST_NPC_BASE_POWER;
            $icon = '🏰';
            
            if ($x == $center && $y == $center) {
                // 中心は神城
                $castleType = 'sacred';
                $isSacred = true;
                $npcPower = CONQUEST_SACRED_NPC_POWER;
                $icon = '⛩️';
            } elseif ($distance == 1) {
                // 内周（神城の周り）
                $castleType = 'inner';
                $npcPower = CONQUEST_NPC_BASE_POWER * CONQUEST_NPC_POWER_MULTIPLIER_INNER;
                $icon = '🏯';
            } elseif ($distance == $maxDistance) {
                // 最外周（外周）- 城を持っていないプレイヤーが最初に攻撃できる
                $castleType = 'outer';
                $npcPower = CONQUEST_NPC_BASE_POWER;
                $icon = '🏰';
            } else {
                // 中間（内周と外周の間）
                $castleType = 'middle';
                $npcPower = CONQUEST_NPC_BASE_POWER * CONQUEST_NPC_POWER_MULTIPLIER_MIDDLE;
                $icon = '🏰';
            }
            
            $castleKey = "castle_{$x}_{$y}";
            $castleKeys[$x][$y] = $castleKey;
            
            // 城の名前を生成
            $names = [
                'outer' => ['辺境の砦', '前哨基地', '守りの塔', '見張りの城', '境界の城'],
                'middle' => ['中央砦', '堅牢城', '戦略拠点', '守護の城'],
                'inner' => ['王城', '要塞', '大城塞', '内城'],
                'sacred' => ['神城']
            ];
            $nameList = $names[$castleType];
            $name = $nameList[($x + $y) % count($nameList)];
            
            $castleData[] = [
                'key' => $castleKey,
                'name' => $name,
                'x' => $x,
                'y' => $y,
                'type' => $castleType,
                'is_sacred' => $isSacred,
                'npc_power' => $npcPower,
                'icon' => $icon
            ];
        }
    }
    
    // 城を挿入
    $stmt = $pdo->prepare("
        INSERT INTO conquest_castles (season_id, castle_key, name, position_x, position_y, castle_type, is_sacred, npc_defense_power, icon)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($castleData as $castle) {
        $stmt->execute([
            $seasonId,
            $castle['key'],
            $castle['name'],
            $castle['x'],
            $castle['y'],
            $castle['type'],
            $castle['is_sacred'] ? 1 : 0,
            $castle['npc_power'],
            $castle['icon']
        ]);
    }
    
    // 隣接関係を設定
    $stmt = $pdo->prepare("SELECT id, castle_key, position_x, position_y FROM conquest_castles WHERE season_id = ?");
    $stmt->execute([$seasonId]);
    $castles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $castleIdMap = [];
    foreach ($castles as $c) {
        $castleIdMap[$c['position_x'] . '_' . $c['position_y']] = $c['id'];
    }
    
    $adjacencyStmt = $pdo->prepare("
        INSERT IGNORE INTO conquest_castle_adjacency (castle_id, adjacent_castle_id)
        VALUES (?, ?)
    ");
    
    foreach ($castles as $castle) {
        $x = $castle['position_x'];
        $y = $castle['position_y'];
        
        // 上下左右の隣接
        $neighbors = [
            [$x-1, $y], [$x+1, $y], [$x, $y-1], [$x, $y+1],
            // 斜めも許可
            [$x-1, $y-1], [$x+1, $y-1], [$x-1, $y+1], [$x+1, $y+1]
        ];
        
        foreach ($neighbors as $neighbor) {
            $nx = $neighbor[0];
            $ny = $neighbor[1];
            $key = $nx . '_' . $ny;
            
            if (isset($castleIdMap[$key])) {
                $adjacencyStmt->execute([$castle['id'], $castleIdMap[$key]]);
            }
        }
    }
}

/**
 * ユーザーが攻撃可能な城を取得
 */
function getAttackableCastles($pdo, $userId, $seasonId) {
    // ユーザーが占領している城を取得
    $stmt = $pdo->prepare("SELECT id FROM conquest_castles WHERE season_id = ? AND owner_user_id = ?");
    $stmt->execute([$seasonId, $userId]);
    $ownedCastles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($ownedCastles)) {
        // 城を持っていない場合、外周の城のみ攻撃可能
        $stmt = $pdo->prepare("
            SELECT cc.* 
            FROM conquest_castles cc
            WHERE cc.season_id = ? AND cc.castle_type = 'outer'
        ");
        $stmt->execute([$seasonId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 所有城に隣接する、自分が持っていない城を取得
    $placeholders = implode(',', array_fill(0, count($ownedCastles), '?'));
    $stmt = $pdo->prepare("
        SELECT DISTINCT cc.*
        FROM conquest_castles cc
        JOIN conquest_castle_adjacency cca ON cc.id = cca.adjacent_castle_id
        WHERE cca.castle_id IN ($placeholders)
          AND cc.season_id = ?
          AND (cc.owner_user_id IS NULL OR cc.owner_user_id != ?)
    ");
    $params = array_merge($ownedCastles, [$seasonId, $userId]);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 城の防御パワーを計算
 */
function calculateCastleDefensePower($pdo, $castle) {
    if ($castle['owner_user_id'] === null) {
        // NPC防御
        return [
            'total_power' => $castle['npc_defense_power'],
            'is_npc' => true,
            'troops' => []
        ];
    }
    
    // ユーザー防御部隊を取得
    $stmt = $pdo->prepare("
        SELECT ccd.*, tt.name, tt.icon, tt.attack_power, tt.defense_power, 
               COALESCE(tt.health_points, 100) as health_points,
               COALESCE(tt.troop_category, 'infantry') as troop_category
        FROM conquest_castle_defense ccd
        JOIN civilization_troop_types tt ON ccd.troop_type_id = tt.id
        WHERE ccd.castle_id = ?
    ");
    $stmt->execute([$castle['id']]);
    $defenseTroops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalPower = 0;
    $troops = [];
    
    foreach ($defenseTroops as $troop) {
        $power = ($troop['attack_power'] + floor($troop['defense_power'] / 2) + floor($troop['health_points'] / 50)) * $troop['count'];
        $totalPower += $power;
        $troops[] = [
            'troop_type_id' => $troop['troop_type_id'],
            'name' => $troop['name'],
            'icon' => $troop['icon'],
            'count' => $troop['count'],
            'power' => $power,
            'category' => $troop['troop_category']
        ];
    }
    
    // 防御部隊がない場合はNPCデフォルト防御
    if (empty($troops)) {
        return [
            'total_power' => max(50, $castle['npc_defense_power'] / 2),
            'is_npc' => true,
            'troops' => []
        ];
    }
    
    return [
        'total_power' => $totalPower,
        'is_npc' => false,
        'defender_user_id' => $castle['owner_user_id'],
        'troops' => $troops
    ];
}

// ===============================================
// API Actions
// ===============================================

// シーズン情報を取得
if ($action === 'get_season') {
    try {
        $season = getOrCreateActiveSeason($pdo);
        
        // マップデータを取得
        $stmt = $pdo->prepare("
            SELECT cc.*, uc.civilization_name as owner_civ_name, u.handle as owner_handle
            FROM conquest_castles cc
            LEFT JOIN user_civilizations uc ON cc.owner_user_id = uc.user_id
            LEFT JOIN users u ON cc.owner_user_id = u.id
            WHERE cc.season_id = ?
            ORDER BY cc.position_y, cc.position_x
        ");
        $stmt->execute([$season['id']]);
        $castles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ユーザーが所有する城を取得
        $stmt = $pdo->prepare("SELECT id FROM conquest_castles WHERE season_id = ? AND owner_user_id = ?");
        $stmt->execute([$season['id'], $me['id']]);
        $ownedCastleIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        
        // 攻撃可能な城を取得
        $attackableCastles = getAttackableCastles($pdo, $me['id'], $season['id']);
        $attackableCastleIds = array_map('intval', array_column($attackableCastles, 'id'));
        
        // 残り時間を計算
        $remainingSeconds = max(0, strtotime($season['ends_at']) - time());
        
        echo json_encode([
            'ok' => true,
            'season' => $season,
            'castles' => $castles,
            'owned_castle_ids' => $ownedCastleIds,
            'attackable_castle_ids' => $attackableCastleIds,
            'remaining_seconds' => $remainingSeconds,
            'map_size' => CONQUEST_MAP_SIZE
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 城の詳細を取得
if ($action === 'get_castle') {
    $castleId = (int)($input['castle_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("
            SELECT cc.*, uc.civilization_name as owner_civ_name, u.handle as owner_handle
            FROM conquest_castles cc
            LEFT JOIN user_civilizations uc ON cc.owner_user_id = uc.user_id
            LEFT JOIN users u ON cc.owner_user_id = u.id
            WHERE cc.id = ?
        ");
        $stmt->execute([$castleId]);
        $castle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$castle) {
            throw new Exception('城が見つかりません');
        }
        
        // 防御パワーを計算
        $defense = calculateCastleDefensePower($pdo, $castle);
        
        // 隣接城を取得
        $stmt = $pdo->prepare("
            SELECT cc.id, cc.name, cc.icon, cc.owner_user_id, uc.civilization_name
            FROM conquest_castle_adjacency cca
            JOIN conquest_castles cc ON cca.adjacent_castle_id = cc.id
            LEFT JOIN user_civilizations uc ON cc.owner_user_id = uc.user_id
            WHERE cca.castle_id = ?
        ");
        $stmt->execute([$castleId]);
        $adjacentCastles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 最近の戦闘ログを取得
        $stmt = $pdo->prepare("
            SELECT cbl.*, 
                   attacker.handle as attacker_handle,
                   attacker_civ.civilization_name as attacker_civ_name
            FROM conquest_battle_logs cbl
            JOIN users attacker ON cbl.attacker_user_id = attacker.id
            LEFT JOIN user_civilizations attacker_civ ON cbl.attacker_user_id = attacker_civ.user_id
            WHERE cbl.castle_id = ?
            ORDER BY cbl.battle_at DESC
            LIMIT 10
        ");
        $stmt->execute([$castleId]);
        $recentBattles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'castle' => $castle,
            'defense' => $defense,
            'adjacent_castles' => $adjacentCastles,
            'recent_battles' => $recentBattles
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 城を攻撃
if ($action === 'attack_castle') {
    $castleId = (int)($input['castle_id'] ?? 0);
    $troops = $input['troops'] ?? []; // [{troop_type_id: 1, count: 10}, ...]
    
    if (empty($troops)) {
        echo json_encode(['ok' => false, 'error' => '攻撃部隊を選択してください']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        $season = getOrCreateActiveSeason($pdo);
        
        // 城を取得
        $stmt = $pdo->prepare("SELECT * FROM conquest_castles WHERE id = ? AND season_id = ?");
        $stmt->execute([$castleId, $season['id']]);
        $castle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$castle) {
            throw new Exception('城が見つかりません');
        }
        
        if ($castle['owner_user_id'] == $me['id']) {
            throw new Exception('自分の城を攻撃することはできません');
        }
        
        // 攻撃可能かチェック
        $attackableCastles = getAttackableCastles($pdo, $me['id'], $season['id']);
        $attackableCastleIds = array_map('intval', array_column($attackableCastles, 'id'));
        
        if (!in_array((int)$castle['id'], $attackableCastleIds, true)) {
            throw new Exception('この城は攻撃できません。隣接した城を占領してから攻撃してください。');
        }
        
        // 攻撃部隊を検証
        $attackerTroops = [];
        $attackerPower = 0;
        
        foreach ($troops as $troop) {
            $troopTypeId = (int)$troop['troop_type_id'];
            $count = (int)$troop['count'];
            
            if ($count <= 0) continue;
            
            // 所有兵士数を確認
            $stmt = $pdo->prepare("
                SELECT uct.count, tt.name, tt.icon, tt.attack_power, tt.defense_power,
                       COALESCE(tt.health_points, 100) as health_points,
                       COALESCE(tt.troop_category, 'infantry') as troop_category
                FROM user_civilization_troops uct
                JOIN civilization_troop_types tt ON uct.troop_type_id = tt.id
                WHERE uct.user_id = ? AND uct.troop_type_id = ?
            ");
            $stmt->execute([$me['id'], $troopTypeId]);
            $userTroop = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userTroop || $userTroop['count'] < $count) {
                throw new Exception('兵士が不足しています');
            }
            
            $power = ($userTroop['attack_power'] + floor($userTroop['defense_power'] / 2) + floor($userTroop['health_points'] / 50)) * $count;
            $attackerPower += $power;
            
            $attackerTroops[] = [
                'troop_type_id' => $troopTypeId,
                'name' => $userTroop['name'],
                'icon' => $userTroop['icon'],
                'count' => $count,
                'power' => $power,
                'category' => $userTroop['troop_category']
            ];
        }
        
        if ($attackerPower <= 0) {
            throw new Exception('攻撃部隊のパワーが不足しています');
        }
        
        // 防御パワーを計算
        $defense = calculateCastleDefensePower($pdo, $castle);
        $defenderPower = $defense['total_power'];
        
        // 戦闘判定
        $attackerRoll = mt_rand(1, 100) + ($attackerPower * CONQUEST_ATTACKER_BONUS);
        $defenderRoll = mt_rand(1, 100) + $defenderPower;
        
        $attackerWins = $attackerRoll > $defenderRoll;
        
        // 損失を計算
        $attackerLosses = [];
        $attackerWounded = [];
        $defenderLosses = [];
        $defenderWounded = [];
        
        $lossRate = $attackerWins ? 0.1 : 0.3; // 勝者は10%、敗者は30%の損失
        $woundedRate = CONQUEST_WOUNDED_RATE;
        
        foreach ($attackerTroops as $troop) {
            $losses = (int)floor($troop['count'] * $lossRate);
            $wounded = (int)floor($troop['count'] * $woundedRate);
            
            if ($losses > 0) {
                $attackerLosses[$troop['troop_type_id']] = $losses;
            }
            if ($wounded > 0) {
                $attackerWounded[$troop['troop_type_id']] = $wounded;
            }
            
            // 兵士を減少
            $totalLoss = min($troop['count'], $losses + $wounded);
            $stmt = $pdo->prepare("
                UPDATE user_civilization_troops
                SET count = count - ?
                WHERE user_id = ? AND troop_type_id = ?
            ");
            $stmt->execute([$totalLoss, $me['id'], $troop['troop_type_id']]);
            
            // 負傷兵を追加
            if ($wounded > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_civilization_wounded_troops (user_id, troop_type_id, count)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE count = count + ?
                ");
                $stmt->execute([$me['id'], $troop['troop_type_id'], $wounded, $wounded]);
            }
        }
        
        // 防御側の損失処理
        if (!$defense['is_npc'] && !empty($defense['troops'])) {
            $defLossRate = $attackerWins ? 0.3 : 0.1;
            
            foreach ($defense['troops'] as $troop) {
                $losses = (int)floor($troop['count'] * $defLossRate);
                $wounded = (int)floor($troop['count'] * $woundedRate);
                
                if ($losses > 0) {
                    $defenderLosses[$troop['troop_type_id']] = $losses;
                }
                if ($wounded > 0) {
                    $defenderWounded[$troop['troop_type_id']] = $wounded;
                }
                
                // 城の防御部隊から減少
                $totalLoss = min($troop['count'], $losses + $wounded);
                $stmt = $pdo->prepare("
                    UPDATE conquest_castle_defense
                    SET count = count - ?
                    WHERE castle_id = ? AND troop_type_id = ?
                ");
                $stmt->execute([$totalLoss, $castle['id'], $troop['troop_type_id']]);
                
                // 防御側ユーザーの負傷兵を追加
                if ($wounded > 0 && $defense['defender_user_id']) {
                    $stmt = $pdo->prepare("
                        INSERT INTO user_civilization_wounded_troops (user_id, troop_type_id, count)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE count = count + ?
                    ");
                    $stmt->execute([$defense['defender_user_id'], $troop['troop_type_id'], $wounded, $wounded]);
                }
            }
        }
        
        // 城の占領
        $castleCaptured = false;
        if ($attackerWins) {
            $castleCaptured = true;
            
            // 残りの防御部隊を元の所有者に戻す
            if (!$defense['is_npc'] && $defense['defender_user_id']) {
                $stmt = $pdo->prepare("SELECT troop_type_id, count, user_id FROM conquest_castle_defense WHERE castle_id = ? AND count > 0");
                $stmt->execute([$castle['id']]);
                $remainingTroops = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($remainingTroops as $troop) {
                    $stmt = $pdo->prepare("
                        INSERT INTO user_civilization_troops (user_id, troop_type_id, count)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE count = count + ?
                    ");
                    $stmt->execute([$troop['user_id'], $troop['troop_type_id'], $troop['count'], $troop['count']]);
                }
            }
            
            $stmt = $pdo->prepare("UPDATE conquest_castles SET owner_user_id = ? WHERE id = ?");
            $stmt->execute([$me['id'], $castle['id']]);
            
            // 古い防御部隊をクリア
            $stmt = $pdo->prepare("DELETE FROM conquest_castle_defense WHERE castle_id = ?");
            $stmt->execute([$castle['id']]);
        }
        
        // 戦闘ログを記録
        $stmt = $pdo->prepare("
            INSERT INTO conquest_battle_logs 
            (season_id, castle_id, attacker_user_id, defender_user_id, 
             attacker_troops, defender_troops, attacker_power, defender_power,
             attacker_losses, defender_losses, attacker_wounded, defender_wounded,
             winner_user_id, castle_captured)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $season['id'],
            $castle['id'],
            $me['id'],
            $defense['is_npc'] ? null : $defense['defender_user_id'],
            json_encode($attackerTroops),
            json_encode($defense['troops']),
            $attackerPower,
            $defenderPower,
            json_encode($attackerLosses),
            json_encode($defenderLosses),
            json_encode($attackerWounded),
            json_encode($defenderWounded),
            $attackerWins ? $me['id'] : ($defense['is_npc'] ? null : $defense['defender_user_id']),
            $castleCaptured ? 1 : 0
        ]);
        
        $pdo->commit();
        
        $resultText = $attackerWins ? '勝利！' : '敗北...';
        $message = $attackerWins 
            ? "{$castle['name']}を占領しました！" 
            : "{$castle['name']}の攻略に失敗しました...";
        
        echo json_encode([
            'ok' => true,
            'result' => $attackerWins ? 'victory' : 'defeat',
            'message' => $message,
            'castle_captured' => $castleCaptured,
            'attacker_power' => $attackerPower,
            'defender_power' => $defenderPower,
            'attacker_losses' => $attackerLosses,
            'attacker_wounded' => $attackerWounded,
            'defender_losses' => $defenderLosses,
            'defender_wounded' => $defenderWounded
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 城に防御部隊を配置
if ($action === 'set_castle_defense') {
    $castleId = (int)($input['castle_id'] ?? 0);
    $troops = $input['troops'] ?? []; // [{troop_type_id: 1, count: 10}, ...]
    
    $pdo->beginTransaction();
    try {
        $season = getOrCreateActiveSeason($pdo);
        
        // 城を取得（自分が所有しているか確認）
        $stmt = $pdo->prepare("SELECT * FROM conquest_castles WHERE id = ? AND season_id = ? AND owner_user_id = ?");
        $stmt->execute([$castleId, $season['id'], $me['id']]);
        $castle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$castle) {
            throw new Exception('この城を所有していません');
        }
        
        // 既存の防御部隊を手元に戻す
        $stmt = $pdo->prepare("SELECT troop_type_id, count FROM conquest_castle_defense WHERE castle_id = ? AND user_id = ?");
        $stmt->execute([$castleId, $me['id']]);
        $existingTroops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($existingTroops as $existingTroop) {
            if ($existingTroop['count'] > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_civilization_troops (user_id, troop_type_id, count)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE count = count + ?
                ");
                $stmt->execute([$me['id'], $existingTroop['troop_type_id'], $existingTroop['count'], $existingTroop['count']]);
            }
        }
        
        // 既存の防御部隊をクリア
        $stmt = $pdo->prepare("DELETE FROM conquest_castle_defense WHERE castle_id = ? AND user_id = ?");
        $stmt->execute([$castleId, $me['id']]);
        
        // 新しい防御部隊を設定
        foreach ($troops as $troop) {
            $troopTypeId = (int)$troop['troop_type_id'];
            $count = (int)$troop['count'];
            
            if ($count <= 0) continue;
            
            // 所有兵士数を確認
            $stmt = $pdo->prepare("SELECT count FROM user_civilization_troops WHERE user_id = ? AND troop_type_id = ?");
            $stmt->execute([$me['id'], $troopTypeId]);
            $ownedCount = (int)$stmt->fetchColumn();
            
            if ($ownedCount < $count) {
                throw new Exception('兵士が不足しています');
            }
            
            // 兵士を消費
            $stmt = $pdo->prepare("
                UPDATE user_civilization_troops
                SET count = count - ?
                WHERE user_id = ? AND troop_type_id = ?
            ");
            $stmt->execute([$count, $me['id'], $troopTypeId]);
            
            // 防御部隊に追加
            $stmt = $pdo->prepare("
                INSERT INTO conquest_castle_defense (castle_id, user_id, troop_type_id, count)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$castleId, $me['id'], $troopTypeId, $count]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => '防御部隊を配置しました'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 城から防御部隊を撤退
if ($action === 'withdraw_castle_defense') {
    $castleId = (int)($input['castle_id'] ?? 0);
    
    $pdo->beginTransaction();
    try {
        $season = getOrCreateActiveSeason($pdo);
        
        // 城を取得
        $stmt = $pdo->prepare("SELECT * FROM conquest_castles WHERE id = ? AND season_id = ?");
        $stmt->execute([$castleId, $season['id']]);
        $castle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$castle) {
            throw new Exception('城が見つかりません');
        }
        
        // 自分の防御部隊を取得
        $stmt = $pdo->prepare("SELECT * FROM conquest_castle_defense WHERE castle_id = ? AND user_id = ?");
        $stmt->execute([$castleId, $me['id']]);
        $defenseTroops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 兵士を戻す
        foreach ($defenseTroops as $troop) {
            if ($troop['count'] > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_civilization_troops (user_id, troop_type_id, count)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE count = count + ?
                ");
                $stmt->execute([$me['id'], $troop['troop_type_id'], $troop['count'], $troop['count']]);
            }
        }
        
        // 防御部隊を削除
        $stmt = $pdo->prepare("DELETE FROM conquest_castle_defense WHERE castle_id = ? AND user_id = ?");
        $stmt->execute([$castleId, $me['id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => '防御部隊を撤退させました'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ランキングを取得
if ($action === 'get_ranking') {
    try {
        $season = getOrCreateActiveSeason($pdo);
        
        // 城の数でランキング
        $stmt = $pdo->prepare("
            SELECT cc.owner_user_id, u.handle, uc.civilization_name,
                   COUNT(*) as castle_count,
                   SUM(CASE WHEN cc.is_sacred THEN 1 ELSE 0 END) as sacred_count
            FROM conquest_castles cc
            JOIN users u ON cc.owner_user_id = u.id
            JOIN user_civilizations uc ON cc.owner_user_id = uc.user_id
            WHERE cc.season_id = ? AND cc.owner_user_id IS NOT NULL
            GROUP BY cc.owner_user_id
            ORDER BY sacred_count DESC, castle_count DESC
            LIMIT 20
        ");
        $stmt->execute([$season['id']]);
        $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'rankings' => $rankings
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// シーズンをリセット（管理者のみ）
if ($action === 'reset_season') {
    // 管理者チェック（role='admin'であればOK）
    if (($me['role'] ?? '') !== 'admin') {
        echo json_encode(['ok' => false, 'error' => '管理者のみ実行できます']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        // 現在のアクティブシーズンを取得
        $stmt = $pdo->prepare("SELECT * FROM conquest_seasons WHERE is_active = TRUE ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $currentSeason = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentSeason) {
            // 報酬を配布
            distributeSeasonRewards($pdo, $currentSeason['id']);
            
            // 神城を持っていたユーザーを勝者として記録
            $stmt = $pdo->prepare("
                SELECT cc.owner_user_id, uc.civilization_name 
                FROM conquest_castles cc
                LEFT JOIN user_civilizations uc ON cc.owner_user_id = uc.user_id
                WHERE cc.season_id = ? AND cc.is_sacred = TRUE
            ");
            $stmt->execute([$currentSeason['id']]);
            $winner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($winner && $winner['owner_user_id']) {
                $stmt = $pdo->prepare("UPDATE conquest_seasons SET winner_user_id = ?, winner_civilization_name = ? WHERE id = ?");
                $stmt->execute([$winner['owner_user_id'], $winner['civilization_name'], $currentSeason['id']]);
            }
            
            // シーズンを終了
            $stmt = $pdo->prepare("UPDATE conquest_seasons SET is_active = FALSE WHERE id = ?");
            $stmt->execute([$currentSeason['id']]);
        }
        
        // 新しいシーズンを作成
        $season = createNewSeason($pdo);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => '報酬を配布し、新しいシーズンを開始しました',
            'season' => $season
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 過去のシーズンを取得
if ($action === 'get_past_seasons') {
    try {
        $stmt = $pdo->prepare("
            SELECT cs.*, u.handle as winner_handle
            FROM conquest_seasons cs
            LEFT JOIN users u ON cs.winner_user_id = u.id
            WHERE cs.is_active = FALSE
            ORDER BY cs.season_number DESC
            LIMIT 10
        ");
        $stmt->execute();
        $pastSeasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'past_seasons' => $pastSeasons
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'invalid_action']);
