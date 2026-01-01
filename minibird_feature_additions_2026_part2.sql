-- ===============================================
-- MiniBird 機能追加 2026 Part 2
-- ④ 不足している資源・施設・研究・兵士の追加
-- ⑤ 兵種ユニークスキルのダメージ/バフ/デバフ追跡
-- ===============================================

USE microblog;

-- ===============================================
-- ④ 不足している資源タイプの追加
-- ===============================================

-- 包帯（bandages）を追加
INSERT IGNORE INTO civilization_resource_types (resource_key, name, icon, description, unlock_order, color) VALUES
('bandages', '包帯', '🩹', '負傷した兵士を回復するために必要', 4, '#F5F5DC'),
('rubber', 'ゴム', '⚫', '工業製品に必要な弾性素材', 9, '#2F4F4F'),
('titanium', 'チタン', '🔩', '航空・宇宙産業に必要な軽量金属', 10, '#708090');

-- ===============================================
-- 不足している資源を生産する施設の追加
-- ===============================================

-- 包帯を生産する施設
INSERT IGNORE INTO civilization_building_types 
(building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) 
SELECT 
    'bandage_factory', '包帯工場', '🩹', '負傷兵の回復に使う包帯を生産', 'production',
    (SELECT id FROM civilization_resource_types WHERE resource_key = 'bandages' LIMIT 1),
    50.0, 10, 3, 5000, '{"cloth": 50, "herbs": 30}', 1800, 0, 0
WHERE NOT EXISTS (SELECT 1 FROM civilization_building_types WHERE building_key = 'bandage_factory');

-- ゴムを生産する施設
INSERT IGNORE INTO civilization_building_types 
(building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) 
SELECT 
    'rubber_plantation', 'ゴム農園', '🌴', 'ゴムの木を栽培してゴムを生産', 'production',
    (SELECT id FROM civilization_resource_types WHERE resource_key = 'rubber' LIMIT 1),
    30.0, 10, 6, 50000, '{"wood": 500, "food": 300}', 7200, 0, 0
WHERE NOT EXISTS (SELECT 1 FROM civilization_building_types WHERE building_key = 'rubber_plantation');

-- チタンを生産する施設
INSERT IGNORE INTO civilization_building_types 
(building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) 
SELECT 
    'titanium_mine', 'チタン鉱山', '🔩', 'チタン鉱石を採掘・精錬', 'production',
    (SELECT id FROM civilization_resource_types WHERE resource_key = 'titanium' LIMIT 1),
    20.0, 10, 7, 80000, '{"iron": 800, "stone": 1000}', 14400, 0, 0
WHERE NOT EXISTS (SELECT 1 FROM civilization_building_types WHERE building_key = 'titanium_mine');

-- ===============================================
-- 不足している資源をアンロックする前提研究の追加
-- ===============================================

-- 包帯のアンロック研究
INSERT IGNORE INTO civilization_researches 
(research_key, name, icon, description, era_id, unlock_building_id, unlock_resource_id, research_cost_points, research_time_seconds, prerequisite_research_id)
SELECT 
    'medical_supplies', '医療物資学', '🩹', '包帯などの医療物資の製造技術を研究',
    3,
    (SELECT id FROM civilization_building_types WHERE building_key = 'bandage_factory' LIMIT 1),
    (SELECT id FROM civilization_resource_types WHERE resource_key = 'bandages' LIMIT 1),
    500, 1800, NULL
WHERE NOT EXISTS (SELECT 1 FROM civilization_researches WHERE research_key = 'medical_supplies');

-- ゴムのアンロック研究
INSERT IGNORE INTO civilization_researches 
(research_key, name, icon, description, era_id, unlock_building_id, unlock_resource_id, research_cost_points, research_time_seconds, prerequisite_research_id)
SELECT 
    'rubber_cultivation', 'ゴム栽培', '🌴', 'ゴムの木の栽培とゴム精製技術',
    6,
    (SELECT id FROM civilization_building_types WHERE building_key = 'rubber_plantation' LIMIT 1),
    (SELECT id FROM civilization_resource_types WHERE resource_key = 'rubber' LIMIT 1),
    3000, 7200, NULL
