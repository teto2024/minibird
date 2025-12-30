<?php
// ===============================================
// conquest_api.php
// 占領戦システムAPI
// ===============================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/battle_engine.php';
require_once __DIR__ . '/quest_helpers.php';

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
define('CONQUEST_REWARD_RANK_1', [100000, 1000, 100]);   // 1位報酬
define('CONQUEST_REWARD_RANK_2', [50000, 500, 50]);     // 2位報酬
define('CONQUEST_REWARD_RANK_3', [30000, 300, 30]);     // 3位報酬
define('CONQUEST_REWARD_RANK_4_10', [10000, 100, 10]);   // 4-10位報酬
define('CONQUEST_REWARD_PARTICIPANT', [500, 5, 1]);   // 参加報酬（11位以下）

// 装備バフの軍事力への変換定数
define('CONQUEST_HEALTH_TO_POWER_RATIO', 10);       // 体力から軍事力への変換比率
define('CONQUEST_TROOP_HEALTH_TO_POWER_RATIO', 50); // 兵種体力から軍事力への変換比率
define('CONQUEST_ARMOR_MAX_REDUCTION', 0.5);         // アーマーによる最大ダメージ軽減率（50%）
define('CONQUEST_ARMOR_PERCENT_DIVISOR', 100);       // アーマー値を軽減率に変換する除数

// 砲撃システム定数
define('CONQUEST_BOMBARDMENT_INTERVAL_MINUTES', 30);     // 砲撃間隔（分）
define('CONQUEST_BOMBARDMENT_BASE_RATE', 0.05);          // 基本損失率（5%）
define('CONQUEST_BOMBARDMENT_COST_FACTOR', 0.0001);      // コストによる損失軽減係数
define('CONQUEST_BOMBARDMENT_MAX_COST_REDUCTION', 0.04); // コストによる最大軽減率
define('CONQUEST_BOMBARDMENT_MIN_LOSS_RATE', 0.01);      // 最低損失率（1%）
define('CONQUEST_BOMBARDMENT_VARIANCE_RANGE', 20);       // 乱数変動幅（±%）
define('CONQUEST_BOMBARDMENT_WARNING_MINUTES', 5);       // 警告表示開始（分）

// 城耐久度システム定数
define('CONQUEST_DURABILITY_OUTER', 500);                // 外周城の耐久度
define('CONQUEST_DURABILITY_MIDDLE', 1000);              // 中間城の耐久度
define('CONQUEST_DURABILITY_INNER', 2000);               // 内周城の耐久度
define('CONQUEST_DURABILITY_SACRED', 5000);              // 神城の耐久度
define('CONQUEST_BASE_DURABILITY_DAMAGE', 100);          // 基本耐久度ダメージ（砲撃基準値）
define('CONQUEST_BOMBARDMENT_DURABILITY_DAMAGE', 100);   // 砲撃による耐久度ダメージ基準値
define('CONQUEST_DURABILITY_DAMAGE_VARIANCE', 0.2);      // 砲撃ダメージの乱数変動幅（±20%）
define('CONQUEST_SIEGE_DURABILITY_MULTIPLIER', 3.0);     // 攻城兵器の耐久度ダメージ倍率（デフォルト）
define('CONQUEST_DEFENDER_BONUS', 1.2);                  // 占領戦防御側ボーナス（20%増加）
define('CONQUEST_ANNOUNCEMENT_BOT_ID', 5);               // お知らせbot ユーザーID

// 出撃兵士数上限システム定数（civilization_api.phpと同じ）
define('CONQUEST_BASE_TROOP_DEPLOYMENT_LIMIT', 100);     // 基本出撃兵士数上限

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
 * ユーザーの装備バフを取得するヘルパー関数
 * 
 * @param PDO $pdo データベース接続
 * @param int $userId ユーザーID
 * @return array ['attack' => float, 'armor' => float, 'health' => float] 各バフの合計値
 */
