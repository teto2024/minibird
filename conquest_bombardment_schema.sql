-- ===============================================
-- MiniBird 占領戦砲撃システムスキーマ
-- 30分おきに砲撃が発生し、城に配置した兵士が削られる
-- ===============================================

USE microblog;

-- ===============================================
-- 砲撃ログテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS conquest_bombardment_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    season_id INT UNSIGNED NOT NULL,
    castle_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT '被害を受けたユーザー',
    bombardment_at DATETIME NOT NULL COMMENT '砲撃発生時刻',
    troops_wounded JSON NOT NULL COMMENT '負傷兵詳細 [{troop_type_id, count, name, cost}]',
    total_wounded INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '総負傷兵数',
    log_message TEXT COMMENT '砲撃ログメッセージ',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES conquest_seasons(id) ON DELETE CASCADE,
    FOREIGN KEY (castle_id) REFERENCES conquest_castles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_season (season_id),
    INDEX idx_castle (castle_id),
    INDEX idx_user (user_id),
    INDEX idx_bombardment_at (bombardment_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='占領戦砲撃ログ';

-- ===============================================
-- 城に最終砲撃時刻を追加
-- ===============================================
ALTER TABLE conquest_castles
ADD COLUMN IF NOT EXISTS last_bombardment_at DATETIME NULL COMMENT '最終砲撃時刻' AFTER icon;

-- ===============================================
-- 砲撃ログを戦闘ログタイプに追加
-- conquest_battle_logsテーブルにログタイプを追加
-- ===============================================
ALTER TABLE conquest_battle_logs
ADD COLUMN IF NOT EXISTS log_type ENUM('battle', 'bombardment') NOT NULL DEFAULT 'battle' COMMENT 'ログタイプ' AFTER id;

-- ===============================================
-- 砲撃用ターンログテーブル（戦闘ログと共通形式）
-- ===============================================
-- 砲撃ログはconquest_battle_turn_logsを使用する
-- action_typeに'bombardment'を追加
ALTER TABLE conquest_battle_turn_logs
MODIFY COLUMN action_type ENUM('attack', 'skill', 'status_effect', 'defeat', 'bombardment') NOT NULL COMMENT 'アクション種類';

-- テーブル作成完了メッセージ
SELECT 'Conquest bombardment system schema created successfully' AS status;
