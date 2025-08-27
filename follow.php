<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ログインが必要です']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$target_id = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;

if ($target_id <= 0 || $target_id === $user_id) {
    echo json_encode(['status' => 'error', 'message' => '対象ユーザーが不正です']);
    exit;
}

try {
    if ($action === 'follow') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO follows (follower_id, followee_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $target_id]);
        echo json_encode(['status' => 'success', 'message' => 'フォローしました']);
    } elseif ($action === 'unfollow') {
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followee_id = ?");
        $stmt->execute([$user_id, $target_id]);
        echo json_encode(['status' => 'success', 'message' => 'フォロー解除しました']);
    } else {
        echo json_encode(['status' => 'error', 'message' => '不明なアクションです']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'DBエラー: ' . $e->getMessage()]);
}
