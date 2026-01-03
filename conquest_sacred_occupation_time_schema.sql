-- ===============================================
-- MiniBird 占領戦 神城累計占領時間トラッキングスキーマ
-- 神城の累計占領時間を記録し、ランキング決定に使用
-- ===============================================

USE microblog;

-- ===============================================
-- conquest_seasonsテーブルに神城累計占領時間カラムを追加
-- 各プレイヤーのシーズン内での神城累計占領時間を記録
-- ===============================================
CREATE TABLE IF NOT EXISTS conquest_sacred_occupation_time (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    season_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    total_occupation_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '神城の累計占領時間（秒）',
    last_occupation_start DATETIME NULL COMMENT '最後に神城を占領した時刻',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES conquest_seasons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_season_user (season_id, user_id),
    INDEX idx_season (season_id),
    INDEX idx_user (user_id),
    INDEX idx_total_time (total_occupation_seconds)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='占領戦 神城累計占領時間';

-- ===============================================
-- conquest_castlesテーブルに最後の占領時刻カラムを追加
-- 神城の占領時刻を記録するため
-- ===============================================
ALTER TABLE conquest_castles
ADD COLUMN IF NOT EXISTS sacred_occupation_started_at DATETIME NULL COMMENT '神城が占領された時刻' AFTER owner_user_id;

-- ===============================================
-- conquest_season_rewardsテーブルに神城占領時間カラムを追加
-- ===============================================
ALTER TABLE conquest_season_rewards
ADD COLUMN IF NOT EXISTS sacred_occupation_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '神城の累計占領時間（秒）' AFTER castle_count;

-- テーブル作成完了メッセージ
SELECT 'Conquest sacred occupation time tracking schema created successfully' AS status;
