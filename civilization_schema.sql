-- ===============================================
-- MiniBird 文明育成システム（Home Quest風）
-- コイン投資 → 資源生産 → 施設研究 → 建物建設 → 文明発展
-- ===============================================

USE microblog;

-- ===============================================
-- 資源タイプマスター
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_resource_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resource_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    description TEXT,
    unlock_order INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'アンロック順序（0=最初から利用可能）',
    color VARCHAR(20) NOT NULL DEFAULT '#666666',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_unlock_order (unlock_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='資源タイプマスター';

-- ===============================================
-- 建物タイプマスター
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_building_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    building_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    description TEXT,
    category ENUM('production', 'housing', 'military', 'research', 'special') NOT NULL DEFAULT 'production',
    produces_resource_id INT UNSIGNED NULL COMMENT '生産する資源ID',
    production_rate DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '1時間あたりの生産量',
    max_level INT UNSIGNED NOT NULL DEFAULT 10,
    unlock_era_id INT UNSIGNED NULL COMMENT 'アンロックに必要な時代',
    base_build_cost_coins INT UNSIGNED NOT NULL DEFAULT 0,
    base_build_cost_resources JSON COMMENT '建設に必要な資源 {"wood": 10, "stone": 5}',
    base_build_time_seconds INT UNSIGNED NOT NULL DEFAULT 60,
    population_capacity INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '住民収容人数（housingカテゴリ用）',
    military_power INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '軍事力（militaryカテゴリ用）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_unlock_era (unlock_era_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='建物タイプマスター';

-- ===============================================
-- 時代マスター
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_eras (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    era_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    description TEXT,
    era_order INT UNSIGNED NOT NULL COMMENT '時代の順序',
    unlock_population INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'アンロックに必要な人口',
    unlock_research_points INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'アンロックに必要な研究ポイント',
    color VARCHAR(20) NOT NULL DEFAULT '#666666',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_era_order (era_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='時代マスター';

-- ===============================================
-- 研究マスター
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_researches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    research_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    description TEXT,
    era_id INT UNSIGNED NOT NULL,
    unlock_building_id INT UNSIGNED NULL COMMENT 'アンロックする建物',
    unlock_resource_id INT UNSIGNED NULL COMMENT 'アンロックする資源',
    research_cost_points INT UNSIGNED NOT NULL DEFAULT 100,
    research_time_seconds INT UNSIGNED NOT NULL DEFAULT 300,
    prerequisite_research_id INT UNSIGNED NULL COMMENT '前提研究',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (era_id) REFERENCES civilization_eras(id) ON DELETE CASCADE,
    INDEX idx_era (era_id),
    INDEX idx_prerequisite (prerequisite_research_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='研究マスター';

-- ===============================================
-- ユーザー文明
-- ===============================================
CREATE TABLE IF NOT EXISTS user_civilizations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    civilization_name VARCHAR(100) NOT NULL DEFAULT '新しい文明',
    current_era_id INT UNSIGNED NOT NULL DEFAULT 1,
    population INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '現在の人口',
    max_population INT UNSIGNED NOT NULL DEFAULT 10 COMMENT '最大人口',
    research_points INT UNSIGNED NOT NULL DEFAULT 0,
    military_power INT UNSIGNED NOT NULL DEFAULT 0,
    total_invested_coins BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累計投資コイン',
    last_resource_collection DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (current_era_id) REFERENCES civilization_eras(id),
    UNIQUE KEY unique_user (user_id),
    INDEX idx_military_power (military_power),
    INDEX idx_population (population)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー文明';

-- ===============================================
-- ユーザー資源
-- ===============================================
CREATE TABLE IF NOT EXISTS user_civilization_resources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    resource_type_id INT UNSIGNED NOT NULL,
    amount DECIMAL(20,2) NOT NULL DEFAULT 0,
    unlocked BOOLEAN NOT NULL DEFAULT FALSE,
    unlocked_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_type_id) REFERENCES civilization_resource_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_resource (user_id, resource_type_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー資源';

-- ===============================================
-- ユーザー建物
-- ===============================================
CREATE TABLE IF NOT EXISTS user_civilization_buildings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    building_type_id INT UNSIGNED NOT NULL,
    level INT UNSIGNED NOT NULL DEFAULT 1,
    is_constructing BOOLEAN NOT NULL DEFAULT FALSE,
    construction_started_at DATETIME NULL,
    construction_completes_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (building_type_id) REFERENCES civilization_building_types(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_construction (is_constructing, construction_completes_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー建物';

-- ===============================================
-- ユーザー研究進捗
-- ===============================================
CREATE TABLE IF NOT EXISTS user_civilization_researches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    research_id INT UNSIGNED NOT NULL,
    is_completed BOOLEAN NOT NULL DEFAULT FALSE,
    is_researching BOOLEAN NOT NULL DEFAULT FALSE,
    research_started_at DATETIME NULL,
    research_completes_at DATETIME NULL,
    completed_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (research_id) REFERENCES civilization_researches(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_research (user_id, research_id),
    INDEX idx_user (user_id),
    INDEX idx_researching (is_researching, research_completes_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー研究進捗';

-- ===============================================
-- 戦争ログ
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_war_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attacker_user_id INT UNSIGNED NOT NULL,
    defender_user_id INT UNSIGNED NOT NULL,
    attacker_power INT UNSIGNED NOT NULL,
    defender_power INT UNSIGNED NOT NULL,
    winner_user_id INT UNSIGNED NOT NULL,
    loot_coins INT UNSIGNED NOT NULL DEFAULT 0,
    loot_resources JSON COMMENT '略奪した資源',
    battle_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attacker_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (defender_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (winner_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_attacker (attacker_user_id),
    INDEX idx_defender (defender_user_id),
    INDEX idx_battle_at (battle_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='戦争ログ';

-- ===============================================
-- 初期データ投入
-- ===============================================

-- 時代初期データ
INSERT IGNORE INTO civilization_eras (era_key, name, icon, description, era_order, unlock_population, unlock_research_points, color) VALUES
('stone_age', '石器時代', '🪨', '文明の夜明け。狩猟と採集で生き延びる。', 1, 0, 0, '#8B7355'),
('bronze_age', '青銅器時代', '🔶', '金属の発見。農業と貿易が始まる。', 2, 50, 100, '#CD7F32'),
('iron_age', '鉄器時代', '⚔️', '鉄の力で領土を広げる。', 3, 200, 500, '#4A5568'),
('medieval', '中世', '🏰', '城と騎士の時代。封建制度が栄える。', 4, 500, 1500, '#9370DB'),
('renaissance', 'ルネサンス', '🎨', '芸術と科学の復興。大航海時代の幕開け。', 5, 1000, 4000, '#DA70D6'),
('industrial', '産業革命', '🏭', '機械の力で世界を変える。', 6, 2500, 10000, '#708090'),
('modern', '現代', '🌆', '情報と技術の時代。', 7, 5000, 25000, '#4169E1');

-- 資源初期データ
INSERT IGNORE INTO civilization_resource_types (resource_key, name, icon, description, unlock_order, color) VALUES
('food', '食料', '🍖', '住民を養う基本資源', 0, '#8B4513'),
('wood', '木材', '🪵', '建設に必要な基本資源', 0, '#228B22'),
('stone', '石材', '🪨', '頑丈な建物に必要', 0, '#808080'),
('bronze', '青銅', '🔶', '道具と武器の素材', 1, '#CD7F32'),
('iron', '鉄', '⚙️', '強力な武器と建物に必要', 2, '#4A5568'),
('gold', '金', '💰', '貿易と高級品に使用', 3, '#FFD700'),
('knowledge', '知識', '📚', '研究と発展に必要', 4, '#4169E1'),
('oil', '石油', '🛢️', '産業と軍事に必要', 5, '#2F4F4F');

-- 建物初期データ
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
-- 石器時代
('hut', '小屋', '🛖', '住民が住む基本的な住居', 'housing', NULL, 0, 5, 1, 100, NULL, 60, 5, 0),
('hunting_ground', '狩場', '🏹', '食料を生産する', 'production', 1, 10, 5, 1, 150, NULL, 120, 0, 0),
('quarry', '採石場', '⛏️', '石材を採掘する', 'production', 3, 8, 5, 1, 200, '{"food": 20}', 180, 0, 0),
('lumber_camp', '伐採場', '🪓', '木材を生産する', 'production', 2, 12, 5, 1, 150, '{"food": 15}', 150, 0, 0),

-- 青銅器時代
('farm', '農場', '🌾', '効率的に食料を生産', 'production', 1, 25, 10, 2, 500, '{"wood": 50, "stone": 30}', 300, 0, 0),
('bronze_foundry', '青銅鋳造所', '🔥', '青銅を生産する', 'production', 4, 5, 10, 2, 800, '{"wood": 100, "stone": 80}', 600, 0, 0),
('house', '家', '🏠', 'より多くの住民が住める', 'housing', NULL, 0, 10, 2, 400, '{"wood": 40, "stone": 20}', 240, 15, 0),
('barracks', '兵舎', '⚔️', '兵士を訓練する', 'military', NULL, 0, 10, 2, 600, '{"wood": 60, "bronze": 20}', 360, 0, 20),

-- 鉄器時代
('iron_mine', '鉄鉱山', '⛏️', '鉄を採掘する', 'production', 5, 4, 15, 3, 1500, '{"stone": 200, "bronze": 50}', 900, 0, 0),
('blacksmith', '鍛冶場', '🔨', '武器と道具を作る', 'production', NULL, 0, 15, 3, 2000, '{"iron": 30, "stone": 100}', 1200, 0, 10),
('fortress', '要塞', '🏯', '防御力と軍事力を上げる', 'military', NULL, 0, 10, 3, 3000, '{"stone": 300, "iron": 50}', 1800, 0, 50),
('villa', '邸宅', '🏛️', '多くの住民と贅沢な生活', 'housing', NULL, 0, 10, 3, 2500, '{"stone": 150, "iron": 20, "bronze": 30}', 1500, 30, 0),

-- 中世
('castle', '城', '🏰', '王国の象徴', 'military', NULL, 0, 5, 4, 10000, '{"stone": 500, "iron": 100, "gold": 50}', 7200, 50, 200),
('gold_mine', '金鉱山', '💰', '金を採掘する', 'production', 6, 2, 10, 4, 5000, '{"iron": 100, "stone": 300}', 3600, 0, 0),
('library', '図書館', '📚', '知識を蓄積する', 'research', 7, 5, 10, 4, 4000, '{"wood": 200, "stone": 100, "gold": 20}', 2400, 0, 0),
('cathedral', '大聖堂', '⛪', '人々の心を豊かにする', 'special', NULL, 0, 5, 4, 8000, '{"stone": 400, "gold": 100}', 5400, 0, 0),

-- ルネサンス以降
('university', '大学', '🎓', '研究効率を上げる', 'research', 7, 15, 10, 5, 15000, '{"stone": 500, "gold": 100, "knowledge": 50}', 7200, 0, 0),
('factory', '工場', '🏭', '大量生産を可能にする', 'production', NULL, 0, 15, 6, 25000, '{"iron": 500, "oil": 100}', 10800, 0, 0),
('military_base', '軍事基地', '🎖️', '最強の軍事施設', 'military', NULL, 0, 10, 6, 50000, '{"iron": 800, "oil": 200}', 14400, 0, 500);

-- 研究初期データ
INSERT IGNORE INTO civilization_researches (research_key, name, icon, description, era_id, unlock_building_id, unlock_resource_id, research_cost_points, research_time_seconds, prerequisite_research_id) VALUES
-- 石器時代
('basic_tools', '基本道具', '🔧', '基本的な石器道具を作る', 1, NULL, NULL, 10, 60, NULL),
('fire', '火の利用', '🔥', '火を使いこなす', 1, NULL, NULL, 25, 120, 1),
('agriculture_basics', '農業の基礎', '🌱', '植物を育てる知識', 1, NULL, NULL, 50, 300, 2),

-- 青銅器時代
('bronze_working', '青銅加工', '🔶', '青銅を加工する技術', 2, 6, 4, 100, 600, 3),
('writing', '文字', '📜', '情報を記録する', 2, NULL, NULL, 150, 900, 4),
('military_training', '軍事訓練', '⚔️', '兵士を訓練する', 2, 8, NULL, 200, 1200, 4),

-- 鉄器時代
('iron_working', '鉄加工', '⚙️', '鉄を加工する技術', 3, 9, 5, 300, 1800, 6),
('engineering', '工学', '📐', '建築と機械の知識', 3, 11, NULL, 400, 2400, 7),
('philosophy', '哲学', '🤔', '知恵を深める', 3, NULL, 7, 350, 2100, 5),

-- 中世
('feudalism', '封建制度', '👑', '領土を統治する', 4, 13, NULL, 500, 3600, 8),
('banking', '銀行業', '🏦', '金融システムを確立', 4, 14, 6, 600, 4200, 10),
('theology', '神学', '⛪', '信仰を体系化する', 4, 16, NULL, 550, 3900, 9),

-- ルネサンス
('scientific_method', '科学的方法', '🔬', '体系的な研究方法', 5, 17, NULL, 800, 5400, 12),
('navigation', '航海術', '🧭', '海を渡る技術', 5, NULL, NULL, 750, 5100, 11),

-- 産業革命
('steam_power', '蒸気機関', '🚂', '蒸気の力を利用', 6, 18, 8, 1200, 7200, 13),
('electricity', '電気', '⚡', '電気を利用する', 6, NULL, NULL, 1500, 9000, 15);

-- テーブル作成完了メッセージ
SELECT 'Civilization system tables created successfully' AS status;
