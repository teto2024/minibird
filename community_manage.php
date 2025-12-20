<?php
// ===============================================
// community_manage.php
// コミュニティ管理API（作成、メンバー管理）
// ===============================================

require_once __DIR__ . '/config.php';
$pdo = db();
$user_id = $_SESSION['user_id'] ?? null;

// Check if this is a JSON API request or HTML form submission
// Check for XMLHttpRequest header or JSON content type
$is_json_request = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                   || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) 
                   || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

if (!$is_json_request) {
    // HTML form submission - no JSON header
} else {
    header('Content-Type: application/json; charset=utf-8');
}

if (!$user_id) {
    if ($is_json_request) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    } else {
        header('Location: communities.php?msg=' . urlencode('ログインが必要です'));
    }
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        // ===============================================
        // コミュニティ作成
        // ===============================================
        case 'create':
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($name)) {
                throw new Exception('Community name required');
            }
            
            if (mb_strlen($name) > 100) {
                throw new Exception('Name too long (max 100 chars)');
            }
            
            // コミュニティ作成
            $stmt = $pdo->prepare("
                INSERT INTO communities (name, description, owner_id, is_private, allow_repost, created_at)
                VALUES (?, ?, ?, TRUE, FALSE, NOW())
            ");
            $stmt->execute([$name, $description, $user_id]);
            $community_id = $pdo->lastInsertId();
            
            // オーナーをメンバーとして追加
            $stmt = $pdo->prepare("
                INSERT INTO community_members (community_id, user_id, added_by, role, created_at)
                VALUES (?, ?, ?, 'owner', NOW())
            ");
            $stmt->execute([$community_id, $user_id, $user_id]);
            
            // Redirect for HTML form, JSON response for API
            if ($is_json_request) {
                echo json_encode([
                    'ok' => true,
                    'community_id' => $community_id,
                    'message' => 'Community created'
                ]);
            } else {
                header('Location: communities.php?msg=' . urlencode('コミュニティを作成しました！'));
            }
            break;

        // ===============================================
        // コミュニティ情報取得
        // ===============================================
        case 'get_info':
            $community_id = intval($_GET['community_id'] ?? 0);
            
            if (!$community_id) {
                throw new Exception('Community ID required');
            }
            
            // コミュニティ情報取得
            $stmt = $pdo->prepare("
                SELECT c.*, u.handle as owner_handle
                FROM communities c
                JOIN users u ON u.id = c.owner_id
                WHERE c.id = ?
            ");
            $stmt->execute([$community_id]);
            $community = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$community) {
                throw new Exception('Community not found');
            }
            
            // メンバー取得
            $stmt = $pdo->prepare("
                SELECT cm.user_id, cm.role, cm.created_at, u.handle
                FROM community_members cm
                JOIN users u ON u.id = cm.user_id
                WHERE cm.community_id = ?
                ORDER BY cm.created_at ASC
            ");
            $stmt->execute([$community_id]);
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 現在のユーザーの権限チェック
            $user_role = null;
            foreach ($members as $member) {
                if ($member['user_id'] == $user_id) {
                    $user_role = $member['role'];
                    break;
                }
            }
            
            echo json_encode([
                'ok' => true,
                'community' => $community,
                'members' => $members,
                'user_role' => $user_role
            ]);
            break;

        // ===============================================
        // ユーザーが参加しているコミュニティ一覧取得
        // ===============================================
        case 'list_my_communities':
            $stmt = $pdo->prepare("
                SELECT c.*, cm.role, u.handle as owner_handle,
                       (SELECT COUNT(*) FROM community_members WHERE community_id = c.id) as member_count
                FROM communities c
                JOIN community_members cm ON cm.community_id = c.id
                JOIN users u ON u.id = c.owner_id
                WHERE cm.user_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $communities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'ok' => true,
                'communities' => $communities
            ]);
            break;

        // ===============================================
        // メンバー追加
        // ===============================================
        case 'add_member':
            $community_id = intval($_POST['community_id'] ?? 0);
            $target_handle = trim($_POST['handle'] ?? '');
            
            if (!$community_id || empty($target_handle)) {
                throw new Exception('Community ID and handle required');
            }
            
            // オーナーチェック
            $stmt = $pdo->prepare("SELECT owner_id FROM communities WHERE id=?");
            $stmt->execute([$community_id]);
            $community = $stmt->fetch();
            
            if (!$community) {
                throw new Exception('Community not found');
            }
            
            if ($community['owner_id'] != $user_id) {
                throw new Exception('Only owner can add members');
            }
            
            // ターゲットユーザーID取得
            $stmt = $pdo->prepare("SELECT id FROM users WHERE handle=?");
            $stmt->execute([$target_handle]);
            $target = $stmt->fetch();
            
            if (!$target) {
                throw new Exception('User not found');
            }
            
            $target_id = $target['id'];
            
            // 既にメンバーかチェック
            $stmt = $pdo->prepare("SELECT 1 FROM community_members WHERE community_id=? AND user_id=?");
            $stmt->execute([$community_id, $target_id]);
            if ($stmt->fetch()) {
                throw new Exception('User is already a member');
            }
            
            // メンバー追加
            $stmt = $pdo->prepare("
                INSERT INTO community_members (community_id, user_id, added_by, role, created_at)
                VALUES (?, ?, ?, 'member', NOW())
            ");
            $stmt->execute([$community_id, $target_id, $user_id]);
            
            echo json_encode([
                'ok' => true,
                'message' => 'Member added'
            ]);
            break;

        // ===============================================
        // メンバー削除
        // ===============================================
        case 'remove_member':
            $community_id = intval($_POST['community_id'] ?? 0);
            $target_id = intval($_POST['user_id'] ?? 0);
            
            if (!$community_id || !$target_id) {
                throw new Exception('Community ID and user ID required');
            }
            
            // オーナーチェック
            $stmt = $pdo->prepare("SELECT owner_id FROM communities WHERE id=?");
            $stmt->execute([$community_id]);
            $community = $stmt->fetch();
            
            if (!$community) {
                throw new Exception('Community not found');
            }
            
            if ($community['owner_id'] != $user_id) {
                throw new Exception('Only owner can remove members');
            }
            
            // オーナー自身は削除不可
            if ($target_id == $user_id) {
                throw new Exception('Cannot remove owner');
            }
            
            // メンバー削除
            $stmt = $pdo->prepare("DELETE FROM community_members WHERE community_id=? AND user_id=?");
            $stmt->execute([$community_id, $target_id]);
            
            echo json_encode([
                'ok' => true,
                'message' => 'Member removed'
            ]);
            break;

        // ===============================================
        // コミュニティ更新
        // ===============================================
        case 'update':
            $community_id = intval($_POST['community_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (!$community_id || empty($name)) {
                throw new Exception('Community ID and name required');
            }
            
            // オーナーチェック
            $stmt = $pdo->prepare("SELECT owner_id FROM communities WHERE id=?");
            $stmt->execute([$community_id]);
            $community = $stmt->fetch();
            
            if (!$community) {
                throw new Exception('Community not found');
            }
            
            if ($community['owner_id'] != $user_id) {
                throw new Exception('Only owner can update community');
            }
            
            // コミュニティ更新
            $stmt = $pdo->prepare("
                UPDATE communities 
                SET name=?, description=?, updated_at=NOW()
                WHERE id=?
            ");
            $stmt->execute([$name, $description, $community_id]);
            
            echo json_encode([
                'ok' => true,
                'message' => 'Community updated'
            ]);
            break;

        // ===============================================
        // コミュニティ削除
        // ===============================================
        case 'delete':
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
            
            // コミュニティ削除（カスケードでメンバー、投稿も削除）
            $stmt = $pdo->prepare("DELETE FROM communities WHERE id=?");
            $stmt->execute([$community_id]);
            
            echo json_encode([
                'ok' => true,
                'message' => 'Community deleted'
            ]);
            break;

        // ===============================================
        // 公開設定変更
        // ===============================================
        case 'toggle_public':
            $community_id = intval($_POST['community_id'] ?? 0);
            
            if (!$community_id) {
                throw new Exception('Community ID required');
            }
            
            // オーナーチェック
            $stmt = $pdo->prepare("SELECT owner_id, is_public FROM communities WHERE id=?");
            $stmt->execute([$community_id]);
            $community = $stmt->fetch();
            
            if (!$community) {
                throw new Exception('Community not found');
            }
            
            if ($community['owner_id'] != $user_id) {
                throw new Exception('Only owner can change public setting');
            }
            
            // 公開設定を反転
            $new_is_public = $community['is_public'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE communities SET is_public=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$new_is_public, $community_id]);
            
            echo json_encode([
                'ok' => true,
                'is_public' => $new_is_public,
                'message' => $new_is_public ? 'Community is now public' : 'Community is now private'
            ]);
            break;

        // ===============================================
        // コミュニティに参加
        // ===============================================
        case 'join_community':
            $community_id = intval($_POST['community_id'] ?? 0);
            
            if (!$community_id) {
                throw new Exception('Community ID required');
            }
            
            // コミュニティの公開設定チェック
            $stmt = $pdo->prepare("SELECT is_public FROM communities WHERE id=?");
            $stmt->execute([$community_id]);
            $community = $stmt->fetch();
            
            if (!$community) {
                throw new Exception('Community not found');
            }
            
            if (!$community['is_public']) {
                throw new Exception('This community is private');
            }
            
            // 既にメンバーかチェック
            $stmt = $pdo->prepare("SELECT 1 FROM community_members WHERE community_id=? AND user_id=?");
            $stmt->execute([$community_id, $user_id]);
            if ($stmt->fetch()) {
                throw new Exception('You are already a member');
            }
            
            // メンバー追加
            $stmt = $pdo->prepare("
                INSERT INTO community_members (community_id, user_id, added_by, role, created_at)
                VALUES (?, ?, ?, 'member', NOW())
            ");
            $stmt->execute([$community_id, $user_id, $user_id]);
            
            echo json_encode([
                'ok' => true,
                'message' => 'Joined community'
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    if ($is_json_request) {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'error' => $e->getMessage()
        ]);
    } else {
        header('Location: communities.php?msg=' . urlencode('エラー: ' . $e->getMessage()));
    }
}
