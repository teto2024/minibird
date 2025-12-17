<?php
// パスワードリセット申請API
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$pdo = db();

try {
    if ($action === 'request_password_reset') {
        $handle = trim($input['handle'] ?? '');
        $new_password = $input['new_password'] ?? '';
        $reason = trim($input['reason'] ?? '');
        
        if (empty($handle) || empty($new_password) || empty($reason)) {
            throw new Exception('All fields required');
        }
        
        if (strlen($new_password) < 6) {
            throw new Exception('Password must be at least 6 characters');
        }
        
        // ユーザー存在確認
        $stmt = $pdo->prepare("SELECT id FROM users WHERE handle = ?");
        $stmt->execute([$handle]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // 既存の pending リクエストがあるか確認
        $stmt = $pdo->prepare("
            SELECT id FROM password_reset_requests 
            WHERE user_id = ? AND status = 'pending'
        ");
        $stmt->execute([$user['id']]);
        if ($stmt->fetch()) {
            throw new Exception('You already have a pending request');
        }
        
        // 新しいパスワードハッシュを生成
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // 申請を記録
        $stmt = $pdo->prepare("
            INSERT INTO password_reset_requests 
            (user_id, handle, reason, new_password_hash, status, requested_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$user['id'], $handle, $reason, $new_password_hash]);
        
        echo json_encode([
            'ok' => true,
            'message' => 'Request submitted successfully'
        ]);
        exit;
    }
    
    throw new Exception('Invalid action');
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
