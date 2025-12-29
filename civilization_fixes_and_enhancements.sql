-- ===============================================
-- MiniBird 文明育成システム 修正と拡張
-- 1. ガラスや馬といった一部資源が表示されない問題の修正
-- 2. バランス調整（高コスト兵の上方修正）
-- 3. ステルス兵種の設定
-- 4. 占領戦マップの複雑化（移動、地形バフを設定）
-- ===============================================

USE microblog;

-- ===============================================
-- 1. 不足している資源タイプの追加
-- ===============================================
-- 馬とガラスがまだなければ追加（冪等性を確保）
INSERT IGNORE INTO civilization_resource_types (resource_key, name, icon, description, unlock_order, color) VALUES
('horses', '馬', '🐴', '騎兵と輸送に使用', 2, '#8B4513'),
('glass', 'ガラス', '🔮', '窓と科学機器に使用', 3, '#ADD8E6'),
('herbs', '薬草', '🌿', '医薬品の原料', 3, '#228B22'),
('medicine', '医薬品', '💊', '負傷兵の治療に使用', 4, '#FF6B6B'),
('steel', '鋼鉄', '🗡️', '強力な武器と防具に使用', 4, '#708090'),
('gunpowder_res', '火薬資源', '💥', '火器の生産に必要', 5, '#FF4500'),
('electronics', '電子部品', '🔌', '現代兵器に必要', 6, '#00CED1');

-- ===============================================
-- 2. 馬とガラスを生産する建物の追加
-- ===============================================
-- 厩舎に馬の生産を追加（既に存在する場合は更新）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'horses' LIMIT 1
),
production_rate = 2.0
WHERE building_key = 'stable' AND produces_resource_id IS NULL;

-- ガラス工房がガラスを生産するように更新
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'glass' LIMIT 1
),
production_rate = 4.0,
category = 'production'
WHERE building_key = 'glassworks';

-- 薬草園が薬草を生産するように追加（存在しなければ）
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
('herb_garden', '薬草園', '🌿', '薬草を栽培する', 'production', NULL, 8.0, 10, 3, 1500, '{"wood": 80, "food": 50}', 1200, 0, 0);

-- 薬草園の生産資源IDを設定
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'herbs' LIMIT 1
)
WHERE building_key = 'herb_garden' AND produces_resource_id IS NULL;

-- 牧場（馬の生産施設）を追加
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
('ranch', '牧場', '🐎', '馬を飼育する', 'production', NULL, 3.0, 10, 2, 1200, '{"wood": 60, "food": 80}', 900, 0, 0);

-- 牧場の生産資源IDを設定
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'horses' LIMIT 1
)
WHERE building_key = 'ranch' AND produces_resource_id IS NULL;

-- ===============================================
-- 3. 馬とガラスをアンロックする研究の追加
-- ===============================================
INSERT IGNORE INTO civilization_researches (research_key, name, icon, description, era_id, unlock_building_id, unlock_resource_id, research_cost_points, research_time_seconds, prerequisite_research_id) VALUES
('animal_husbandry', '畜産', '🐴', '馬を飼育する技術を学ぶ', 2, NULL, NULL, 80, 480, NULL),
('glassmaking', 'ガラス製造', '🔮', 'ガラスを製造する技術', 3, NULL, NULL, 150, 720, NULL),
('herbal_medicine', '薬草学', '🌿', '薬草の利用法を学ぶ', 3, NULL, NULL, 120, 600, NULL);

-- 研究に資源アンロックを設定
UPDATE civilization_researches 
SET unlock_resource_id = (SELECT id FROM civilization_resource_types WHERE resource_key = 'horses' LIMIT 1)
WHERE research_key = 'animal_husbandry' AND unlock_resource_id IS NULL;

UPDATE civilization_researches 
SET unlock_resource_id = (SELECT id FROM civilization_resource_types WHERE resource_key = 'glass' LIMIT 1)
WHERE research_key = 'glassmaking' AND unlock_resource_id IS NULL;

UPDATE civilization_researches 
SET unlock_resource_id = (SELECT id FROM civilization_resource_types WHERE resource_key = 'herbs' LIMIT 1)
WHERE research_key = 'herbal_medicine' AND unlock_resource_id IS NULL;

-- ===============================================
-- 4. 既存ユーザーへの資源アンロック
-- ===============================================
-- 青銅器時代以上のユーザーには馬をアンロック
INSERT IGNORE INTO user_civilization_resources (user_id, resource_type_id, amount, unlocked, unlocked_at)
SELECT uc.user_id, rt.id, 0, TRUE, NOW()
FROM user_civilizations uc
CROSS JOIN civilization_resource_types rt
WHERE rt.resource_key = 'horses'
  AND uc.current_era_id >= 2;

