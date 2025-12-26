-- ===============================================
-- MiniBird Feature Enhancement Schema
-- 機能拡張用テーブル定義
-- ===============================================

-- ユーザーテーブルに最終操作時刻カラムを追加（オンライン状態表示用）
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS last_activity DATETIME NULL DEFAULT NULL COMMENT '最終操作時刻';

-- インデックスを追加（オンラインユーザー検索用）
CREATE INDEX IF NOT EXISTS idx_users_last_activity ON users(last_activity);

-- 装備作成履歴テーブル（既存）
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

-- 装備アップグレード履歴テーブル（既存）
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

-- トークン鍛冶履歴テーブル（既存）
CREATE TABLE IF NOT EXISTS token_forge_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    from_type VARCHAR(50) NOT NULL COMMENT '変換元トークン種類',
    from_amount INT NOT NULL COMMENT '消費量',
    to_type VARCHAR(50) NOT NULL COMMENT '変換先トークン種類',
    to_amount INT NOT NULL DEFAULT 1 COMMENT '獲得量',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='トークン鍛冶履歴';

-- ユーザーごとの集中タスク統計（既存）
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
    UNIQUE KEY unique_user (user_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザーごとの集中タスク統計';

-- バフテーブル（集中タイマーの報酬バフ用）
CREATE TABLE IF NOT EXISTS buffs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL COMMENT 'ユーザーID（NULLの場合は全体バフ）',
    type VARCHAR(50) NOT NULL COMMENT 'バフタイプ（task, chat_festival等）',
    level INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'バフレベル（10回まで重ねがけ可能）',
    start_time DATETIME NOT NULL COMMENT 'バフ開始時刻',
    end_time DATETIME NOT NULL COMMENT 'バフ終了時刻',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_time (start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='バフ情報';

-- テーブル作成完了メッセージ
SELECT 'Feature enhancement schema created successfully' AS status;
