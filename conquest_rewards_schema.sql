-- ===============================================
-- MiniBird 占領戦報酬システムスキーマ
-- シーズン終了時のランキング報酬を管理
-- ===============================================

USE microblog;

-- ===============================================
-- conquest_seasonsテーブルに報酬配布フラグを追加
-- ===============================================
ALTER TABLE conquest_seasons
ADD COLUMN IF NOT EXISTS rewards_distributed BOOLEAN NOT NULL DEFAULT FALSE COMMENT '報酬配布済みフラグ' AFTER winner_civilization_name;

-- ===============================================
-- シーズン報酬ログテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS conquest_season_rewards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    season_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rank_position INT UNSIGNED NOT NULL COMMENT 'シーズン終了時の順位',
    coins_reward INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '獲得コイン',
    crystals_reward INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '獲得クリスタル',
    diamonds_reward INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '獲得ダイヤモンド',
    castle_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'シーズン終了時の城数',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES conquest_seasons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_season_user (season_id, user_id),
    INDEX idx_season (season_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='占領戦シーズン報酬ログ';

-- テーブル作成完了メッセージ
SELECT 'Conquest rewards schema created successfully' AS status;
