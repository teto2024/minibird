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
 * デイリークエスト全完了チェック（報酬付与はしない）
 * @param int $user_id ユーザーID
 */
function check_daily_quest_completion($user_id) {
    // 報酬は手動で受け取る仕組みに変更したため、この関数は何もしない
    return false;
}

/**
 * ウィークリークエスト全完了チェック（報酬付与はしない）
 * @param int $user_id ユーザーID
 */
function check_weekly_quest_completion($user_id) {
    // 報酬は手動で受け取る仕組みに変更したため、この関数は何もしない
    return false;
}

/**
 * リレークエスト全完了チェック（報酬付与はしない）
 * @param int $user_id ユーザーID
 */
function check_relay_quest_completion($user_id) {
    // 報酬は手動で受け取る仕組みに変更したため、この関数は何もしない
    return false;
}

/**
 * 集中タスク専用のクエスト進行チェック
 * @param int $user_id ユーザーID
 * @param int $minutes 集中タスクの分数
 */
function check_focus_quest_progress($user_id, $minutes) {
    $pdo = db();
    $today_end = date('Y-m-d 23:59:59');
    $week_end = date('Y-m-d 23:59:59', strtotime('sunday this week'));
    
    // 15分以上の場合、focus_min_15クエストを完了
    if ($minutes >= 15) {
        check_daily_quest_progress($user_id, 'focus_min_15', 1);
    }
    
    // focus_total_100クエスト（累計分数）を進行
    check_weekly_quest_progress($user_id, 'focus_total_100', $minutes);
}
