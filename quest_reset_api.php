<?php
// ===============================================
// quest_reset_api.php
// クエストリセットAPI（リレークエスト用）
// ===============================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/quest_progress.php';

header('Content-Type: application/json');

$me = user();
if (!$me) {
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

// JSON入力を受け取る
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'reset_relay') {
    $success = reset_relay_quests($me['id']);
    
    if ($success) {
        echo json_encode([
            'ok' => true,
            'message' => 'リレークエストをリセットしました'
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'error' => 'リセットに失敗しました'
        ]);
    }
} else {
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid action'
    ]);
}
