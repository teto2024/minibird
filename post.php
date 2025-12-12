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

        $pdo = db();

        // --- チャット祭バフ判定 ---
        $nowStr = (new DateTime())->format('Y-m-d H:i:s');
        $st = $pdo->prepare("
            SELECT 1 
            FROM buffs 
            WHERE type='chat_festival' AND start_time<=? AND end_time>=? 
            LIMIT 1
        ");
        $st->execute([$nowStr,$nowStr]);
        if ($st->fetchColumn()) {
            $faces = [
    // シンプルだけど可愛い
    '٩( ᐛ )و','(ง ˙ω˙)ว','( ᐛ )و','٩(　ᐕ)و','(　ᐕ)⁾⁾','ᕕ( ᐛ )ᕗ','m(._.)m',
    '(　˙³˙)','乁( ˙ω˙ 乁)ｳｨｰ!','_(ゝLꒊ:)_','_(:3 ⌒ﾞ)_','三三ᕕ( ᐛ )ᕗ','ᕙ(⍢)ᕗ',
    '(๑¯ω¯๑)','(๏ɷ๏)','( :D)┸┓','o(:3 )～(¦3ꇤ )=','(((ง’ω’)و三 ง’ω’)ڡ≡ｼｭｯｼｭ',
    '₍₍ ᕕ( ˘ω˘ )ᕗ⁾⁾','＼＼٩( ‘ω’ )و //／／','(　³ω³ )','(⊙ө⊙;)','ฅ(๑*д*๑)ฅ!!',
    '( ‘Θ’)','_(•̀ω•́ 」∠)_ ₎₎','(꒦໊ྀʚ꒦໊ི )','✋(　˙-˙　)ﾊﾊｯ','(｡˘•ε•˘｡)','(　˙灬˙　)♡',
    'ฅ•ﻌ•ฅ','ฅ’ω’ฅ','0(:3 _ )～','⁽˙³˙⁾◟( ˘•ω•˘ )◞⁽˙³˙⁾','└(‘- ‘ ┌)└( ‘-‘ )┘(┐’ -‘)┘',

    // その他( ᐛ )この顔
    '( ᐛ )','( ᐙ )','（ ᑒ ）','( ⌳̈ )','ᐠ( ᐛ )ᐟ','\\( ᐙ )/','ᐠ( ᐕ )ᐟ',
    'ᐠ( ᐛ )ᐟ','(/ ᐕ)/','(੭ ᐕ)੭','( ᐛ )?','(੭ ᐕ)？','( ᐛ )σ','(˙◁˙)','(૭ ᐕ)૭',
    '(੭ुᐛ)੭ु⁾⁾','( ᐛ ).｡oஇ','| ᐕ)⁾⁾♡ʾʾ','│ᐕ) ⁾⁾','| ᐕ)','.*.｡ଘ( ᐛ ) ଓ','(   ᐛღ )',

    // 絵文字つき
    '( ᐕ)ﾉ ⁾⁾⭐','( ᐛ👐)','(*ᐛ*)ᒃ✨','(☝ ᐛ )☝','( ᐛ🙏)','☝️( ᐛ☝️)','👏(　ᐛ 　)',

    // 二人組
    '⁽⁽*( ᐖ )*⁾⁾ ₍₍*( ᐛ )*₎₎','ᐠ( ᐛ )ᐟᐠ( ᐛ )ᐟ'
];

            shuffle($faces);
            // 2～3個をランダムで選択して文末に追加
            $count = random_int(2,3);
            $face_str = implode(' ', array_slice($faces, 0, $count));
            $content .= " ".$face_str;
        }

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
        $pdo->prepare("INSERT INTO posts(user_id,content_md,content_html,nsfw,media_path,media_type,created_at) VALUES(?,?,?,?,?,?,NOW())")
            ->execute([$_SESSION['uid'],$content,$html,$nsfw,$media_path,$media_type]);
        $post_id = $pdo->lastInsertId();

        // random coin reward
        $coins = random_int(10,70);
        $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id=?")->execute([$coins,$_SESSION['uid']]);
        $pdo->prepare("INSERT INTO reward_events(user_id,kind,amount,meta) VALUES(?,?,?,JSON_OBJECT('post_id',?))")
            ->execute([$_SESSION['uid'],'post_reward',$coins,$post_id]);

        // クエスト進行チェック
        if (file_exists(__DIR__ . '/quest_progress.php')) {
            require_once __DIR__ . '/quest_progress.php';
            check_quest_progress($_SESSION['uid'], 'post', 1);
            check_quest_progress_with_text($_SESSION['uid'], 'post_contains', $content);
        }

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