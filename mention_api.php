<?php
// ===============================================
// mention_api.php
// メンション補完用API
// ===============================================

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$me = user();
if (!$me) {
    echo json_encode(['ok' => false, 'error' => 'login_required']);
    exit;
}

$query = trim($_GET['q'] ?? '');

// 空クエリの場合は空配列を返す
if ($query === '') {
    echo json_encode(['ok' => true, 'users' => []]);
    exit;
}

// @を除去
$query = ltrim($query, '@');

if (strlen($query) < 1) {
    echo json_encode(['ok' => true, 'users' => []]);
    exit;
}

try {
    $pdo = db();
    
    // ユーザー検索（handle または display_name で前方一致優先）
    // Note: For better performance, consider adding indexes on handle and display_name columns
    $stmt = $pdo->prepare("
        SELECT id, handle, display_name, icon
        FROM users
        WHERE (handle LIKE ? OR display_name LIKE ?)
        ORDER BY 
            CASE WHEN handle LIKE ? THEN 0 ELSE 1 END,
            handle
        LIMIT 10
    ");
    $likeQuery = $query . '%';
    $stmt->execute([$likeQuery, '%' . $query . '%', $likeQuery]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // アイコンのデフォルト値を設定
    foreach ($users as &$user) {
        if (empty($user['icon'])) {
            $user['icon'] = '/uploads/icons/default_icon.png';
        }
    }
    
    echo json_encode(['ok' => true, 'users' => $users]);
} catch (Exception $e) {
    error_log("Mention API error: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'db_error']);
}
