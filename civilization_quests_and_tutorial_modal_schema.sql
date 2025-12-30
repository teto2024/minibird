-- ===============================================
-- MiniBird 文明育成システム: クエストとチュートリアルモーダル
-- 1. チュートリアルはモーダル/ガイド付きでユーザーを誘導
-- 2. 上位兵種訓練時に馬、布、薬草、ガラス、石油、医薬品、硫黄、石炭を消費
-- 3. 文明ごとのクエスト（チュートリアル以外、モーダルガイド不要）
-- ===============================================

USE microblog;

-- ===============================================
-- 1. 追加資源タイプの確認（存在しなければ追加）
-- ===============================================
INSERT IGNORE INTO civilization_resource_types (resource_key, name, icon, description, unlock_order, color) VALUES
('cloth', '布', '🧵', '衣服や装備に使用する布地', 2, '#DEB887'),
('herbs', '薬草', '🌿', '治療と薬品に使用', 2, '#228B22'),
('horses', '馬', '🐎', '騎兵訓練に必要', 3, '#8B4513'),
('glass', 'ガラス', '🔮', '建設や装備に使用', 3, '#87CEEB'),
('medicine', '医薬品', '💊', '高度な治療に使用', 5, '#FF6B6B'),
('sulfur', '硫黄', '🔶', '火薬と爆発物に使用', 3, '#FFFF00'),
('coal', '石炭', '⬛', '産業と鍛冶に使用', 4, '#36454F'),
('oil', '石油', '🛢️', '産業と軍事に必要', 5, '#2F4F4F');

-- ===============================================
-- 2. 上位兵種の訓練コストに追加資源を設定
-- 騎兵系: 馬を消費
-- 遠距離系: 布を消費（マント、制服など）
-- 攻城系: 石油、石炭を消費
-- 全兵種（中世以降）: 治療時に薬草、医薬品を消費
-- ===============================================

-- 騎兵系に馬を追加
UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.horses', 2
)
WHERE troop_key = 'chariot' AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.horses') IS NULL);

UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.horses', 3
)
WHERE troop_key = 'cavalry' AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.horses') IS NULL);

UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.horses', 5
)
WHERE troop_key = 'knight' AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.horses') IS NULL);

UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.horses', 3
)
WHERE troop_key = 'dragoon' AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.horses') IS NULL);

-- 中世以降の兵種に布を追加（制服用）
UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.cloth', 2
)
WHERE troop_key IN ('crossbowman', 'longbowman', 'musketeer', 'rifleman') 
  AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.cloth') IS NULL);

UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.cloth', 3
)
WHERE troop_key IN ('infantry', 'marine', 'paratroopers', 'special_forces') 
  AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.cloth') IS NULL);

-- 攻城兵器に石炭・硫黄を追加（火薬用）
UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.sulfur', 5,
    '$.coal', 3
)
WHERE troop_key IN ('cannon', 'trebuchet') 
  AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.sulfur') IS NULL);

-- 産業革命以降の兵器に石油を追加
UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.oil', 10
)
WHERE troop_key IN ('artillery') 
  AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.oil') IS NULL);

UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.oil', 30
)
WHERE troop_key IN ('ironclad') 
  AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.oil') IS NULL);

-- 現代兵器に石油を大量追加
UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.oil', 50
)
WHERE troop_key IN ('tank') 
  AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.oil') IS NULL);

UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.oil', 80
)
WHERE troop_key IN ('fighter', 'stealth_fighter') 
  AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.oil') IS NULL);

UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.oil', 100
)
WHERE troop_key IN ('bomber') 
  AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.oil') IS NULL);

UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.oil', 60
)
WHERE troop_key IN ('submarine', 'nuclear_submarine') 
  AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.oil') IS NULL);

UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.oil', 150
)
WHERE troop_key IN ('aircraft_carrier') 
  AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.oil') IS NULL);

UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.oil', 20
)
WHERE troop_key IN ('missile_launcher') 
  AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.oil') IS NULL);

