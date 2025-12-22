<?php
require_once __DIR__ . '/config.php';
require_login();

$me = user();
$pdo = db();

// 管理者チェック
if ($me['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'admin_required']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $category = $_POST['category'] ?? 'feature';
            $version = trim($_POST['version'] ?? '');
            $is_published = ($_POST['is_published'] ?? '0') === '1';
            
            if (!$title || !$content) {
                throw new Exception('タイトルと内容は必須です');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO updates (title, content, category, version, is_published, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $content, $category, $version ?: null, $is_published, $me['id']]);
            
            echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
            break;
            
        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $category = $_POST['category'] ?? 'feature';
            $version = trim($_POST['version'] ?? '');
            $is_published = ($_POST['is_published'] ?? '0') === '1';
            
            if (!$id || !$title || !$content) {
                throw new Exception('必須項目が不足しています');
            }
            
            $stmt = $pdo->prepare("
                UPDATE updates 
                SET title = ?, content = ?, category = ?, version = ?, is_published = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$title, $content, $category, $version ?: null, $is_published, $id]);
            
            echo json_encode(['ok' => true]);
            break;
            
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('IDが指定されていません');
            }
            
            $stmt = $pdo->prepare("DELETE FROM updates WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['ok' => true]);
            break;
            
        default:
            throw new Exception('無効なアクションです');
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