WHERE NOT EXISTS (SELECT 1 FROM civilization_researches WHERE research_key = 'rubber_cultivation');

-- チタンのアンロック研究
INSERT IGNORE INTO civilization_researches 
(research_key, name, icon, description, era_id, unlock_building_id, unlock_resource_id, research_cost_points, research_time_seconds, prerequisite_research_id)
SELECT 
    'titanium_metallurgy', 'チタン冶金学', '🔩', 'チタンの採掘と精錬技術',
    7,
    (SELECT id FROM civilization_building_types WHERE building_key = 'titanium_mine' LIMIT 1),
    (SELECT id FROM civilization_resource_types WHERE resource_key = 'titanium' LIMIT 1),
    5000, 14400, NULL
WHERE NOT EXISTS (SELECT 1 FROM civilization_researches WHERE research_key = 'titanium_metallurgy');

-- ===============================================
-- 資源値の追加（civilization_api.phpで使用）
-- この値はPHPコードで手動で更新する必要があります
-- ===============================================
-- bandages: 1.5
-- rubber: 2.5
-- titanium: 4.0

-- ===============================================
-- ⑤ 兵種ユニークスキルのダメージ/バフ/デバフ追跡用テーブル
-- ===============================================

-- バトルスキル効果ログテーブル
CREATE TABLE IF NOT EXISTS battle_skill_effect_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    battle_type ENUM('conquest', 'wandering_monster', 'world_boss', 'portal_boss', 'war') NOT NULL COMMENT '戦闘タイプ',
    battle_id BIGINT UNSIGNED NOT NULL COMMENT '戦闘ID（各戦闘ログテーブルのID）',
    turn_number INT UNSIGNED NOT NULL,
    user_id INT NOT NULL COMMENT 'スキル使用者のユーザーID',
    troop_type_id INT UNSIGNED NULL COMMENT '兵種ID（NULLならヒーロースキル）',
    skill_id INT UNSIGNED NULL COMMENT 'battle_special_skills.id',
    skill_name VARCHAR(100) NOT NULL,
    skill_icon VARCHAR(50),
    effect_type ENUM('damage', 'buff', 'debuff', 'heal', 'special') NOT NULL,
    effect_target ENUM('self', 'enemy', 'ally', 'all') NOT NULL DEFAULT 'enemy',
    effect_value DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '効果量（ダメージ量、バフ/デバフ%など）',
    effect_duration INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '効果持続ターン数',
    description TEXT COMMENT '効果の説明',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_battle (battle_type, battle_id),
    INDEX idx_user (user_id),
    INDEX idx_troop (troop_type_id),
    INDEX idx_skill (skill_id),
    INDEX idx_turn (battle_type, battle_id, turn_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='戦闘中のスキル効果ログ';

-- ユーザー兵士スキル統計テーブル（兵士タブ表示用）
CREATE TABLE IF NOT EXISTS user_troop_skill_stats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    troop_type_id INT UNSIGNED NOT NULL,
    total_skill_activations INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'スキル発動回数',
    total_damage_dealt BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '与えたダメージ合計',
    total_buff_value BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'バフ効果合計',
    total_debuff_value BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'デバフ効果合計',
    total_heal_value BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '回復効果合計',
    last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (troop_type_id) REFERENCES civilization_troop_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_troop (user_id, troop_type_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー兵士スキル統計';

-- ===============================================
-- 既存の資源価値マップに新しい資源を追加
-- （これはcivilization_api.phpの$RESOURCE_VALUESに手動で追加する必要があります）
-- ===============================================

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'MiniBird feature additions 2026 Part 2 schema applied successfully' AS status;
