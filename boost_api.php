<?php
// ===============================================
// boost_api.php
// ポストブースト機能 API
// ===============================================

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$me = user();
if (!$me) {
    echo json_encode(['ok' => false, 'error' => 'login_required']);
    exit;
}

$pdo = db();
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

// ブースト実行
if ($action === 'boost') {
    $post_id = (int)($input['post_id'] ?? 0);
    
    if (!$post_id) {
        echo json_encode(['ok' => false, 'error' => 'invalid_post_id']);
        exit;
    }
    
    // 投稿の存在確認と投稿日時取得
    $stmt = $pdo->prepare("SELECT id, user_id, created_at FROM posts WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        echo json_encode(['ok' => false, 'error' => 'post_not_found']);
        exit;
    }
    
    // コインとクリスタルのチェック
    $coins_cost = 200;
    $crystals_cost = 20;
    
    if ($me['coins'] < $coins_cost || $me['crystals'] < $crystals_cost) {
        echo json_encode(['ok' => false, 'error' => 'insufficient_currency', 
            'required' => ['coins' => $coins_cost, 'crystals' => $crystals_cost],
            'current' => ['coins' => $me['coins'], 'crystals' => $me['crystals']]
        ]);
        exit;
    }
    
    // 期限計算：投稿日から2日後
    $post_created = new DateTime($post['created_at']);
    $expires_at = (clone $post_created)->modify('+2 days')->format('Y-m-d H:i:s');
    
    try {
        $pdo->beginTransaction();
        
        // ブースト記録を追加
        $stmt = $pdo->prepare("
            INSERT INTO post_boosts (post_id, user_id, coins_spent, crystals_spent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$post_id, $me['id'], $coins_cost, $crystals_cost, $expires_at]);
        
        // コインとクリスタルを消費
        $stmt = $pdo->prepare("
            UPDATE users 
            SET coins = coins - ?, crystals = crystals - ?
            WHERE id = ?
        ");
        $stmt->execute([$coins_cost, $crystals_cost, $me['id']]);
        
        // 投稿者に通知を送る（自分の投稿は除く）
        if ($post['user_id'] != $me['id']) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, actor_id, type, post_id, created_at, is_read)
                VALUES (?, ?, 'boost', ?, NOW(), 0)
            ");
            $stmt->execute([$post['user_id'], $me['id'], $post_id]);
        }
        
        $pdo->commit();
        
        // ブースト数を取得
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as boost_count 
            FROM post_boosts 
            WHERE post_id = ? AND expires_at > NOW()
        ");
        $stmt->execute([$post_id]);
        $boost_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'ok' => true, 
            'boost_count' => (int)$boost_info['boost_count'],
            'remaining' => [
                'coins' => $me['coins'] - $coins_cost,
                'crystals' => $me['crystals'] - $crystals_cost
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => 'database_error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ブースト状態取得
if ($action === 'get_boost_status') {
    $post_id = (int)($input['post_id'] ?? 0);
    
    if (!$post_id) {
        echo json_encode(['ok' => false, 'error' => 'invalid_post_id']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as boost_count,
               EXISTS(SELECT 1 FROM post_boosts WHERE post_id = ? AND user_id = ? AND expires_at > NOW()) as user_boosted
        FROM post_boosts 
        WHERE post_id = ? AND expires_at > NOW()
    ");
    $stmt->execute([$post_id, $me['id'], $post_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'ok' => true,
        'boost_count' => (int)$result['boost_count'],
        'user_boosted' => (bool)$result['user_boosted']
    ]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'invalid_action']);
