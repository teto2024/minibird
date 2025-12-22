-- ===============================================
-- Focus Task Improvements Schema
-- 集中タスク改良用テーブル定義
-- ===============================================

USE syugetsu2025_clone;

-- ユーザーの集中タスク統計テーブル
CREATE TABLE IF NOT EXISTS focus_statistics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    last_success_date DATE NULL COMMENT '最後に成功した日',
    current_streak INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '現在の連続日数',
    max_streak INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最大連続日数',
    consecutive_successes INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '連続成功回数',
    total_successes INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累計成功回数',
    total_failures INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累計失敗回数',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザーごとの集中タスク統計';

-- 初期データ挿入（既存ユーザー用）
INSERT IGNORE INTO focus_statistics (user_id, last_success_date, current_streak, consecutive_successes, total_successes, total_failures)
SELECT 
    id as user_id,
    NULL as last_success_date,
    0 as current_streak,
    0 as consecutive_successes,
    0 as total_successes,
    0 as total_failures
FROM users;

SELECT 'Focus statistics table created successfully' AS status;
