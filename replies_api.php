<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

if ($action==='list'){
  $post_id = (int)($input['post_id'] ?? 0);
  $pdo = db();
  $st = $pdo->prepare("SELECT r.*, u.handle FROM replies r JOIN users u ON u.id=r.user_id WHERE r.post_id=? ORDER BY r.id DESC LIMIT 200");
  $st->execute([$post_id]);
  $items = [];
  foreach ($st as $row){
    $items[] = [
      'id'=>(int)$row['id'],
      'handle'=>$row['handle'],
      'content_html'=>$row['content_html'],
      'created_at'=>$row['created_at']
    ];
  }
  echo json_encode(['ok'=>true,'items'=>$items]); exit;
}

if ($action==='create'){
  require_login();
  $post_id = (int)($input['post_id'] ?? 0);
  $content = trim($input['content'] ?? '');
  $nsfw = (int)($input['nsfw'] ?? 0);
  if ($post_id<=0 || $content===''){ echo json_encode(['ok'=>false,'error'=>'bad_input']); exit; }
  $pdo = db();
  $html = markdown_to_html($content);
  $pdo->prepare("INSERT INTO replies(post_id,user_id,content_md,content_html,nsfw,created_at) VALUES(?,?,?,?,?,NOW())")
      ->execute([$post_id,$_SESSION['uid'],$content,$html,$nsfw]);
  echo json_encode(['ok'=>true]); exit;
}

echo json_encode(['ok'=>false,'error'=>'unknown_action']);
