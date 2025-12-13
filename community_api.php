<?php
// ===============================================
// community_api.php
// コミュニティフィードAPI
// ===============================================

require_once __DIR__ . '/config.php';
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
            $offset = intval($_GET['offset'] ?? 0);
            $limit = intval($_GET['limit'] ?? 50);
            
            $stmt = $pdo->prepare("
                SELECT 
                    cp.*,
                    u.handle, u.active_frame_id,
                    (SELECT COUNT(*) FROM community_post_likes WHERE post_id = cp.id) as like_count,
                    (SELECT COUNT(*) FROM community_posts WHERE parent_id = cp.id) as reply_count,
                    (SELECT 1 FROM community_post_likes WHERE post_id = cp.id AND user_id = ?) as user_liked
                FROM community_posts cp
                JOIN users u ON u.id = cp.user_id
                WHERE cp.community_id = ? AND cp.parent_id IS NULL
                ORDER BY cp.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$user_id, $community_id, $limit, $offset]);
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
                    u.handle, u.active_frame_id,
                    (SELECT COUNT(*) FROM community_post_likes WHERE post_id = cp.id) as like_count,
                    (SELECT COUNT(*) FROM community_posts WHERE parent_id = cp.id) as reply_count,
                    (SELECT 1 FROM community_post_likes WHERE post_id = cp.id AND user_id = ?) as user_liked
                FROM community_posts cp
                JOIN users u ON u.id = cp.user_id
                WHERE cp.parent_id = ?
                ORDER BY cp.created_at ASC
            ");
            $stmt->execute([$user_id, $post_id]);
            $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['ok' => true, 'replies' => $replies]);
            break;

        // ===============================================
        // 投稿削除
        // ===============================================
        case 'delete_post':
            $post_id = intval($_POST['post_id'] ?? 0);
            
            if (!$post_id) {
                throw new Exception('Post ID required');
            }
            
            // 投稿の所有者チェック
            $stmt = $pdo->prepare("SELECT user_id, media_path FROM community_posts WHERE id=?");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch();
            
            if (!$post) {
                throw new Exception('Post not found');
            }
            
            if ($post['user_id'] != $user_id) {
                throw new Exception('Not authorized to delete this post');
            }
            
            // メディアファイル削除
            if ($post['media_path'] && file_exists(__DIR__ . '/' . $post['media_path'])) {
                unlink(__DIR__ . '/' . $post['media_path']);
            }
            
            // 投稿削除（カスケードで返信、いいねも削除される）
            $stmt = $pdo->prepare("DELETE FROM community_posts WHERE id=?");
            $stmt->execute([$post_id]);
            
            echo json_encode(['ok' => true, 'message' => 'Post deleted']);
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
