<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/token_drop.php';
require_login();
$pdo = db();

// 報酬計算用の定数（上方修正）
define('REWARD_BASE_COINS', 15);       // 10から15に上方修正
define('REWARD_BASE_CRYSTALS', 3);     // 2から3に上方修正
define('REWARD_COINS_EXP_RATE', 1.05); // 1.04から1.05に上方修正
define('REWARD_CRYSTALS_EXP_RATE', 1.02); // 1.015から1.02に上方修正
define('REWARD_SERVER_TIME_MULTIPLIER', 1.08); // 1.06から1.08に上方修正
define('REWARD_MAX_MINUTES', 180); // 最大時間制限

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
    // 成功・失敗ごとのコイン・クリスタル計算
    // 定数を使用して計算（クライアント側と同期）
    // 最大値チェックでオーバーフロー防止
    // --------------------------
        if ($status === 'success') {
        // 成功時：改善された指数関数的な時間ボーナス
        $safe_mins = min($mins, REWARD_MAX_MINUTES);
        $time_multiplier = pow(REWARD_SERVER_TIME_MULTIPLIER, $safe_mins);

        $final_coins    = (int)floor($coins * $time_multiplier * $total_multiplier);
        $final_crystals = (int)floor($crystals * $time_multiplier * $total_multiplier);
    } else {
        // 失敗時：実施時間 / 設定時間 の比率で報酬を決定

        // 実施時間（分単位、最低1分保証、最大値制限）
        $actual_mins = max(1, min(floor((strtotime($ended_at) - strtotime($started_at)) / 60), REWARD_MAX_MINUTES));

        // 実施比率（0〜1）
        $progress_ratio = min(1, $actual_mins / min($mins, REWARD_MAX_MINUTES));

        // 実施時間に応じた指数補正
        $time_multiplier = pow(REWARD_SERVER_TIME_MULTIPLIER, $actual_mins);

        // 比率＋指数補正＋各種ボーナスを掛け合わせ
        $final_coins    = (int)floor($coins * $progress_ratio * $time_multiplier * $total_multiplier);
        $final_crystals = (int)floor($crystals * $progress_ratio * $time_multiplier * $total_multiplier);
    }


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
        // 報酬イベント登録（成功）
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

        // --------------------------
        // トークンドロップ（成功時）
        // バフとタッグボーナスをトークンにも適用
        // --------------------------
        $token_drops = drop_tokens($uid, 'focus_success', $mins, $total_multiplier);

    } else {
        // --------------------------
        // 失敗時：コイン・クリスタルのみ加算（ティア変更なし）
        // --------------------------
        $st = $pdo->prepare("UPDATE users SET coins=coins+?, crystals=crystals+? WHERE id=?");
        $st->execute([$final_coins, $final_crystals, $uid]);

        // ティアはそのまま
        $st = $pdo->prepare("SELECT focus_tier FROM users WHERE id=?");
        $st->execute([$uid]);
        $next_tier = (int)($st->fetchColumn() ?? 0);

        // --------------------------
        // 報酬イベント登録（失敗）
        // --------------------------
        $st = $pdo->prepare("INSERT INTO reward_events(user_id,kind,amount,meta) VALUES(?,?,?,?)");
        $st->execute([
            $uid,
            'focus_fail_reward',
            $final_coins + $final_crystals,
            json_encode([
                'task' => $task,
                'coins' => $final_coins,
                'crystals' => $final_crystals,
                'total_multiplier' => $total_multiplier,
                'start_buff_multiplier' => $start_buff_multiplier,
                'tag_bonus_active' => $tag_bonus_active
            ])
        ]);

        // --------------------------
        // トークンドロップ（失敗時）
        // バフとタッグボーナスをトークンにも適用
        // --------------------------
        $actual_mins = max(1, floor((strtotime($ended_at) - strtotime($started_at)) / 60));
        $token_drops = drop_tokens($uid, 'focus_fail', $actual_mins, $total_multiplier);
    }

    // --------------------------
    // 統計情報の更新と取得
    // --------------------------
    $today = date('Y-m-d');
    
    // 統計レコードの取得または作成
    $st = $pdo->prepare("
        INSERT INTO focus_statistics (user_id, last_success_date, current_streak, consecutive_successes, total_successes, total_failures)
        VALUES (?, NULL, 0, 0, 0, 0)
        ON DUPLICATE KEY UPDATE user_id = user_id
    ");
    $st->execute([$uid]);
    
    $st = $pdo->prepare("SELECT * FROM focus_statistics WHERE user_id = ?");
    $st->execute([$uid]);
    $stats = $st->fetch(PDO::FETCH_ASSOC);
    
    if ($status === 'success') {
        // 成功時の統計更新
        $consecutive_successes = ($stats['consecutive_successes'] ?? 0) + 1;
        $total_successes = ($stats['total_successes'] ?? 0) + 1;
        $last_date = $stats['last_success_date'] ?? null;
        $current_streak = $stats['current_streak'] ?? 0;
        
        // 連続日数の更新
        if ($last_date === null) {
            // 初回
            $current_streak = 1;
        } elseif ($last_date === $today) {
            // 同じ日の場合はストリークは変わらない
        } elseif ($last_date === date('Y-m-d', strtotime('-1 day'))) {
            // 前日の場合はストリーク継続
            $current_streak++;
        } else {
            // 途切れた
            $current_streak = 1;
        }
        
        $max_streak = max($current_streak, $stats['max_streak'] ?? 0);
        
        $st = $pdo->prepare("
            UPDATE focus_statistics 
            SET last_success_date = ?, 
                current_streak = ?, 
                max_streak = ?,
                consecutive_successes = ?,
                total_successes = ?
            WHERE user_id = ?
        ");
        $st->execute([$today, $current_streak, $max_streak, $consecutive_successes, $total_successes, $uid]);
    } else {
        // 失敗時の統計更新
        $consecutive_successes = 0; // リセット
        $total_failures = ($stats['total_failures'] ?? 0) + 1;
        $current_streak = $stats['current_streak'] ?? 0;
        
        $st = $pdo->prepare("
            UPDATE focus_statistics 
            SET consecutive_successes = 0,
                total_failures = ?
            WHERE user_id = ?
        ");
        $st->execute([$total_failures, $uid]);
    }
    
    // パーセンタイル計算用の統計取得
    // 本日の累計時間
    $st = $pdo->prepare("
        SELECT SUM(minutes) as today_total 
        FROM focus_tasks 
        WHERE user_id = ? AND DATE(started_at) = CURDATE() AND status = 'success'
    ");
    $st->execute([$uid]);
    $today_total = (int)($st->fetchColumn() ?? 0);
    
    // 直近一週間の累計時間
    $st = $pdo->prepare("
        SELECT SUM(minutes) as week_total 
        FROM focus_tasks 
        WHERE user_id = ? AND started_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = 'success'
    ");
    $st->execute([$uid]);
    $week_total = (int)($st->fetchColumn() ?? 0);
    
    // 全ユーザーの本日累計時間の分布
    $st = $pdo->prepare("
        SELECT SUM(minutes) as total 
        FROM focus_tasks 
        WHERE DATE(started_at) = CURDATE() AND status = 'success'
        GROUP BY user_id
        ORDER BY total DESC
    ");
    $st->execute();
    $all_today_totals = $st->fetchAll(PDO::FETCH_COLUMN);
    
    // パーセンタイル計算
    $today_percentile = 0;
    if (count($all_today_totals) > 0) {
        $rank = 1;
        foreach ($all_today_totals as $total) {
            if ($today_total > $total) break;
            $rank++;
        }
        $today_percentile = 100 - (($rank - 1) / count($all_today_totals) * 100);
    }
    
    // 同様に週間と累計のパーセンタイルを計算
    $st = $pdo->prepare("
        SELECT SUM(minutes) as total 
        FROM focus_tasks 
        WHERE started_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = 'success'
        GROUP BY user_id
        ORDER BY total DESC
    ");
    $st->execute();
    $all_week_totals = $st->fetchAll(PDO::FETCH_COLUMN);
    
    $week_percentile = 0;
    if (count($all_week_totals) > 0) {
        $rank = 1;
        foreach ($all_week_totals as $total) {
            if ($week_total > $total) break;
            $rank++;
        }
        $week_percentile = 100 - (($rank - 1) / count($all_week_totals) * 100);
    }
    
    // 累計時間でのパーセンタイル
    $st = $pdo->prepare("SELECT total_focus_time FROM users ORDER BY total_focus_time DESC");
    $st->execute();
    $all_totals = $st->fetchAll(PDO::FETCH_COLUMN);
    $user_total = $user['total_focus_time'] ?? 0;
    
    $total_percentile = 0;
    if (count($all_totals) > 0) {
        $rank = 1;
        foreach ($all_totals as $total) {
            if ($user_total > $total) break;
            $rank++;
        }
        $total_percentile = 100 - (($rank - 1) / count($all_totals) * 100);
    }

    // --------------------------
    // JSON出力（バフ＋タッグ後の値 + 統計情報 + トークンドロップ）
    // --------------------------
    json_exit([
        'ok' => true,
        'tier' => $next_tier,
        'coins' => $final_coins,
        'crystals' => $final_crystals,
        'total_multiplier' => $total_multiplier,
        'start_buff_multiplier' => $start_buff_multiplier,
        'tag_bonus_active' => $tag_bonus_active,
        'token_drops' => $token_drops ?? [],
        'statistics' => [
            'consecutive_successes' => $status === 'success' ? $consecutive_successes : 0,
            'current_streak' => $current_streak,
            'today_total' => $today_total,
            'week_total' => $week_total,
            'total_time' => $user_total,
            'today_percentile' => round($today_percentile, 1),
            'week_percentile' => round($week_percentile, 1),
            'total_percentile' => round($total_percentile, 1)
        ]
    ]);

} catch (Exception $e) {
    json_exit(['ok'=>false,'error'=>'exception','msg'=>$e->getMessage()]);
}