function getConquestUserEquipmentBuffs($pdo, $userId) {
    // ユーザーIDの検証
    if (!is_int($userId) && !is_numeric($userId)) {
        return ['attack' => 0, 'armor' => 0, 'health' => 0];
    }
    $userId = (int)$userId;
    if ($userId <= 0) {
        return ['attack' => 0, 'armor' => 0, 'health' => 0];
    }
    
    $stmt = $pdo->prepare("
        SELECT buffs FROM user_equipment 
        WHERE user_id = ? AND is_equipped = 1
    ");
    $stmt->execute([$userId]);
    $equippedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalBuffs = [
        'attack' => 0,
        'armor' => 0,
        'health' => 0
    ];
    
    foreach ($equippedItems as $item) {
        $decoded = json_decode($item['buffs'], true);
        $buffs = is_array($decoded) ? $decoded : [];
        foreach ($totalBuffs as $key => $value) {
            if (isset($buffs[$key])) {
                $totalBuffs[$key] += (float)$buffs[$key];
            }
        }
    }
    
    return $totalBuffs;
}

/**
 * 出撃兵士数上限を計算するヘルパー関数
 * 建物のtroop_deployment_bonus列をレベルと掛け合わせて計算
 * 
 * @param PDO $pdo データベース接続
 * @param int $userId ユーザーID
 * @return array ['base_limit' => int, 'building_bonus' => int, 'total_limit' => int]
 */
function calculateConquestTroopDeploymentLimit($pdo, $userId) {
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
    
    $baseLimit = CONQUEST_BASE_TROOP_DEPLOYMENT_LIMIT;
    $totalLimit = $baseLimit + $buildingBonus;
    
    return [
        'base_limit' => $baseLimit,
        'building_bonus' => $buildingBonus,
        'total_limit' => $totalLimit
    ];
}

/**
 * 装備バフから追加軍事力を計算
 * アーマーは防御側のダメージ軽減として別途使用するため、攻撃力と体力のみ軍事力に変換
 * 
 * @param array $equipmentBuffs 装備バフ配列
 * @return int 追加軍事力
 */
function calculateEquipmentPower($equipmentBuffs) {
    return (int)floor($equipmentBuffs['attack'] + ($equipmentBuffs['health'] / CONQUEST_HEALTH_TO_POWER_RATIO));
}

/**
 * 城の種類に応じた最大耐久度を取得
 * @param string $castleType 城の種類
 * @return int 最大耐久度
 */
function getCastleMaxDurability($castleType) {
    switch ($castleType) {
        case 'outer':
            return CONQUEST_DURABILITY_OUTER;
        case 'middle':
            return CONQUEST_DURABILITY_MIDDLE;
        case 'inner':
            return CONQUEST_DURABILITY_INNER;
        case 'sacred':
            return CONQUEST_DURABILITY_SACRED;
        default:
            return CONQUEST_DURABILITY_OUTER;
    }
}

/**
 * 攻撃部隊の攻城ダメージを計算
 * @param PDO $pdo
 * @param array $troops 攻撃部隊 [{troop_type_id, count}, ...]
 * @return int 攻城ダメージ
 */
function calculateSiegeDamage($pdo, $troops) {
    $totalDamage = 0;
    
    foreach ($troops as $troop) {
        $troopTypeId = (int)$troop['troop_type_id'];
        $count = (int)$troop['count'];
        
        if ($count <= 0) continue;
        
        // 兵種の攻城ダメージ倍率を取得
        $stmt = $pdo->prepare("
            SELECT attack_power, COALESCE(siege_damage_multiplier, 1.0) as siege_multiplier
            FROM civilization_troop_types WHERE id = ?
        ");
        $stmt->execute([$troopTypeId]);
        $troopType = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($troopType) {
            // 攻城ダメージ = 攻撃力 * 攻城ダメージ倍率 * 兵数 / 10
            $damage = (int)floor($troopType['attack_power'] * $troopType['siege_multiplier'] * $count / 10);
            $totalDamage += $damage;
        }
    }
    
    // 最低でも基本ダメージを与える
    return max(CONQUEST_BASE_DURABILITY_DAMAGE, $totalDamage);
}

/**
 * 占領告知メッセージを送信
 * 外周以外の城を占領した場合にお知らせbotから全体フィードに投稿
 * 
 * @param PDO $pdo データベース接続
 * @param string $castleName 城の名前
 * @param string $castleType 城の種類
 * @param string|null $playerHandle 占領したプレイヤーのハンドル (NPCの場合はnull)
 * @param bool $isNpcCapture NPCによる占領かどうか
 */
function sendConquestAnnouncement($pdo, $castleName, $castleType, $playerHandle = null, $isNpcCapture = false) {
    // 外周城は告知しない
    if ($castleType === 'outer') {
        return;
    }
    
    // メッセージを生成
    if ($isNpcCapture) {
        $content = "NPCが{$castleName}を占領しました！";
    } else {
        $content = "@{$playerHandle}が{$castleName}を占領しました！破竹の勢い！！";
    }
    
    // お知らせbotとして投稿
    $html = markdown_to_html($content);
    $stmt = $pdo->prepare("
        INSERT INTO posts (user_id, content_md, content_html, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([CONQUEST_ANNOUNCEMENT_BOT_ID, $content, $html]);
}

/**
 * 地形バフを取得
 * @param PDO $pdo データベース接続
 * @param string $terrainType 地形タイプ
 * @param string $troopCategory 兵種カテゴリ
 * @return array ['attack_buff' => float, 'defense_buff' => float]
 */
function getTerrainBuff($pdo, $terrainType, $troopCategory) {
    $stmt = $pdo->prepare("
        SELECT attack_buff, defense_buff 
        FROM conquest_terrain_buffs 
        WHERE terrain_type = ? AND troop_category = ?
    ");
    $stmt->execute([$terrainType, $troopCategory]);
    $buff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($buff) {
        return [
            'attack_buff' => (float)$buff['attack_buff'],
            'defense_buff' => (float)$buff['defense_buff']
        ];
    }
    
    return ['attack_buff' => 1.0, 'defense_buff' => 1.0];
}

/**
 * 部隊の地形バフを計算
 * @param PDO $pdo データベース接続
 * @param array $troops 部隊情報 [{troop_type_id, count, category}, ...]
 * @param string $terrainType 地形タイプ
 * @param bool $isDefender 防御側かどうか
 * @return float 総合バフ倍率
 */
function calculateTerrainBuffMultiplier($pdo, $troops, $terrainType, $isDefender = false) {
    if (empty($terrainType) || $terrainType === 'plains') {
        return 1.0; // 平原はデフォルト
    }
    
    $totalPower = 0;
    $buffedPower = 0;
    
    foreach ($troops as $troop) {
        $count = (int)($troop['count'] ?? 0);
        if ($count <= 0) continue;
        
        // 兵種情報を取得
        $troopTypeId = (int)$troop['troop_type_id'];
        $stmt = $pdo->prepare("
            SELECT attack_power, defense_power, COALESCE(troop_category, 'infantry') as troop_category
            FROM civilization_troop_types WHERE id = ?
        ");
        $stmt->execute([$troopTypeId]);
        $troopInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$troopInfo) continue;
        
        $power = ($troopInfo['attack_power'] + $troopInfo['defense_power']) * $count;
        $totalPower += $power;
        
        // 地形バフを取得
        $buff = getTerrainBuff($pdo, $terrainType, $troopInfo['troop_category']);
        $buffMultiplier = $isDefender ? $buff['defense_buff'] : $buff['attack_buff'];
        $buffedPower += $power * $buffMultiplier;
    }
    
    if ($totalPower <= 0) {
        return 1.0;
    }
    
    return $buffedPower / $totalPower;
}

/**
 * ステルス兵を除外した防御部隊を取得（敵から見えない）
 * @param PDO $pdo データベース接続
 * @param array $troops 防御部隊
 * @return array ステルス以外の部隊
 */
function getVisibleDefenseTroops($pdo, $troops) {
    $visibleTroops = [];
    
    foreach ($troops as $troop) {
        $troopTypeId = (int)($troop['troop_type_id'] ?? 0);
        
        $stmt = $pdo->prepare("
            SELECT COALESCE(is_stealth, FALSE) as is_stealth
            FROM civilization_troop_types WHERE id = ?
        ");
        $stmt->execute([$troopTypeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // ステルスでない兵士のみ表示
        if (!$result || !$result['is_stealth']) {
            $visibleTroops[] = $troop;
        }
    }
    
    return $visibleTroops;
}

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
    
    // 地形タイプとその設定
    $terrainTypes = [
        'plains' => ['movement_cost' => 1, 'defense_bonus' => 1.00],
        'forest' => ['movement_cost' => 2, 'defense_bonus' => 1.15],
        'mountain' => ['movement_cost' => 3, 'defense_bonus' => 1.25],
        'river' => ['movement_cost' => 2, 'defense_bonus' => 1.10],
        'coastal' => ['movement_cost' => 1, 'defense_bonus' => 1.05],
        'fortress' => ['movement_cost' => 2, 'defense_bonus' => 1.30]
    ];
    
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
            $terrainType = 'plains';
            
            if ($x == $center && $y == $center) {
                // 中心は神城
                $castleType = 'sacred';
                $isSacred = true;
                $npcPower = CONQUEST_SACRED_NPC_POWER;
                $icon = '⛩️';
                $terrainType = 'fortress';
            } elseif ($distance == 1) {
                // 内周（神城の周り）
                $castleType = 'inner';
                $npcPower = CONQUEST_NPC_BASE_POWER * CONQUEST_NPC_POWER_MULTIPLIER_INNER;
                $icon = '🏯';
                $terrainType = 'fortress';
            } elseif ($distance == $maxDistance) {
                // 最外周（外周）- 城を持っていないプレイヤーが最初に攻撃できる
                $castleType = 'outer';
                $npcPower = CONQUEST_NPC_BASE_POWER;
                $icon = '🏰';
                // 外周は位置に応じて地形を割り当て
                if ($x == 0) {
                    $terrainType = 'plains';
                } elseif ($x == $size - 1) {
                    $terrainType = 'forest';
                } elseif ($y == 0) {
                    $terrainType = 'coastal';
                } elseif ($y == $size - 1) {
                    $terrainType = 'mountain';
                } else {
                    // コーナーはランダムに割り当て
                    $outerTerrains = ['plains', 'forest', 'coastal', 'mountain'];
                    $terrainType = $outerTerrains[($x + $y) % count($outerTerrains)];
                }
            } else {
                // 中間（内周と外周の間）
                $castleType = 'middle';
                $npcPower = CONQUEST_NPC_BASE_POWER * CONQUEST_NPC_POWER_MULTIPLIER_MIDDLE;
                $icon = '🏰';
                $terrainType = 'river';
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
            
            // 耐久度を取得
            $durability = getCastleMaxDurability($castleType);
            
            // 地形設定を取得
            $terrainSettings = $terrainTypes[$terrainType];
            
            $castleData[] = [
                'key' => $castleKey,
                'name' => $name,
                'x' => $x,
                'y' => $y,
                'type' => $castleType,
                'is_sacred' => $isSacred,
                'npc_power' => $npcPower,
                'icon' => $icon,
                'durability' => $durability,
                'max_durability' => $durability,
                'terrain_type' => $terrainType,
                'movement_cost' => $terrainSettings['movement_cost'],
                'terrain_defense_bonus' => $terrainSettings['defense_bonus']
            ];
        }
    }
    
    // 城を挿入（地形情報を含む）
    $stmt = $pdo->prepare("
        INSERT INTO conquest_castles (season_id, castle_key, name, position_x, position_y, castle_type, is_sacred, npc_defense_power, icon, durability, max_durability, terrain_type, movement_cost, terrain_defense_bonus)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            $castle['icon'],
            $castle['durability'],
            $castle['max_durability'],
            $castle['terrain_type'],
            $castle['movement_cost'],
            $castle['terrain_defense_bonus']
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
 * 城の防御パワーを計算（装備バフを含む）
 */
function calculateCastleDefensePower($pdo, $castle) {
    if ($castle['owner_user_id'] === null) {
        // NPC防御
        return [
            'total_power' => $castle['npc_defense_power'],
            'is_npc' => true,
            'troops' => [],
            'equipment_buffs' => ['attack' => 0, 'armor' => 0, 'health' => 0],
            'equipment_power' => 0
        ];
    }
    
    // ユーザーの装備バフを取得
    $equipmentBuffs = getConquestUserEquipmentBuffs($pdo, $castle['owner_user_id']);
    $equipmentPower = calculateEquipmentPower($equipmentBuffs);
    
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
    
    $troopPower = 0;
    $troops = [];
    
    foreach ($defenseTroops as $troop) {
        $power = ($troop['attack_power'] + floor($troop['defense_power'] / 2) + floor($troop['health_points'] / CONQUEST_TROOP_HEALTH_TO_POWER_RATIO)) * $troop['count'];
        $troopPower += $power;
        $troops[] = [
            'troop_type_id' => $troop['troop_type_id'],
            'name' => $troop['name'],
            'icon' => $troop['icon'],
            'count' => $troop['count'],
            'power' => $power,
            'category' => $troop['troop_category']
        ];
    }
    
    // 防御部隊がない場合はNPCデフォルト防御 + 装備バフ
    if (empty($troops)) {
        $basePower = max(50, $castle['npc_defense_power'] / 2);
        return [
            'total_power' => $basePower + $equipmentPower,
            'is_npc' => true,
            'troops' => [],
            'equipment_buffs' => $equipmentBuffs,
            'equipment_power' => $equipmentPower
        ];
    }
    
    // 兵士を配置した場合: 兵士パワー + 装備パワー
    // 修正: 兵士を置いた時の方が弱くならないよう、最低でもNPC防御の半分は維持
    $minBasePower = max(50, $castle['npc_defense_power'] / 2);
    $totalPower = max($minBasePower, $troopPower) + $equipmentPower;
    
    return [
        'total_power' => $totalPower,
        'troop_power' => $troopPower,
        'is_npc' => false,
        'defender_user_id' => $castle['owner_user_id'],
        'troops' => $troops,
        'equipment_buffs' => $equipmentBuffs,
        'equipment_power' => $equipmentPower
    ];
}

// ===============================================
// API Actions
// ===============================================

// シーズン情報を取得
if ($action === 'get_season') {
    try {
        $season = getOrCreateActiveSeason($pdo);
        
        // 砲撃処理を実行（cron不要: ページアクセス時に自動処理）
        // 砲撃対象の城があるか事前チェック（軽量クエリ）
        $bombardmentInterval = CONQUEST_BOMBARDMENT_INTERVAL_MINUTES;
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) FROM conquest_castles 
            WHERE season_id = ? 
              AND owner_user_id IS NOT NULL
              AND (last_bombardment_at IS NULL OR last_bombardment_at < DATE_SUB(NOW(), INTERVAL ? MINUTE))
            LIMIT 1
        ");
        $checkStmt->execute([$season['id'], $bombardmentInterval]);
        $hasCastlesToBombard = (int)$checkStmt->fetchColumn() > 0;
        
        if ($hasCastlesToBombard) {
            try {
                $pdo->beginTransaction();
                processAllBombardments($pdo, $season['id']);
                $pdo->commit();
            } catch (Exception $bombException) {
                $pdo->rollBack();
                error_log("Conquest bombardment error: " . $bombException->getMessage());
            }
        }
        
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
            SELECT cc.*, uc.civilization_name as owner_civ_name, u.handle as owner_handle,
                   TIMESTAMPDIFF(MINUTE, COALESCE(cc.last_bombardment_at, DATE_SUB(NOW(), INTERVAL 1 HOUR)), NOW()) as minutes_since_bombardment
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
        
        // 自分の城でない場合、ステルス兵を隠す
        if ($castle['owner_user_id'] != $me['id'] && !empty($defense['troops'])) {
            $defense['visible_troops'] = getVisibleDefenseTroops($pdo, $defense['troops']);
        } else {
            $defense['visible_troops'] = $defense['troops'];
        }
        
        // 地形情報を追加
        $terrainType = $castle['terrain_type'] ?? 'plains';
        $terrainDefenseBonus = (float)($castle['terrain_defense_bonus'] ?? 1.0);
        $movementCost = (int)($castle['movement_cost'] ?? 1);
        
        // 地形バフ情報を取得
        $stmt = $pdo->prepare("SELECT * FROM conquest_terrain_buffs WHERE terrain_type = ?");
        $stmt->execute([$terrainType]);
        $terrainBuffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $terrainInfo = [
            'type' => $terrainType,
            'defense_bonus' => $terrainDefenseBonus,
            'movement_cost' => $movementCost,
            'buffs' => $terrainBuffs
        ];
        
        // ユーザーの総軍事力を取得（戦力比較用）
        $myEquipmentBuffs = getConquestUserEquipmentBuffs($pdo, $me['id']);
        $myEquipmentPower = calculateEquipmentPower($myEquipmentBuffs);
        
        // ユーザーの兵士合計パワーを計算
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM((tt.attack_power + FLOOR(tt.defense_power / 2) + FLOOR(COALESCE(tt.health_points, 100) / 50)) * uct.count), 0) as troop_power
            FROM user_civilization_troops uct
            JOIN civilization_troop_types tt ON uct.troop_type_id = tt.id
            WHERE uct.user_id = ?
        ");
        $stmt->execute([$me['id']]);
        $myTroopPower = (int)$stmt->fetchColumn();
        
        $myTotalPower = $myTroopPower + $myEquipmentPower;
        
        // 隣接城を取得（地形情報を含む）
        $stmt = $pdo->prepare("
            SELECT cc.id, cc.name, cc.icon, cc.owner_user_id, uc.civilization_name,
                   COALESCE(cc.terrain_type, 'plains') as terrain_type,
                   COALESCE(cc.movement_cost, 1) as movement_cost
            FROM conquest_castle_adjacency cca
            JOIN conquest_castles cc ON cca.adjacent_castle_id = cc.id
            LEFT JOIN user_civilizations uc ON cc.owner_user_id = uc.user_id
            WHERE cca.castle_id = ?
        ");
        $stmt->execute([$castleId]);
        $adjacentCastles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 最近の戦闘ログを取得（砲撃ログも含む）
        $stmt = $pdo->prepare("
            SELECT cbl.*, 
                   attacker.handle as attacker_handle,
                   attacker_civ.civilization_name as attacker_civ_name,
                   COALESCE(cbl.log_type, 'battle') as log_type
            FROM conquest_battle_logs cbl
            JOIN users attacker ON cbl.attacker_user_id = attacker.id
            LEFT JOIN user_civilizations attacker_civ ON cbl.attacker_user_id = attacker_civ.user_id
            WHERE cbl.castle_id = ?
            ORDER BY cbl.battle_at DESC
            LIMIT 10
        ");
        $stmt->execute([$castleId]);
        $recentBattles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 砲撃状況
        $minutesSince = (int)($castle['minutes_since_bombardment'] ?? 60);
        $minutesUntilNext = max(0, CONQUEST_BOMBARDMENT_INTERVAL_MINUTES - $minutesSince);
        
        $bombardmentStatus = [
            'last_bombardment_at' => $castle['last_bombardment_at'],
            'minutes_since' => $minutesSince,
            'minutes_until_next' => $minutesUntilNext,
            'interval_minutes' => CONQUEST_BOMBARDMENT_INTERVAL_MINUTES
        ];
        
        echo json_encode([
            'ok' => true,
            'castle' => $castle,
            'defense' => $defense,
            'terrain_info' => $terrainInfo,
            'my_power' => $myTotalPower,
            'my_troop_power' => $myTroopPower,
            'my_equipment_power' => $myEquipmentPower,
            'adjacent_castles' => $adjacentCastles,
            'recent_battles' => $recentBattles,
            'bombardment_status' => $bombardmentStatus
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 城を攻撃（ターン制バトルシステム）
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
        
        // 攻撃者の装備バフを取得
        $attackerEquipmentBuffs = getConquestUserEquipmentBuffs($pdo, $me['id']);
        
        // 攻撃部隊を検証
        $attackerTroops = [];
        foreach ($troops as $troop) {
            $troopTypeId = (int)$troop['troop_type_id'];
            $count = (int)$troop['count'];
            
            if ($count <= 0) continue;
            
            // 所有兵士数を確認
            $stmt = $pdo->prepare("
                SELECT uct.count FROM user_civilization_troops uct
                WHERE uct.user_id = ? AND uct.troop_type_id = ?
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
        $deploymentLimit = calculateConquestTroopDeploymentLimit($pdo, $me['id']);
        if ($totalTroopCount > $deploymentLimit['total_limit']) {
            throw new Exception('出撃兵士数の上限（' . $deploymentLimit['total_limit'] . '人）を超えています。司令部や軍事センターを建設すると上限が増加します。');
        }
        
        // 防御側のデータを取得
        $defense = calculateCastleDefensePower($pdo, $castle);
        
        // バトルユニットを準備
        $attackerUnit = prepareBattleUnit($attackerTroops, $attackerEquipmentBuffs, $pdo);
        
        // 地形バフを計算（防御側が有利）
        $terrainType = $castle['terrain_type'] ?? 'plains';
        $terrainDefenseBonus = (float)($castle['terrain_defense_bonus'] ?? 1.0);
        
        // 攻撃側の地形バフを計算
        $attackerTerrainBuff = calculateTerrainBuffMultiplier($pdo, $attackerTroops, $terrainType, false);
        // 防御側の地形バフを計算
        $defenderTerrainBuff = calculateTerrainBuffMultiplier($pdo, $defense['troops'] ?? [], $terrainType, true);
        
        // 攻撃側に地形バフを適用
        $attackerUnit['attack'] = (int)floor($attackerUnit['attack'] * $attackerTerrainBuff);
        
        // 防御側ユニットを準備
        if ($defense['is_npc']) {
            // NPC防御ユニット
            $defenderUnit = prepareNpcDefenseUnit($defense['total_power']);
            // NPCにも地形防御ボーナスを適用
            $defenderUnit['armor'] = (int)floor($defenderUnit['armor'] * $terrainDefenseBonus);
        } else {
            // プレイヤー防御ユニット
            $defenderTroops = [];
            foreach ($defense['troops'] as $troop) {
                $defenderTroops[] = [
                    'troop_type_id' => $troop['troop_type_id'],
                    'count' => $troop['count']
                ];
            }
            $defenderEquipmentBuffs = $defense['equipment_buffs'];
            $defenderUnit = prepareBattleUnit($defenderTroops, $defenderEquipmentBuffs, $pdo);
            // 防御側に地形バフを適用
            $defenderUnit['armor'] = (int)floor($defenderUnit['armor'] * $defenderTerrainBuff * $terrainDefenseBonus);
        }
        
        // 占領戦で防御側に有利なボーナスを適用（防御力と攻撃力を20%増加）
        $defenderUnit['armor'] = (int)floor($defenderUnit['armor'] * CONQUEST_DEFENDER_BONUS);
        $defenderUnit['attack'] = (int)floor($defenderUnit['attack'] * CONQUEST_DEFENDER_BONUS);
        
        // ターン制バトルを実行
        $battleResult = executeTurnBattle($attackerUnit, $defenderUnit);
        $attackerWins = $battleResult['attacker_wins'];
        
        // 損失を計算（HPの減少率に基づく）
        $attackerLosses = [];
        $attackerWounded = [];
        $defenderLosses = [];
        $defenderWounded = [];
        
        $attackerHpLossRate = 1 - ($battleResult['attacker_final_hp'] / max(1, $battleResult['attacker_max_hp']));
        $defenderHpLossRate = 1 - ($battleResult['defender_final_hp'] / max(1, $battleResult['defender_max_hp']));
        
        // 攻撃側の損失処理
        foreach ($attackerUnit['troops'] as $troop) {
            $troopTypeId = $troop['troop_type_id'];
            $count = $troop['count'];
            
            // HPの減少率に応じた損失
            $totalLossCount = (int)floor($count * $attackerHpLossRate);
            $deaths = (int)floor($totalLossCount * CONQUEST_DEATH_RATE / (CONQUEST_DEATH_RATE + CONQUEST_WOUNDED_RATE));
            $wounded = $totalLossCount - $deaths;
            
            if ($deaths > 0) {
                $attackerLosses[$troopTypeId] = $deaths;
            }
            if ($wounded > 0) {
                $attackerWounded[$troopTypeId] = $wounded;
            }
            
            // 兵士を減少
            if ($totalLossCount > 0) {
                $stmt = $pdo->prepare("
                    UPDATE user_civilization_troops
                    SET count = count - ?
                    WHERE user_id = ? AND troop_type_id = ?
                ");
                $stmt->execute([$totalLossCount, $me['id'], $troopTypeId]);
            }
            
            // 負傷兵を追加
            if ($wounded > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_civilization_wounded_troops (user_id, troop_type_id, count)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE count = count + ?
                ");
                $stmt->execute([$me['id'], $troopTypeId, $wounded, $wounded]);
            }
        }
        
        // 防御側の損失処理
        if (!$defense['is_npc'] && !empty($defense['troops'])) {
            foreach ($defenderUnit['troops'] as $troop) {
                $troopTypeId = $troop['troop_type_id'];
                $count = $troop['count'];
                
                $totalLossCount = (int)floor($count * $defenderHpLossRate);
                $deaths = (int)floor($totalLossCount * CONQUEST_DEATH_RATE / (CONQUEST_DEATH_RATE + CONQUEST_WOUNDED_RATE));
                $wounded = $totalLossCount - $deaths;
                
                if ($deaths > 0) {
                    $defenderLosses[$troopTypeId] = $deaths;
                }
                if ($wounded > 0) {
                    $defenderWounded[$troopTypeId] = $wounded;
                }
                
                // 城の防御部隊から減少
                if ($totalLossCount > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE conquest_castle_defense
                        SET count = count - ?
                        WHERE castle_id = ? AND troop_type_id = ?
                    ");
                    $stmt->execute([$totalLossCount, $castle['id'], $troopTypeId]);
                }
                
                // 防御側ユーザーの負傷兵を追加
                if ($wounded > 0 && !empty($defense['defender_user_id'])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO user_civilization_wounded_troops (user_id, troop_type_id, count)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE count = count + ?
                    ");
                    $stmt->execute([$defense['defender_user_id'], $troopTypeId, $wounded, $wounded]);
                }
            }
        }
        
        // 城の占領処理（耐久度システムを考慮）
        $castleCaptured = false;
        $durabilityDamage = 0;
        $isDurabilityAttack = false;
        
        // 現在の耐久度を取得（カラムが存在しない場合のデフォルト値）
        $currentDurability = isset($castle['durability']) ? (int)$castle['durability'] : getCastleMaxDurability($castle['castle_type']);
        $maxDurability = isset($castle['max_durability']) ? (int)$castle['max_durability'] : getCastleMaxDurability($castle['castle_type']);
        
        if ($attackerWins) {
            // 守備兵がいない場合は耐久度を削る
            if ($defense['is_npc'] || empty($defense['troops'])) {
                $isDurabilityAttack = true;
                
                // 攻城ダメージを計算
                $durabilityDamage = calculateSiegeDamage($pdo, $attackerTroops);
                $newDurability = max(0, $currentDurability - $durabilityDamage);
                
                // 耐久度を更新
                $stmt = $pdo->prepare("UPDATE conquest_castles SET durability = ? WHERE id = ?");
                $stmt->execute([$newDurability, $castle['id']]);
                
                // 耐久度が0になったら城を占領
                if ($newDurability <= 0) {
                    $castleCaptured = true;
                    
                    // 城を占領し、耐久度をリセット
                    $stmt = $pdo->prepare("UPDATE conquest_castles SET owner_user_id = ?, durability = ? WHERE id = ?");
                    $stmt->execute([$me['id'], $maxDurability, $castle['id']]);
                    
                    // 古い防御部隊をクリア
                    $stmt = $pdo->prepare("DELETE FROM conquest_castle_defense WHERE castle_id = ?");
                    $stmt->execute([$castle['id']]);
                    
                    // お知らせ投稿（外周以外の場合）
                    sendConquestAnnouncement($pdo, $castle['name'], $castle['castle_type'], $me['handle'], false);
                }
            } else {
                // 守備兵がいる場合は通常の占領
                $castleCaptured = true;
                
                // 残りの防御部隊を元の所有者に戻す
                if (!$defense['is_npc'] && !empty($defense['defender_user_id'])) {
                    $stmt = $pdo->prepare("SELECT troop_type_id, count, user_id FROM conquest_castle_defense WHERE castle_id = ? AND count > 0");
                    $stmt->execute([$castle['id']]);
                    $remainingTroops = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $returnStmt = $pdo->prepare("
                        INSERT INTO user_civilization_troops (user_id, troop_type_id, count)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE count = count + ?
                    ");
                    foreach ($remainingTroops as $troop) {
                        $returnStmt->execute([$troop['user_id'], $troop['troop_type_id'], $troop['count'], $troop['count']]);
                    }
                }
                
                // 城を占領し、耐久度をリセット
                $stmt = $pdo->prepare("UPDATE conquest_castles SET owner_user_id = ?, durability = ? WHERE id = ?");
                $stmt->execute([$me['id'], $maxDurability, $castle['id']]);
                
                // 古い防御部隊をクリア
                $stmt = $pdo->prepare("DELETE FROM conquest_castle_defense WHERE castle_id = ?");
                $stmt->execute([$castle['id']]);
                
                // お知らせ投稿（外周以外の場合）
                sendConquestAnnouncement($pdo, $castle['name'], $castle['castle_type'], $me['handle'], false);
            }
        }
        
        // 戦闘ログを記録（ターン制バトル情報と耐久度ダメージを含む）
        $battleSummary = generateBattleSummary($battleResult);
        $defenderId = $defense['is_npc'] ? null : ($defense['defender_user_id'] ?? null);
        $winnerId = $attackerWins ? $me['id'] : $defenderId;
        
        $stmt = $pdo->prepare("
            INSERT INTO conquest_battle_logs 
            (season_id, castle_id, attacker_user_id, defender_user_id, 
             attacker_troops, defender_troops, attacker_power, defender_power,
             attacker_losses, defender_losses, attacker_wounded, defender_wounded,
             winner_user_id, castle_captured, durability_damage, is_durability_attack,
             total_turns, battle_log_summary, attacker_final_hp, defender_final_hp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $season['id'],
            $castle['id'],
            $me['id'],
            $defenderId,
            json_encode($attackerUnit['troops']),
            json_encode($defenderUnit['troops']),
            $attackerUnit['attack'],
            $defenderUnit['attack'],
            json_encode($attackerLosses),
            json_encode($defenderLosses),
            json_encode($attackerWounded),
            json_encode($defenderWounded),
            $winnerId,
            $castleCaptured ? 1 : 0,
            $durabilityDamage,
            $isDurabilityAttack ? 1 : 0,
            $battleResult['total_turns'],
            $battleSummary,
            $battleResult['attacker_final_hp'],
            $battleResult['defender_final_hp']
        ]);
        $battleId = $pdo->lastInsertId();
        
        // ターン制バトルログを保存
        saveConquestBattleTurnLogs($pdo, $battleId, $battleResult['turn_logs']);
        
        // 城を占領した場合にクエスト進捗を更新
        if ($castleCaptured) {
            updateCivilizationQuestProgressHelper($pdo, $me['id'], 'conquest', null, 1);
        }
        
        $pdo->commit();
        
        // メッセージの生成
        if ($attackerWins) {
            if ($isDurabilityAttack) {
                if ($castleCaptured) {
                    $message = "{$castle['name']}の耐久度を0にし、占領しました！（耐久度ダメージ: {$durabilityDamage}）";
                } else {
                    $newDurability = $currentDurability - $durabilityDamage;
                    $message = "{$castle['name']}の耐久度を削りました！（ダメージ: {$durabilityDamage}、残り耐久度: {$newDurability}/{$maxDurability}）";
                }
            } else {
                $message = "{$castle['name']}を{$battleResult['total_turns']}ターンの激戦の末、占領しました！";
            }
        } else {
            $message = "{$castle['name']}の攻略に失敗しました...{$battleResult['total_turns']}ターンの戦いでした。";
        }
        
        echo json_encode([
            'ok' => true,
            'result' => $attackerWins ? 'victory' : 'defeat',
            'message' => $message,
            'castle_captured' => $castleCaptured,
            'battle_id' => $battleId,
            'durability_damage' => $durabilityDamage,
            'is_durability_attack' => $isDurabilityAttack,
            'current_durability' => $isDurabilityAttack ? max(0, $currentDurability - $durabilityDamage) : $currentDurability,
            'max_durability' => $maxDurability,
            'battle_result' => [
                'total_turns' => $battleResult['total_turns'],
                'attacker_final_hp' => $battleResult['attacker_final_hp'],
                'attacker_max_hp' => $battleResult['attacker_max_hp'],
                'defender_final_hp' => $battleResult['defender_final_hp'],
                'defender_max_hp' => $battleResult['defender_max_hp']
            ],
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
        $stmt = $pdo->prepare("SELECT troop_type_id, count FROM conquest_castle_defense WHERE castle_id = ? AND user_id = ? AND count > 0");
        $stmt->execute([$castleId, $me['id']]);
        $existingTroops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($existingTroops)) {
            $returnStmt = $pdo->prepare("
                INSERT INTO user_civilization_troops (user_id, troop_type_id, count)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE count = count + ?
            ");
            foreach ($existingTroops as $existingTroop) {
                $returnStmt->execute([$me['id'], $existingTroop['troop_type_id'], $existingTroop['count'], $existingTroop['count']]);
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
        $stmt = $pdo->prepare("SELECT troop_type_id, count FROM conquest_castle_defense WHERE castle_id = ? AND user_id = ? AND count > 0");
        $stmt->execute([$castleId, $me['id']]);
        $defenseTroops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 兵士を戻す
        if (!empty($defenseTroops)) {
            $returnStmt = $pdo->prepare("
                INSERT INTO user_civilization_troops (user_id, troop_type_id, count)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE count = count + ?
            ");
            foreach ($defenseTroops as $troop) {
                $returnStmt->execute([$me['id'], $troop['troop_type_id'], $troop['count'], $troop['count']]);
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

// ===============================================
// 占領戦バトルログ詳細（ターンログ）を取得
// ===============================================
if ($action === 'get_conquest_battle_turn_logs') {
    $battleId = (int)($input['battle_id'] ?? 0);
    
    if ($battleId <= 0) {
        echo json_encode(['ok' => false, 'error' => '戦闘ログIDが指定されていません']);
        exit;
    }
    
    try {
        // 戦闘ログの基本情報を取得
        $stmt = $pdo->prepare("
            SELECT 
                cbl.*,
                cc.name as castle_name, cc.icon as castle_icon,
                attacker.handle as attacker_handle,
                attacker.display_name as attacker_name,
                defender.handle as defender_handle,
                defender.display_name as defender_name,
                ac.civilization_name as attacker_civ_name,
                dc.civilization_name as defender_civ_name
            FROM conquest_battle_logs cbl
            JOIN conquest_castles cc ON cbl.castle_id = cc.id
            JOIN users attacker ON cbl.attacker_user_id = attacker.id
            LEFT JOIN users defender ON cbl.defender_user_id = defender.id
            LEFT JOIN user_civilizations ac ON cbl.attacker_user_id = ac.user_id
            LEFT JOIN user_civilizations dc ON cbl.defender_user_id = dc.user_id
            WHERE cbl.id = ?
        ");
        $stmt->execute([$battleId]);
        $battleLog = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$battleLog) {
            echo json_encode(['ok' => false, 'error' => '戦闘ログが見つかりません']);
            exit;
        }
        
        // ターンログを取得
        $stmt = $pdo->prepare("
            SELECT * FROM conquest_battle_turn_logs
            WHERE battle_id = ?
            ORDER BY turn_number ASC, id ASC
        ");
        $stmt->execute([$battleId]);
        $turnLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 兵種情報を取得
        $troopNames = [];
        $stmt = $pdo->query("SELECT id, name, icon FROM civilization_troop_types");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $troopNames[$row['id']] = [
                'name' => $row['name'],
                'icon' => $row['icon']
            ];
        }
        
        echo json_encode([
            'ok' => true,
            'battle_log' => $battleLog,
            'turn_logs' => $turnLogs,
            'troop_names' => $troopNames,
            'my_user_id' => $me['id']
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

/**
 * 砲撃を処理する関数
 * 30分おきに各城の防御部隊が少しずつ削られる
 * 低コスト兵は多く、高コスト兵は少しだけ
 */
function processBombardment($pdo, $castleId, $seasonId) {
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
    
    // 現在の耐久度を取得
    $currentDurability = isset($castle['durability']) ? (int)$castle['durability'] : getCastleMaxDurability($castle['castle_type']);
    $maxDurability = isset($castle['max_durability']) ? (int)$castle['max_durability'] : getCastleMaxDurability($castle['castle_type']);
    
    $woundedTroops = [];
    $totalWounded = 0;
    $durabilityDamage = 0;
    $castleFallen = false;
    $logMessages = ["💥 砲撃発生！ ({$castle['name']})"];
    
    if (empty($defenseTroops)) {
        // 防御部隊がない場合は耐久度を削る（乱数変動を適用）
        $varianceRange = CONQUEST_DURABILITY_DAMAGE_VARIANCE;
        $randomVariance = 1 + (mt_rand(-100, 100) / 100) * $varianceRange;
        $durabilityDamage = (int)floor(CONQUEST_BOMBARDMENT_DURABILITY_DAMAGE * $randomVariance);
        $durabilityDamage = max(1, $durabilityDamage); // 最低1ダメージ
        $newDurability = max(0, $currentDurability - $durabilityDamage);
        $logMessages[] = "🏰 城壁へのダメージ: {$durabilityDamage}（残り耐久度: {$newDurability}/{$maxDurability}）";
        
        // 耐久度を更新
        $stmt = $pdo->prepare("UPDATE conquest_castles SET durability = ? WHERE id = ?");
        $stmt->execute([$newDurability, $castleId]);
        
        // 耐久度が0になったら城をNPCに渡す
        if ($newDurability <= 0) {
            $castleFallen = true;
            $logMessages[] = "⚠️ {$castle['name']}が陥落！所有者はNPCに変更されました。";
            
            // 城をNPCに渡し、耐久度をリセット
            $stmt = $pdo->prepare("UPDATE conquest_castles SET owner_user_id = NULL, durability = ? WHERE id = ?");
            $stmt->execute([$maxDurability, $castleId]);
            
            // お知らせ投稿（外周以外の場合）
            sendConquestAnnouncement($pdo, $castle['name'], $castle['castle_type'], null, true);
        }
    } else {
        // 防御部隊がいる場合は兵士にダメージを与える
        foreach ($defenseTroops as $troop) {
            // コストに基づく損失率計算
            // 低コスト兵ほど損失率が高い（基本5%から、コストが高いほど軽減）
            $costFactor = min(CONQUEST_BOMBARDMENT_MAX_COST_REDUCTION, $troop['train_cost_coins'] * CONQUEST_BOMBARDMENT_COST_FACTOR);
            $lossRate = max(CONQUEST_BOMBARDMENT_MIN_LOSS_RATE, CONQUEST_BOMBARDMENT_BASE_RATE - $costFactor);
            
            // 負傷兵数を計算（乱数幅を持たせる）
            $baseWounded = (int)floor($troop['count'] * $lossRate);
            $varianceRange = CONQUEST_BOMBARDMENT_VARIANCE_RANGE;
            $randomVariance = mt_rand(-$varianceRange, $varianceRange) / 100;
            $wounded = max(1, (int)floor($baseWounded * (1 + $randomVariance)));
            $wounded = min($wounded, $troop['count']); // 配置数を超えない
            
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
    }
    
    if ($totalWounded == 0 && $durabilityDamage == 0) {
        return ['ok' => false, 'message' => '砲撃被害なし'];
    }
    
    // 砲撃ログを記録
    $stmt = $pdo->prepare("
        INSERT INTO conquest_bombardment_logs 
        (season_id, castle_id, user_id, bombardment_at, troops_wounded, total_wounded, durability_damage, log_message)
        VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)
    ");
    $stmt->execute([
        $seasonId,
        $castleId,
        $castle['owner_user_id'],
        json_encode($woundedTroops),
        $totalWounded,
        $durabilityDamage,
        implode("\n", $logMessages)
    ]);
    $bombardmentLogId = $pdo->lastInsertId();
    
    // 城の最終砲撃時刻を更新
    $stmt = $pdo->prepare("UPDATE conquest_castles SET last_bombardment_at = NOW() WHERE id = ?");
    $stmt->execute([$castleId]);
    
    // 戦闘ログにも砲撃ログを記録（log_type = 'bombardment'）
    $stmt = $pdo->prepare("
        INSERT INTO conquest_battle_logs 
        (log_type, season_id, castle_id, attacker_user_id, defender_user_id,
         attacker_troops, defender_troops, attacker_power, defender_power,
         attacker_losses, defender_losses, attacker_wounded, defender_wounded,
         winner_user_id, castle_captured, durability_damage, total_turns, battle_log_summary)
        VALUES ('bombardment', ?, ?, ?, ?, '[]', ?, 0, 0, '{}', ?, '{}', ?, NULL, ?, ?, 1, ?)
    ");
    $stmt->execute([
        $seasonId,
        $castleId,
        $castle['owner_user_id'], // 砲撃対象を攻撃者扱い（ログ用）
        $castle['owner_user_id'],
        json_encode($woundedTroops),
        json_encode(array_column($woundedTroops, 'count', 'troop_type_id')),
        json_encode(array_column($woundedTroops, 'count', 'troop_type_id')),
        $castleFallen ? 1 : 0,
        $durabilityDamage,
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
        $totalWounded + $durabilityDamage,
        implode("\n", $logMessages)
    ]);
    
    return [
        'ok' => true,
        'castle_id' => $castleId,
        'castle_name' => $castle['name'],
        'total_wounded' => $totalWounded,
        'wounded_troops' => $woundedTroops,
        'durability_damage' => $durabilityDamage,
        'castle_fallen' => $castleFallen,
        'log_messages' => $logMessages,
        'bombardment_log_id' => $bombardmentLogId,
        'battle_log_id' => $battleLogId
    ];
}

/**
 * 全ての占領済み城に対して砲撃を処理する
 */
function processAllBombardments($pdo, $seasonId) {
    // 占領済みの城を取得（最後の砲撃から30分以上経過した城）
    $bombardmentInterval = CONQUEST_BOMBARDMENT_INTERVAL_MINUTES;
    $stmt = $pdo->prepare("
        SELECT id FROM conquest_castles 
        WHERE season_id = ? 
          AND owner_user_id IS NOT NULL
          AND (last_bombardment_at IS NULL OR last_bombardment_at < DATE_SUB(NOW(), INTERVAL ? MINUTE))
    ");
    $stmt->execute([$seasonId, $bombardmentInterval]);
    $castles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $results = [];
    foreach ($castles as $castleId) {
        $result = processBombardment($pdo, $castleId, $seasonId);
        if ($result['ok']) {
            $results[] = $result;
        }
    }
    
    return $results;
}

// ===============================================
// 砲撃処理API
// ===============================================
if ($action === 'process_bombardment') {
    $pdo->beginTransaction();
    try {
        $season = getOrCreateActiveSeason($pdo);
        
        // 全城の砲撃を処理
        $results = processAllBombardments($pdo, $season['id']);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'processed_count' => count($results),
            'results' => $results
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// 自分の城の砲撃ログを取得
// ===============================================
if ($action === 'get_bombardment_logs') {
    $castleId = (int)($input['castle_id'] ?? 0);
    
    try {
        $season = getOrCreateActiveSeason($pdo);
        
        $query = "
            SELECT cbl.*, cc.name as castle_name, cc.icon as castle_icon
            FROM conquest_bombardment_logs cbl
            JOIN conquest_castles cc ON cbl.castle_id = cc.id
            WHERE cbl.season_id = ? AND cbl.user_id = ?
        ";
        $params = [$season['id'], $me['id']];
        
        if ($castleId > 0) {
            $query .= " AND cbl.castle_id = ?";
            $params[] = $castleId;
        }
        
        $query .= " ORDER BY cbl.bombardment_at DESC LIMIT 50";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'bombardment_logs' => $logs
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// 城詳細に砲撃状況を含める
// ===============================================
if ($action === 'get_castle_bombardment_status') {
    $castleId = (int)($input['castle_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("
            SELECT cc.*, 
                   TIMESTAMPDIFF(MINUTE, COALESCE(cc.last_bombardment_at, DATE_SUB(NOW(), INTERVAL 1 HOUR)), NOW()) as minutes_since_bombardment
            FROM conquest_castles cc
            WHERE cc.id = ?
        ");
        $stmt->execute([$castleId]);
        $castle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$castle) {
            echo json_encode(['ok' => false, 'error' => '城が見つかりません']);
            exit;
        }
        
        $minutesSince = (int)$castle['minutes_since_bombardment'];
        $minutesUntilNext = max(0, CONQUEST_BOMBARDMENT_INTERVAL_MINUTES - $minutesSince);
        
        // 直近の砲撃ログを取得
        $stmt = $pdo->prepare("
            SELECT * FROM conquest_bombardment_logs
            WHERE castle_id = ?
            ORDER BY bombardment_at DESC
            LIMIT 5
        ");
        $stmt->execute([$castleId]);
        $recentBombardments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true,
            'last_bombardment_at' => $castle['last_bombardment_at'],
            'minutes_since_bombardment' => $minutesSince,
            'minutes_until_next' => $minutesUntilNext,
            'recent_bombardments' => $recentBombardments
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// 移動システムAPI
// ===============================================

// 移動時間の計算定数（秒）
define('CONQUEST_MOVEMENT_BASE_TIME', 300); // 基本移動時間（5分）
define('CONQUEST_MOVEMENT_CRYSTAL_COST_PER_MINUTE', 1); // 1分短縮あたりのクリスタルコスト
define('CONQUEST_MOVEMENT_DIAMOND_COST_PER_MINUTE', 0.5); // 1分短縮あたりのダイヤモンドコスト

/**
 * 2つの城間の移動時間を計算
 * @param array $fromCastle 出発城
 * @param array $toCastle 到着城
 * @return int 移動時間（秒）
 */
function calculateMovementTime($fromCastle, $toCastle) {
    // 基本時間 * 到着城の移動コスト
    $movementCost = (int)($toCastle['movement_cost'] ?? 1);
    
    // 城の距離に基づいて時間を調整
    $fromType = $fromCastle['castle_type'] ?? 'outer';
    $toType = $toCastle['castle_type'] ?? 'outer';
    
    $typeMultiplier = [
        'outer' => 1,
        'middle' => 2,
        'inner' => 3,
        'sacred' => 4
    ];
    
    $fromMultiplier = $typeMultiplier[$fromType] ?? 1;
    $toMultiplier = $typeMultiplier[$toType] ?? 1;
    
    // 外周から内側へ行くほど時間がかかる
    $distanceMultiplier = max(1, abs($toMultiplier - $fromMultiplier));
    
    return CONQUEST_MOVEMENT_BASE_TIME * $movementCost * $distanceMultiplier;
}

// 移動キューを取得
if ($action === 'get_movement_queue') {
    try {
        $season = getOrCreateActiveSeason($pdo);
        
        $stmt = $pdo->prepare("
            SELECT cmq.*, 
                   fc.name as from_castle_name, fc.icon as from_castle_icon,
                   tc.name as to_castle_name, tc.icon as to_castle_icon,
                   TIMESTAMPDIFF(SECOND, NOW(), cmq.arrives_at) as seconds_remaining
            FROM conquest_movement_queue cmq
            JOIN conquest_castles fc ON cmq.from_castle_id = fc.id
            JOIN conquest_castles tc ON cmq.to_castle_id = tc.id
            WHERE cmq.user_id = ? AND cmq.season_id = ? AND cmq.is_completed = FALSE AND cmq.is_cancelled = FALSE
            ORDER BY cmq.arrives_at ASC
        ");
        $stmt->execute([$me['id'], $season['id']]);
        $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 完了した移動を処理
        foreach ($queue as &$movement) {
            if ($movement['seconds_remaining'] <= 0) {
                // 移動完了処理
                processCompletedMovement($pdo, $movement);
                $movement['is_completed'] = true;
            }
        }
        
        echo json_encode([
            'ok' => true,
            'movement_queue' => $queue
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 移動を開始
if ($action === 'start_movement') {
    $fromCastleId = (int)($input['from_castle_id'] ?? 0);
    $toCastleId = (int)($input['to_castle_id'] ?? 0);
    $troops = $input['troops'] ?? [];
    
    if (empty($troops) || $fromCastleId <= 0 || $toCastleId <= 0) {
        echo json_encode(['ok' => false, 'error' => '移動情報が不足しています']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        $season = getOrCreateActiveSeason($pdo);
        
        // 出発城を確認
        $stmt = $pdo->prepare("SELECT * FROM conquest_castles WHERE id = ? AND season_id = ? AND owner_user_id = ?");
        $stmt->execute([$fromCastleId, $season['id'], $me['id']]);
        $fromCastle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$fromCastle) {
            throw new Exception('出発城を所有していません');
        }
        
        // 到着城を確認（隣接している必要がある）
        $stmt = $pdo->prepare("
            SELECT cc.* FROM conquest_castles cc
            JOIN conquest_castle_adjacency cca ON cc.id = cca.adjacent_castle_id
            WHERE cca.castle_id = ? AND cc.id = ? AND cc.season_id = ?
        ");
        $stmt->execute([$fromCastleId, $toCastleId, $season['id']]);
        $toCastle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$toCastle) {
            throw new Exception('隣接していない城には移動できません');
        }
        
        // 兵士を確認し消費
        $movingTroops = [];
        foreach ($troops as $troop) {
            $troopTypeId = (int)$troop['troop_type_id'];
            $count = (int)$troop['count'];
            
            if ($count <= 0) continue;
            
            // 城の防御部隊から取得
            $stmt = $pdo->prepare("
                SELECT count FROM conquest_castle_defense 
                WHERE castle_id = ? AND user_id = ? AND troop_type_id = ?
            ");
            $stmt->execute([$fromCastleId, $me['id'], $troopTypeId]);
            $ownedCount = (int)$stmt->fetchColumn();
            
            if ($ownedCount < $count) {
                throw new Exception('城に配置されている兵士が不足しています');
            }
            
            // 城の防御部隊から減少
            $stmt = $pdo->prepare("
                UPDATE conquest_castle_defense 
                SET count = count - ?
                WHERE castle_id = ? AND user_id = ? AND troop_type_id = ?
            ");
            $stmt->execute([$count, $fromCastleId, $me['id'], $troopTypeId]);
            
            $movingTroops[] = ['troop_type_id' => $troopTypeId, 'count' => $count];
        }
        
        if (empty($movingTroops)) {
            throw new Exception('移動する兵士を選択してください');
        }
        
        // 移動時間を計算
        $movementTime = calculateMovementTime($fromCastle, $toCastle);
        $arrivesAt = date('Y-m-d H:i:s', time() + $movementTime);
        
        // 移動キューに追加
        $stmt = $pdo->prepare("
            INSERT INTO conquest_movement_queue 
            (season_id, user_id, from_castle_id, to_castle_id, troops, arrives_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $season['id'],
            $me['id'],
            $fromCastleId,
            $toCastleId,
            json_encode($movingTroops),
            $arrivesAt
        ]);
        $movementId = $pdo->lastInsertId();
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => '移動を開始しました',
            'movement_id' => $movementId,
            'movement_time' => $movementTime,
            'arrives_at' => $arrivesAt
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 移動を短縮（クリスタル/ダイヤモンド使用）
if ($action === 'speed_up_movement') {
    $movementId = (int)($input['movement_id'] ?? 0);
    $currency = $input['currency'] ?? 'crystal'; // 'crystal' or 'diamond'
    
    if ($movementId <= 0) {
        echo json_encode(['ok' => false, 'error' => '移動IDが不正です']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        // 移動を取得
        $stmt = $pdo->prepare("
            SELECT * FROM conquest_movement_queue 
            WHERE id = ? AND user_id = ? AND is_completed = FALSE AND is_cancelled = FALSE
        ");
        $stmt->execute([$movementId, $me['id']]);
        $movement = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$movement) {
            throw new Exception('移動が見つかりません');
        }
        
        // 残り時間を計算
        $remainingSeconds = max(0, strtotime($movement['arrives_at']) - time());
        $remainingMinutes = ceil($remainingSeconds / 60);
        
        if ($remainingMinutes <= 0) {
            throw new Exception('移動はすでに完了しています');
        }
        
        // コストを計算と通貨種別の検証
        if ($currency === 'diamond') {
            $cost = max(1, (int)ceil($remainingMinutes * CONQUEST_MOVEMENT_DIAMOND_COST_PER_MINUTE));
            $currencyName = 'ダイヤモンド';
            
            // 通貨を確認
            $stmt = $pdo->prepare("SELECT diamonds FROM users WHERE id = ?");
            $stmt->execute([$me['id']]);
            $userCurrency = (int)$stmt->fetchColumn();
            
            if ($userCurrency < $cost) {
                throw new Exception("{$currencyName}が不足しています（必要: {$cost}）");
            }
            
            // 通貨を消費
            $stmt = $pdo->prepare("UPDATE users SET diamonds = diamonds - ? WHERE id = ?");
            $stmt->execute([$cost, $me['id']]);
        } elseif ($currency === 'crystal') {
            $cost = max(1, (int)ceil($remainingMinutes * CONQUEST_MOVEMENT_CRYSTAL_COST_PER_MINUTE));
            $currencyName = 'クリスタル';
            
            // 通貨を確認
            $stmt = $pdo->prepare("SELECT crystals FROM users WHERE id = ?");
            $stmt->execute([$me['id']]);
            $userCurrency = (int)$stmt->fetchColumn();
            
            if ($userCurrency < $cost) {
                throw new Exception("{$currencyName}が不足しています（必要: {$cost}）");
            }
            
            // 通貨を消費
            $stmt = $pdo->prepare("UPDATE users SET crystals = crystals - ? WHERE id = ?");
            $stmt->execute([$cost, $me['id']]);
        } else {
            throw new Exception('無効な通貨タイプです（crystal または diamond を指定してください）');
        }
        
        // 即時完了処理
        $stmt = $pdo->prepare("UPDATE conquest_movement_queue SET arrives_at = NOW(), is_completed = TRUE WHERE id = ?");
        $stmt->execute([$movementId]);
        
        // 兵士を到着城に配置
        $troops = json_decode($movement['troops'], true) ?: [];
        foreach ($troops as $troop) {
            $stmt = $pdo->prepare("
                INSERT INTO conquest_castle_defense (castle_id, user_id, troop_type_id, count)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE count = count + ?
            ");
            $stmt->execute([
                $movement['to_castle_id'],
                $me['id'],
                $troop['troop_type_id'],
                $troop['count'],
                $troop['count']
            ]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => '移動を即時完了しました',
            'cost' => $cost,
            'currency' => $currencyName
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

/**
 * 完了した移動を処理
 */
function processCompletedMovement($pdo, $movement) {
    $pdo->beginTransaction();
    try {
        // 移動を完了としてマーク
        $stmt = $pdo->prepare("UPDATE conquest_movement_queue SET is_completed = TRUE WHERE id = ?");
        $stmt->execute([$movement['id']]);
        
        // 到着城に兵士を配置
        $troops = json_decode($movement['troops'], true) ?: [];
        foreach ($troops as $troop) {
            $stmt = $pdo->prepare("
                INSERT INTO conquest_castle_defense (castle_id, user_id, troop_type_id, count)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE count = count + ?
            ");
            $stmt->execute([
                $movement['to_castle_id'],
                $movement['user_id'],
                $troop['troop_type_id'],
                $troop['count'],
                $troop['count']
            ]);
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Movement completion error: " . $e->getMessage());
    }
}

echo json_encode(['ok' => false, 'error' => 'invalid_action']);