-- 鉄器時代以上のユーザーにはガラスと薬草をアンロック
INSERT IGNORE INTO user_civilization_resources (user_id, resource_type_id, amount, unlocked, unlocked_at)
SELECT uc.user_id, rt.id, 0, TRUE, NOW()
FROM user_civilizations uc
CROSS JOIN civilization_resource_types rt
WHERE rt.resource_key IN ('glass', 'herbs')
  AND uc.current_era_id >= 3;

-- ===============================================
-- 5. バランス調整：高コスト兵の体力、攻撃力、防御力を上方修正
-- ===============================================
-- 低コスト兵の若干の弱体化
UPDATE civilization_troop_types SET attack_power = 4, defense_power = 2, health_points = 40 WHERE troop_key = 'hunter';
UPDATE civilization_troop_types SET attack_power = 6, defense_power = 4, health_points = 60 WHERE troop_key = 'warrior';
UPDATE civilization_troop_types SET attack_power = 2, defense_power = 1, health_points = 25 WHERE troop_key = 'scout';

-- 中コスト兵のステータス維持/微調整
UPDATE civilization_troop_types SET attack_power = 10, defense_power = 8, health_points = 100 WHERE troop_key = 'spearman';
UPDATE civilization_troop_types SET attack_power = 15, defense_power = 6, health_points = 90 WHERE troop_key = 'chariot';
UPDATE civilization_troop_types SET attack_power = 6, defense_power = 4, health_points = 60 WHERE troop_key = 'militia';
UPDATE civilization_troop_types SET attack_power = 12, defense_power = 18, health_points = 130 WHERE troop_key = 'phalanx';

-- 高コスト兵の大幅上方修正
UPDATE civilization_troop_types SET attack_power = 25, defense_power = 20, health_points = 180 WHERE troop_key = 'swordsman';
UPDATE civilization_troop_types SET attack_power = 35, defense_power = 18, health_points = 160 WHERE troop_key = 'cavalry';
UPDATE civilization_troop_types SET attack_power = 22, defense_power = 10, health_points = 90 WHERE troop_key = 'archer';
UPDATE civilization_troop_types SET attack_power = 20, defense_power = 28, health_points = 180 WHERE troop_key = 'pikeman';
UPDATE civilization_troop_types SET attack_power = 60, defense_power = 50, health_points = 350 WHERE troop_key = 'war_elephant';

-- 中世兵種の強化
UPDATE civilization_troop_types SET attack_power = 55, defense_power = 45, health_points = 280 WHERE troop_key = 'knight';
UPDATE civilization_troop_types SET attack_power = 40, defense_power = 20, health_points = 120 WHERE troop_key = 'crossbowman';
UPDATE civilization_troop_types SET attack_power = 70, defense_power = 15, health_points = 100 WHERE troop_key = 'catapult';
UPDATE civilization_troop_types SET attack_power = 45, defense_power = 15, health_points = 100 WHERE troop_key = 'longbowman';
UPDATE civilization_troop_types SET attack_power = 90, defense_power = 10, health_points = 80 WHERE troop_key = 'trebuchet';

-- ルネサンス兵種の強化
UPDATE civilization_troop_types SET attack_power = 60, defense_power = 30, health_points = 140 WHERE troop_key = 'musketeer';
UPDATE civilization_troop_types SET attack_power = 110, defense_power = 25, health_points = 130 WHERE troop_key = 'cannon';
UPDATE civilization_troop_types SET attack_power = 85, defense_power = 60, health_points = 350 WHERE troop_key = 'galleon';
UPDATE civilization_troop_types SET attack_power = 70, defense_power = 35, health_points = 150 WHERE troop_key = 'rifleman';
UPDATE civilization_troop_types SET attack_power = 75, defense_power = 40, health_points = 160 WHERE troop_key = 'dragoon';
UPDATE civilization_troop_types SET attack_power = 95, defense_power = 70, health_points = 300 WHERE troop_key = 'frigate';

-- 産業革命兵種の強化
UPDATE civilization_troop_types SET attack_power = 85, defense_power = 55, health_points = 200 WHERE troop_key = 'infantry';
UPDATE civilization_troop_types SET attack_power = 140, defense_power = 40, health_points = 160 WHERE troop_key = 'artillery';
UPDATE civilization_troop_types SET attack_power = 170, defense_power = 120, health_points = 500 WHERE troop_key = 'ironclad';
UPDATE civilization_troop_types SET attack_power = 100, defense_power = 70, health_points = 220 WHERE troop_key = 'marine';

