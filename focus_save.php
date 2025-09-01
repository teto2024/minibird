<?php
require_once __DIR__ . '/config.php';
require_login();
$pdo = db();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// JSON データ取得
$input = json_decode(file_get_contents('php://input'), true);

// 入力値取得
$task       = trim($input['task'] ?? '');
$started_at = $input['started_at'] ?? '';
$ended_at   = $input['ended_at'] ?? '';
$mins       = (int)($input['mins'] ?? 0);
$coins      = (int)($input['coins'] ?? 0);
$crystals   = (int)($input['crystals'] ?? 0);
$status     = $input['status'] ?? 'success'; // success / fail

// 必須フィールド確認
if ($task && $started_at && $ended_at) {

    // タスク履歴保存
    $st = $pdo->prepare("
        INSERT INTO focus_tasks 
        (user_id, task, started_at, ended_at, minutes, coins, crystals, status) 
        VALUES (?,?,?,?,?,?,?,?)
    ");
    $st->execute([
        $_SESSION['uid'], $task, $started_at, $ended_at,
        $mins, $coins, $crystals, $status
    ]);

    if ($status === 'success') {
        // 成功時のみコイン・クリスタル加算
        $st = $pdo->prepare("UPDATE users SET coins=coins+?, crystals=crystals+? WHERE id=?");
        $st->execute([$coins, $crystals, $_SESSION['uid']]);

        // 報酬イベント登録
        $st = $pdo->prepare("INSERT INTO reward_events(user_id,kind,amount,meta) VALUES(?,?,?,?)");
        $st->execute([
            $_SESSION['uid'],
            'focus_reward',
            $coins + $crystals,
            json_encode(['task'=>$task])
        ]);
    }

    echo json_encode(['ok'=>true]);

} else {
    echo json_encode(['ok'=>false,'error'=>'missing fields']);
}
