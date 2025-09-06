<?php
require_once __DIR__ . '/config.php';
require_login();
$pdo = db();

// JSONデータ取得
$input_raw = file_get_contents('php://input');
error_log("RAW INPUT: " . $input_raw);
$input = json_decode($input_raw, true);

if ($input === null) {
    error_log("JSON decode error: " . json_last_error_msg());
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'invalid json']);
    exit;
}

// 入力値取得
$task       = trim($input['task'] ?? '');
$started_at = $input['started_at'] ?? '';
$ended_at   = $input['ended_at'] ?? '';
$mins       = (int)($input['mins'] ?? 0);
$coins      = (int)($input['coins'] ?? 0);
$crystals   = (int)($input['crystals'] ?? 0);
$status     = $input['status'] ?? 'success';
$tag_handle = trim($input['tag_handle'] ?? '');
$uid        = $_SESSION['uid'] ?? 0;

// JSON専用出力関数
function json_exit($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// 必須フィールド確認
if (!$task || !$started_at || !$ended_at || !$uid) {
    json_exit(['ok'=>false,'error'=>'missing fields']);
}

try {
    // --------------------------
    // タッグボーナス判定
    // --------------------------
    $tagged_user_id = null;
    $bonus_multiplier = 1;
    $tag_bonus_active = false;

    if ($status === 'success' && $tag_handle) {
        $st = $pdo->prepare("SELECT id FROM users WHERE handle=? LIMIT 1");
        $st->execute([$tag_handle]);
        $tagged_user_id_candidate = $st->fetchColumn();

        if ($tagged_user_id_candidate) {
            $st = $pdo->prepare("
                SELECT started_at FROM focus_tasks 
                WHERE user_id=? AND status='success'
                ORDER BY started_at DESC LIMIT 1
            ");
            $st->execute([$tagged_user_id_candidate]);
            $tagged_started = $st->fetchColumn();

            if ($tagged_started) {
                $diff = abs(strtotime($started_at) - strtotime($tagged_started));
                if ($diff <= 180) { // ±3分以内
                    $bonus_multiplier = 2;
                    $tagged_user_id = $tagged_user_id_candidate;
                    $tag_bonus_active = true;
                }
            }
        }
    }

    // --------------------------
// タスク開始時のバフ倍率取得
// --------------------------
$nowStr = (new DateTime())->format('Y-m-d H:i:s'); // サーバー時刻を使用
$st = $pdo->prepare("
    SELECT MAX(level) as lvl
    FROM buffs
    WHERE type = 'task'
      AND start_time <= ?
      AND end_time >= ?
");
$st->execute([$nowStr, $nowStr]);
$level_at_start = (int)($st->fetchColumn() ?? 0);
$start_buff_multiplier = $level_at_start > 0 ? 1 + 0.2 * $level_at_start : 1;


    // --------------------------
    // タッグボーナスと掛け合わせる
    // --------------------------
    $total_multiplier = $bonus_multiplier * $start_buff_multiplier;

    // --------------------------
    // 実際に適用するコイン・クリスタル
    // --------------------------
    $final_coins    = (int)($coins * $total_multiplier);
    $final_crystals = (int)($crystals * $total_multiplier);

    // --------------------------
    // タスク履歴保存（開始時バフ倍率も記録）
    // --------------------------
    $st = $pdo->prepare("
        INSERT INTO focus_tasks
        (user_id, task, started_at, ended_at, minutes, coins, crystals, status, tagged_user_id, buff_multiplier)
        VALUES (?,?,?,?,?,?,?,?,?,?)
    ");
    $st->execute([$uid, $task, $started_at, $ended_at, $mins, $final_coins, $final_crystals, $status, $tagged_user_id, $start_buff_multiplier]);

    $next_tier = 0;

    if ($status === 'success') {
        // --------------------------
        // コイン・クリスタル加算
        // --------------------------
        $st = $pdo->prepare("UPDATE users SET coins=coins+?, crystals=crystals+? WHERE id=?");
        $st->execute([$final_coins, $final_crystals, $uid]);

        // --------------------------
        // 累計時間更新
        // --------------------------
        $st = $pdo->prepare("UPDATE users SET total_focus_time = total_focus_time + ? WHERE id=?");
        $st->execute([$mins, $uid]);

        // --------------------------
        // 累計時間取得・ティア更新
        // --------------------------
        $st = $pdo->prepare("SELECT total_focus_time, focus_tier FROM users WHERE id=?");
        $st->execute([$uid]);
        $user = $st->fetch(PDO::FETCH_ASSOC);
        $total_time = (int)($user['total_focus_time'] ?? 0);
        $tier       = (int)($user['focus_tier'] ?? 0);

        $tier_times = [
            1=>10,2=>15,3=>22,4=>33,5=>50,6=>76,7=>115,8=>173,9=>260,10=>390,
            11=>480,12=>600,13=>750,14=>900,15=>1100,16=>1300,17=>1500,18=>1750,
            19=>2000,20=>2300,21=>2600,22=>2900,23=>3300,24=>3700,25=>4100,
            26=>4500,27=>5000,28=>5500,29=>6000,30=>6600
        ];

        $next_tier = $tier;
        while ($next_tier < 30) {
            if (isset($tier_times[$next_tier+1]) && $total_time >= $tier_times[$next_tier+1]) {
                $next_tier++;
            } else break;
        }

        if ($next_tier != $tier) {
            $st = $pdo->prepare("UPDATE users SET focus_tier=? WHERE id=?");
            $st->execute([$next_tier, $uid]);
        }

        // --------------------------
        // 報酬イベント登録（開始時バフ＋タッグ後の値を記録）
        // --------------------------
        $st = $pdo->prepare("INSERT INTO reward_events(user_id,kind,amount,meta) VALUES(?,?,?,?)");
        $st->execute([
            $uid,
            'focus_reward',
            $final_coins + $final_crystals,
            json_encode([
                'task' => $task,
                'tier' => $next_tier,
                'coins' => $final_coins,
                'crystals' => $final_crystals,
                'total_multiplier' => $total_multiplier,
                'start_buff_multiplier' => $start_buff_multiplier,
                'tag_bonus_active' => $tag_bonus_active
            ])
        ]);

    } else {
        // 失敗時はティア変更なし
        $st = $pdo->prepare("SELECT focus_tier FROM users WHERE id=?");
        $st->execute([$uid]);
        $next_tier = (int)($st->fetchColumn() ?? 0);
    }

    // --------------------------
    // JSON出力（バフ＋タッグ後の値）
    // --------------------------
    json_exit([
        'ok' => true,
        'tier' => $next_tier,
        'coins' => $final_coins,
        'crystals' => $final_crystals,
        'total_multiplier' => $total_multiplier,
        'start_buff_multiplier' => $start_buff_multiplier,
        'tag_bonus_active' => $tag_bonus_active
    ]);

} catch (Exception $e) {
    json_exit(['ok'=>false,'error'=>'exception','msg'=>$e->getMessage()]);
}
