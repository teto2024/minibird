<?php
// ===============================================
// appeal_api.php
// 異議申し立て機能 API
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

// 異議申し立てを送信
if ($action === 'submit_appeal') {
    $reason = trim($input['reason'] ?? '');
    
    if (!$reason) {
        echo json_encode(['ok' => false, 'error' => 'reason_required']);
        exit;
    }
    
    // 既に審査待ちの異議申し立てがないかチェック
    $stmt = $pdo->prepare("SELECT id FROM appeals WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$me['id']]);
    if ($stmt->fetch()) {
        echo json_encode(['ok' => false, 'error' => 'appeal_pending', 'message' => '既に審査待ちの異議申し立てがあります']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO appeals (user_id, reason, status, created_at)
            VALUES (?, ?, 'pending', NOW())
        ");
        $stmt->execute([$me['id'], $reason]);
        
        echo json_encode(['ok' => true, 'message' => '異議申し立てを受け付けました']);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => 'database_error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'invalid_action']);
