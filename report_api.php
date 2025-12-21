<?php
// ===============================================
// report_api.php
// 通報機能 API
// ===============================================

require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);  // 本番環境ではエラー表示をオフに
error_reporting(E_ALL);

$me = user();
if (!$me) {
    echo json_encode(['ok' => false, 'error' => 'login_required'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = db();

// JSONリクエストの読み取り（エラーハンドリング強化）
$input_raw = file_get_contents('php://input');
$input = json_decode($input_raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['ok' => false, 'error' => 'invalid_json', 'message' => 'JSONの解析に失敗しました'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = $input ?: [];
$action = $input['action'] ?? '';

// 通報を送信
if ($action === 'submit_report') {
    $post_id = (int)($input['post_id'] ?? 0);
    $reason = trim($input['reason'] ?? '');
    $details = trim($input['details'] ?? '');
    
    if (!$post_id || !$reason) {
        echo json_encode(['ok' => false, 'error' => 'invalid_input', 'message' => '投稿IDと理由は必須です'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 投稿の存在確認
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$post_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['ok' => false, 'error' => 'post_not_found', 'message' => '投稿が見つかりません'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 既に同じユーザーが同じ投稿を通報していないかチェック
    $stmt = $pdo->prepare("SELECT id FROM reports WHERE post_id = ? AND reporter_id = ?");
    $stmt->execute([$post_id, $me['id']]);
    if ($stmt->fetch()) {
        echo json_encode(['ok' => false, 'error' => 'already_reported', 'message' => 'この投稿は既に通報済みです'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO reports (post_id, reporter_id, reason, details, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$post_id, $me['id'], $reason, $details]);
        
        echo json_encode(['ok' => true, 'message' => '通報を受け付けました'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log('Report submission error: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'database_error', 'message' => 'データベースエラーが発生しました'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'invalid_action', 'message' => '無効なアクションです'], JSON_UNESCAPED_UNICODE);
