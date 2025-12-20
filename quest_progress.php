<?php
// ===============================================
// quest_progress.php
// クエスト進行チェック処理
// ===============================================

require_once __DIR__ . '/config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * クエスト進行をチェックして報酬を付与
 * @param int $user_id ユーザーID
 * @param string $action アクション種類（post, like, repost）
 * @param int $count 進行カウント
 */
function check_quest_progress($user_id, $action, $count = 1) {
    $pdo = db();
    
    // デイリークエスト進行チェック
    check_daily_quest_progress($user_id, $action, $count);
    
    // ウィークリークエスト進行チェック
    check_weekly_quest_progress($user_id, $action, $count);
    
    // リレークエスト進行チェック
    check_relay_quest_progress($user_id, $action, $count);
}

/**
 * リレークエストをリセット
 * @param int $user_id ユーザーID
 * @return bool 成功したかどうか
 */
function reset_relay_quests($user_id) {
    $pdo = db();
    try {
        $pdo->beginTransaction();
        
        // リレークエストの進行状況を削除
        $stmt = $pdo->prepare("
            DELETE FROM user_quest_progress 
            WHERE user_id = ? 
            AND quest_id IN (SELECT id FROM quests WHERE type = 'relay')
        ");
        $stmt->execute([$user_id]);
        
        // リレー進行状況をリセット（current_orderを1に戻す）
        $stmt = $pdo->prepare("
            INSERT INTO relay_quest_progress (user_id, current_order, updated_at)
            VALUES (?, 1, NOW())
            ON DUPLICATE KEY UPDATE current_order = 1, last_completed_quest_id = NULL, updated_at = NOW()
        ");
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Relay quest reset error: " . $e->getMessage());
        return false;
    }
}

/**
 * テキスト含有チェックを伴うクエスト進行
 * @param int $user_id ユーザーID
 * @param string $action アクション種類（post_contains）
 * @param string $text 投稿テキスト
 */
function check_quest_progress_with_text($user_id, $action, $text) {
    $pdo = db();
    
    if ($action !== 'post_contains') return;
    
    // デイリークエストでテキスト含有チェック
    $today_start = date('Y-m-d 00:00:00');
    $today_end = date('Y-m-d 23:59:59');
    
    $stmt = $pdo->prepare("
        SELECT q.*, uqp.id as progress_id, uqp.progress, uqp.status
        FROM quests q
        LEFT JOIN user_quest_progress uqp ON uqp.quest_id = q.id AND uqp.user_id = ? 
            AND uqp.expired_at >= NOW()
        WHERE q.type = 'daily' AND q.is_active = TRUE
    ");
    $stmt->execute([$user_id]);
    $quests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($quests as $quest) {
        $conditions = json_decode($quest['conditions'], true);
        
        if ($conditions['action'] === 'post_contains') {
            $search_text = $conditions['text'] ?? '';
            
            if (stripos($text, $search_text) !== false) {
                // テキストが含まれている
                if (!$quest['progress_id']) {
                    // 新規クエスト開始
                    $stmt = $pdo->prepare("
                        INSERT INTO user_quest_progress (user_id, quest_id, progress, status, started_at, expired_at)
                        VALUES (?, ?, 1, 'completed', NOW(), ?)
                    ");
                    $stmt->execute([$user_id, $quest['id'], $today_end]);
                    
                    // 報酬付与
                    grant_quest_reward($user_id, $quest);
                } elseif ($quest['status'] === 'active') {
                    // 完了
                    $stmt = $pdo->prepare("
                        UPDATE user_quest_progress 
                        SET progress = 1, status = 'completed', completed_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$quest['progress_id']]);
                    
                    // 報酬付与
                    grant_quest_reward($user_id, $quest);
                }
            }
        }
    }
}

/**
 * デイリークエスト進行チェック
 */
function check_daily_quest_progress($user_id, $action, $count) {
    $pdo = db();
    $today_end = date('Y-m-d 23:59:59');
    
    $stmt = $pdo->prepare("
        SELECT q.*, uqp.id as progress_id, uqp.progress, uqp.status
        FROM quests q
        LEFT JOIN user_quest_progress uqp ON uqp.quest_id = q.id AND uqp.user_id = ? 
            AND uqp.expired_at >= NOW()
        WHERE q.type = 'daily' AND q.is_active = TRUE
    ");
    $stmt->execute([$user_id]);
    $quests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($quests as $quest) {
        $conditions = json_decode($quest['conditions'], true);
        
        if ($conditions['action'] === $action) {
            $required = $conditions['count'] ?? 1;
            
            if (!$quest['progress_id']) {
                // 新規クエスト開始
                $new_progress = $count;
                $status = ($new_progress >= $required) ? 'completed' : 'active';
                
                $stmt = $pdo->prepare("
                    INSERT INTO user_quest_progress (user_id, quest_id, progress, status, started_at, expired_at)
                    VALUES (?, ?, ?, ?, NOW(), ?)
                ");
                $stmt->execute([$user_id, $quest['id'], $new_progress, $status, $today_end]);
                
                if ($status === 'completed') {
                    grant_quest_reward($user_id, $quest);
                }
            } elseif ($quest['status'] === 'active') {
                // 進行中のクエスト更新
                $new_progress = $quest['progress'] + $count;
                $status = ($new_progress >= $required) ? 'completed' : 'active';
                
                $stmt = $pdo->prepare("
                    UPDATE user_quest_progress 
                    SET progress = ?, status = ?, completed_at = ?
                    WHERE id = ?
                ");
                $completed_at = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
                $stmt->execute([$new_progress, $status, $completed_at, $quest['progress_id']]);
                
                if ($status === 'completed') {
                    grant_quest_reward($user_id, $quest);
                }
            }
        }
    }
}

/**
 * ウィークリークエスト進行チェック
 */
function check_weekly_quest_progress($user_id, $action, $count) {
    $pdo = db();
    $week_end = date('Y-m-d 23:59:59', strtotime('sunday this week'));
    
    $stmt = $pdo->prepare("
        SELECT q.*, uqp.id as progress_id, uqp.progress, uqp.status
        FROM quests q
        LEFT JOIN user_quest_progress uqp ON uqp.quest_id = q.id AND uqp.user_id = ? 
            AND uqp.expired_at >= NOW()
        WHERE q.type = 'weekly' AND q.is_active = TRUE
    ");
    $stmt->execute([$user_id]);
    $quests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($quests as $quest) {
        $conditions = json_decode($quest['conditions'], true);
        
        if ($conditions['action'] === $action) {
            $required = $conditions['count'] ?? 1;
            
            if (!$quest['progress_id']) {
                // 新規クエスト開始
                $new_progress = $count;
                $status = ($new_progress >= $required) ? 'completed' : 'active';
                
                $stmt = $pdo->prepare("
                    INSERT INTO user_quest_progress (user_id, quest_id, progress, status, started_at, expired_at)
                    VALUES (?, ?, ?, ?, NOW(), ?)
                ");
                $stmt->execute([$user_id, $quest['id'], $new_progress, $status, $week_end]);
                
                if ($status === 'completed') {
                    grant_quest_reward($user_id, $quest);
                }
            } elseif ($quest['status'] === 'active') {
                // 進行中のクエスト更新
                $new_progress = $quest['progress'] + $count;
                $status = ($new_progress >= $required) ? 'completed' : 'active';
                
                $stmt = $pdo->prepare("
                    UPDATE user_quest_progress 
                    SET progress = ?, status = ?, completed_at = ?
                    WHERE id = ?
                ");
                $completed_at = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
                $stmt->execute([$new_progress, $status, $completed_at, $quest['progress_id']]);
                
                if ($status === 'completed') {
                    grant_quest_reward($user_id, $quest);
                }
            }
        }
    }
}

/**
 * リレークエスト進行チェック
 */
function check_relay_quest_progress($user_id, $action, $count) {
    $pdo = db();
    
    // 現在のリレー順番を取得
    $stmt = $pdo->prepare("SELECT current_order FROM relay_quest_progress WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $relay_progress = $stmt->fetch();
    
    if (!$relay_progress) {
        // 初回リレー開始
        $stmt = $pdo->prepare("INSERT INTO relay_quest_progress (user_id, current_order) VALUES (?, 1)");
        $stmt->execute([$user_id]);
        $current_order = 1;
    } else {
        $current_order = $relay_progress['current_order'];
    }
    
    // 現在の順番のリレークエストを取得
    $stmt = $pdo->prepare("
        SELECT q.*, uqp.id as progress_id, uqp.progress, uqp.status
        FROM quests q
        LEFT JOIN user_quest_progress uqp ON uqp.quest_id = q.id AND uqp.user_id = ?
        WHERE q.type = 'relay' AND q.relay_order = ? AND q.is_active = TRUE
    ");
    $stmt->execute([$user_id, $current_order]);
    $quest = $stmt->fetch();
    
    if (!$quest) return; // 該当クエストなし
    
    $conditions = json_decode($quest['conditions'], true);
    
    if ($conditions['action'] === $action) {
        $required = $conditions['count'] ?? 1;
        
        if (!$quest['progress_id']) {
            // 新規クエスト開始
            $new_progress = $count;
            $status = ($new_progress >= $required) ? 'completed' : 'active';
            
            $stmt = $pdo->prepare("
                INSERT INTO user_quest_progress (user_id, quest_id, progress, status, started_at, completed_at)
                VALUES (?, ?, ?, ?, NOW(), ?)
            ");
            $completed_at = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
            $stmt->execute([$user_id, $quest['id'], $new_progress, $status, $completed_at]);
            
            if ($status === 'completed') {
                grant_quest_reward($user_id, $quest);
                
                // 次のリレーに進む
                $stmt = $pdo->prepare("
                    UPDATE relay_quest_progress 
                    SET current_order = current_order + 1, last_completed_quest_id = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$quest['id'], $user_id]);
            }
        } elseif ($quest['status'] === 'active') {
            // 進行中のクエスト更新
            $new_progress = $quest['progress'] + $count;
            $status = ($new_progress >= $required) ? 'completed' : 'active';
            
            $stmt = $pdo->prepare("
                UPDATE user_quest_progress 
                SET progress = ?, status = ?, completed_at = ?
                WHERE id = ?
            ");
            $completed_at = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
            $stmt->execute([$new_progress, $status, $completed_at, $quest['progress_id']]);
            
            if ($status === 'completed') {
                grant_quest_reward($user_id, $quest);
                
                // 次のリレーに進む
                $stmt = $pdo->prepare("
                    UPDATE relay_quest_progress 
                    SET current_order = current_order + 1, last_completed_quest_id = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$quest['id'], $user_id]);
            }
        }
    }
}

/**
 * クエスト報酬付与
 */
function grant_quest_reward($user_id, $quest) {
    $pdo = db();
    
    $coins = intval($quest['reward_coins'] ?? 0);
    $crystals = intval($quest['reward_crystals'] ?? 0);
    $diamonds = intval($quest['reward_diamonds'] ?? 0);
    
    if ($coins > 0 || $crystals > 0 || $diamonds > 0) {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET coins = coins + ?, crystals = crystals + ?, diamonds = diamonds + ?
            WHERE id = ?
        ");
        $stmt->execute([$coins, $crystals, $diamonds, $user_id]);
        
        // 報酬履歴記録
        if ($coins > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO reward_events (user_id, kind, amount, meta, created_at)
                VALUES (?, 'quest_reward', ?, JSON_OBJECT('quest_id', ?, 'currency', 'coins'), NOW())
            ");
            $stmt->execute([$user_id, $coins, $quest['id']]);
        }
        if ($crystals > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO reward_events (user_id, kind, amount, meta, created_at)
                VALUES (?, 'quest_reward', ?, JSON_OBJECT('quest_id', ?, 'currency', 'crystals'), NOW())
            ");
            $stmt->execute([$user_id, $crystals, $quest['id']]);
        }
        if ($diamonds > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO reward_events (user_id, kind, amount, meta, created_at)
                VALUES (?, 'quest_reward', ?, JSON_OBJECT('quest_id', ?, 'currency', 'diamonds'), NOW())
            ");
            $stmt->execute([$user_id, $diamonds, $quest['id']]);
        }
    }
    
    // クエスト完了チェック
    $quest_type = $quest['type'] ?? '';
    if ($quest_type === 'daily') {
        check_daily_quest_completion($user_id);
    } elseif ($quest_type === 'weekly') {
        check_weekly_quest_completion($user_id);
    } elseif ($quest_type === 'relay') {
        check_relay_quest_completion($user_id);
    }
}

/**
 * デイリー・ウィークリークエストの自動リセットチェック
 * ページ読み込み時に呼び出して、期限切れクエストを自動的にexpiredにマークする
 * @param int $user_id ユーザーID
 */
function auto_reset_quests($user_id) {
    $pdo = db();
    
    // 期限切れのクエストをexpiredに更新
    $stmt = $pdo->prepare("
        UPDATE user_quest_progress 
        SET status = 'expired'
        WHERE user_id = ? 
        AND status != 'expired' 
        AND expired_at IS NOT NULL 
        AND expired_at < NOW()
    ");
    $stmt->execute([$user_id]);
    
    return true;
}

/**
 * デイリークエスト全完了チェックと報酬付与
 * @param int $user_id ユーザーID
 */
function check_daily_quest_completion($user_id) {
    $pdo = db();
    $today = date('Y-m-d');
    
    // 今日のデイリークエスト全完了チェック
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN uqp.status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM quests q
        LEFT JOIN user_quest_progress uqp ON uqp.quest_id = q.id AND uqp.user_id = ? 
            AND DATE(uqp.started_at) = ?
        WHERE q.type = 'daily' AND q.is_active = TRUE
    ");
    $stmt->execute([$user_id, $today]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0 && $result['completed'] == $result['total']) {
        // すでに報酬を受け取っているかチェック
        $stmt = $pdo->prepare("
            SELECT id FROM quest_completions 
            WHERE user_id = ? AND completion_type = 'daily' AND period_key = ?
        ");
        $stmt->execute([$user_id, $today]);
        
        if (!$stmt->fetch()) {
            // 報酬付与
            $coins = 3000;
            $crystals = 15;
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO quest_completions (user_id, completion_type, period_key, reward_coins, reward_crystals)
                    VALUES (?, 'daily', ?, ?, ?)
                ");
                $stmt->execute([$user_id, $today, $coins, $crystals]);
                
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET coins = coins + ?, crystals = crystals + ?, daily_quest_completions = daily_quest_completions + 1
                    WHERE id = ?
                ");
                $stmt->execute([$coins, $crystals, $user_id]);
                
                $pdo->commit();
                return true;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Daily quest completion error: " . $e->getMessage());
            }
        }
    }
    return false;
}

/**
 * ウィークリークエスト全完了チェックと報酬付与
 * @param int $user_id ユーザーID
 */
function check_weekly_quest_completion($user_id) {
    $pdo = db();
    $week_key = date('Y-W'); // 年-週番号
    
    // 今週のウィークリークエスト全完了チェック
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN uqp.status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM quests q
        LEFT JOIN user_quest_progress uqp ON uqp.quest_id = q.id AND uqp.user_id = ? 
            AND YEARWEEK(uqp.started_at, 1) = YEARWEEK(NOW(), 1)
        WHERE q.type = 'weekly' AND q.is_active = TRUE
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0 && $result['completed'] == $result['total']) {
        // すでに報酬を受け取っているかチェック
        $stmt = $pdo->prepare("
            SELECT id FROM quest_completions 
            WHERE user_id = ? AND completion_type = 'weekly' AND period_key = ?
        ");
        $stmt->execute([$user_id, $week_key]);
        
        if (!$stmt->fetch()) {
            // 報酬付与
            $coins = 10000;
            $crystals = 50;
            $diamonds = 2;
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO quest_completions (user_id, completion_type, period_key, reward_coins, reward_crystals, reward_diamonds)
                    VALUES (?, 'weekly', ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $week_key, $coins, $crystals, $diamonds]);
                
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET coins = coins + ?, crystals = crystals + ?, diamonds = diamonds + ?, weekly_quest_completions = weekly_quest_completions + 1
                    WHERE id = ?
                ");
                $stmt->execute([$coins, $crystals, $diamonds, $user_id]);
                
                $pdo->commit();
                return true;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Weekly quest completion error: " . $e->getMessage());
            }
        }
    }
    return false;
}

/**
 * リレークエスト全完了チェックと報酬付与
 * @param int $user_id ユーザーID
 */
function check_relay_quest_completion($user_id) {
    $pdo = db();
    
    // リレークエストの総数取得
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM quests WHERE type = 'relay' AND is_active = TRUE");
    $stmt->execute();
    $total_relay = (int)$stmt->fetchColumn();
    
    // 現在のリレー進行状況
    $stmt = $pdo->prepare("SELECT current_order FROM relay_quest_progress WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $progress = $stmt->fetch();
    
    if ($progress && $progress['current_order'] > $total_relay) {
        // 全リレークエスト完了
        $completion_key = 'relay_' . date('Y-m-d'); // 1日1回のみ
        
        // すでに報酬を受け取っているかチェック
        $stmt = $pdo->prepare("
            SELECT id FROM quest_completions 
            WHERE user_id = ? AND completion_type = 'relay' AND period_key = ?
        ");
        $stmt->execute([$user_id, $completion_key]);
        
        if (!$stmt->fetch()) {
            // 報酬付与
            $coins = 2000;
            $diamonds = 1;
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO quest_completions (user_id, completion_type, period_key, reward_coins, reward_diamonds)
                    VALUES (?, 'relay', ?, ?, ?)
                ");
                $stmt->execute([$user_id, $completion_key, $coins, $diamonds]);
                
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET coins = coins + ?, diamonds = diamonds + ?, relay_quest_completions = relay_quest_completions + 1
                    WHERE id = ?
                ");
                $stmt->execute([$coins, $diamonds, $user_id]);
                
                $pdo->commit();
                return true;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Relay quest completion error: " . $e->getMessage());
            }
        }
    }
    return false;
}
