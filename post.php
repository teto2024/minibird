<?php
// ----- デバッグ用設定 -----
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

// 定数定義
define('ALLOWED_MEDIA_EXTENSIONS', ['png','jpg','jpeg','gif','webp','mp4','webm']);
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

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
    
    // ファイルアップロード検証関数
    function validate_and_upload_file($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }
        
        // ファイルサイズチェック
        if ($file['size'] > MAX_FILE_SIZE) {
            return null;
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_MEDIA_EXTENSIONS)) {
            return null;
        }
        
        // アップロードディレクトリの確認
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // より安全なファイル名生成
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . $name;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $targetPath;
        }
        
        return null;
    }

    if ($action === 'create_post') {
        require_login();
        $u = user();
        if ($u['muted_until'] && strtotime($u['muted_until']) > time()) {
            $remaining_seconds = strtotime($u['muted_until']) - time();
            $remaining_hours = floor($remaining_seconds / 3600);
            $remaining_minutes = floor(($remaining_seconds % 3600) / 60);
            $remaining_time_str = '';
            if ($remaining_hours > 0) {
                $remaining_time_str = "{$remaining_hours}時間{$remaining_minutes}分";
            } else {
                $remaining_time_str = "{$remaining_minutes}分";
            }
            echo json_encode([
                'ok' => false,
                'error' => 'muted',
                'muted_until' => $u['muted_until'],
                'remaining_time' => $remaining_time_str
            ]);
            exit;
        }

        $content = trim($_POST['content'] ?? '');
        $nsfw = ($_POST['nsfw'] ?? '0') === '1' ? 1 : 0;

        // 複数画像の確認（media[]またはmedia_0, media_1...）
        $hasMedia = false;
        if (!empty($_FILES['media']['name']) && is_string($_FILES['media']['name'])) {
            $hasMedia = true;
        }
        // media_0からmedia_3までチェック
        for ($i = 0; $i < 4; $i++) {
            if (!empty($_FILES["media_$i"]['name'])) {
                $hasMedia = true;
                break;
            }
        }

        if ($content === '' && !$hasMedia) {
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

        // 複数画像アップロード対応（最大4枚）
        $media_path = null; 
        $media_type = null;
        $media_paths = [];

        // 単一画像の場合（後方互換性）
        if (!empty($_FILES['media']['name']) && is_string($_FILES['media']['name'])) {
            $size = $_FILES['media']['size'] ?? 0;
            if ($size > $GLOBALS['MAX_UPLOAD_BYTES']) { echo json_encode(['ok'=>false,'error'=>'file_too_large']); exit; }
            $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
            
            // 動画フォーマット
            $video_exts = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v', 'flv', 'wmv', 'ogv'];
            // 画像フォーマット
            $image_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico', 'avif', 'heic', 'heif'];
            // 音声フォーマット
            $audio_exts = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma', 'opus'];
            // ドキュメント・その他のファイルフォーマット
            $document_exts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', '7z', 'tar', 'gz'];
            
            if (in_array($ext, $video_exts)) {
                $media_type = 'video';
            } elseif (in_array($ext, $audio_exts)) {
                $media_type = 'audio';
            } elseif (in_array($ext, $image_exts)) {
                $media_type = 'image';
            } elseif (in_array($ext, $document_exts)) {
                $media_type = 'document';
            } else {
                echo json_encode(['ok'=>false,'error'=>'unsupported_file_type']); exit;
            }
            
            $safe = bin2hex(random_bytes(12)).'.'.$ext;
            $dir = __DIR__ . '/uploads';
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $dest = $dir.'/'.$safe;
            if (!move_uploaded_file($_FILES['media']['tmp_name'], $dest)) { echo json_encode(['ok'=>false,'error'=>'upload_failed']); exit; }
            $media_path = 'uploads/'.$safe;
            $media_paths[] = $media_path;
        }

        // 複数画像の場合（media_0, media_1, media_2, media_3）
        for ($i = 0; $i < 4; $i++) {
            $key = 'media_' . $i;
            if (!empty($_FILES[$key]['name'])) {
                $size = $_FILES[$key]['size'] ?? 0;
                if ($size > $GLOBALS['MAX_UPLOAD_BYTES']) { 
                    echo json_encode(['ok'=>false,'error'=>'file_too_large', 'file'=>$i]); exit; 
                }
                $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
                
                // 動画フォーマット
                $video_exts = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v', 'flv', 'wmv', 'ogv'];
                // 画像フォーマット
                $image_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico', 'avif', 'heic', 'heif'];
                // 音声フォーマット
                $audio_exts = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma', 'opus'];
                // ドキュメント・その他のファイルフォーマット
                $document_exts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', '7z', 'tar', 'gz'];
                
                if (in_array($ext, $video_exts)) {
                    $type = 'video';
                } elseif (in_array($ext, $audio_exts)) {
                    $type = 'audio';
                } elseif (in_array($ext, $image_exts)) {
                    $type = 'image';
                } elseif (in_array($ext, $document_exts)) {
                    $type = 'document';
                } else {
                    echo json_encode(['ok'=>false,'error'=>'unsupported_file_type', 'file'=>$i]); exit;
                }
                
                // 最初のメディアタイプを記録
                if ($media_type === null) {
                    $media_type = $type;
                }
                
                $safe = bin2hex(random_bytes(12)).'.'.$ext;
                $dir = __DIR__ . '/uploads';
                if (!is_dir($dir)) mkdir($dir, 0775, true);
                $dest = $dir.'/'.$safe;
                if (!move_uploaded_file($_FILES[$key]['tmp_name'], $dest)) { 
                    echo json_encode(['ok'=>false,'error'=>'upload_failed', 'file'=>$i]); exit; 
                }
                $path = 'uploads/'.$safe;
                $media_paths[] = $path;
                
                // 最初の画像を media_path にも設定（後方互換性）
                if ($media_path === null) {
                    $media_path = $path;
                }
            }
        }

        $media_paths_json = !empty($media_paths) ? json_encode($media_paths) : null;

        $html = markdown_to_html($content);
        $pdo->prepare("INSERT INTO posts(user_id,content_md,content_html,nsfw,media_path,media_type,media_paths,created_at) VALUES(?,?,?,?,?,?,?,NOW())")
            ->execute([$_SESSION['uid'],$content,$html,$nsfw,$media_path,$media_type,$media_paths_json]);
        $post_id = $pdo->lastInsertId();

        // random coin reward
        $coins = random_int(70,130);
        $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id=?")->execute([$coins,$_SESSION['uid']]);
        $pdo->prepare("INSERT INTO reward_events(user_id,kind,amount,meta) VALUES(?,?,?,JSON_OBJECT('post_id',?))")
            ->execute([$_SESSION['uid'],'post_reward',$coins,$post_id]);

        // クエスト進行チェック
        if (file_exists(__DIR__ . '/quest_progress.php')) {
            require_once __DIR__ . '/quest_progress.php';
            check_quest_progress($_SESSION['uid'], 'post', 1);
            check_quest_progress_with_text($_SESSION['uid'], 'post_contains', $content);
        }

        // メンション通知の処理
        create_mention_notifications($content, $_SESSION['uid'], $post_id, $pdo);

        echo json_encode(['ok'=>true,'id'=>$post_id]); exit;
    }

    if ($action === 'quote_post') {
        require_login();
        $post_id = (int)($_POST['post_id'] ?? $input['post_id'] ?? 0);
        $content = trim($_POST['content'] ?? $input['content'] ?? '');
        $nsfw = (int)($_POST['nsfw'] ?? $input['nsfw'] ?? 0);
        
        // 複数画像の処理
        $mediaPaths = [];
        if (isset($_FILES['media']) && !empty($_FILES['media']['name']) && !is_array($_FILES['media']['name'])) {
            // 単一画像
            $uploadedPath = validate_and_upload_file($_FILES['media']);
            if ($uploadedPath) {
                $mediaPaths[] = $uploadedPath;
            }
        } else {
            // 複数画像 (media_0, media_1, media_2, media_3)
            for ($i = 0; $i < 4; $i++) {
                if (!empty($_FILES["media_$i"]['name'])) {
                    $uploadedPath = validate_and_upload_file($_FILES["media_$i"]);
                    if ($uploadedPath) {
                        $mediaPaths[] = $uploadedPath;
                    }
                }
            }
        }
        
        // コンテンツまたはメディアが必要
        if ($post_id<=0 || ($content==='' && count($mediaPaths)===0)){ 
            echo json_encode(['ok'=>false,'error'=>'bad_input']); 
            exit; 
        }
        
        $pdo = db();
        $st = $pdo->prepare("SELECT id, content_md FROM posts WHERE id=?");
        $st->execute([$post_id]);
        $ref = $st->fetch();
        if (!$ref) { echo json_encode(['ok'=>false,'error'=>'not_found']); exit; }
        
        // 引用時は埋め込み表示のみで、引用テキストは不要
        $html = markdown_to_html($content);
        
        // 画像がある場合はJSON形式で保存
        if (count($mediaPaths) > 0) {
            $mediaJson = json_encode($mediaPaths);
            $pdo->prepare("INSERT INTO posts(user_id,content_md,content_html,quote_post_id,nsfw,media_paths,created_at) VALUES(?,?,?,?,?,?,NOW())")
                ->execute([$_SESSION['uid'],$content,$html,$post_id,$nsfw,$mediaJson]);
        } else {
            $pdo->prepare("INSERT INTO posts(user_id,content_md,content_html,quote_post_id,nsfw,created_at) VALUES(?,?,?,?,?,NOW())")
                ->execute([$_SESSION['uid'],$content,$html,$post_id,$nsfw]);
        }
        
        echo json_encode(['ok'=>true]);
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'unknown_action']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>'php_exception', 'message'=>$e->getMessage()]);
    exit;
}