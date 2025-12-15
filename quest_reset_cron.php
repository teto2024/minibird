<?php
// ===============================================
// quest_reset_cron.php
// デイリー・ウィークリークエストのリセット
// CRONで毎日午前0時に実行する想定
// 例: 0 0 * * * /usr/bin/php /path/to/quest_reset_cron.php
// ===============================================

require_once __DIR__ . '/config.php';

$pdo = db();
$now = new DateTime('now', new DateTimeZone('UTC'));
$current_day = $now->format('w'); // 0 (日曜) ~ 6 (土曜)

try {
    // ====================
    // デイリークエストのリセット
    // 完了したクエストは期限切れにし、進行中のクエストは削除
    // ====================
    // まず、完了したクエストを期限切れにする
    $stmt = $pdo->prepare("
        UPDATE user_quest_progress 
        SET status = 'expired', updated_at = NOW()
        WHERE quest_id IN (SELECT id FROM quests WHERE reset_type = 'daily')
        AND status = 'completed'
    ");
    $stmt->execute();
    $daily_completed_count = $stmt->rowCount();
    
    // 進行中のクエストを削除
    $stmt = $pdo->prepare("
        DELETE FROM user_quest_progress 
        WHERE quest_id IN (SELECT id FROM quests WHERE reset_type = 'daily')
        AND status != 'expired'
    ");
    $stmt->execute();
    $daily_active_count = $stmt->rowCount();
    
    $daily_reset_count = $daily_completed_count + $daily_active_count;
    
    // ====================
    // ウィークリークエストのリセット（毎週日曜日）
    // ====================
    $weekly_reset_count = 0;
    if ($current_day == 0) { // 日曜日
        // まず、完了したクエストを期限切れにする
        $stmt = $pdo->prepare("
            UPDATE user_quest_progress 
            SET status = 'expired', updated_at = NOW()
            WHERE quest_id IN (SELECT id FROM quests WHERE reset_type = 'weekly')
            AND status = 'completed'
        ");
        $stmt->execute();
        $weekly_completed_count = $stmt->rowCount();
        
        // 進行中のクエストを削除
        $stmt = $pdo->prepare("
            DELETE FROM user_quest_progress 
            WHERE quest_id IN (SELECT id FROM quests WHERE reset_type = 'weekly')
            AND status != 'expired'
        ");
        $stmt->execute();
        $weekly_active_count = $stmt->rowCount();
        
        $weekly_reset_count = $weekly_completed_count + $weekly_active_count;
    }
    
    // ログ出力
    $log_message = sprintf(
        "[%s] Quest Reset CRON executed. Daily: %d, Weekly: %d\n",
        $now->format('Y-m-d H:i:s'),
        $daily_reset_count,
        $weekly_reset_count
    );
    
    file_put_contents(__DIR__ . '/quest_reset.log', $log_message, FILE_APPEND);
    
    echo "Success: Daily=$daily_reset_count, Weekly=$weekly_reset_count\n";
    
} catch (Exception $e) {
    $error_log = sprintf(
        "[%s] ERROR: %s\n",
        $now->format('Y-m-d H:i:s'),
        $e->getMessage()
    );
    file_put_contents(__DIR__ . '/quest_reset.log', $error_log, FILE_APPEND);
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