-- 現代兵種の大幅強化
UPDATE civilization_troop_types SET attack_power = 220, defense_power = 150, health_points = 600 WHERE troop_key = 'tank';
UPDATE civilization_troop_types SET attack_power = 250, defense_power = 80, health_points = 350 WHERE troop_key = 'fighter';
UPDATE civilization_troop_types SET attack_power = 350, defense_power = 50, health_points = 300 WHERE troop_key = 'bomber';
UPDATE civilization_troop_types SET attack_power = 280, defense_power = 100, health_points = 450 WHERE troop_key = 'submarine';
UPDATE civilization_troop_types SET attack_power = 130, defense_power = 60, health_points = 200 WHERE troop_key = 'paratroopers';
UPDATE civilization_troop_types SET attack_power = 180, defense_power = 120, health_points = 300 WHERE troop_key = 'special_forces';
UPDATE civilization_troop_types SET attack_power = 300, defense_power = 30, health_points = 120 WHERE troop_key = 'missile_launcher';
UPDATE civilization_troop_types SET attack_power = 320, defense_power = 100, health_points = 400 WHERE troop_key = 'stealth_fighter';
UPDATE civilization_troop_types SET attack_power = 450, defense_power = 300, health_points = 900 WHERE troop_key = 'aircraft_carrier';
UPDATE civilization_troop_types SET attack_power = 400, defense_power = 200, health_points = 650 WHERE troop_key = 'nuclear_submarine';

-- ===============================================
-- 6. ステルス兵種の設定
-- ===============================================
-- ステルスカラムの追加
ALTER TABLE civilization_troop_types
ADD COLUMN IF NOT EXISTS is_stealth BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'ステルス兵種（敵から見えない）' AFTER troop_category;

-- ステルス兵種を設定
UPDATE civilization_troop_types SET is_stealth = TRUE WHERE troop_key = 'scout';
UPDATE civilization_troop_types SET is_stealth = TRUE WHERE troop_key = 'special_forces';
UPDATE civilization_troop_types SET is_stealth = TRUE WHERE troop_key = 'stealth_fighter';
UPDATE civilization_troop_types SET is_stealth = TRUE WHERE troop_key = 'nuclear_submarine';
UPDATE civilization_troop_types SET is_stealth = TRUE WHERE troop_key = 'submarine';

-- ===============================================
-- 7. 占領戦マップの地形バフシステム
-- ===============================================
-- 城に地形タイプを追加
ALTER TABLE conquest_castles
ADD COLUMN IF NOT EXISTS terrain_type ENUM('plains', 'forest', 'mountain', 'river', 'coastal', 'fortress') NOT NULL DEFAULT 'plains' COMMENT '地形タイプ' AFTER icon,
ADD COLUMN IF NOT EXISTS movement_cost INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '移動コスト（時間係数）' AFTER terrain_type,
ADD COLUMN IF NOT EXISTS terrain_defense_bonus DECIMAL(3,2) NOT NULL DEFAULT 1.00 COMMENT '地形防御ボーナス' AFTER movement_cost;

