<?php
// ===============================================
// community_api.php
// コミュニティフィードAPI
// ===============================================

require_once __DIR__ . '/config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pdo = db();
$user_id = $_SESSION['uid'] ?? null;

header('Content-Type: application/json; charset=utf-8');

if (!$user_id) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        // ===============================================
        // コミュニティ投稿を取得
        // ===============================================
        case 'get_posts':
            $community_id = intval($_GET['community_id'] ?? 0);
            
            if (!$community_id) {
                throw new Exception('Community ID required');
            }
            
            // メンバーチェック
            $stmt = $pdo->prepare("SELECT 1 FROM community_members WHERE community_id=? AND user_id=?");
            $stmt->execute([$community_id, $user_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Not a member of this community');
            }
            
            // 投稿取得（親投稿のみ、返信は除外）
            $limit  = max(1, min(100, intval($_GET['limit'] ?? 50)));
            $offset = max(0, intval($_GET['offset'] ?? 0));

            $sql = "
                SELECT 
                    cp.*,
                    u.handle, 
                    u.display_name,
                    u.icon,
                    u.vip_level,
                    u.active_frame_id,
                    f.css_token AS frame_class,
                    tp.title_text, 
                    tp.title_css,
                    (SELECT COUNT(*) FROM community_post_likes WHERE post_id = cp.id) as like_count,
                    (SELECT COUNT(*) FROM community_posts WHERE parent_id = cp.id) as reply_count,
                    (SELECT 1 FROM community_post_likes WHERE post_id = cp.id AND user_id = ?) as user_liked
                FROM community_posts cp
                JOIN users u ON u.id = cp.user_id
                LEFT JOIN frames f ON f.id = u.active_frame_id
                LEFT JOIN user_titles ut ON ut.user_id = u.id AND ut.is_equipped = TRUE
                LEFT JOIN title_packages tp ON tp.id = ut.title_id
                WHERE cp.community_id = ? AND cp.parent_id IS NULL
                ORDER BY cp.created_at DESC
                LIMIT $limit OFFSET $offset
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $community_id]);

            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['ok' => true, 'posts' => $posts]);
            break;

        // ===============================================
        // 投稿作成
        // ===============================================
        case 'create_post':
            $community_id = intval($_POST['community_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
            $is_nsfw = !empty($_POST['is_nsfw']) ? 1 : 0;
            
            if (!$community_id || empty($content)) {
                throw new Exception('Community ID and content required');
            }
            
            if (mb_strlen($content) > 1024) {
                throw new Exception('Content too long (max 1024 chars)');
            }
            
            // メンバーチェック
            $stmt = $pdo->prepare("SELECT 1 FROM community_members WHERE community_id=? AND user_id=?");
            $stmt->execute([$community_id, $user_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Not a member of this community');
            }
            
            // 画像・動画アップロード処理
            $media_path = null;
            if (!empty($_FILES['media']['name'])) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'];
                $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($ext, $allowed)) {
                    throw new Exception('Invalid file type');
                }
                
                if ($_FILES['media']['size'] > 10 * 1024 * 1024) { // 10MB
                    throw new Exception('File too large (max 10MB)');
                }
                
                $filename = uniqid('community_', true) . '.' . $ext;
                $upload_path = __DIR__ . '/uploads/' . $filename;
                
                if (!move_uploaded_file($_FILES['media']['tmp_name'], $upload_path)) {
                    throw new Exception('Upload failed');
                }
                
                $media_path = 'uploads/' . $filename;
            }
            
            // 投稿作成
            $stmt = $pdo->prepare("
                INSERT INTO community_posts (community_id, user_id, content, media_path, is_nsfw, parent_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$community_id, $user_id, $content, $media_path, $is_nsfw, $parent_id]);
            $post_id = $pdo->lastInsertId();
            
            // リプライの場合、元投稿の投稿者に通知を送る（自分へのリプライは除外）
            if ($parent_id) {
                $stmt = $pdo->prepare("SELECT user_id FROM community_posts WHERE id=?");
                $stmt->execute([$parent_id]);
                $parent_user_id = $stmt->fetchColumn();
                
                if ($parent_user_id && $parent_user_id != $user_id) {
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, type, from_user_id, post_id, created_at)
                        VALUES (?, 'community_reply', ?, ?, NOW())
                    ");
                    $stmt->execute([$parent_user_id, $user_id, $post_id]);
                }
            }
            
            // メンション通知の処理
            if (preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches)) {
                $mentioned_handles = array_unique($matches[1]);
                foreach ($mentioned_handles as $handle) {
                    // メンションされたユーザーのIDを取得
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE handle = ?");
                    $stmt->execute([$handle]);
                    $mentioned_user_id = $stmt->fetchColumn();
                    
                    // 自分自身へのメンション、存在しないユーザーは除外
                    if ($mentioned_user_id && $mentioned_user_id != $user_id) {
                        // コミュニティメンバーかどうか確認
                        $stmt = $pdo->prepare("SELECT 1 FROM community_members WHERE community_id=? AND user_id=?");
                        $stmt->execute([$community_id, $mentioned_user_id]);
                        if ($stmt->fetch()) {
                            // 通知を送る
                            $stmt = $pdo->prepare("
                                INSERT INTO notifications (user_id, type, from_user_id, post_id, created_at)
                                VALUES (?, 'community_mention', ?, ?, NOW())
                            ");
                            $stmt->execute([$mentioned_user_id, $user_id, $post_id]);
                        }
                    }
                }
            }
            
            // クエスト進行チェック（投稿アクション）
            include_once __DIR__ . '/quest_progress.php';
            check_quest_progress($user_id, 'post', 1);
            check_quest_progress_with_text($user_id, 'post_contains', $content);
            
            echo json_encode([
                'ok' => true,
                'post_id' => $post_id,
                'message' => 'Post created'
            ]);
            break;

        // ===============================================
        // いいね切り替え
        // ===============================================
        case 'toggle_like':
            $post_id = intval($_POST['post_id'] ?? 0);
            
            if (!$post_id) {
                throw new Exception('Post ID required');
            }
            
            // 投稿存在チェック＆コミュニティメンバーチェック
            $stmt = $pdo->prepare("
                SELECT cp.community_id 
                FROM community_posts cp
                JOIN community_members cm ON cm.community_id = cp.community_id
                WHERE cp.id = ? AND cm.user_id = ?
            ");
            $stmt->execute([$post_id, $user_id]);
            $post = $stmt->fetch();
            
            if (!$post) {
                throw new Exception('Post not found or not accessible');
            }
            
            // いいね状態チェック
            $stmt = $pdo->prepare("SELECT id FROM community_post_likes WHERE post_id=? AND user_id=?");
            $stmt->execute([$post_id, $user_id]);
            $liked = $stmt->fetch();
            
            if ($liked) {
                // いいね削除
                $stmt = $pdo->prepare("DELETE FROM community_post_likes WHERE post_id=? AND user_id=?");
                $stmt->execute([$post_id, $user_id]);
                $action_taken = 'unliked';
            } else {
                // いいね追加
                $stmt = $pdo->prepare("INSERT INTO community_post_likes (post_id, user_id, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$post_id, $user_id]);
                $action_taken = 'liked';
                
                // 投稿者に通知を送る（自分へのいいねは除外）
                $stmt = $pdo->prepare("SELECT user_id, community_id FROM community_posts WHERE id=?");
                $stmt->execute([$post_id]);
                $post_info = $stmt->fetch();
                
                if ($post_info && $post_info['user_id'] != $user_id) {
                    // 通知テーブルに追加
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, type, from_user_id, post_id, created_at)
                        VALUES (?, 'community_like', ?, ?, NOW())
                    ");
                    $stmt->execute([$post_info['user_id'], $user_id, $post_id]);
                }
                
                // クエスト進行チェック（いいねアクション）
                include_once __DIR__ . '/quest_progress.php';
                check_quest_progress($user_id, 'like', 1);
            }
            
            // いいね数取得
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM community_post_likes WHERE post_id=?");
            $stmt->execute([$post_id]);
            $like_count = $stmt->fetchColumn();
            
            echo json_encode([
                'ok' => true,
                'action' => $action_taken,
                'like_count' => $like_count
            ]);
            break;

        // ===============================================
        // 返信取得
        // ===============================================
        case 'get_replies':
            $post_id = intval($_GET['post_id'] ?? 0);
            
            if (!$post_id) {
                throw new Exception('Post ID required');
            }
            
            // 投稿存在チェック＆コミュニティメンバーチェック
            $stmt = $pdo->prepare("
                SELECT cp.community_id 
                FROM community_posts cp
                JOIN community_members cm ON cm.community_id = cp.community_id
                WHERE cp.id = ? AND cm.user_id = ?
            ");
            $stmt->execute([$post_id, $user_id]);
            $post = $stmt->fetch();
            
            if (!$post) {
                throw new Exception('Post not found or not accessible');
            }
            
            // 返信取得
            $stmt = $pdo->prepare("
                SELECT 
                    cp.*,
                    u.handle,
                    u.display_name,
                    u.icon,
                    u.vip_level,
                    u.active_frame_id,
                    f.css_token AS frame_class,
                    tp.title_text,
                    tp.title_css,
                    (SELECT COUNT(*) FROM community_post_likes WHERE post_id = cp.id) as like_count,
                    (SELECT COUNT(*) FROM community_posts WHERE parent_id = cp.id) as reply_count,
                    (SELECT 1 FROM community_post_likes WHERE post_id = cp.id AND user_id = ?) as user_liked
                FROM community_posts cp
                JOIN users u ON u.id = cp.user_id
                LEFT JOIN frames f ON f.id = u.active_frame_id
                LEFT JOIN user_titles ut ON ut.user_id = u.id AND ut.is_equipped = TRUE
                LEFT JOIN title_packages tp ON tp.id = ut.title_id
                WHERE cp.parent_id = ?
                ORDER BY cp.created_at ASC
            ");
            $stmt->execute([$user_id, $post_id]);
            $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['ok' => true, 'replies' => $replies]);
            break;

        // ===============================================
        // 投稿削除（ソフトデリート）
        // ===============================================
        case 'delete_post':
            $post_id = intval($_POST['post_id'] ?? 0);
            
            if (!$post_id) {
                throw new Exception('Post ID required');
            }
            
            // 投稿の所有者チェック
            $stmt = $pdo->prepare("SELECT user_id, is_deleted FROM community_posts WHERE id=?");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch();
            
            if (!$post) {
                throw new Exception('Post not found');
            }
            
            if ($post['user_id'] != $user_id) {
                throw new Exception('Not authorized to delete this post');
            }
            
            if ($post['is_deleted']) {
                throw new Exception('Post already deleted');
            }
            
            // 投稿を削除済みにマーク（ソフトデリート）
            $stmt = $pdo->prepare("UPDATE community_posts SET is_deleted = TRUE, deleted_at = NOW() WHERE id=?");
            $stmt->execute([$post_id]);
            
            echo json_encode(['ok' => true, 'message' => 'Post deleted']);
            break;

        // ===============================================
        // コミュニティ情報更新（オーナーのみ）
        // ===============================================
        case 'update_community':
            $community_id = intval($_POST['community_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (!$community_id || empty($name)) {
                throw new Exception('Community ID and name required');
            }
            
            if (mb_strlen($name) > 100) {
                throw new Exception('Name too long (max 100 chars)');
            }
            
            // オーナーチェック
            $stmt = $pdo->prepare("SELECT owner_id FROM communities WHERE id=?");
            $stmt->execute([$community_id]);
            $community = $stmt->fetch();
            
            if (!$community) {
                throw new Exception('Community not found');
            }
            
            if ($community['owner_id'] != $user_id) {
                throw new Exception('Only owner can edit community info');
            }
            
            // コミュニティ情報更新
            $stmt = $pdo->prepare("
                UPDATE communities 
                SET name = ?, description = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $community_id]);
            
            echo json_encode(['ok' => true, 'message' => 'Community updated']);
            break;

        // ===============================================
        // コミュニティ削除（オーナーのみ）
        // ===============================================
        case 'delete_community':
            $community_id = intval($_POST['community_id'] ?? 0);
            
            if (!$community_id) {
                throw new Exception('Community ID required');
            }
            
            // オーナーチェック
            $stmt = $pdo->prepare("SELECT owner_id FROM communities WHERE id=?");
            $stmt->execute([$community_id]);
            $community = $stmt->fetch();
            
            if (!$community) {
                throw new Exception('Community not found');
            }
            
            if ($community['owner_id'] != $user_id) {
                throw new Exception('Only owner can delete community');
            }
            
            // コミュニティに属する投稿のメディアファイルを削除
            $stmt = $pdo->prepare("SELECT media_path FROM community_posts WHERE community_id=? AND media_path IS NOT NULL");
            $stmt->execute([$community_id]);
            $media_files = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($media_files as $media_path) {
                if ($media_path && file_exists(__DIR__ . '/' . $media_path)) {
                    unlink(__DIR__ . '/' . $media_path);
                }
            }
            
            // コミュニティ削除（カスケードで投稿、メンバー、いいねも削除される）
            $stmt = $pdo->prepare("DELETE FROM communities WHERE id=?");
            $stmt->execute([$community_id]);
            
            echo json_encode(['ok' => true, 'message' => 'Community deleted']);
            break;

        // ===============================================
        // コミュニティ脱退
        // ===============================================
        case 'leave_community':
            $community_id = intval($_POST['community_id'] ?? 0);
            
            if (!$community_id) {
                throw new Exception('Community ID required');
            }
            
            // オーナーチェック（オーナーは脱退できない）
            $stmt = $pdo->prepare("SELECT owner_id FROM communities WHERE id=?");
            $stmt->execute([$community_id]);
            $community = $stmt->fetch();
            
            if (!$community) {
                throw new Exception('Community not found');
            }
            
            if ($community['owner_id'] == $user_id) {
                throw new Exception('Owner cannot leave community. Please delete it instead.');
            }
            
            // メンバーシップ削除
            $stmt = $pdo->prepare("DELETE FROM community_members WHERE community_id=? AND user_id=?");
            $stmt->execute([$community_id, $user_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('You are not a member of this community');
            }
            
            echo json_encode(['ok' => true, 'message' => 'Left community']);
            break;

        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}