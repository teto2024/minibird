<?php
session_start();
require_once 'config.php';
$pdo = db();


header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ログインが必要です']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$target_id = isset($_POST['target_id']) ? (int)$_POST['target_id'] : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : $user_id);

try {
    if ($action === 'list_following') {
        // 指定ユーザーがフォローしている一覧
        $stmt = $pdo->prepare("SELECT u.id, u.handle, u.display_name, u.icon 
            FROM follows f 
            JOIN users u ON f.followee_id = u.id 
            WHERE f.follower_id = ?");
        $stmt->execute([$target_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'following' => $rows]);

    } elseif ($action === 'list_followers') {
        // 指定ユーザーをフォローしている一覧
        $stmt = $pdo->prepare("SELECT u.id, u.handle, u.display_name, u.icon 
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

    } elseif ($action === 'follow' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // フォロー処理
        $check_id = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;
        if ($check_id <= 0 || $check_id === $user_id) {
            echo json_encode(['status' => 'error', 'message' => '不正な対象です']);
            exit;
        }
        // 既存チェック
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND followee_id = ?");
        $stmt->execute([$user_id, $check_id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'すでにフォローしています']);
            exit;
        }
        // 追加
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, followee_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $check_id]);
        
        // フォロー通知を作成
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, actor_id, type, created_at, is_read)
            VALUES (?, ?, 'follow', NOW(), 0)
        ");
        $stmt->execute([$check_id, $user_id]);
        
        echo json_encode(['status' => 'success', 'message' => 'フォローしました']);

    } elseif ($action === 'unfollow' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // アンフォロー処理
        $check_id = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followee_id = ?");
        $stmt->execute([$user_id, $check_id]);
        echo json_encode(['status' => 'success', 'message' => 'フォローを解除しました']);

    } else {
        echo json_encode(['status' => 'error', 'message' => '不明なアクションです']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'DBエラー: ' . $e->getMessage()]);
}
