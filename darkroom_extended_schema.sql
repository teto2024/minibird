-- ===============================================
-- A Dark Room 拡張機能用テーブル定義
-- レベリング、クエスト、アイテムクラフト、戦闘システム
-- ===============================================

USE microblog;

-- ===============================================
-- プレイヤーステータステーブル（レベリング）
-- ===============================================
CREATE TABLE IF NOT EXISTS darkroom_player_stats (
    user_id INT UNSIGNED NOT NULL,
    level INT UNSIGNED NOT NULL DEFAULT 1,
    experience INT UNSIGNED NOT NULL DEFAULT 0,
    health INT UNSIGNED NOT NULL DEFAULT 100,
    max_health INT UNSIGNED NOT NULL DEFAULT 100,
    attack INT UNSIGNED NOT NULL DEFAULT 10,
    defense INT UNSIGNED NOT NULL DEFAULT 5,
    agility INT UNSIGNED NOT NULL DEFAULT 5,
    stat_points INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '未割り振りのステータスポイント',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='プレイヤーのレベルとステータス';

-- ===============================================
-- アイテムマスターテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS darkroom_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_key VARCHAR(100) NOT NULL UNIQUE COMMENT 'アイテムの一意識別子',
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('weapon', 'armor', 'consumable', 'material', 'quest', 'misc') NOT NULL,
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') NOT NULL DEFAULT 'common',
    stats JSON COMMENT '{"attack": 10, "defense": 5, "health": 20} など',
    effects JSON COMMENT '{"heal": 50, "buff_attack": 10, "duration": 60} など',
    stack_limit INT UNSIGNED NOT NULL DEFAULT 99,
    is_tradeable BOOLEAN NOT NULL DEFAULT TRUE,
    is_craftable BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_rarity (rarity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='アイテムマスターデータ';

-- ===============================================
-- プレイヤーインベントリ
-- ===============================================
CREATE TABLE IF NOT EXISTS darkroom_inventory (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    equipped BOOLEAN NOT NULL DEFAULT FALSE,
    acquired_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES darkroom_items(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_item (item_id),
    INDEX idx_equipped (equipped),
    UNIQUE KEY unique_user_item (user_id, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='プレイヤーのアイテム所持情報';

-- ===============================================
-- クラフトレシピテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS darkroom_recipes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipe_key VARCHAR(100) NOT NULL UNIQUE,
    result_item_id INT UNSIGNED NOT NULL COMMENT '完成するアイテム',
    result_quantity INT UNSIGNED NOT NULL DEFAULT 1,
    required_level INT UNSIGNED NOT NULL DEFAULT 1,
    materials JSON NOT NULL COMMENT '[{"item_id": 1, "quantity": 3}, {"item_id": 2, "quantity": 2}]',
    crafting_time INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '作成にかかる秒数',
    experience_reward INT UNSIGNED NOT NULL DEFAULT 0,
    is_unlocked_default BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (result_item_id) REFERENCES darkroom_items(id) ON DELETE CASCADE,
    INDEX idx_level (required_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='アイテムクラフトレシピ';

-- ===============================================
-- クエストマスターテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS darkroom_quests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quest_key VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    type ENUM('main', 'side', 'daily', 'achievement') NOT NULL DEFAULT 'side',
    required_level INT UNSIGNED NOT NULL DEFAULT 1,
    objectives JSON NOT NULL COMMENT '[{"type": "kill", "target": "wolf", "count": 5}, {"type": "gather", "item_id": 1, "count": 10}]',
    rewards JSON NOT NULL COMMENT '{"experience": 100, "coins": 50, "items": [{"item_id": 1, "quantity": 2}]}',
    is_repeatable BOOLEAN NOT NULL DEFAULT FALSE,
    prerequisite_quest_id INT UNSIGNED NULL COMMENT '前提クエストID',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_level (required_level),
    FOREIGN KEY (prerequisite_quest_id) REFERENCES darkroom_quests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='クエストマスターデータ';

-- ===============================================
-- プレイヤークエスト進行状況
-- ===============================================
CREATE TABLE IF NOT EXISTS darkroom_quest_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    quest_id INT UNSIGNED NOT NULL,
    status ENUM('available', 'active', 'completed', 'failed') NOT NULL DEFAULT 'available',
    progress JSON NOT NULL COMMENT '{"0": 3, "1": 7} - objectives配列のインデックスと進捗',
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quest_id) REFERENCES darkroom_quests(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    UNIQUE KEY unique_user_quest (user_id, quest_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='プレイヤーのクエスト進行状況';

-- ===============================================
-- 敵（モンスター）マスターテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS darkroom_enemies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enemy_key VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    level INT UNSIGNED NOT NULL DEFAULT 1,
    health INT UNSIGNED NOT NULL DEFAULT 50,
    attack INT UNSIGNED NOT NULL DEFAULT 10,
    defense INT UNSIGNED NOT NULL DEFAULT 5,
    agility INT UNSIGNED NOT NULL DEFAULT 5,
    experience_reward INT UNSIGNED NOT NULL DEFAULT 10,
    loot_table JSON COMMENT '[{"item_id": 1, "drop_rate": 0.3, "quantity_min": 1, "quantity_max": 3}]',
    special_abilities JSON COMMENT '["poison_attack", "critical_strike"]',
    spawn_locations JSON COMMENT '["dark_forest", "abandoned_ruins"]',
    is_boss BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_boss (is_boss)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='敵キャラクターマスターデータ';

-- ===============================================
-- 戦闘ログ
-- ===============================================
CREATE TABLE IF NOT EXISTS darkroom_battle_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    enemy_id INT UNSIGNED NOT NULL,
    result ENUM('victory', 'defeat', 'fled') NOT NULL,
    turns INT UNSIGNED NOT NULL DEFAULT 0,
    damage_dealt INT UNSIGNED NOT NULL DEFAULT 0,
    damage_taken INT UNSIGNED NOT NULL DEFAULT 0,
    experience_gained INT UNSIGNED NOT NULL DEFAULT 0,
    loot JSON COMMENT '[{"item_id": 1, "quantity": 2}]',
    battle_log TEXT COMMENT '戦闘の詳細ログ',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (enemy_id) REFERENCES darkroom_enemies(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_result (result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='戦闘履歴ログ';

-- ===============================================
-- プレイヤーのアンロック情報（拡張用）
-- ===============================================
CREATE TABLE IF NOT EXISTS darkroom_unlocks (
    user_id INT UNSIGNED NOT NULL,
    unlock_key VARCHAR(100) NOT NULL COMMENT 'recipe_1, area_forest, skill_fireball など',
    unlocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, unlock_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='プレイヤーのアンロック状況';

-- ===============================================
-- 初期データ投入
-- ===============================================

-- 基本マテリアルアイテム
INSERT IGNORE INTO darkroom_items (item_key, name, description, type, rarity, stats, stack_limit) VALUES
('wood', '木材', '基本的な建材。様々な用途に使える。', 'material', 'common', NULL, 999),
('stone', '石材', '硬い石。武器や建築に使用。', 'material', 'common', NULL, 999),
('iron_ore', '鉄鉱石', '鉄を精錬できる鉱石。', 'material', 'uncommon', NULL, 999),
('leather', '革', '動物の皮から作った革。防具に使える。', 'material', 'common', NULL, 999),
('herb', '薬草', '回復効果のある草。', 'material', 'common', NULL, 999);

-- 武器
INSERT IGNORE INTO darkroom_items (item_key, name, description, type, rarity, stats, stack_limit, is_craftable) VALUES
('wooden_sword', '木の剣', '木で作った簡易的な剣。', 'weapon', 'common', '{"attack": 5}', 1, TRUE),
('stone_axe', '石の斧', '石で作った斧。攻撃力が高い。', 'weapon', 'common', '{"attack": 8}', 1, TRUE),
('iron_sword', '鉄の剣', '鉄製の頑丈な剣。', 'weapon', 'uncommon', '{"attack": 15}', 1, TRUE);

-- 防具
INSERT IGNORE INTO darkroom_items (item_key, name, description, type, rarity, stats, stack_limit, is_craftable) VALUES
('leather_armor', '革の鎧', '革製の軽い鎧。', 'armor', 'common', '{"defense": 5, "max_health": 20}', 1, TRUE),
('iron_armor', '鉄の鎧', '鉄製の重厚な鎧。', 'armor', 'uncommon', '{"defense": 12, "max_health": 40}', 1, TRUE);

-- 消耗品
INSERT IGNORE INTO darkroom_items (item_key, name, description, type, rarity, effects, stack_limit, is_craftable) VALUES
('health_potion', '体力ポーション', '体力を50回復する。', 'consumable', 'common', '{"heal": 50}', 99, TRUE),
('strength_potion', '力のポーション', '60秒間攻撃力+10。', 'consumable', 'uncommon', '{"buff_attack": 10, "duration": 60}', 99, TRUE);

-- クラフトレシピ
INSERT IGNORE INTO darkroom_recipes (recipe_key, result_item_id, result_quantity, required_level, materials, crafting_time, experience_reward) VALUES
('craft_wooden_sword', (SELECT id FROM darkroom_items WHERE item_key='wooden_sword'), 1, 1, '[{"item_key": "wood", "quantity": 5}]', 3, 10),
('craft_stone_axe', (SELECT id FROM darkroom_items WHERE item_key='stone_axe'), 1, 3, '[{"item_key": "wood", "quantity": 3}, {"item_key": "stone", "quantity": 5}]', 5, 20),
('craft_iron_sword', (SELECT id FROM darkroom_items WHERE item_key='iron_sword'), 1, 5, '[{"item_key": "wood", "quantity": 2}, {"item_key": "iron_ore", "quantity": 8}]', 10, 50),
('craft_leather_armor', (SELECT id FROM darkroom_items WHERE item_key='leather_armor'), 1, 2, '[{"item_key": "leather", "quantity": 8}]', 8, 30),
('craft_iron_armor', (SELECT id FROM darkroom_items WHERE item_key='iron_armor'), 1, 6, '[{"item_key": "iron_ore", "quantity": 15}, {"item_key": "leather", "quantity": 5}]', 15, 80),
('craft_health_potion', (SELECT id FROM darkroom_items WHERE item_key='health_potion'), 1, 1, '[{"item_key": "herb", "quantity": 3}]', 2, 5);

-- 敵データ
INSERT IGNORE INTO darkroom_enemies (enemy_key, name, description, level, health, attack, defense, agility, experience_reward, loot_table) VALUES
('rat', 'ネズミ', '小さな野生のネズミ。弱い。', 1, 20, 5, 2, 8, 5, '[{"item_key": "leather", "drop_rate": 0.3, "quantity_min": 1, "quantity_max": 1}]'),
('wolf', '野生の狼', '凶暴な狼。群れで襲ってくる。', 3, 50, 12, 5, 10, 20, '[{"item_key": "leather", "drop_rate": 0.6, "quantity_min": 2, "quantity_max": 3}]'),
('goblin', 'ゴブリン', '小型の亜人。武器を持っている。', 5, 80, 18, 8, 12, 40, '[{"item_key": "stone", "drop_rate": 0.5, "quantity_min": 2, "quantity_max": 4}, {"item_key": "wood", "drop_rate": 0.4, "quantity_min": 3, "quantity_max": 5}]'),
('orc', 'オーク', '大型で凶暴な戦士。', 8, 150, 25, 15, 8, 80, '[{"item_key": "iron_ore", "drop_rate": 0.7, "quantity_min": 3, "quantity_max": 5}]'),
('dark_knight', 'ダークナイト', '闇に堕ちた騎士。強力なボス。', 15, 500, 45, 30, 15, 500, '[{"item_key": "iron_ore", "drop_rate": 1.0, "quantity_min": 10, "quantity_max": 15}]');

UPDATE darkroom_enemies SET is_boss = TRUE WHERE enemy_key = 'dark_knight';

-- クエストデータ
INSERT IGNORE INTO darkroom_quests (quest_key, title, description, type, required_level, objectives, rewards) VALUES
('tutorial_gather', '最初の一歩', '木材を10個集めよう', 'main', 1, '[{"type": "gather", "item_key": "wood", "count": 10}]', '{"experience": 20, "coins": 10}'),
('tutorial_craft', '武器作成', '木の剣を1本作成しよう', 'main', 1, '[{"type": "craft", "recipe_key": "craft_wooden_sword", "count": 1}]', '{"experience": 30, "coins": 20, "items": [{"item_key": "health_potion", "quantity": 3}]}'),
('hunt_rats', 'ネズミ退治', 'ネズミを5匹倒そう', 'side', 2, '[{"type": "kill", "enemy_key": "rat", "count": 5}]', '{"experience": 50, "coins": 30}'),
('hunt_wolves', '狼の脅威', '野生の狼を3匹倒そう', 'side', 4, '[{"type": "kill", "enemy_key": "wolf", "count": 3}]', '{"experience": 100, "coins": 60, "items": [{"item_key": "leather_armor", "quantity": 1}]}'),
('defeat_boss', 'ダークナイト討伐', 'ダークナイトを倒そう', 'main', 12, '[{"type": "kill", "enemy_key": "dark_knight", "count": 1}]', '{"experience": 1000, "coins": 500, "crystals": 10}');

-- テーブル作成完了メッセージ
SELECT 'Extended darkroom tables created successfully' AS status;
