<?php
// 管理者用パスワードリセット申請API
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// セッション認証チェック
if (!isset($_SESSION['uid'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'login_required']);
    exit;
}

// 管理者権限チェック (id = 1)
$admin_id = (int)$_SESSION['uid'];
if ($admin_id !== 1) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'admin_only']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$pdo = db();

try {
    if ($action === 'list_requests') {
        $status = $input['status'] ?? 'all';
        
        $sql = "
            SELECT 
                prr.id,
                prr.user_id,
                prr.handle,
                prr.reason,
                prr.status,
                prr.admin_comment,
                prr.requested_at,
                prr.reviewed_at,
                prr.reviewed_by,
                u_reviewer.handle as reviewer_handle
            FROM password_reset_requests prr
            LEFT JOIN users u_reviewer ON prr.reviewed_by = u_reviewer.id
        ";
        
        $params = [];
        if ($status !== 'all') {
            if (!in_array($status, ['pending', 'approved', 'rejected'])) {
                throw new Exception("Invalid status filter: '$status'. Must be pending, approved, rejected, or all");
            }
            $sql .= " WHERE prr.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY prr.requested_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $requests = $stmt->fetchAll();
        
        echo json_encode([
            'ok' => true,
            'requests' => $requests
        ]);
        exit;
    }
    
    if ($action === 'approve_request') {
        $request_id = (int)($input['request_id'] ?? 0);
        $admin_comment = trim($input['admin_comment'] ?? '');
        
        if (!$request_id) {
            throw new Exception('Request ID required');
        }
        
        // リクエスト取得
        $stmt = $pdo->prepare("
            SELECT user_id, new_password_hash, status 
            FROM password_reset_requests 
            WHERE id = ?
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            throw new Exception('Request not found');
        }
        
        if ($request['status'] !== 'pending') {
            throw new Exception('Request already processed');
        }
        
        if (!$request['new_password_hash']) {
            throw new Exception('No password hash found');
        }
        
        // パスワードハッシュの形式を検証
        $hashInfo = password_get_info($request['new_password_hash']);
        if ($hashInfo['algo'] === null || $hashInfo['algo'] === 0) {
            throw new Exception('Invalid password hash format');
        }
        
        // トランザクション開始
        $pdo->beginTransaction();
        
        try {
            // ユーザーのパスワード更新
            $stmt = $pdo->prepare("
                UPDATE users 
                SET passhash = ? 
                WHERE id = ?
            ");
            $stmt->execute([$request['new_password_hash'], $request['user_id']]);
            
            // リクエストステータス更新
            $stmt = $pdo->prepare("
                UPDATE password_reset_requests 
                SET status = 'approved',
                    reviewed_at = NOW(),
                    reviewed_by = ?,
                    admin_comment = ?
                WHERE id = ?
            ");
            $stmt->execute([$admin_id, $admin_comment, $request_id]);
            
            $pdo->commit();
            
            echo json_encode([
                'ok' => true,
                'message' => 'Request approved successfully'
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
        exit;
    }
    
    if ($action === 'reject_request') {
        $request_id = (int)($input['request_id'] ?? 0);
        $admin_comment = trim($input['admin_comment'] ?? '');
        
        if (!$request_id) {
            throw new Exception('Request ID required');
        }
        
        // リクエスト取得
        $stmt = $pdo->prepare("
            SELECT status 
            FROM password_reset_requests 
            WHERE id = ?
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            throw new Exception('Request not found');
        }
        
        if ($request['status'] !== 'pending') {
            throw new Exception('Request already processed');
        }
        
        // リクエストステータス更新
        $stmt = $pdo->prepare("
            UPDATE password_reset_requests 
            SET status = 'rejected',
                reviewed_at = NOW(),
                reviewed_by = ?,
                admin_comment = ?
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $admin_comment, $request_id]);
        
        echo json_encode([
            'ok' => true,
            'message' => 'Request rejected successfully'
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