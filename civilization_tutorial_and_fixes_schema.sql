-- ===============================================
-- MiniBird 文明育成システム 修正とチュートリアル追加
-- 1. 硫黄と石炭の生産建物追加
-- 2. 兵士訓練・治療時の追加資源消費
-- 3. チュートリアルシステム
-- ===============================================

USE microblog;

-- ===============================================
-- 1. 硫黄と石炭が表示されない問題の修正
-- 資源がデータベースに存在することを確認
-- ===============================================

-- 硫黄と石炭の資源を追加（存在しなければ）
INSERT IGNORE INTO civilization_resource_types (resource_key, name, icon, description, unlock_order, color) VALUES
('sulfur', '硫黄', '🔶', '火薬と爆発物に使用', 3, '#FFFF00'),
('coal', '石炭', '⬛', '産業と鍛冶に使用', 4, '#36454F');

-- ===============================================
-- 2. 硫黄と石炭を生産する建物の追加
-- ===============================================

-- 硫黄鉱山を追加
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
('sulfur_mine', '硫黄鉱山', '🔶', '硫黄を採掘する', 'production', NULL, 3.0, 10, 3, 2000, '{"stone": 150, "iron": 50}', 1500, 0, 0);

-- 炭鉱を追加
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
('coal_mine', '炭鉱', '⬛', '石炭を採掘する', 'production', NULL, 4.0, 10, 4, 2500, '{"stone": 200, "iron": 80}', 1800, 0, 0);

-- 硫黄鉱山の生産資源IDを設定
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'sulfur' LIMIT 1
)
WHERE building_key = 'sulfur_mine' AND produces_resource_id IS NULL;

-- 炭鉱の生産資源IDを設定
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1
)
WHERE building_key = 'coal_mine' AND produces_resource_id IS NULL;

-- ===============================================
-- 3. 硫黄と石炭をアンロックする研究の追加
-- ===============================================
INSERT IGNORE INTO civilization_researches (research_key, name, icon, description, era_id, unlock_building_id, unlock_resource_id, research_cost_points, research_time_seconds, prerequisite_research_id) VALUES
('sulfur_mining', '硫黄採掘', '🔶', '硫黄を採掘する技術を学ぶ', 3, NULL, NULL, 180, 900, NULL),
('coal_mining', '石炭採掘', '⬛', '石炭を採掘する技術を学ぶ', 4, NULL, NULL, 250, 1200, NULL);

-- 研究に資源アンロックを設定
UPDATE civilization_researches 
SET unlock_resource_id = (SELECT id FROM civilization_resource_types WHERE resource_key = 'sulfur' LIMIT 1)
WHERE research_key = 'sulfur_mining' AND unlock_resource_id IS NULL;

UPDATE civilization_researches 
SET unlock_resource_id = (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1)
WHERE research_key = 'coal_mining' AND unlock_resource_id IS NULL;

-- ===============================================
-- 4. 既存ユーザーへの硫黄・石炭のアンロック
-- ===============================================
-- 鉄器時代以上のユーザーには硫黄をアンロック
INSERT IGNORE INTO user_civilization_resources (user_id, resource_type_id, amount, unlocked, unlocked_at)
SELECT uc.user_id, rt.id, 0, TRUE, NOW()
FROM user_civilizations uc
CROSS JOIN civilization_resource_types rt
WHERE rt.resource_key = 'sulfur'
  AND uc.current_era_id >= 3;

-- 中世以上のユーザーには石炭をアンロック
INSERT IGNORE INTO user_civilization_resources (user_id, resource_type_id, amount, unlocked, unlocked_at)
SELECT uc.user_id, rt.id, 0, TRUE, NOW()
FROM user_civilizations uc
CROSS JOIN civilization_resource_types rt
WHERE rt.resource_key = 'coal'
  AND uc.current_era_id >= 4;

-- ===============================================
-- 5. チュートリアルシステム用テーブル
-- ===============================================

