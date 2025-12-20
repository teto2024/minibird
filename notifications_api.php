<?php
// notifications_api.php - MariaDB対応 安定版（display_nameが空ならhandleにフォールバック）
ob_start();
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();

try {
    require 'config.php';
    require_login(); // ログインチェック

    header('Content-Type: application/json; charset=utf-8');

    $pdo = db();
    $user_id = $_SESSION['uid'];

    // パラメータ
    $since_id = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;
    $limit = min(isset($_GET['limit']) ? (int)$_GET['limit'] : 20, 50);

    // 通知取得SQL（handleも取得、from_user_idも対応）
    $sql = "
        SELECT n.id, n.type, n.post_id, n.created_at, n.is_read,
               COALESCE(n.from_user_id, n.actor_id) AS actor_id_resolved,
               a.display_name AS actor_name, a.handle AS actor_handle, a.icon AS actor_icon,
               p.id AS post_id, p.content_md AS post_text,
               cp.id AS community_post_id, cp.content AS community_post_text
        FROM notifications n
        LEFT JOIN users a ON COALESCE(n.from_user_id, n.actor_id) = a.id
        LEFT JOIN posts p ON n.post_id = p.id
        LEFT JOIN community_posts cp ON n.post_id = cp.id
        WHERE n.user_id = ?
    ";
    $params = [$user_id];

    if ($since_id > 0) {
        $sql .= " AND n.id > ? ";
        $params[] = $since_id;
    }

    // LIMIT は直接埋め込み（MariaDB対応）
    $sql .= " ORDER BY n.id DESC LIMIT " . (int)$limit;

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $result = [];

    foreach ($rows as $r) {
        // 表示名が空なら handle にフォールバック
        $displayName = $r['actor_name'] ?: ($r['actor_handle'] ?: '匿名');

        // 通知メッセージ
        switch ($r['type']) {
            case 'like':   $message = "{$displayName} さんがあなたの投稿にいいねしました"; break;
            case 'reply':  $message = "{$displayName} さんがあなたの投稿にリプライしました"; break;
            case 'repost': $message = "{$displayName} さんがあなたの投稿をリポストしました"; break;
            case 'quote':  $message = "{$displayName} さんがあなたの投稿を引用リポストしました"; break;
            case 'follow': $message = "{$displayName} さんがあなたをフォローしました"; break;
            case 'community_like': $message = "{$displayName} さんがあなたのコミュニティ投稿にいいねしました"; break;
            case 'community_reply': $message = "{$displayName} さんがあなたのコミュニティ投稿にリプライしました"; break;
            default:       $message = "";
        }
        
        // 投稿テキストの決定（通常投稿 or コミュニティ投稿）
        $post_text = $r['post_text'] ?: ($r['community_post_text'] ?: null);
        $actual_post_id = $r['post_id'] ?: ($r['community_post_id'] ?: null);

        $result[] = [
            "id" => (int)$r['id'],
            "type" => $r['type'],
            "actor" => [
                "id" => (int)$r['actor_id_resolved'],
                "display_name" => $r['actor_name'] ?: $r['actor_handle'],
                "icon" => $r['actor_icon'] ?: '/default_icon.png'
            ],
            "post" => $actual_post_id ? [
                "id" => (int)$actual_post_id,
                "text" => $post_text
            ] : null,
            "created_at" => $r['created_at'],
            "is_read" => (int)$r['is_read'],
            "message" => $message,
            "highlight" => $r['is_read'] == 0
        ];
    }

    ob_end_clean(); // 余計な出力を削除
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'exception',
        'message' => $e->getMessage()
    ]);
    exit;
}
