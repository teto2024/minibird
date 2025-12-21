<?php
// ===============================================
// report_api.php
// 通報機能 API
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

// 通報を送信
if ($action === 'submit_report') {
    $post_id = (int)($input['post_id'] ?? 0);
    $reason = trim($input['reason'] ?? '');
    $details = trim($input['details'] ?? '');
    
    if (!$post_id || !$reason) {
        echo json_encode(['ok' => false, 'error' => 'invalid_input']);
        exit;
    }
    
    // 投稿の存在確認
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$post_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['ok' => false, 'error' => 'post_not_found']);
        exit;
    }
    
    // 既に同じユーザーが同じ投稿を通報していないかチェック
    $stmt = $pdo->prepare("SELECT id FROM reports WHERE post_id = ? AND reporter_id = ?");
    $stmt->execute([$post_id, $me['id']]);
    if ($stmt->fetch()) {
        echo json_encode(['ok' => false, 'error' => 'already_reported']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO reports (post_id, reporter_id, reason, details, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$post_id, $me['id'], $reason, $details]);
        
        echo json_encode(['ok' => true, 'message' => '通報を受け付けました']);
    } catch (Exception $e) {
        error_log('Report submission error: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'database_error', 'message' => 'データベースエラーが発生しました']);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'invalid_action']);
