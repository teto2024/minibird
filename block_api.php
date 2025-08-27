<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ログインが必要です']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$target_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $user_id;

try {
    if ($action === 'list_blocks') {
        // 指定ユーザーがブロックしている一覧
        $stmt = $pdo->prepare("SELECT u.id, u.handle_name 
            FROM user_blocks b 
            JOIN users u ON b.blocked_id = u.id 
            WHERE b.blocker_id = ?");
        $stmt->execute([$target_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'blocks' => $rows]);

    } elseif ($action === 'is_blocked') {
        // ログインユーザーが対象をブロックしているか確認
        $check_id = isset($_GET['target_id']) ? (int)$_GET['target_id'] : 0;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?");
        $stmt->execute([$user_id, $check_id]);
        $is_blocked = $stmt->fetchColumn() > 0;
        echo json_encode(['status' => 'success', 'is_blocked' => $is_blocked]);

    } else {
        echo json_encode(['status' => 'error', 'message' => '不明なアクションです']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'DBエラー: ' . $e->getMessage()]);
}
