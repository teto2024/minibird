<?php
require_once __DIR__ . '/config.php';
$me = user();
if (!$me || !in_array($me['role'], ['mod','admin'])) { http_response_code(403); echo "forbidden"; exit; }
$pdo = db();
if (isset($_GET['del'])){
  $id = (int)$_GET['del'];
  $pdo->prepare("UPDATE posts SET deleted_at=NOW(), deleted_by_mod=1 WHERE id=?")->execute([$id]);
  $pdo->prepare("INSERT INTO moderation_logs(mod_user_id,action,target_post_id,reason) VALUES(?,?,?,?)")->execute([$me['id'],'delete_post',$id,'manual']);
}
header('Location: admin.php');
