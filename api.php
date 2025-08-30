<?php
require_once __DIR__.'/config.php';


header('Content-Type: application/json');

$uid = $_SESSION['uid'] ?? 0;
if (!$uid) { echo json_encode(['ok'=>false,'error'=>'login_required']); exit; }

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

$pdo = db();

if ($action === 'bookmark_toggle') {
    $post_id = (int)($input['post_id'] ?? 0);
    if (!$post_id) { echo json_encode(['ok'=>false,'error'=>'invalid']); exit; }

    // 既にブックマークされているか
    $st = $pdo->prepare("SELECT 1 FROM bookmarks WHERE user_id=? AND post_id=?");
    $st->execute([$uid, $post_id]);
    $exists = (bool)$st->fetchColumn();

    if ($exists) {
        $st = $pdo->prepare("DELETE FROM bookmarks WHERE user_id=? AND post_id=?");
        $st->execute([$uid, $post_id]);
        echo json_encode(['ok'=>true,'bookmarked'=>false]);
    } else {
        $st = $pdo->prepare("INSERT INTO bookmarks (user_id, post_id, created_at) VALUES (?, ?, NOW())");
        $st->execute([$uid, $post_id]);
        echo json_encode(['ok'=>true,'bookmarked'=>true]);
    }
    exit;
}

echo json_encode(['ok'=>false,'error'=>'unknown_action']);
