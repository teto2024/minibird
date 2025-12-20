-- ===============================================
-- ポストブースト機能とクエストコンプリート報酬用スキーマ
-- ===============================================

USE syugetsu2025_clone;

-- ポストブーストテーブル
CREATE TABLE IF NOT EXISTS post_boosts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'ブーストしたユーザー',
    coins_spent INT UNSIGNED NOT NULL DEFAULT 200,
    crystals_spent INT UNSIGNED NOT NULL DEFAULT 20,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL COMMENT '投稿日から2日後',
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_post_expires (post_id, expires_at),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ポストブースト記録';

-- クエストコンプリート報酬記録テーブル
CREATE TABLE IF NOT EXISTS quest_completions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    completion_type ENUM('daily', 'weekly', 'relay') NOT NULL,
    completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reward_coins INT UNSIGNED NOT NULL DEFAULT 0,
    reward_crystals INT UNSIGNED NOT NULL DEFAULT 0,
    reward_diamonds INT UNSIGNED NOT NULL DEFAULT 0,
    period_key VARCHAR(50) NOT NULL COMMENT '期間識別子（例: 2025-12-20 for daily, 2025-W51 for weekly）',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_period (user_id, completion_type, period_key),
    INDEX idx_user_type (user_id, completion_type),
    INDEX idx_completed (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='クエストコンプリート報酬記録';

-- VIP統計追跡テーブル（既存のusersテーブルに追加する列が多い場合）
-- ALTER TABLE users を使用して必要な列を追加
ALTER TABLE users ADD COLUMN IF NOT EXISTS daily_quest_completions INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'デイリークエスト完了回数';
ALTER TABLE users ADD COLUMN IF NOT EXISTS weekly_quest_completions INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ウィークリークエスト完了回数';
ALTER TABLE users ADD COLUMN IF NOT EXISTS relay_quest_completions INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'リレークエスト完了回数';

SELECT 'Boost and Quest Completion tables created successfully' AS status;
