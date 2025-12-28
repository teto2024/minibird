-- ===============================================
-- MiniBird 文明育成システム 戦争システム高度化スキーマ
-- 攻撃/防御兵士選択、負傷兵、病院、訓練キュー、占領戦
-- ===============================================

USE microblog;

-- ===============================================
-- 負傷兵テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS user_civilization_wounded_troops (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    troop_type_id INT UNSIGNED NOT NULL,
    count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '負傷兵の数',
    wounded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (troop_type_id) REFERENCES civilization_troop_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_wounded_troop (user_id, troop_type_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー負傷兵';

-- ===============================================
-- 治療キューテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS user_civilization_healing_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    troop_type_id INT UNSIGNED NOT NULL,
    count INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '治療中の兵士数',
    healing_started_at DATETIME NOT NULL,
    healing_completes_at DATETIME NOT NULL,
    building_id BIGINT UNSIGNED NULL COMMENT '使用している病院建物のID',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (troop_type_id) REFERENCES civilization_troop_types(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_completes (healing_completes_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='治療キュー';

-- ===============================================
-- 訓練キューテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS user_civilization_training_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    troop_type_id INT UNSIGNED NOT NULL,
    count INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '訓練中の兵士数',
    training_started_at DATETIME NOT NULL,
    training_completes_at DATETIME NOT NULL,
    building_id BIGINT UNSIGNED NULL COMMENT '使用している訓練施設のID',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (troop_type_id) REFERENCES civilization_troop_types(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_completes (training_completes_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='訓練キュー';

-- ===============================================
-- 防御部隊設定テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS user_civilization_defense_troops (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    troop_type_id INT UNSIGNED NOT NULL,
    assigned_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '防御に割り当てた兵士数',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (troop_type_id) REFERENCES civilization_troop_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_defense_troop (user_id, troop_type_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー防御部隊設定';

-- ===============================================
-- 占領戦シーズンテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS conquest_seasons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    season_number INT UNSIGNED NOT NULL COMMENT 'シーズン番号',
    started_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    winner_user_id INT UNSIGNED NULL COMMENT '勝者のユーザーID',
    winner_civilization_name VARCHAR(100) NULL COMMENT '勝者の文明名',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (winner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_active (is_active),
    INDEX idx_ends (ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='占領戦シーズン';

-- ===============================================
-- 占領戦マップ城テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS conquest_castles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    season_id INT UNSIGNED NOT NULL,
    castle_key VARCHAR(50) NOT NULL COMMENT '城の識別子',
    name VARCHAR(100) NOT NULL COMMENT '城の名前',
    position_x INT NOT NULL COMMENT 'マップ上のX座標',
    position_y INT NOT NULL COMMENT 'マップ上のY座標',
    castle_type ENUM('outer', 'middle', 'inner', 'sacred') NOT NULL DEFAULT 'outer' COMMENT '城の種類',
    is_sacred BOOLEAN NOT NULL DEFAULT FALSE COMMENT '神城かどうか',
    owner_user_id INT UNSIGNED NULL COMMENT '現在の占領者のユーザーID',
    npc_defense_power INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'NPC防御パワー（未占領時）',
    icon VARCHAR(50) NOT NULL DEFAULT '🏰',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES conquest_seasons(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_season_castle (season_id, castle_key),
    INDEX idx_season (season_id),
    INDEX idx_owner (owner_user_id),
    INDEX idx_position (position_x, position_y)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='占領戦マップ城';

-- ===============================================
-- 城隣接関係テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS conquest_castle_adjacency (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    castle_id INT UNSIGNED NOT NULL,
    adjacent_castle_id INT UNSIGNED NOT NULL,
    FOREIGN KEY (castle_id) REFERENCES conquest_castles(id) ON DELETE CASCADE,
    FOREIGN KEY (adjacent_castle_id) REFERENCES conquest_castles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_adjacency (castle_id, adjacent_castle_id),
    INDEX idx_castle (castle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='城隣接関係';

-- ===============================================
-- 城防御部隊テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS conquest_castle_defense (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    castle_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT '部隊を配置したユーザー',
    troop_type_id INT UNSIGNED NOT NULL,
    count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (castle_id) REFERENCES conquest_castles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (troop_type_id) REFERENCES civilization_troop_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_castle_user_troop (castle_id, user_id, troop_type_id),
    INDEX idx_castle (castle_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='城防御部隊';

-- ===============================================
-- 占領戦戦闘ログテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS conquest_battle_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    season_id INT UNSIGNED NOT NULL,
    castle_id INT UNSIGNED NOT NULL,
    attacker_user_id INT UNSIGNED NOT NULL,
    defender_user_id INT UNSIGNED NULL COMMENT 'NPC防御の場合はNULL',
    attacker_troops JSON NOT NULL COMMENT '攻撃側の部隊構成',
    defender_troops JSON NOT NULL COMMENT '防御側の部隊構成',
    attacker_power INT UNSIGNED NOT NULL,
    defender_power INT UNSIGNED NOT NULL,
    attacker_losses JSON COMMENT '攻撃側の損失',
    defender_losses JSON COMMENT '防御側の損失',
    attacker_wounded JSON COMMENT '攻撃側の負傷兵',
    defender_wounded JSON COMMENT '防御側の負傷兵',
    winner_user_id INT UNSIGNED NULL COMMENT '勝者（NPC勝利の場合はNULL）',
    castle_captured BOOLEAN NOT NULL DEFAULT FALSE,
    battle_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES conquest_seasons(id) ON DELETE CASCADE,
    FOREIGN KEY (castle_id) REFERENCES conquest_castles(id) ON DELETE CASCADE,
    FOREIGN KEY (attacker_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (defender_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_season (season_id),
    INDEX idx_castle (castle_id),
    INDEX idx_attacker (attacker_user_id),
    INDEX idx_battle_at (battle_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='占領戦戦闘ログ';

-- ===============================================
-- 戦争ログテーブルに負傷兵情報を追加
-- ===============================================
ALTER TABLE civilization_war_logs
ADD COLUMN IF NOT EXISTS attacker_troops_used JSON COMMENT '攻撃側が使用した部隊' AFTER loot_resources,
ADD COLUMN IF NOT EXISTS defender_troops_used JSON COMMENT '防御側が使用した部隊' AFTER attacker_troops_used,
ADD COLUMN IF NOT EXISTS attacker_losses JSON COMMENT '攻撃側の損失' AFTER defender_troops_used,
ADD COLUMN IF NOT EXISTS defender_losses JSON COMMENT '防御側の損失' AFTER attacker_losses,
ADD COLUMN IF NOT EXISTS attacker_wounded JSON COMMENT '攻撃側の負傷兵' AFTER defender_losses,
ADD COLUMN IF NOT EXISTS defender_wounded JSON COMMENT '防御側の負傷兵' AFTER attacker_wounded;

-- ===============================================
-- 兵種タイプにヒーリング時間を追加
-- ===============================================
ALTER TABLE civilization_troop_types
ADD COLUMN IF NOT EXISTS heal_time_seconds INT UNSIGNED NOT NULL DEFAULT 30 COMMENT '1体あたりの治療時間' AFTER train_time_seconds,
ADD COLUMN IF NOT EXISTS heal_cost_coins INT UNSIGNED NOT NULL DEFAULT 10 COMMENT '1体あたりの治療コイン' AFTER heal_time_seconds,
ADD COLUMN IF NOT EXISTS heal_cost_resources JSON COMMENT '治療に必要な資源' AFTER heal_cost_coins;

-- ===============================================
-- 追加建物タイプ（病院系）
-- ===============================================
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
('field_hospital', '野戦病院', '🏥', '負傷兵を治療する仮設施設。治療速度は低いが低コスト', 'military', NULL, 0, 10, 2, 500, '{"wood": 60, "food": 30}', 300, 0, 0),
('hospital', '病院', '🏨', '本格的な医療施設。負傷兵の治療速度が向上', 'military', NULL, 0, 10, 4, 5000, '{"stone": 200, "iron": 50, "gold": 20}', 3600, 0, 0),
('medical_center', '医療センター', '🏩', '最新の医療技術を備えた施設。治療速度と容量が大幅向上', 'military', NULL, 0, 5, 6, 20000, '{"stone": 400, "iron": 200, "oil": 100}', 14400, 0, 0);

-- ===============================================
-- 追加研究（医療系）
-- ===============================================
INSERT IGNORE INTO civilization_researches (research_key, name, icon, description, era_id, unlock_building_id, unlock_resource_id, research_cost_points, research_time_seconds, prerequisite_research_id) VALUES
('first_aid', '応急処置', '🩹', '負傷兵の治療時間を10%短縮', 2, NULL, NULL, 80, 480, NULL),
('surgery', '外科手術', '💉', '負傷兵の治療時間を20%短縮', 4, NULL, NULL, 400, 2400, NULL),
('advanced_medicine', '先進医療', '🔬', '負傷兵の治療時間を30%短縮', 6, NULL, NULL, 1000, 6000, NULL),
('battlefield_medicine', '戦場医療', '🚑', '戦闘後の負傷兵発生率を10%低下', 3, NULL, NULL, 250, 1500, NULL),
('combat_medics', '衛生兵', '👨‍⚕️', '戦闘後の負傷兵発生率を20%低下', 5, NULL, NULL, 700, 4200, NULL),
('regeneration_tech', '再生医療', '🧬', '負傷兵が一定確率で自動回復', 7, NULL, NULL, 2000, 12000, NULL);

-- ===============================================
-- 追加兵種
-- ===============================================
INSERT IGNORE INTO civilization_troop_types (troop_key, name, icon, description, unlock_era_id, attack_power, defense_power, health_points, troop_category, train_cost_coins, train_cost_resources, train_time_seconds, heal_time_seconds, heal_cost_coins) VALUES
-- 医療ユニット
('medic', '衛生兵', '🩺', '負傷兵の治療を補助する', 3, 5, 10, 60, 'infantry', 200, '{"food": 30}', 120, 20, 5),
('field_surgeon', '野戦外科医', '👨‍⚕️', '戦場で負傷兵を治療', 5, 8, 15, 80, 'infantry', 500, '{"food": 50, "knowledge": 10}', 300, 30, 10),

-- 追加攻城兵器
('siege_tower', '攻城塔', '🗼', '城壁を乗り越える移動塔', 4, 40, 60, 250, 'siege', 1200, '{"wood": 200, "iron": 50}', 900, 120, 50),
('battering_ram', '破城槌', '🪵', '城門を破壊する', 3, 60, 30, 200, 'siege', 800, '{"wood": 150, "iron": 30}', 600, 90, 40),

-- 追加特殊ユニット
('royal_guard', '親衛隊', '👑', '王を守る精鋭部隊', 4, 50, 45, 180, 'infantry', 1000, '{"food": 80, "iron": 40, "gold": 10}', 600, 80, 40),
('berserker', '狂戦士', '😤', '圧倒的な攻撃力を持つ', 3, 70, 15, 100, 'infantry', 600, '{"food": 60, "iron": 25}', 300, 50, 25);

-- ===============================================
-- 追加資源タイプ
-- ===============================================
INSERT IGNORE INTO civilization_resource_types (resource_key, name, icon, description, unlock_order, color) VALUES
('medicine', '医薬品', '💊', '負傷兵の治療に使用', 4, '#FF69B4'),
('bandages', '包帯', '🩹', '基本的な治療材料', 2, '#FFFFFF'),
('herbs', '薬草', '🌿', '治療薬の原料', 1, '#228B22'),
('steel', '鋼鉄', '⚙️', '高品質な武器と防具に使用', 4, '#708090'),
('gunpowder', '火薬', '💥', '火器と爆発物に使用', 5, '#2F4F4F'),
('rubber', 'ゴム', '⚫', '近代的な装備に使用', 6, '#1C1C1C'),
('electronics', '電子部品', '🔌', '現代技術に必要', 7, '#00BFFF'),
('titanium', 'チタン', '🔩', '軽量で強靭な金属', 7, '#C0C0C0');

-- ===============================================
-- 追加建物タイプ（追加生産系）
-- ===============================================
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
('herb_garden', '薬草園', '🌿', '薬草を栽培する', 'production', NULL, 0, 10, 2, 300, '{"wood": 30, "food": 20}', 180, 0, 0),
('apothecary', '調剤所', '⚗️', '医薬品を製造する', 'production', NULL, 0, 10, 4, 3000, '{"stone": 100, "herbs": 30}', 1800, 0, 0),
('steel_mill', '製鋼所', '🏭', '鋼鉄を生産する', 'production', NULL, 0, 10, 5, 8000, '{"iron": 200, "coal": 100}', 7200, 0, 0),
('gunpowder_factory', '火薬工場', '💥', '火薬を製造する', 'production', NULL, 0, 10, 5, 6000, '{"sulfur": 50, "coal": 50}', 5400, 0, 0),
('electronics_factory', '電子部品工場', '🔌', '電子部品を製造する', 'production', NULL, 0, 5, 7, 50000, '{"iron": 300, "oil": 200}', 28800, 0, 0);

-- テーブル作成完了メッセージ
SELECT 'War system advanced schema created successfully' AS status;
