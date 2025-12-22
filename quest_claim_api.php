<?php
// ===============================================
// quest_claim_api.php
// クエスト完了報酬受け取りAPI
// ===============================================

require_once __DIR__ . '/config.php';
require_login();
header('Content-Type: application/json');

$pdo = db();
$user_id = $_SESSION['uid'];

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$type = $input['type'] ?? ''; // 'daily', 'weekly', 'relay'

try {
    if ($type === 'daily') {
        $today = date('Y-m-d');
        
        // デイリークエスト全完了チェック
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN uqp.status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM quests q
            LEFT JOIN user_quest_progress uqp ON uqp.quest_id = q.id AND uqp.user_id = ? 
                AND DATE(uqp.started_at) = ?
            WHERE q.type = 'daily' AND q.is_active = TRUE
        ");
        $stmt->execute([$user_id, $today]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] == 0 || $result['completed'] != $result['total']) {
            echo json_encode(['ok' => false, 'error' => 'not_completed']);
            exit;
        }
        
        // すでに報酬を受け取っているかチェック
        $stmt = $pdo->prepare("
            SELECT id FROM quest_completions 
            WHERE user_id = ? AND completion_type = 'daily' AND period_key = ?
        ");
        $stmt->execute([$user_id, $today]);
        
        if ($stmt->fetch()) {
            echo json_encode(['ok' => false, 'error' => 'already_claimed']);
            exit;
        }
        
        // 報酬付与
        $coins = 3000;
        $crystals = 15;
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO quest_completions (user_id, completion_type, period_key, reward_coins, reward_crystals)
                VALUES (?, 'daily', ?, ?, ?)
            ");
            $stmt->execute([$user_id, $today, $coins, $crystals]);
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET coins = coins + ?, crystals = crystals + ?, daily_quest_completions = daily_quest_completions + 1
                WHERE id = ?
            ");
            $stmt->execute([$coins, $crystals, $user_id]);
            
            $pdo->commit();
            echo json_encode(['ok' => true, 'coins' => $coins, 'crystals' => $crystals, 'diamonds' => 0]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => 'database_error']);
        }
    } elseif ($type === 'weekly') {
        $week_key = date('Y-W');
        
        // ウィークリークエスト全完了チェック
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN uqp.status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM quests q
            LEFT JOIN user_quest_progress uqp ON uqp.quest_id = q.id AND uqp.user_id = ? 
                AND YEARWEEK(uqp.started_at, 1) = YEARWEEK(NOW(), 1)
            WHERE q.type = 'weekly' AND q.is_active = TRUE
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] == 0 || $result['completed'] != $result['total']) {
            echo json_encode(['ok' => false, 'error' => 'not_completed']);
            exit;
        }
        
        // すでに報酬を受け取っているかチェック
        $stmt = $pdo->prepare("
            SELECT id FROM quest_completions 
            WHERE user_id = ? AND completion_type = 'weekly' AND period_key = ?
        ");
        $stmt->execute([$user_id, $week_key]);
        
        if ($stmt->fetch()) {
            echo json_encode(['ok' => false, 'error' => 'already_claimed']);
            exit;
        }
        
        // 報酬付与
        $coins = 10000;
        $crystals = 50;
        $diamonds = 2;
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO quest_completions (user_id, completion_type, period_key, reward_coins, reward_crystals, reward_diamonds)
                VALUES (?, 'weekly', ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $week_key, $coins, $crystals, $diamonds]);
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET coins = coins + ?, crystals = crystals + ?, diamonds = diamonds + ?, weekly_quest_completions = weekly_quest_completions + 1
                WHERE id = ?
            ");
            $stmt->execute([$coins, $crystals, $diamonds, $user_id]);
            
            $pdo->commit();
            echo json_encode(['ok' => true, 'coins' => $coins, 'crystals' => $crystals, 'diamonds' => $diamonds]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => 'database_error']);
        }
    } elseif ($type === 'relay') {
        $completion_key = 'relay_' . date('Y-m-d');
        
        // リレークエスト全完了チェック
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM quests WHERE type = 'relay' AND is_active = TRUE");
        $stmt->execute();
        $total_relay = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT current_order FROM relay_quest_progress WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $progress = $stmt->fetch();
        
        if (!$progress || $progress['current_order'] <= $total_relay) {
            echo json_encode(['ok' => false, 'error' => 'not_completed']);
            exit;
        }
        
        // すでに報酬を受け取っているかチェック
        $stmt = $pdo->prepare("
            SELECT id FROM quest_completions 
            WHERE user_id = ? AND completion_type = 'relay' AND period_key = ?
        ");
        $stmt->execute([$user_id, $completion_key]);
        
        if ($stmt->fetch()) {
            echo json_encode(['ok' => false, 'error' => 'already_claimed']);
            exit;
        }
        
        // 報酬付与
        $coins = 2000;
        $diamonds = 1;
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO quest_completions (user_id, completion_type, period_key, reward_coins, reward_diamonds)
                VALUES (?, 'relay', ?, ?, ?)
            ");
            $stmt->execute([$user_id, $completion_key, $coins, $diamonds]);
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET coins = coins + ?, diamonds = diamonds + ?, relay_quest_completions = relay_quest_completions + 1
                WHERE id = ?
            ");
            $stmt->execute([$coins, $diamonds, $user_id]);
            
            $pdo->commit();
            echo json_encode(['ok' => true, 'coins' => $coins, 'crystals' => 0, 'diamonds' => $diamonds]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => 'database_error']);
        }
    } else {
        echo json_encode(['ok' => false, 'error' => 'invalid_type']);
    }
} catch (Exception $e) {
    error_log("Quest claim error: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'exception', 'message' => $e->getMessage()]);
}
