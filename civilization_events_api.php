<?php
// ===============================================
// civilization_events_api.php
// イベントシステムAPI
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

// ===============================================
// デイリータスク関連
// ===============================================

/**
 * 今日のデイリータスクを取得または初期化
 */
function getDailyTasks($pdo, $userId) {
    $today = date('Y-m-d');
    
    // アクティブなタスクを取得
    $stmt = $pdo->prepare("SELECT * FROM civilization_daily_tasks WHERE is_active = TRUE");
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    
    foreach ($tasks as $task) {
        // ユーザーの進捗を取得または作成
        $stmt = $pdo->prepare("
            SELECT * FROM user_daily_task_progress 
            WHERE user_id = ? AND task_id = ? AND task_date = ?
        ");
        $stmt->execute([$userId, $task['id'], $today]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$progress) {
            // 今日の進捗がなければ作成
            $stmt = $pdo->prepare("
                INSERT INTO user_daily_task_progress (user_id, task_id, task_date, current_progress)
                VALUES (?, ?, ?, 0)
            ");
            $stmt->execute([$userId, $task['id'], $today]);
            $progress = [
                'current_progress' => 0,
                'is_completed' => false,
                'is_claimed' => false
            ];
        }
        
        $result[] = [
            'id' => $task['id'],
            'task_key' => $task['task_key'],
            'name' => $task['name'],
            'description' => $task['description'],
            'icon' => $task['icon'],
            'task_type' => $task['task_type'],
            'target_count' => $task['target_count'],
            'current_progress' => (int)$progress['current_progress'],
            'is_completed' => (bool)$progress['is_completed'],
            'is_claimed' => (bool)$progress['is_claimed'],
            'reward_coins' => (int)$task['reward_coins'],
            'reward_crystals' => (int)$task['reward_crystals'],
            'reward_diamonds' => (int)$task['reward_diamonds'],
            'reward_exp' => (int)$task['reward_exp']
        ];
    }
    
    return $result;
}

/**
 * デイリータスクの進捗を更新
 */
function updateDailyTaskProgress($pdo, $userId, $taskType, $amount = 1) {
    $today = date('Y-m-d');
    
    // タスクタイプに該当するタスクを取得
    $stmt = $pdo->prepare("SELECT id, target_count FROM civilization_daily_tasks WHERE task_type = ? AND is_active = TRUE");
    $stmt->execute([$taskType]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tasks as $task) {
        // 進捗を更新
        $stmt = $pdo->prepare("
            INSERT INTO user_daily_task_progress (user_id, task_id, task_date, current_progress)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                current_progress = LEAST(current_progress + ?, ?),
                is_completed = (current_progress + ? >= ?)
        ");
        $stmt->execute([
            $userId, $task['id'], $today, min($amount, $task['target_count']),
            $amount, $task['target_count'],
            $amount, $task['target_count']
        ]);
    }
}

// デイリータスク一覧を取得
if ($action === 'get_daily_tasks') {
    try {
        $tasks = getDailyTasks($pdo, $me['id']);
        
        // 全タスク完了報酬の確認
        $allCompleted = true;
        $allClaimed = true;
        foreach ($tasks as $task) {
            if (!$task['is_completed']) $allCompleted = false;
            if (!$task['is_claimed']) $allClaimed = false;
        }
        
        echo json_encode([
            'ok' => true,
            'tasks' => $tasks,
            'all_completed' => $allCompleted,
            'all_claimed' => $allClaimed,
            'date' => date('Y-m-d')
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// デイリータスク報酬を受け取る
if ($action === 'claim_daily_task') {
    $taskId = (int)($input['task_id'] ?? 0);
    $today = date('Y-m-d');
    
    $pdo->beginTransaction();
    try {
        // タスクを取得
        $stmt = $pdo->prepare("SELECT * FROM civilization_daily_tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task) {
            throw new Exception('タスクが見つかりません');
        }
        
        // 進捗を確認
        $stmt = $pdo->prepare("
            SELECT * FROM user_daily_task_progress 
            WHERE user_id = ? AND task_id = ? AND task_date = ?
        ");
        $stmt->execute([$me['id'], $taskId, $today]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$progress) {
            throw new Exception('タスク進捗が見つかりません');
        }
        
        if (!$progress['is_completed'] && $progress['current_progress'] < $task['target_count']) {
            throw new Exception('タスクが未完了です');
        }
        
        if ($progress['is_claimed']) {
            throw new Exception('既に報酬を受け取っています');
        }
        
        // 報酬を付与
        $stmt = $pdo->prepare("
            UPDATE users SET 
                coins = coins + ?,
                crystals = crystals + ?,
                diamonds = diamonds + ?
            WHERE id = ?
        ");
        $stmt->execute([
            $task['reward_coins'],
            $task['reward_crystals'],
            $task['reward_diamonds'],
            $me['id']
        ]);
        
        // 経験値を付与
        if ($task['reward_exp'] > 0) {
            grant_exp($me['id'], 'daily_task', 0);
        }
        
        // 報酬受取済みに更新
        $stmt = $pdo->prepare("
            UPDATE user_daily_task_progress 
            SET is_claimed = TRUE, claimed_at = NOW()
            WHERE user_id = ? AND task_id = ? AND task_date = ?
        ");
        $stmt->execute([$me['id'], $taskId, $today]);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => '報酬を受け取りました！',
            'rewards' => [
                'coins' => $task['reward_coins'],
                'crystals' => $task['reward_crystals'],
                'diamonds' => $task['reward_diamonds'],
                'exp' => $task['reward_exp']
            ]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// スペシャルイベント関連
// ===============================================

// アクティブなスペシャルイベントを取得
if ($action === 'get_special_events') {
    try {
        $now = date('Y-m-d H:i:s');
        
        // アクティブなスペシャルイベントを取得
        $stmt = $pdo->prepare("
            SELECT * FROM civilization_events 
            WHERE event_type = 'special' 
              AND is_active = TRUE 
              AND start_date <= ? 
              AND end_date >= ?
            ORDER BY start_date ASC
        ");
        $stmt->execute([$now, $now]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        foreach ($events as $event) {
            // 限定アイテムを取得
            $stmt = $pdo->prepare("
                SELECT sei.*, COALESCE(usei.count, 0) as user_count
                FROM special_event_items sei
                LEFT JOIN user_special_event_items usei ON sei.id = usei.item_id AND usei.user_id = ?
                WHERE sei.event_id = ?
            ");
            $stmt->execute([$me['id'], $event['id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 交換所を取得
            $stmt = $pdo->prepare("
                SELECT see.*, sei.name as item_name, sei.icon as item_icon
                FROM special_event_exchange see
                JOIN special_event_items sei ON see.item_id = sei.id
                WHERE see.event_id = ?
            ");
            $stmt->execute([$event['id']]);
            $exchanges = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ポータルボスを取得
            $stmt = $pdo->prepare("SELECT * FROM special_event_portal_bosses WHERE event_id = ?");
            $stmt->execute([$event['id']]);
            $portalBosses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ポータルボスの攻撃可能状態をチェック
            foreach ($portalBosses as &$boss) {
                $stmt = $pdo->prepare("
                    SELECT attacked_at FROM user_portal_boss_attacks 
                    WHERE user_id = ? AND boss_id = ? 
                    ORDER BY attacked_at DESC LIMIT 1
                ");
                $stmt->execute([$me['id'], $boss['id']]);
                $lastAttack = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($lastAttack) {
                    $attackedAt = strtotime($lastAttack['attacked_at']);
                    $intervalSeconds = $boss['attack_interval_hours'] * 3600;
                    $nextAttackAt = $attackedAt + $intervalSeconds;
                    $boss['can_attack'] = time() >= $nextAttackAt;
                    $boss['next_attack_at'] = date('Y-m-d H:i:s', $nextAttackAt);
                    $boss['seconds_until_attack'] = max(0, $nextAttackAt - time());
                } else {
                    $boss['can_attack'] = true;
                    $boss['next_attack_at'] = null;
                    $boss['seconds_until_attack'] = 0;
                }
            }
            unset($boss);
            
            $result[] = [
                'id' => $event['id'],
                'event_key' => $event['event_key'],
                'name' => $event['name'],
                'description' => $event['description'],
                'icon' => $event['icon'],
                'start_date' => $event['start_date'],
                'end_date' => $event['end_date'],
                'remaining_seconds' => max(0, strtotime($event['end_date']) - time()),
                'items' => $items,
                'exchanges' => $exchanges,
                'portal_bosses' => $portalBosses
            ];
        }
        
        echo json_encode([
            'ok' => true,
            'events' => $result
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ポータルボスを攻撃
if ($action === 'attack_portal_boss') {
    $bossId = (int)($input['boss_id'] ?? 0);
    $troops = $input['troops'] ?? [];
    
    $pdo->beginTransaction();
    try {
        // ボスを取得
        $stmt = $pdo->prepare("
            SELECT sepb.*, ce.is_active, ce.end_date
            FROM special_event_portal_bosses sepb
            JOIN civilization_events ce ON sepb.event_id = ce.id
            WHERE sepb.id = ?
        ");
        $stmt->execute([$bossId]);
        $boss = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$boss || !$boss['is_active']) {
            throw new Exception('ボスが見つかりません');
        }
        
        if (strtotime($boss['end_date']) < time()) {
            throw new Exception('イベントが終了しています');
        }
        
        // 攻撃可能時間をチェック
        $stmt = $pdo->prepare("
            SELECT attacked_at FROM user_portal_boss_attacks 
            WHERE user_id = ? AND boss_id = ? 
            ORDER BY attacked_at DESC LIMIT 1
        ");
        $stmt->execute([$me['id'], $bossId]);
        $lastAttack = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastAttack) {
            $attackedAt = strtotime($lastAttack['attacked_at']);
            $intervalSeconds = $boss['attack_interval_hours'] * 3600;
            $nextAttackAt = $attackedAt + $intervalSeconds;
            
            if (time() < $nextAttackAt) {
                $remainingMinutes = ceil(($nextAttackAt - time()) / 60);
                throw new Exception("あと{$remainingMinutes}分後に攻撃可能になります");
            }
        }
        
        // 攻撃力を計算
        $totalPower = 0;
        foreach ($troops as $troop) {
            $troopTypeId = (int)($troop['troop_type_id'] ?? 0);
            $count = (int)($troop['count'] ?? 0);
            
            if ($count <= 0) continue;
            
            $stmt = $pdo->prepare("SELECT attack_power, defense_power FROM civilization_troop_types WHERE id = ?");
            $stmt->execute([$troopTypeId]);
            $troopType = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($troopType) {
                $totalPower += ($troopType['attack_power'] + $troopType['defense_power'] / 2) * $count;
            }
        }
        
        // ダメージ計算（ランダム要素を含む）
        $baseDamage = (int)floor($totalPower * (mt_rand(80, 120) / 100));
        $damage = max(1, $baseDamage);
        
        // ドロップアイテムを決定
        $lootReceived = [];
        $lootTable = json_decode($boss['loot_table'], true) ?: [];
        
        foreach ($lootTable as $loot) {
            if (mt_rand(1, 100) <= ($loot['chance'] ?? 10)) {
                $itemId = $loot['item_id'];
                $count = mt_rand($loot['min_count'] ?? 1, $loot['max_count'] ?? 1);
                
                // アイテムを付与
                $stmt = $pdo->prepare("
                    INSERT INTO user_special_event_items (user_id, item_id, count)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE count = count + ?
                ");
                $stmt->execute([$me['id'], $itemId, $count, $count]);
                
                $lootReceived[] = [
                    'item_id' => $itemId,
                    'count' => $count
                ];
            }
        }
        
        // 攻撃履歴を記録
        $stmt = $pdo->prepare("
            INSERT INTO user_portal_boss_attacks (user_id, boss_id, damage_dealt, loot_received, attacked_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$me['id'], $bossId, $damage, json_encode($lootReceived)]);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'damage_dealt' => $damage,
            'loot_received' => $lootReceived,
            'message' => "ボスに{$damage}ダメージを与えました！"
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// イベントアイテムを交換
if ($action === 'exchange_event_item') {
    $exchangeId = (int)($input['exchange_id'] ?? 0);
    
    $pdo->beginTransaction();
    try {
        // 交換情報を取得
        $stmt = $pdo->prepare("
            SELECT see.*, sei.name as item_name
            FROM special_event_exchange see
            JOIN special_event_items sei ON see.item_id = sei.id
            WHERE see.id = ?
        ");
        $stmt->execute([$exchangeId]);
        $exchange = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$exchange) {
            throw new Exception('交換情報が見つかりません');
        }
        
        // ユーザーの所持アイテムを確認
        $stmt = $pdo->prepare("
            SELECT count FROM user_special_event_items 
            WHERE user_id = ? AND item_id = ?
        ");
        $stmt->execute([$me['id'], $exchange['item_id']]);
        $userItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $userItemCount = $userItem ? (int)$userItem['count'] : 0;
        
        if ($userItemCount < $exchange['required_count']) {
            throw new Exception("アイテムが不足しています（必要: {$exchange['required_count']}、所持: {$userItemCount}）");
        }
        
        // 交換上限をチェック
        if ($exchange['exchange_limit'] !== null) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM user_event_exchange_history 
                WHERE user_id = ? AND exchange_id = ?
            ");
            $stmt->execute([$me['id'], $exchangeId]);
            $exchangeCount = (int)$stmt->fetchColumn();
            
            if ($exchangeCount >= $exchange['exchange_limit']) {
                throw new Exception('交換上限に達しています');
            }
        }
        
        // アイテムを消費
        $stmt = $pdo->prepare("
            UPDATE user_special_event_items 
            SET count = count - ? 
            WHERE user_id = ? AND item_id = ?
        ");
        $stmt->execute([$exchange['required_count'], $me['id'], $exchange['item_id']]);
        
        // 報酬を付与
        switch ($exchange['reward_type']) {
            case 'coins':
                $stmt = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
                $stmt->execute([$exchange['reward_amount'], $me['id']]);
                break;
            case 'crystals':
                $stmt = $pdo->prepare("UPDATE users SET crystals = crystals + ? WHERE id = ?");
                $stmt->execute([$exchange['reward_amount'], $me['id']]);
                break;
            case 'diamonds':
                $stmt = $pdo->prepare("UPDATE users SET diamonds = diamonds + ? WHERE id = ?");
                $stmt->execute([$exchange['reward_amount'], $me['id']]);
                break;
        }
        
        // 交換履歴を記録
        $stmt = $pdo->prepare("
            INSERT INTO user_event_exchange_history (user_id, exchange_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$me['id'], $exchangeId]);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => '交換が完了しました！',
            'reward_type' => $exchange['reward_type'],
            'reward_amount' => $exchange['reward_amount']
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// ヒーローイベント関連
// ===============================================

// アクティブなヒーローイベントを取得
if ($action === 'get_hero_events') {
    try {
        $now = date('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("
            SELECT ce.*, he.featured_hero_id, he.bonus_shard_rate, he.gacha_discount_percent,
                   h.name as hero_name, h.icon as hero_icon, h.title as hero_title
            FROM civilization_events ce
            JOIN hero_events he ON ce.id = he.event_id
            JOIN heroes h ON he.featured_hero_id = h.id
            WHERE ce.event_type = 'hero' 
              AND ce.is_active = TRUE 
              AND ce.start_date <= ? 
              AND ce.end_date >= ?
            ORDER BY ce.start_date ASC
        ");
        $stmt->execute([$now, $now]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        foreach ($events as $event) {
            // ヒーローイベントのタスクを取得
            $stmt = $pdo->prepare("
                SELECT het.*, COALESCE(uhetp.current_progress, 0) as current_progress,
                       COALESCE(uhetp.is_completed, FALSE) as is_completed,
                       COALESCE(uhetp.is_claimed, FALSE) as is_claimed
                FROM hero_event_tasks het
                JOIN hero_events he ON het.hero_event_id = he.id
                LEFT JOIN user_hero_event_task_progress uhetp ON het.id = uhetp.task_id AND uhetp.user_id = ?
                WHERE he.event_id = ?
            ");
            $stmt->execute([$me['id'], $event['id']]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ポイント報酬を取得
            $stmt = $pdo->prepare("
                SELECT hepr.*
                FROM hero_event_point_rewards hepr
                JOIN hero_events he ON hepr.hero_event_id = he.id
                WHERE he.event_id = ?
                ORDER BY hepr.required_points ASC
            ");
            $stmt->execute([$event['id']]);
            $pointRewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ユーザーの進捗を取得
            $stmt = $pdo->prepare("
                SELECT current_points, claimed_rewards
                FROM user_hero_event_progress uhep
                JOIN hero_events he ON uhep.hero_event_id = he.id
                WHERE uhep.user_id = ? AND he.event_id = ?
            ");
            $stmt->execute([$me['id'], $event['id']]);
            $userProgress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $result[] = [
                'id' => $event['id'],
                'event_key' => $event['event_key'],
                'name' => $event['name'],
                'description' => $event['description'],
                'icon' => $event['icon'],
                'start_date' => $event['start_date'],
                'end_date' => $event['end_date'],
                'remaining_seconds' => max(0, strtotime($event['end_date']) - time()),
                'featured_hero' => [
                    'id' => $event['featured_hero_id'],
                    'name' => $event['hero_name'],
                    'icon' => $event['hero_icon'],
                    'title' => $event['hero_title']
                ],
                'bonus_shard_rate' => (float)$event['bonus_shard_rate'],
                'gacha_discount_percent' => (int)$event['gacha_discount_percent'],
                'tasks' => $tasks,
                'point_rewards' => $pointRewards,
                'current_points' => $userProgress ? (int)$userProgress['current_points'] : 0,
                'claimed_rewards' => $userProgress ? json_decode($userProgress['claimed_rewards'], true) : []
            ];
        }
        
        echo json_encode([
            'ok' => true,
            'events' => $result
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ヒーローイベントタスク報酬を受け取る
if ($action === 'claim_hero_event_task') {
    $taskId = (int)($input['task_id'] ?? 0);
    
    $pdo->beginTransaction();
    try {
        // タスクを取得
        $stmt = $pdo->prepare("
            SELECT het.*, he.id as hero_event_id, ce.is_active, ce.end_date
            FROM hero_event_tasks het
            JOIN hero_events he ON het.hero_event_id = he.id
            JOIN civilization_events ce ON he.event_id = ce.id
            WHERE het.id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task || !$task['is_active']) {
            throw new Exception('タスクが見つかりません');
        }
        
        // 進捗を確認
        $stmt = $pdo->prepare("
            SELECT * FROM user_hero_event_task_progress 
            WHERE user_id = ? AND task_id = ?
        ");
        $stmt->execute([$me['id'], $taskId]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$progress || !$progress['is_completed']) {
            throw new Exception('タスクが未完了です');
        }
        
        if ($progress['is_claimed']) {
            throw new Exception('既に報酬を受け取っています');
        }
        
        // ポイントを付与
        $stmt = $pdo->prepare("
            INSERT INTO user_hero_event_progress (user_id, hero_event_id, current_points, claimed_rewards)
            VALUES (?, ?, ?, '[]')
            ON DUPLICATE KEY UPDATE current_points = current_points + ?
        ");
        $stmt->execute([$me['id'], $task['hero_event_id'], $task['points_reward'], $task['points_reward']]);
        
        // 報酬受取済みに更新
        $stmt = $pdo->prepare("
            UPDATE user_hero_event_task_progress 
            SET is_claimed = TRUE
            WHERE user_id = ? AND task_id = ?
        ");
        $stmt->execute([$me['id'], $taskId]);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => "ポイント{$task['points_reward']}を獲得しました！",
            'points_gained' => $task['points_reward']
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ヒーローイベントポイント報酬を受け取る
if ($action === 'claim_hero_event_point_reward') {
    $rewardId = (int)($input['reward_id'] ?? 0);
    
    $pdo->beginTransaction();
    try {
        // 報酬を取得
        $stmt = $pdo->prepare("
            SELECT hepr.*, he.id as hero_event_id, he.featured_hero_id, ce.is_active
            FROM hero_event_point_rewards hepr
            JOIN hero_events he ON hepr.hero_event_id = he.id
            JOIN civilization_events ce ON he.event_id = ce.id
            WHERE hepr.id = ?
        ");
        $stmt->execute([$rewardId]);
        $reward = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reward || !$reward['is_active']) {
            throw new Exception('報酬が見つかりません');
        }
        
        // ユーザーの進捗を取得
        $stmt = $pdo->prepare("
            SELECT current_points, claimed_rewards
            FROM user_hero_event_progress
            WHERE user_id = ? AND hero_event_id = ?
        ");
        $stmt->execute([$me['id'], $reward['hero_event_id']]);
        $userProgress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userProgress) {
            throw new Exception('イベント進捗がありません');
        }
        
        if ($userProgress['current_points'] < $reward['required_points']) {
            throw new Exception('ポイントが不足しています');
        }
        
        $claimedRewards = json_decode($userProgress['claimed_rewards'], true) ?: [];
        if (in_array($rewardId, $claimedRewards)) {
            throw new Exception('既に報酬を受け取っています');
        }
        
        // 報酬を付与
        switch ($reward['reward_type']) {
            case 'coins':
                $stmt = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
                $stmt->execute([$reward['reward_amount'], $me['id']]);
                break;
            case 'crystals':
                $stmt = $pdo->prepare("UPDATE users SET crystals = crystals + ? WHERE id = ?");
                $stmt->execute([$reward['reward_amount'], $me['id']]);
                break;
            case 'diamonds':
                $stmt = $pdo->prepare("UPDATE users SET diamonds = diamonds + ? WHERE id = ?");
                $stmt->execute([$reward['reward_amount'], $me['id']]);
                break;
            case 'hero_shards':
                $stmt = $pdo->prepare("
                    INSERT INTO user_heroes (user_id, hero_id, shards)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE shards = shards + ?
                ");
                $stmt->execute([$me['id'], $reward['featured_hero_id'], $reward['reward_amount'], $reward['reward_amount']]);
                break;
        }
        
        // 受け取り済みに追加
        $claimedRewards[] = $rewardId;
        $stmt = $pdo->prepare("
            UPDATE user_hero_event_progress 
            SET claimed_rewards = ?
            WHERE user_id = ? AND hero_event_id = ?
        ");
        $stmt->execute([json_encode($claimedRewards), $me['id'], $reward['hero_event_id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => '報酬を受け取りました！',
            'reward_type' => $reward['reward_type'],
            'reward_amount' => $reward['reward_amount']
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'invalid_action']);
