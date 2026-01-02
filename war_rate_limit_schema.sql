-- ===============================================
-- MiniBird 戦争レート制限スキーマ
-- ユーザーが1時間に3回までしか戦争を仕掛けられないように制限
-- ===============================================

USE microblog;

-- ===============================================
-- 戦争レート制限テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS user_war_rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '攻撃者のユーザーID',
    attack_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '攻撃実行時刻',
    target_user_id INT UNSIGNED NOT NULL COMMENT '攻撃対象のユーザーID',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_timestamp (user_id, attack_timestamp),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='戦争レート制限追跡テーブル（1時間に3回まで）';

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'War rate limit schema created successfully' AS status;
