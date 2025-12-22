-- ===============================================
-- Token System Schema
-- 7種類のトークンを追加
-- ===============================================

USE syugetsu2025_clone;

-- ユーザーテーブルに7種類のトークン列を追加
-- 既に存在する場合はスキップ

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS normal_tokens INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ノーマルトークン（灰色）',
ADD COLUMN IF NOT EXISTS rare_tokens INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'レアトークン（緑）',
ADD COLUMN IF NOT EXISTS unique_tokens INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ユニークトークン（青）',
ADD COLUMN IF NOT EXISTS legend_tokens INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'レジェンドトークン（黄色）',
ADD COLUMN IF NOT EXISTS epic_tokens INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'エピックトークン（紫）',
ADD COLUMN IF NOT EXISTS hero_tokens INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ヒーロートークン（赤）',
ADD COLUMN IF NOT EXISTS mythic_tokens INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ミシックトークン（虹色）';

-- インデックスを追加（トークンでのソートやフィルタリングを高速化）
CREATE INDEX IF NOT EXISTS idx_users_normal_tokens ON users(normal_tokens);
CREATE INDEX IF NOT EXISTS idx_users_rare_tokens ON users(rare_tokens);
CREATE INDEX IF NOT EXISTS idx_users_unique_tokens ON users(unique_tokens);
CREATE INDEX IF NOT EXISTS idx_users_legend_tokens ON users(legend_tokens);
CREATE INDEX IF NOT EXISTS idx_users_epic_tokens ON users(epic_tokens);
CREATE INDEX IF NOT EXISTS idx_users_hero_tokens ON users(hero_tokens);
CREATE INDEX IF NOT EXISTS idx_users_mythic_tokens ON users(mythic_tokens);

-- トークン履歴テーブル（トークンの獲得・使用履歴を記録）
CREATE TABLE IF NOT EXISTS token_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_type ENUM('normal', 'rare', 'unique', 'legend', 'epic', 'hero', 'mythic') NOT NULL,
    amount INT NOT NULL COMMENT '変動量（正の値は獲得、負の値は使用）',
    reason VARCHAR(255) COMMENT '理由（例: quest_reward, shop_purchase, daily_login）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_token (user_id, token_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='トークン獲得・使用履歴';

-- テスト用に全ユーザーに初期トークンを付与（オプション）
-- UPDATE users SET 
--     normal_tokens = 100,
--     rare_tokens = 10,
--     unique_tokens = 5,
--     legend_tokens = 2,
--     epic_tokens = 1,
--     hero_tokens = 0,
--     mythic_tokens = 0;

SELECT 'Token system schema created successfully' AS status;
