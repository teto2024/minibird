<?php
require_once __DIR__.'/config.php';
header('Content-Type: application/json');

$uid = $_SESSION['uid'] ?? 0;
if (!$uid) { echo json_encode(['ok'=>false,'error'=>'login_required']); exit; }

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$post_id = (int)($input['post_id'] ?? 0);

$pdo = db();
$me = user();
$st = $pdo->prepare("SELECT user_id FROM posts WHERE id=?");
$st->execute([$post_id]);
$post = $st->fetch();

if (!$post) { echo json_encode(['ok'=>false,'error'=>'not_found']); exit; }

$canDelete = ($uid == $post['user_id'] || ($me && ($me['role']==='mod' || $me['role']==='admin')));
if (!$canDelete) { echo json_encode(['ok'=>false,'error'=>'forbidden']); exit; }

$byMod = ($uid != $post['user_id']);
$st = $pdo->prepare("UPDATE posts SET deleted_at=NOW(), deleted_by_mod=? WHERE id=?");
$st->execute([$byMod ? 1 : 0, $post_id]);

echo json_encode(['ok'=>true]);
