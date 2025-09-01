<?php
// ----- デバッグ用設定 -----
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

try {

    if ($_SERVER['CONTENT_TYPE'] && str_starts_with($_SERVER['CONTENT_TYPE'], 'multipart/form-data')) {
        $action = $_POST['action'] ?? '';
    } else {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $input['action'] ?? '';
    }

    function pass_banned($text) {
        $pdo = db();
        $rows = $pdo->query("SELECT word FROM banned_words")->fetchAll();
        foreach ($rows as $r) {
            if ($r['word'] !== '' && mb_stripos($text, $r['word']) !== false) return false;
        }
        return true;
    }

    if ($action === 'create_post') {
        require_login();
        $u = user();
        if ($u['muted_until'] && strtotime($u['muted_until']) > time()) {
    echo json_encode(['ok'=>false,'error'=>'muted']); exit;
}
        $content = trim($_POST['content'] ?? '');
        $nsfw = ($_POST['nsfw'] ?? '0') === '1' ? 1 : 0;
        if ($content === '' && empty($_FILES['media']['name'])) {
            echo json_encode(['ok'=>false,'error'=>'empty']); exit;
        }
        if (!pass_banned($content)) { echo json_encode(['ok'=>false,'error'=>'banned_word']); exit; }

        $media_path = null; $media_type = null;
        if (!empty($_FILES['media']['name'])) {
            $size = $_FILES['media']['size'] ?? 0;
            if ($size > $GLOBALS['MAX_UPLOAD_BYTES']) { echo json_encode(['ok'=>false,'error'=>'file_too_large']); exit; }
            $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
            $media_type = in_array($ext, ['mp4','webm']) ? 'video' : 'image';
            $safe = bin2hex(random_bytes(12)).'.'.$ext;
            $dir = __DIR__ . '/uploads';
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $dest = $dir.'/'.$safe;
            if (!move_uploaded_file($_FILES['media']['tmp_name'], $dest)) { echo json_encode(['ok'=>false,'error'=>'upload_failed']); exit; }
            $media_path = 'uploads/'.$safe;
        }

        $html = markdown_to_html($content);
        $pdo = db();
        $pdo->prepare("INSERT INTO posts(user_id,content_md,content_html,nsfw,media_path,media_type,created_at) VALUES(?,?,?,?,?,?,NOW())")
            ->execute([$_SESSION['uid'],$content,$html,$nsfw,$media_path,$media_type]);
        $post_id = $pdo->lastInsertId();

        // random coin reward
        $coins = random_int(1,7);
        $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id=?")->execute([$coins,$_SESSION['uid']]);
        $pdo->prepare("INSERT INTO reward_events(user_id,kind,amount,meta) VALUES(?,?,?,JSON_OBJECT('post_id',?))")
            ->execute([$_SESSION['uid'],'post_reward',$coins,$post_id]);

        echo json_encode(['ok'=>true,'id'=>$post_id]); exit;
    }

    if ($action === 'quote_post') {
        require_login();
        $post_id = (int)($_POST['post_id'] ?? $input['post_id'] ?? 0);
        $content = trim($_POST['content'] ?? $input['content'] ?? '');
        if ($post_id<=0 || $content===''){ echo json_encode(['ok'=>false,'error'=>'bad_input']); exit; }
        $pdo = db();
        $st = $pdo->prepare("SELECT id, content_md FROM posts WHERE id=?");
        $st->execute([$post_id]);
        $ref = $st->fetch();
        if (!$ref) { echo json_encode(['ok'=>false,'error'=>'not_found']); exit; }
        $html = markdown_to_html($content."\n\n> 引用: ".$ref['content_md']);
        $pdo->prepare("INSERT INTO posts(user_id,content_md,content_html,quote_post_id,created_at) VALUES(?,?,?,?,NOW())")
            ->execute([$_SESSION['uid'],$content,$html,$post_id]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'unknown_action']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>'php_exception', 'message'=>$e->getMessage()]);
    exit;
}