-- ガラスを使用する兵種（光学機器用）
UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.glass', 5
)
WHERE troop_key IN ('rifleman', 'musketeer') 
  AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.glass') IS NULL);

UPDATE civilization_troop_types 
SET train_cost_resources = JSON_SET(
    COALESCE(train_cost_resources, '{}'),
    '$.glass', 10
)
WHERE troop_key IN ('fighter', 'bomber', 'stealth_fighter') 
  AND (train_cost_resources IS NULL OR JSON_EXTRACT(train_cost_resources, '$.glass') IS NULL);

-- ===============================================
-- 3. 治療コストに薬草・医薬品を設定するためのカラム追加
-- ===============================================
ALTER TABLE civilization_troop_types
ADD COLUMN IF NOT EXISTS heal_cost_resources JSON COMMENT '治療に必要な追加資源 {"herbs": 1, "medicine": 1}' AFTER train_time_seconds;

-- 石器～青銅器時代: 薬草のみ
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"herbs": 1}'
WHERE unlock_era_id IN (1, 2) AND heal_cost_resources IS NULL;

-- 鉄器～中世: 薬草2
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"herbs": 2}'
WHERE unlock_era_id IN (3, 4) AND heal_cost_resources IS NULL;

-- ルネサンス: 薬草2、医薬品1
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"herbs": 2, "medicine": 1}'
WHERE unlock_era_id = 5 AND heal_cost_resources IS NULL;

-- 産業革命: 薬草1、医薬品2
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"herbs": 1, "medicine": 2}'
WHERE unlock_era_id = 6 AND heal_cost_resources IS NULL;

-- 現代: 医薬品3
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 3}'
WHERE unlock_era_id = 7 AND heal_cost_resources IS NULL;

