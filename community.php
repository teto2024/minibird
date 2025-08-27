<?php
require_once '../config.php';
session_start();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) exit(json_encode(['error'=>'Not logged in']));

$action = $_POST['action'] ?? '';
$community_id = intval($_POST['community_id'] ?? 0);
$target_id = intval($_POST['user_id'] ?? 0);

// コミュニティ情報取得
$stmt = $pdo->prepare("SELECT * FROM communities WHERE id=?");
$stmt->execute([$community_id]);
$community = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$community) exit(json_encode(['error'=>'Community not found']));

$is_owner = ($community['owner_id'] == $user_id);

// 参加者追加（オーナーのみ）
if ($action === 'add_member' && $is_owner) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO community_members (community_id, user_id, added_by, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$community_id, $target_id, $user_id]);
    echo json_encode(['success'=>true]);

// 参加者削除（オーナーのみ）
} elseif ($action === 'remove_member' && $is_owner) {
    $stmt = $pdo->prepare("DELETE FROM community_members WHERE community_id=? AND user_id=?");
    $stmt->execute([$community_id, $target_id]);
    echo json_encode(['success'=>true]);

// コミュニティ情報取得
} elseif ($action === 'get_info') {
    $stmt = $pdo->prepare("
        SELECT cm.user_id, u.handle
        FROM community_members cm
        JOIN users u ON u.id = cm.user_id
        WHERE cm.community_id=?
    ");
    $stmt->execute([$community_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'community' => $community,
        'members' => $members
    ]);
} else {
    echo json_encode(['error'=>'Invalid action or permission denied']);
}
