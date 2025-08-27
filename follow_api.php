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
    if ($action === 'list_following') {
        // 指定ユーザーがフォローしている一覧
        $stmt = $pdo->prepare("SELECT u.id, u.handle_name 
            FROM follows f 
            JOIN users u ON f.followee_id = u.id 
            WHERE f.follower_id = ?");
        $stmt->execute([$target_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'following' => $rows]);

    } elseif ($action === 'list_followers') {
        // 指定ユーザーをフォローしている一覧
        $stmt = $pdo->prepare("SELECT u.id, u.handle_name 
            FROM follows f 
            JOIN users u ON f.follower_id = u.id 
            WHERE f.followee_id = ?");
        $stmt->execute([$target_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'followers' => $rows]);

    } elseif ($action === 'is_following') {
        // ログインユーザーが対象をフォローしているか確認
        $check_id = isset($_GET['target_id']) ? (int)$_GET['target_id'] : 0;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND followee_id = ?");
        $stmt->execute([$user_id, $check_id]);
        $is_following = $stmt->fetchColumn() > 0;
        echo json_encode(['status' => 'success', 'is_following' => $is_following]);

    } else {
        echo json_encode(['status' => 'error', 'message' => '不明なアクションです']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'DBエラー: ' . $e->getMessage()]);
}