-- ===============================================
-- 4. 文明クエストマスターテーブル（チュートリアル以外のクエスト）
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_quests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quest_key VARCHAR(50) NOT NULL UNIQUE,
    quest_category ENUM('training', 'production', 'building', 'research', 'conquest', 'monster', 'world_boss', 'alliance', 'trade') NOT NULL COMMENT 'クエストカテゴリ',
    era_id INT UNSIGNED NOT NULL COMMENT '対象時代',
    title VARCHAR(100) NOT NULL COMMENT 'クエストタイトル',
    description TEXT COMMENT 'クエストの説明',
    icon VARCHAR(50) NOT NULL DEFAULT '📜',
    quest_type ENUM('build', 'train', 'research', 'collect', 'attack', 'defeat_monster', 'damage_boss', 'alliance', 'trade', 'conquest') NOT NULL COMMENT 'クエストタイプ',
    target_key VARCHAR(50) NULL COMMENT '対象のキー（建物キー、兵種キーなど）',
    target_count INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '必要数/達成条件',
    reward_coins INT UNSIGNED NOT NULL DEFAULT 0,
    reward_crystals INT UNSIGNED NOT NULL DEFAULT 0,
    reward_diamonds INT UNSIGNED NOT NULL DEFAULT 0,
    reward_resources JSON COMMENT '報酬資源 {"food": 100, "wood": 50}',
    is_repeatable BOOLEAN NOT NULL DEFAULT FALSE COMMENT '繰り返し可能かどうか',
    cooldown_hours INT UNSIGNED NULL COMMENT '繰り返しクールダウン（時間）',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (quest_category),
    INDEX idx_era (era_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='文明クエストマスター（チュートリアル以外）';

-- ===============================================
-- 5. ユーザークエスト進捗テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS user_civilization_quest_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    quest_id INT UNSIGNED NOT NULL,
    current_progress INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '現在の進捗',
    is_completed BOOLEAN NOT NULL DEFAULT FALSE COMMENT '完了フラグ',
    is_claimed BOOLEAN NOT NULL DEFAULT FALSE COMMENT '報酬受取済フラグ',
    completed_at DATETIME NULL COMMENT '完了日時',
    claimed_at DATETIME NULL COMMENT '報酬受取日時',
    last_reset_at DATETIME NULL COMMENT '最終リセット日時（繰り返し用）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quest_id) REFERENCES civilization_quests(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_quest (user_id, quest_id),
    INDEX idx_user (user_id),
    INDEX idx_completed (is_completed),
    INDEX idx_claimed (is_claimed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー文明クエスト進捗';

-- ===============================================
-- 6. 初期クエストデータ投入
-- 時代に合った報酬を設定
-- ===============================================

-- ======= 石器時代（era_id=1）クエスト =======
INSERT IGNORE INTO civilization_quests (quest_key, quest_category, era_id, title, description, icon, quest_type, target_key, target_count, reward_coins, reward_crystals, reward_diamonds, reward_resources, is_repeatable, cooldown_hours, sort_order) VALUES
('stone_train_10_hunters', 'training', 1, '狩人を10人訓練', '狩人を合計10人訓練しましょう', '🏹', 'train', 'hunter', 10, 200, 0, 0, '{"food": 50, "wood": 30}', FALSE, NULL, 10),
('stone_train_5_warriors', 'training', 1, '戦士を5人訓練', '戦士を合計5人訓練しましょう', '⚔️', 'train', 'warrior', 5, 300, 0, 0, '{"food": 80, "stone": 20}', FALSE, NULL, 20),
('stone_build_2_huts', 'building', 1, '小屋を2つ建設', '小屋を2つ建設して人口を増やしましょう', '🛖', 'build', 'hut', 2, 250, 0, 0, '{"wood": 50}', FALSE, NULL, 30),
('stone_collect_500_food', 'production', 1, '食料を500集める', '食料を合計500集めましょう', '🍖', 'collect', 'food', 500, 150, 0, 0, '{"wood": 30, "stone": 20}', TRUE, 24, 40),
('stone_defeat_wolf', 'monster', 1, '野生の狼を討伐', '野生の狼を1体倒しましょう', '🐺', 'defeat_monster', 'wild_wolf', 1, 400, 5, 0, '{"food": 100}', TRUE, 12, 50);

-- ======= 青銅器時代（era_id=2）クエスト =======
INSERT IGNORE INTO civilization_quests (quest_key, quest_category, era_id, title, description, icon, quest_type, target_key, target_count, reward_coins, reward_crystals, reward_diamonds, reward_resources, is_repeatable, cooldown_hours, sort_order) VALUES
('bronze_train_10_spearmen', 'training', 2, '槍兵を10人訓練', '槍兵を合計10人訓練しましょう', '🗡️', 'train', 'spearman', 10, 500, 5, 0, '{"food": 100, "bronze": 30}', FALSE, NULL, 10),
('bronze_train_3_chariots', 'training', 2, '戦車を3台製造', '戦車を3台製造しましょう', '🛞', 'train', 'chariot', 3, 800, 10, 0, '{"bronze": 50, "wood": 50}', FALSE, NULL, 20),
('bronze_build_farm', 'building', 2, '農場を建設', '農場を1つ建設して食料生産を効率化しましょう', '🌾', 'build', 'farm', 1, 600, 5, 0, '{"food": 200}', FALSE, NULL, 30),
('bronze_research_1', 'research', 2, '研究を1つ完了', '何か1つの研究を完了させましょう', '📚', 'research', NULL, 1, 700, 10, 0, '{"knowledge": 20}', FALSE, NULL, 40),
('bronze_defeat_goblin', 'monster', 2, 'ゴブリンを討伐', 'ゴブリンを3体倒しましょう', '👹', 'defeat_monster', 'goblin', 3, 1000, 15, 1, '{"food": 200, "bronze": 30}', TRUE, 12, 50),
('bronze_collect_300_bronze', 'production', 2, '青銅を300集める', '青銅を合計300集めましょう', '🔶', 'collect', 'bronze', 300, 400, 5, 0, '{"food": 150}', TRUE, 24, 60);

-- ======= 鉄器時代（era_id=3）クエスト =======
INSERT IGNORE INTO civilization_quests (quest_key, quest_category, era_id, title, description, icon, quest_type, target_key, target_count, reward_coins, reward_crystals, reward_diamonds, reward_resources, is_repeatable, cooldown_hours, sort_order) VALUES
('iron_train_10_swordsmen', 'training', 3, '剣士を10人訓練', '剣士を合計10人訓練しましょう', '⚔️', 'train', 'swordsman', 10, 1000, 10, 0, '{"iron": 50, "food": 150}', FALSE, NULL, 10),
('iron_train_5_cavalry', 'training', 3, '騎兵を5人訓練', '騎兵を5人訓練しましょう（馬が必要）', '🐎', 'train', 'cavalry', 5, 1500, 15, 1, '{"horses": 10, "iron": 30}', FALSE, NULL, 20),
('iron_train_10_archers', 'training', 3, '弓兵を10人訓練', '弓兵を10人訓練しましょう', '🏹', 'train', 'archer', 10, 800, 8, 0, '{"wood": 80, "food": 100}', FALSE, NULL, 25),
('iron_build_fortress', 'building', 3, '要塞を建設', '要塞を1つ建設して防御力を高めましょう', '🏯', 'build', 'fortress', 1, 2000, 20, 2, '{"iron": 100, "stone": 200}', FALSE, NULL, 30),
('iron_defeat_orc', 'monster', 3, 'オークを討伐', 'オークを5体倒しましょう', '👿', 'defeat_monster', 'orc', 5, 2500, 25, 2, '{"iron": 100, "gold": 20}', TRUE, 12, 40),
('iron_collect_500_iron', 'production', 3, '鉄を500集める', '鉄を合計500集めましょう', '⚙️', 'collect', 'iron', 500, 1200, 12, 0, '{"food": 300, "stone": 100}', TRUE, 24, 50),
('iron_conquest_1', 'conquest', 3, '拠点を占領', '敵の拠点を1つ占領しましょう', '⚔️', 'conquest', NULL, 1, 3000, 30, 3, '{"gold": 50}', TRUE, 48, 60);

-- ======= 中世（era_id=4）クエスト =======
INSERT IGNORE INTO civilization_quests (quest_key, quest_category, era_id, title, description, icon, quest_type, target_key, target_count, reward_coins, reward_crystals, reward_diamonds, reward_resources, is_repeatable, cooldown_hours, sort_order) VALUES
('medieval_train_5_knights', 'training', 4, '騎士を5人訓練', '騎士を5人訓練しましょう', '🛡️', 'train', 'knight', 5, 3000, 30, 3, '{"iron": 100, "gold": 30, "horses": 15}', FALSE, NULL, 10),
('medieval_train_10_crossbowmen', 'training', 4, 'クロスボウ兵を10人訓練', 'クロスボウ兵を10人訓練しましょう', '🎯', 'train', 'crossbowman', 10, 2000, 20, 1, '{"iron": 80, "cloth": 20}', FALSE, NULL, 20),
('medieval_build_castle', 'building', 4, '城を建設', '城を建設して王国の象徴としましょう', '🏰', 'build', 'castle', 1, 10000, 100, 10, '{"gold": 100, "stone": 500}', FALSE, NULL, 30),
('medieval_build_catapult', 'training', 4, 'カタパルトを3台製造', 'カタパルトを3台製造しましょう', '🪨', 'train', 'catapult', 3, 4000, 40, 4, '{"sulfur": 20, "coal": 15}', FALSE, NULL, 35),
('medieval_defeat_troll', 'monster', 4, 'トロールを討伐', 'トロールを3体倒しましょう', '🧌', 'defeat_monster', 'troll', 3, 5000, 50, 5, '{"gold": 100, "cloth": 50}', TRUE, 12, 40),
('medieval_collect_200_gold', 'production', 4, '金を200集める', '金を合計200集めましょう', '💰', 'collect', 'gold', 200, 3000, 30, 2, '{"knowledge": 50}', TRUE, 24, 50),
('medieval_research_2', 'research', 4, '中世の研究を2つ完了', '中世時代の研究を2つ完了させましょう', '📚', 'research', NULL, 2, 4000, 40, 3, '{"gold": 80}', FALSE, NULL, 60),
('medieval_alliance_1', 'alliance', 4, '同盟を締結', '他の文明と同盟を締結しましょう', '🤝', 'alliance', NULL, 1, 5000, 50, 5, '{"gold": 100, "knowledge": 30}', FALSE, NULL, 70);

-- ======= ルネサンス（era_id=5）クエスト =======
INSERT IGNORE INTO civilization_quests (quest_key, quest_category, era_id, title, description, icon, quest_type, target_key, target_count, reward_coins, reward_crystals, reward_diamonds, reward_resources, is_repeatable, cooldown_hours, sort_order) VALUES
('renaissance_train_10_musketeers', 'training', 5, 'マスケット銃兵を10人訓練', 'マスケット銃兵を10人訓練しましょう', '🔫', 'train', 'musketeer', 10, 5000, 50, 5, '{"iron": 100, "cloth": 50, "glass": 30}', FALSE, NULL, 10),
('renaissance_train_3_cannons', 'training', 5, '大砲を3門製造', '大砲を3門製造しましょう', '💣', 'train', 'cannon', 3, 8000, 80, 8, '{"sulfur": 50, "coal": 30, "iron": 150}', FALSE, NULL, 20),
('renaissance_build_galleon', 'training', 5, 'ガレオン船を2隻建造', 'ガレオン船を2隻建造しましょう', '⛵', 'train', 'galleon', 2, 10000, 100, 10, '{"wood": 400, "iron": 200, "cloth": 80}', FALSE, NULL, 30),
('renaissance_defeat_dragon', 'monster', 5, '幼竜を討伐', '幼竜を2体倒しましょう', '🐲', 'defeat_monster', 'dragon_whelp', 2, 15000, 150, 15, '{"gold": 200, "herbs": 100}', TRUE, 24, 40),
('renaissance_world_boss_dmg', 'world_boss', 5, 'ワールドボスにダメージ', 'ワールドボスに10000以上のダメージを与えましょう', '🦾', 'damage_boss', NULL, 10000, 20000, 200, 20, '{"medicine": 50}', TRUE, 24, 50),
('renaissance_collect_glass', 'production', 5, 'ガラスを300集める', 'ガラスを合計300集めましょう', '🔮', 'collect', 'glass', 300, 6000, 60, 5, '{"gold": 150}', TRUE, 24, 60);

-- ======= 産業革命（era_id=6）クエスト =======
INSERT IGNORE INTO civilization_quests (quest_key, quest_category, era_id, title, description, icon, quest_type, target_key, target_count, reward_coins, reward_crystals, reward_diamonds, reward_resources, is_repeatable, cooldown_hours, sort_order) VALUES
('industrial_train_20_infantry', 'training', 6, '歩兵を20人訓練', '歩兵を20人訓練しましょう', '🎖️', 'train', 'infantry', 20, 10000, 100, 10, '{"iron": 200, "cloth": 100, "oil": 50}', FALSE, NULL, 10),
('industrial_train_5_artillery', 'training', 6, '砲兵を5部隊訓練', '砲兵を5部隊訓練しましょう', '💥', 'train', 'artillery', 5, 15000, 150, 15, '{"oil": 100, "iron": 300, "coal": 80}', FALSE, NULL, 20),
('industrial_build_ironclad', 'training', 6, '装甲艦を建造', '装甲艦を1隻建造しましょう', '🚢', 'train', 'ironclad', 1, 20000, 200, 20, '{"oil": 150, "iron": 400}', FALSE, NULL, 30),
('industrial_collect_oil', 'production', 6, '石油を500集める', '石油を合計500集めましょう', '🛢️', 'collect', 'oil', 500, 12000, 120, 10, '{"coal": 200}', TRUE, 24, 40),
('industrial_defeat_lich', 'monster', 6, 'リッチを討伐', 'リッチを2体倒しましょう', '💀', 'defeat_monster', 'lich', 2, 25000, 250, 25, '{"medicine": 100, "oil": 100}', TRUE, 24, 50),
('industrial_conquest_3', 'conquest', 6, '拠点を3つ占領', '敵の拠点を3つ占領しましょう', '⚔️', 'conquest', NULL, 3, 30000, 300, 30, '{"oil": 200, "iron": 300}', TRUE, 72, 60);

-- ======= 現代（era_id=7）クエスト =======
INSERT IGNORE INTO civilization_quests (quest_key, quest_category, era_id, title, description, icon, quest_type, target_key, target_count, reward_coins, reward_crystals, reward_diamonds, reward_resources, is_repeatable, cooldown_hours, sort_order) VALUES
('modern_train_10_tanks', 'training', 7, '戦車を10台生産', '戦車を10台生産しましょう', '🚜', 'train', 'tank', 10, 50000, 500, 50, '{"oil": 500, "iron": 500}', FALSE, NULL, 10),
('modern_train_5_fighters', 'training', 7, '戦闘機を5機生産', '戦闘機を5機生産しましょう', '✈️', 'train', 'fighter', 5, 60000, 600, 60, '{"oil": 600, "glass": 100}', FALSE, NULL, 20),
('modern_train_3_bombers', 'training', 7, '爆撃機を3機生産', '爆撃機を3機生産しましょう', '💣', 'train', 'bomber', 3, 80000, 800, 80, '{"oil": 800}', FALSE, NULL, 30),
('modern_train_submarine', 'training', 7, '潜水艦を2隻建造', '潜水艦を2隻建造しましょう', '🚤', 'train', 'submarine', 2, 40000, 400, 40, '{"oil": 400, "iron": 300}', FALSE, NULL, 35),
('modern_defeat_elder_dragon', 'monster', 7, '古竜を討伐', '古竜を1体倒しましょう', '🐉', 'defeat_monster', 'elder_dragon', 1, 100000, 1000, 100, '{"medicine": 200, "oil": 300}', TRUE, 48, 40),
('modern_world_boss_kill', 'world_boss', 7, 'ワールドボス撃破に貢献', 'ワールドボスの撃破に貢献しましょう（上位10位以内）', '🌟', 'damage_boss', NULL, 1, 200000, 2000, 200, '{"oil": 500}', TRUE, 72, 50),
('modern_collect_oil_1000', 'production', 7, '石油を1000集める', '石油を合計1000集めましょう', '🛢️', 'collect', 'oil', 1000, 80000, 800, 80, '{"medicine": 300}', TRUE, 48, 60),
('modern_conquest_5', 'conquest', 7, '拠点を5つ占領', '敵の拠点を5つ占領しましょう', '⚔️', 'conquest', NULL, 5, 150000, 1500, 150, '{"oil": 800, "medicine": 200}', TRUE, 168, 70);

-- ===============================================
-- 7. チュートリアルモーダル設定テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_tutorial_modal_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quest_id INT UNSIGNED NOT NULL COMMENT 'チュートリアルクエストID',
    modal_title VARCHAR(100) NOT NULL COMMENT 'モーダルタイトル',
    modal_content TEXT NOT NULL COMMENT 'モーダル本文（HTML可）',
    highlight_selector VARCHAR(255) NULL COMMENT 'ハイライトする要素のCSSセレクタ',
    arrow_position ENUM('top', 'bottom', 'left', 'right') NULL COMMENT '矢印の方向',
    action_hint TEXT NULL COMMENT 'アクションのヒント',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quest_id) REFERENCES civilization_tutorial_quests(id) ON DELETE CASCADE,
    UNIQUE KEY unique_quest (quest_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='チュートリアルモーダル設定';

-- ===============================================
-- 8. チュートリアルモーダル設定データ投入
-- ===============================================
INSERT IGNORE INTO civilization_tutorial_modal_config (quest_id, modal_title, modal_content, highlight_selector, arrow_position, action_hint)
SELECT id, 
    '💰 文明への投資',
    '<p>まずは<strong>コインを投資</strong>して、文明を発展させましょう！</p><p>投資すると<span style="color: #ffd700;">研究ポイント</span>と<span style="color: #32cd32;">基本資源</span>が手に入ります。</p>',
    '.invest-section',
    'bottom',
    '投資額を入力して「投資する」ボタンをクリック'
FROM civilization_tutorial_quests WHERE quest_key = 'tutorial_invest';

INSERT IGNORE INTO civilization_tutorial_modal_config (quest_id, modal_title, modal_content, highlight_selector, arrow_position, action_hint)
SELECT id, 
    '🛖 最初の住居',
    '<p>人口を増やすために<strong>小屋</strong>を建設しましょう！</p><p>建物タブから小屋を選んで建設できます。</p>',
    '.tab-btn[data-tab="buildings"]',
    'bottom',
    '建物タブを開いて「小屋」の「建設」ボタンをクリック'
FROM civilization_tutorial_quests WHERE quest_key = 'tutorial_build_hut';

INSERT IGNORE INTO civilization_tutorial_modal_config (quest_id, modal_title, modal_content, highlight_selector, arrow_position, action_hint)
SELECT id, 
    '🏹 食料の確保',
    '<p>次は<strong>狩場</strong>を建設して食料を生産しましょう！</p><p>食料は兵士の訓練に必要です。</p>',
    '.tab-btn[data-tab="buildings"]',
    'bottom',
    '建物タブから「狩場」を建設'
FROM civilization_tutorial_quests WHERE quest_key = 'tutorial_build_hunting';

INSERT IGNORE INTO civilization_tutorial_modal_config (quest_id, modal_title, modal_content, highlight_selector, arrow_position, action_hint)
SELECT id, 
    '📚 技術の発展',
    '<p><strong>研究</strong>を行って新しい技術をアンロックしましょう！</p><p>研究タブから研究を開始できます。</p>',
    '.tab-btn[data-tab="research"]',
    'bottom',
    '研究タブを開いて研究を開始'
FROM civilization_tutorial_quests WHERE quest_key = 'tutorial_research';

INSERT IGNORE INTO civilization_tutorial_modal_config (quest_id, modal_title, modal_content, highlight_selector, arrow_position, action_hint)
SELECT id, 
    '⚔️ 戦士の訓練',
    '<p><strong>兵士</strong>を訓練して軍事力を高めましょう！</p><p>兵士タブから戦士を訓練できます。</p>',
    '.tab-btn[data-tab="troops"]',
    'bottom',
    '兵士タブを開いて「戦士」を5体訓練'
FROM civilization_tutorial_quests WHERE quest_key = 'tutorial_train_warrior';

INSERT IGNORE INTO civilization_tutorial_modal_config (quest_id, modal_title, modal_content, highlight_selector, arrow_position, action_hint)
SELECT id, 
    '🌾 農業の開始',
    '<p><strong>農場</strong>を建設して効率的に食料を生産しましょう！</p><p>青銅器時代の建物です。</p>',
    '.tab-btn[data-tab="buildings"]',
    'bottom',
    '建物タブから「農場」を建設'
FROM civilization_tutorial_quests WHERE quest_key = 'tutorial_build_farm';

INSERT IGNORE INTO civilization_tutorial_modal_config (quest_id, modal_title, modal_content, highlight_selector, arrow_position, action_hint)
SELECT id, 
    '⚔️ 軍事施設',
    '<p><strong>兵舎</strong>を建設して、より強力な兵士を訓練できるようにしましょう！</p>',
    '.tab-btn[data-tab="buildings"]',
    'bottom',
    '建物タブから「兵舎」を建設'
FROM civilization_tutorial_quests WHERE quest_key = 'tutorial_build_barracks';

INSERT IGNORE INTO civilization_tutorial_modal_config (quest_id, modal_title, modal_content, highlight_selector, arrow_position, action_hint)
SELECT id, 
    '🐎 騎兵の編成',
    '<p><strong>騎兵</strong>は機動力の高いユニットです！</p><p>騎兵には<span style="color: #8B4513;">馬</span>が必要です。</p>',
    '.tab-btn[data-tab="troops"]',
    'bottom',
    '兵士タブから「騎兵」を3体訓練'
FROM civilization_tutorial_quests WHERE quest_key = 'tutorial_train_cavalry';

INSERT IGNORE INTO civilization_tutorial_modal_config (quest_id, modal_title, modal_content, highlight_selector, arrow_position, action_hint)
SELECT id, 
    '🔶 時代の進化',
    '<p><strong>青銅器時代</strong>に進化しましょう！</p><p>時代進化セクションから条件を確認して進化できます。</p>',
    '.era-progress',
    'top',
    '条件を満たしたら「青銅器時代に進化する」をクリック'
FROM civilization_tutorial_quests WHERE quest_key = 'tutorial_advance_era';

INSERT IGNORE INTO civilization_tutorial_modal_config (quest_id, modal_title, modal_content, highlight_selector, arrow_position, action_hint)
SELECT id, 
    '🎉 チュートリアル完了！',
    '<p>おめでとうございます！<strong>チュートリアル</strong>を完了しました！</p><p>豪華な報酬を受け取って、文明をさらに発展させましょう！</p>',
    NULL,
    NULL,
    '報酬を受け取るボタンをクリック'
FROM civilization_tutorial_quests WHERE quest_key = 'tutorial_complete';

-- ===============================================
-- 9. ユーザーチュートリアルモーダル表示状態テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS user_tutorial_modal_state (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    current_modal_quest_id INT UNSIGNED NULL COMMENT '現在表示中のモーダルクエストID',
    modal_dismissed BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'モーダルを閉じたかどうか',
    modal_shown_at DATETIME NULL COMMENT 'モーダル表示日時',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (current_modal_quest_id) REFERENCES civilization_tutorial_quests(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザーチュートリアルモーダル表示状態';

-- ===============================================
-- 10. 追加資源生産建物
-- ===============================================
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
('textile_mill', '織物工場', '🧵', '布を生産する', 'production', NULL, 4.0, 10, 3, 1500, '{"wood": 80, "iron": 30}', 1200, 0, 0),
('herb_garden', '薬草園', '🌿', '薬草を栽培する', 'production', NULL, 3.0, 10, 2, 800, '{"wood": 50, "stone": 30}', 900, 0, 0),
('horse_stable', '馬牧場', '🐎', '馬を育てる', 'production', NULL, 2.0, 10, 3, 2000, '{"wood": 100, "food": 200}', 1500, 0, 0),
('glassworks', 'ガラス工房', '🔮', 'ガラスを生産する', 'production', NULL, 2.5, 10, 4, 3000, '{"stone": 150, "coal": 50}', 2100, 0, 0),
('pharmacy', '製薬所', '💊', '医薬品を製造する', 'production', NULL, 1.5, 10, 5, 5000, '{"herbs": 100, "glass": 30}', 3000, 0, 0);

-- 生産資源IDを設定
UPDATE civilization_building_types SET produces_resource_id = (SELECT id FROM civilization_resource_types WHERE resource_key = 'cloth' LIMIT 1) WHERE building_key = 'textile_mill' AND produces_resource_id IS NULL;
UPDATE civilization_building_types SET produces_resource_id = (SELECT id FROM civilization_resource_types WHERE resource_key = 'herbs' LIMIT 1) WHERE building_key = 'herb_garden' AND produces_resource_id IS NULL;
UPDATE civilization_building_types SET produces_resource_id = (SELECT id FROM civilization_resource_types WHERE resource_key = 'horses' LIMIT 1) WHERE building_key = 'horse_stable' AND produces_resource_id IS NULL;
UPDATE civilization_building_types SET produces_resource_id = (SELECT id FROM civilization_resource_types WHERE resource_key = 'glass' LIMIT 1) WHERE building_key = 'glassworks' AND produces_resource_id IS NULL;
UPDATE civilization_building_types SET produces_resource_id = (SELECT id FROM civilization_resource_types WHERE resource_key = 'medicine' LIMIT 1) WHERE building_key = 'pharmacy' AND produces_resource_id IS NULL;

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'Civilization quests and tutorial modal schema applied successfully' AS status;
