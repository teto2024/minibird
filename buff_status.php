<?php
session_start();
require_once __DIR__ . '/config.php'; // db(), user()

header('Content-Type: application/json');

$me = user();
$pdo = db();

// 現在時刻
$now = new DateTime();
$nowStr = $now->format('Y-m-d H:i:s');

$result = [
    'ok' => true,
    'global_buffs' => [],
    'user_buffs'   => [],
];

// --- グローバルバフ取得 ---
$st = $pdo->prepare("SELECT id, type, level, activated_by, start_time, end_time 
                     FROM buffs 
                     WHERE end_time > ? 
                     ORDER BY type, level DESC");
$st->execute([$nowStr]);
$buffs = $st->fetchAll();

foreach ($buffs as $b) {
    $end = new DateTime($b['end_time']);
    $remaining = max(0, $end->getTimestamp() - $now->getTimestamp());

    $result['global_buffs'][] = [
        'id'         => $b['id'],
        'type'       => $b['type'],
        'level'      => (int)$b['level'],
        'activated_by' => (int)$b['activated_by'],
        'start_time' => $b['start_time'],
        'end_time'   => $b['end_time'],
        'remaining'  => $remaining
    ];
}

// --- 個人用バフ取得（ログイン中ユーザーのみ） ---
if ($me) {
    $st = $pdo->prepare("SELECT id, type, start_time, end_time 
                         FROM user_buffs 
                         WHERE user_id = ? AND end_time > ?");
    $st->execute([$me['id'], $nowStr]);
    $userBuffs = $st->fetchAll();

    foreach ($userBuffs as $ub) {
        $end = new DateTime($ub['end_time']);
        $remaining = max(0, $end->getTimestamp() - $now->getTimestamp());

        $result['user_buffs'][] = [
            'id'         => $ub['id'],
            'type'       => $ub['type'],
            'start_time' => $ub['start_time'],
            'end_time'   => $ub['end_time'],
            'remaining'  => $remaining
        ];
    }
}

echo json_encode($result);