-- チュートリアルクエストマスター
CREATE TABLE IF NOT EXISTS civilization_tutorial_quests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quest_key VARCHAR(50) NOT NULL UNIQUE,
    quest_order INT UNSIGNED NOT NULL COMMENT 'クエストの順序',
    title VARCHAR(100) NOT NULL COMMENT 'クエストタイトル',
    description TEXT COMMENT 'クエストの説明',
    icon VARCHAR(50) NOT NULL DEFAULT '📜',
    quest_type ENUM('build', 'train', 'research', 'invest', 'collect', 'attack', 'era', 'alliance') NOT NULL COMMENT 'クエストタイプ',
    target_key VARCHAR(50) NULL COMMENT '対象のキー（建物キー、兵種キーなど）',
    target_count INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '必要数',
    reward_coins INT UNSIGNED NOT NULL DEFAULT 0,
    reward_crystals INT UNSIGNED NOT NULL DEFAULT 0,
    reward_diamonds INT UNSIGNED NOT NULL DEFAULT 0,
    reward_resources JSON COMMENT '報酬資源 {"food": 100, "wood": 50}',
    is_final BOOLEAN NOT NULL DEFAULT FALSE COMMENT '最終クエストかどうか',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_quest_order (quest_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='チュートリアルクエストマスター';

-- ユーザーチュートリアル進捗
CREATE TABLE IF NOT EXISTS user_civilization_tutorial_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    current_quest_id INT UNSIGNED NULL COMMENT '現在のクエストID',
    is_tutorial_completed BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'チュートリアル完了フラグ',
    tutorial_completed_at DATETIME NULL COMMENT 'チュートリアル完了日時',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (current_quest_id) REFERENCES civilization_tutorial_quests(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザーチュートリアル進捗';

-- ユーザーチュートリアル達成済みクエスト
CREATE TABLE IF NOT EXISTS user_civilization_tutorial_completed (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    quest_id INT UNSIGNED NOT NULL,
    completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quest_id) REFERENCES civilization_tutorial_quests(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_quest (user_id, quest_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー達成済みチュートリアルクエスト';

-- ===============================================
-- 6. チュートリアルクエストデータ投入
-- ===============================================
INSERT IGNORE INTO civilization_tutorial_quests (quest_key, quest_order, title, description, icon, quest_type, target_key, target_count, reward_coins, reward_crystals, reward_diamonds, reward_resources, is_final) VALUES
-- 基本編
('tutorial_invest', 1, '文明への投資', 'コインを投資して研究ポイントを獲得しましょう。1000コイン以上を投資してください。', '💰', 'invest', NULL, 1000, 500, 0, 0, '{"food": 50, "wood": 50}', FALSE),
('tutorial_build_hut', 2, '最初の住居', '小屋を建設して人口を増やしましょう。', '🛖', 'build', 'hut', 1, 300, 0, 0, '{"food": 30}', FALSE),
('tutorial_build_hunting', 3, '食料の確保', '狩場を建設して食料を生産しましょう。', '🏹', 'build', 'hunting_ground', 1, 400, 0, 0, '{"wood": 30}', FALSE),
('tutorial_research', 4, '技術の発展', '何か一つ研究を完了させましょう。', '📚', 'research', NULL, 1, 500, 5, 0, NULL, FALSE),
('tutorial_train_warrior', 5, '戦士の訓練', '戦士を5体訓練しましょう。', '⚔️', 'train', 'warrior', 5, 600, 0, 0, '{"food": 100}', FALSE),
-- 発展編
('tutorial_build_farm', 6, '農業の開始', '農場を建設して効率的に食料を生産しましょう。', '🌾', 'build', 'farm', 1, 800, 5, 0, '{"bronze": 20}', FALSE),
('tutorial_build_barracks', 7, '軍事施設', '兵舎を建設して兵士を訓練できるようにしましょう。', '⚔️', 'build', 'barracks', 1, 1000, 10, 0, NULL, FALSE),
('tutorial_train_cavalry', 8, '騎兵の編成', '騎兵を3体訓練しましょう。', '🐎', 'train', 'cavalry', 3, 1500, 10, 0, '{"iron": 30}', FALSE),
('tutorial_advance_era', 9, '時代の進化', '青銅器時代に進化しましょう。', '🔶', 'era', 'bronze_age', 1, 2000, 20, 0, NULL, FALSE),
-- 最終クエスト（チュートリアル完了）
('tutorial_complete', 10, 'チュートリアル完了！', 'おめでとうございます！チュートリアルを完了しました。豪華報酬を受け取りましょう！', '🎉', 'collect', NULL, 1, 100000, 100000, 100000, NULL, TRUE);

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'Tutorial and resource fixes schema applied successfully' AS status;