-- 地形バフテーブルの作成
CREATE TABLE IF NOT EXISTS conquest_terrain_buffs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    terrain_type VARCHAR(50) NOT NULL,
    troop_category VARCHAR(50) NOT NULL COMMENT '兵種カテゴリ（infantry, cavalry, ranged, siege, naval）',
    attack_buff DECIMAL(3,2) NOT NULL DEFAULT 1.00 COMMENT '攻撃力バフ倍率',
    defense_buff DECIMAL(3,2) NOT NULL DEFAULT 1.00 COMMENT '防御力バフ倍率',
    description VARCHAR(255),
    UNIQUE KEY unique_terrain_category (terrain_type, troop_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='地形と兵種の相性バフ';

-- 地形バフデータを挿入
INSERT IGNORE INTO conquest_terrain_buffs (terrain_type, troop_category, attack_buff, defense_buff, description) VALUES
-- 平原：騎兵有利
('plains', 'cavalry', 1.25, 1.10, '平原では騎兵が有利'),
('plains', 'infantry', 1.00, 1.00, '平原では歩兵は普通'),
('plains', 'ranged', 1.05, 0.95, '平原では射程兵は攻撃やや有利、防御やや不利'),
('plains', 'siege', 1.00, 0.90, '平原では攻城兵器は防御不利'),

-- 森林：歩兵と遠距離有利、騎兵不利
('forest', 'infantry', 1.20, 1.20, '森林では歩兵が有利'),
('forest', 'ranged', 1.15, 1.15, '森林では射程兵が有利'),
('forest', 'cavalry', 0.80, 0.85, '森林では騎兵が不利'),
('forest', 'siege', 0.70, 0.80, '森林では攻城兵器が不利'),

-- 山岳：遠距離と歩兵有利、騎兵と攻城不利
('mountain', 'infantry', 1.15, 1.25, '山岳では歩兵の防御が有利'),
('mountain', 'ranged', 1.30, 1.20, '山岳では射程兵が非常に有利'),
('mountain', 'cavalry', 0.70, 0.75, '山岳では騎兵が大幅不利'),
('mountain', 'siege', 0.60, 0.70, '山岳では攻城兵器が大幅不利'),

-- 河川：歩兵不利、遠距離やや有利
('river', 'infantry', 0.90, 0.85, '河川では歩兵が不利'),
('river', 'cavalry', 0.85, 0.80, '河川では騎兵が不利'),
('river', 'ranged', 1.10, 1.00, '河川では射程兵の攻撃が有利'),
('river', 'siege', 0.80, 0.75, '河川では攻城兵器が不利'),

-- 沿岸：海軍系（siege扱い）有利
('coastal', 'infantry', 0.95, 0.95, '沿岸では歩兵がやや不利'),
('coastal', 'cavalry', 0.90, 0.85, '沿岸では騎兵が不利'),
('coastal', 'ranged', 1.05, 1.00, '沿岸では射程兵はほぼ普通'),
('coastal', 'siege', 1.30, 1.20, '沿岸では海軍・攻城兵器が有利'),

-- 要塞：防御全体有利、攻城兵器の攻撃が有利
('fortress', 'infantry', 1.00, 1.30, '要塞では歩兵の防御が大幅有利'),
('fortress', 'cavalry', 0.90, 1.10, '要塞では騎兵の攻撃が不利、防御は有利'),
('fortress', 'ranged', 1.10, 1.25, '要塞では射程兵の防御が有利'),
('fortress', 'siege', 1.40, 0.90, '要塞では攻城兵器の攻撃が大幅有利、防御は不利');

-- ===============================================
-- 8. 占領戦の移動システム
-- ===============================================
-- 移動キューテーブル
CREATE TABLE IF NOT EXISTS conquest_movement_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    season_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    from_castle_id INT UNSIGNED NOT NULL,
    to_castle_id INT UNSIGNED NOT NULL,
    troops JSON NOT NULL COMMENT '移動中の兵士 [{troop_type_id, count}, ...]',
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    arrives_at DATETIME NOT NULL,
    is_completed BOOLEAN NOT NULL DEFAULT FALSE,
    is_cancelled BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_arrives (arrives_at, is_completed),
    INDEX idx_season (season_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='占領戦移動キュー';

-- 基本移動時間定数（秒）
-- 外周→中間: 300秒（5分）
-- 中間→内周: 600秒（10分）
-- 内周→神城: 900秒（15分）

-- 既存の城に地形タイプを設定（ランダムに割り当て）
UPDATE conquest_castles SET terrain_type = 'plains', movement_cost = 1, terrain_defense_bonus = 1.00 WHERE castle_type = 'outer' AND position_x = 0;
UPDATE conquest_castles SET terrain_type = 'forest', movement_cost = 2, terrain_defense_bonus = 1.15 WHERE castle_type = 'outer' AND position_x = 4;
UPDATE conquest_castles SET terrain_type = 'coastal', movement_cost = 1, terrain_defense_bonus = 1.05 WHERE castle_type = 'outer' AND position_y = 0;
UPDATE conquest_castles SET terrain_type = 'mountain', movement_cost = 3, terrain_defense_bonus = 1.25 WHERE castle_type = 'outer' AND position_y = 4;

UPDATE conquest_castles SET terrain_type = 'river', movement_cost = 2, terrain_defense_bonus = 1.10 WHERE castle_type = 'middle';
UPDATE conquest_castles SET terrain_type = 'fortress', movement_cost = 2, terrain_defense_bonus = 1.30 WHERE castle_type = 'inner';
UPDATE conquest_castles SET terrain_type = 'fortress', movement_cost = 3, terrain_defense_bonus = 1.50 WHERE castle_type = 'sacred';

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'Civilization fixes and enhancements applied successfully' AS status;
