<?php
// mark_notifications_read.php - 安全版
ob_start();
ini_set('display_errors', 0); // 画面には出さずログに記録
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();

try {
    require 'config.php';
    require_login(); // ログインチェック

    header('Content-Type: application/json; charset=utf-8');

    // POST 以外は拒否
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        ob_clean();
        echo json_encode(["ok" => false, "error" => "Method not allowed"]);
        exit;
    }

    $pdo = db();
    $user_id = $_SESSION['uid'];

    $st = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $st->execute([$user_id]);

    ob_clean();
    echo json_encode([
        "ok" => true,
        "updated" => $st->rowCount() // 更新件数
    ]);

} catch (Exception $e) {
    http_response_code(500);
    ob_clean();
    echo json_encode([
        "ok" => false,
        "error" => "exception",
        "message" => $e->getMessage()
    ]);
}

exit;
