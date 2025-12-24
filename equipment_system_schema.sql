-- ===============================================
-- Equipment System Schema for MiniBird
-- 装備システムのテーブル定義
-- ===============================================

-- ===============================================
-- ユーザー装備テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS user_equipment (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    slot ENUM('weapon', 'helm', 'body', 'shoulder', 'arm', 'leg') NOT NULL COMMENT '装備部位',
    name VARCHAR(100) NOT NULL COMMENT '装備名',
    rarity ENUM('normal', 'rare', 'unique', 'legend', 'epic', 'hero', 'mythic') NOT NULL DEFAULT 'normal' COMMENT 'レアリティ',
    buffs JSON NOT NULL COMMENT '装備のバフデータ',
    is_equipped BOOLEAN NOT NULL DEFAULT FALSE COMMENT '装着中かどうか',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_equipment (user_id, slot),
    INDEX idx_user_equipped (user_id, is_equipped)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー所有装備';

-- ===============================================
-- 装備バフ種類マスターテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS equipment_buff_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    buff_key VARCHAR(50) NOT NULL UNIQUE COMMENT 'バフ識別子',
    name VARCHAR(100) NOT NULL COMMENT 'バフ名',
    description TEXT COMMENT '説明',
    icon VARCHAR(10) COMMENT 'アイコン',
    min_value DECIMAL(10,4) NOT NULL DEFAULT 0 COMMENT '最小値',
    max_value_normal DECIMAL(10,4) NOT NULL COMMENT 'ノーマル時最大値',
    max_value_mythic DECIMAL(10,4) NOT NULL COMMENT 'ミシック時最大値',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='装備バフ種類マスター';

-- バフ種類の初期データ
INSERT IGNORE INTO equipment_buff_types (buff_key, name, description, icon, min_value, max_value_normal, max_value_mythic) VALUES
('attack', '攻撃力', 'バトル時の攻撃力を上昇させる', '⚔️', 1, 10, 100),
('armor', 'アーマー', 'シールド値を上昇させる', '🛡️', 1, 10, 100),
('health', '体力', '最大体力を上昇させる', '❤️', 5, 50, 500),
('coin_drop', 'コインドロップ増加', 'コイン獲得量が増加する（%）', '🪙', 1, 5, 50),
('crystal_drop', 'クリスタルドロップ増加', 'クリスタル獲得量が増加する（%）', '💎', 1, 3, 30),
('diamond_drop', 'ダイヤモンドドロップ増加', 'ダイヤモンド獲得量が増加する（%）', '💠', 0.5, 2, 20),
('token_normal_drop', 'ノーマルトークンドロップ増加', 'ノーマルトークン獲得量が増加する（%）', '⚪', 1, 5, 50),
('token_rare_drop', 'レアトークンドロップ増加', 'レアトークン獲得量が増加する（%）', '🟢', 1, 4, 40),
('token_unique_drop', 'ユニークトークンドロップ増加', 'ユニークトークン獲得量が増加する（%）', '🔵', 1, 3, 30),
('token_legend_drop', 'レジェンドトークンドロップ増加', 'レジェンドトークン獲得量が増加する（%）', '🟡', 0.5, 2, 20),
('token_epic_drop', 'エピックトークンドロップ増加', 'エピックトークン獲得量が増加する（%）', '🟣', 0.5, 1.5, 15),
('token_hero_drop', 'ヒーロートークンドロップ増加', 'ヒーロートークン獲得量が増加する（%）', '🔴', 0.3, 1, 10),
('token_mythic_drop', 'ミシックトークンドロップ増加', 'ミシックトークン獲得量が増加する（%）', '🌈', 0.1, 0.5, 5);

-- ===============================================
-- トークン鍛冶履歴テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS token_forge_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    from_type VARCHAR(50) NOT NULL COMMENT '変換元トークン種類',
    from_amount INT NOT NULL COMMENT '消費量',
    to_type VARCHAR(50) NOT NULL COMMENT '変換先トークン種類',
    to_amount INT NOT NULL DEFAULT 1 COMMENT '獲得量',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='トークン鍛冶履歴';

-- ===============================================
-- 装備作成履歴テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS equipment_craft_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    equipment_id BIGINT UNSIGNED NULL COMMENT '作成された装備ID（失敗時はNULL）',
    rarity VARCHAR(20) NOT NULL COMMENT '作成しようとしたレアリティ',
    success BOOLEAN NOT NULL COMMENT '成功したかどうか',
    token_used VARCHAR(20) NOT NULL COMMENT '消費したトークン種類',
    coins_used INT NOT NULL COMMENT '消費したコイン',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='装備作成履歴';

-- ===============================================
-- トークン履歴テーブル（ドロップ記録用）
-- ===============================================
CREATE TABLE IF NOT EXISTS token_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_type VARCHAR(20) NOT NULL COMMENT 'トークン種類（normal, rare, unique, legend, epic, hero, mythic）',
    amount INT NOT NULL COMMENT '数量（正=獲得、負=消費）',
    reason VARCHAR(50) NOT NULL COMMENT '理由（focus_success, focus_fail, post, quote, reply, like, repost, forge等）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_reason (reason)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='トークン履歴';

-- テーブル作成完了メッセージ
SELECT 'Equipment and Token system schema created successfully' AS status;
