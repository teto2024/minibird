-- ===============================================
-- Equipment Upgrade System Schema for MiniBird
-- 装備アップグレードシステムのテーブル修正
-- ===============================================

-- ===============================================
-- user_equipmentテーブルにupgrade_levelカラムを追加
-- ===============================================
ALTER TABLE user_equipment 
ADD COLUMN IF NOT EXISTS upgrade_level INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'アップグレードレベル'
AFTER buffs;

-- ===============================================
-- 装備アップグレード履歴テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS equipment_upgrade_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    equipment_id BIGINT UNSIGNED NOT NULL COMMENT 'アップグレードした装備ID',
    from_level INT UNSIGNED NOT NULL COMMENT 'アップグレード前のレベル',
    to_level INT UNSIGNED NOT NULL COMMENT 'アップグレード後のレベル',
    token_used VARCHAR(20) NOT NULL COMMENT '消費したトークン種類',
    token_amount INT NOT NULL COMMENT '消費したトークン数',
    buff_increase JSON NOT NULL COMMENT 'バフ上昇値',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_equipment (equipment_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='装備アップグレード履歴';

-- テーブル作成完了メッセージ
SELECT 'Equipment upgrade system schema created successfully' AS status;
