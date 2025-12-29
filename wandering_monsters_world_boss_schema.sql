-- ===============================================
-- MiniBird 放浪モンスター＆ワールドボスシステム
-- 放浪モンスター: ユーザーレベルに応じた敵を倒してコイン・資源・兵士を獲得
-- ワールドボス: みんなで倒す強敵、召喚にはダイヤモンドが必要
-- ===============================================

USE microblog;

-- ===============================================
-- 放浪モンスターテーブル（マスター）
-- ===============================================
CREATE TABLE IF NOT EXISTS wandering_monsters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    monster_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    description TEXT,
    min_level INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '出現するユーザー最低レベル',
    max_level INT UNSIGNED NOT NULL DEFAULT 100 COMMENT '出現するユーザー最大レベル',
    base_attack INT UNSIGNED NOT NULL DEFAULT 10,
    base_defense INT UNSIGNED NOT NULL DEFAULT 5,
    base_health INT UNSIGNED NOT NULL DEFAULT 100,
    level_scaling DECIMAL(3,2) NOT NULL DEFAULT 1.10 COMMENT 'レベルごとのステータス倍率',
    reward_coins_min INT UNSIGNED NOT NULL DEFAULT 10,
    reward_coins_max INT UNSIGNED NOT NULL DEFAULT 100,
    reward_crystals_min INT UNSIGNED NOT NULL DEFAULT 0,
    reward_crystals_max INT UNSIGNED NOT NULL DEFAULT 5,
    reward_diamonds_min INT UNSIGNED NOT NULL DEFAULT 0,
    reward_diamonds_max INT UNSIGNED NOT NULL DEFAULT 1,
    soldier_drop_chance DECIMAL(5,2) NOT NULL DEFAULT 5.00 COMMENT '兵士ドロップ確率（%）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (min_level, max_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='放浪モンスターマスター';

-- ===============================================
-- 放浪モンスター資源ドロップテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS wandering_monster_drops (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    monster_id INT UNSIGNED NOT NULL,
    resource_type_id INT UNSIGNED NULL COMMENT '資源タイプ（NULLの場合は兵士）',
    troop_type_id INT UNSIGNED NULL COMMENT '兵種タイプ（NULLの場合は資源）',
    drop_chance DECIMAL(5,2) NOT NULL DEFAULT 10.00 COMMENT 'ドロップ確率（%）',
    amount_min INT UNSIGNED NOT NULL DEFAULT 1,
    amount_max INT UNSIGNED NOT NULL DEFAULT 10,
    FOREIGN KEY (monster_id) REFERENCES wandering_monsters(id) ON DELETE CASCADE,
    INDEX idx_monster (monster_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='放浪モンスターのドロップ品';

-- ===============================================
-- ユーザーの放浪モンスター遭遇状態
-- ===============================================
CREATE TABLE IF NOT EXISTS user_wandering_monster_encounters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    monster_id INT UNSIGNED NOT NULL,
    monster_level INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'このモンスターのレベル',
    current_health INT NOT NULL COMMENT '現在の体力',
    max_health INT UNSIGNED NOT NULL COMMENT '最大体力',
    attack_power INT UNSIGNED NOT NULL,
    defense_power INT UNSIGNED NOT NULL,
    encountered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    defeated_at DATETIME NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (monster_id) REFERENCES wandering_monsters(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='放浪モンスター遭遇履歴';

-- ===============================================
-- 放浪モンスター討伐ログ
-- ===============================================
CREATE TABLE IF NOT EXISTS wandering_monster_battle_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    encounter_id BIGINT UNSIGNED NOT NULL,
    monster_id INT UNSIGNED NOT NULL,
    damage_dealt INT UNSIGNED NOT NULL DEFAULT 0,
    is_defeated BOOLEAN NOT NULL DEFAULT FALSE,
    reward_coins INT UNSIGNED NOT NULL DEFAULT 0,
    reward_crystals INT UNSIGNED NOT NULL DEFAULT 0,
    reward_diamonds INT UNSIGNED NOT NULL DEFAULT 0,
    reward_resources JSON COMMENT 'ドロップした資源 [{resource_type_id, amount}, ...]',
    reward_troops JSON COMMENT 'ドロップした兵士 [{troop_type_id, count}, ...]',
    battle_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (encounter_id) REFERENCES user_wandering_monster_encounters(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_battle_at (battle_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='放浪モンスター討伐ログ';

-- ===============================================
-- ワールドボスマスター
-- ===============================================
CREATE TABLE IF NOT EXISTS world_bosses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    boss_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    description TEXT,
    boss_level INT UNSIGNED NOT NULL COMMENT 'ボスレベル（10, 20, 30...）',
    min_user_level INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '召喚に必要な最低ユーザーレベル',
    summon_cost_diamonds INT UNSIGNED NOT NULL DEFAULT 10 COMMENT '召喚に必要なダイヤモンド',
    base_health BIGINT UNSIGNED NOT NULL DEFAULT 100000,
    base_attack INT UNSIGNED NOT NULL DEFAULT 100,
    base_defense INT UNSIGNED NOT NULL DEFAULT 50,
    time_limit_hours INT UNSIGNED NOT NULL DEFAULT 24 COMMENT '討伐制限時間（時間）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_boss_level (boss_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ワールドボスマスター';

-- ===============================================
-- ワールドボス報酬設定（順位別）
-- ===============================================
CREATE TABLE IF NOT EXISTS world_boss_rewards (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    boss_id INT UNSIGNED NOT NULL,
    rank_start INT UNSIGNED NOT NULL COMMENT '順位開始（1, 2, 3...）',
    rank_end INT UNSIGNED NOT NULL COMMENT '順位終了（1, 10, 50...）',
    reward_coins INT UNSIGNED NOT NULL DEFAULT 0,
    reward_crystals INT UNSIGNED NOT NULL DEFAULT 0,
    reward_diamonds INT UNSIGNED NOT NULL DEFAULT 0,
    reward_resources JSON COMMENT '報酬資源 [{resource_type_id, amount}, ...]',
    reward_troops JSON COMMENT '報酬兵士 [{troop_type_id, count}, ...]',
    FOREIGN KEY (boss_id) REFERENCES world_bosses(id) ON DELETE CASCADE,
    INDEX idx_boss_rank (boss_id, rank_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ワールドボス報酬設定';

-- ===============================================
-- アクティブなワールドボスインスタンス
-- ===============================================
CREATE TABLE IF NOT EXISTS world_boss_instances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    boss_id INT UNSIGNED NOT NULL,
    summoner_user_id INT UNSIGNED NOT NULL COMMENT '召喚したユーザー',
    current_health BIGINT NOT NULL COMMENT '現在の体力',
    max_health BIGINT UNSIGNED NOT NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ends_at DATETIME NOT NULL COMMENT '討伐期限',
    defeated_at DATETIME NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    rewards_distributed BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (boss_id) REFERENCES world_bosses(id) ON DELETE CASCADE,
    FOREIGN KEY (summoner_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_active (is_active),
    INDEX idx_ends (ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='アクティブなワールドボスインスタンス';

-- ===============================================
-- ワールドボスへの参加・ダメージログ
-- ===============================================
CREATE TABLE IF NOT EXISTS world_boss_damage_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instance_id BIGINT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    damage_dealt BIGINT UNSIGNED NOT NULL DEFAULT 0,
    attack_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '攻撃回数',
    last_attack_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instance_id) REFERENCES world_boss_instances(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_instance_user (instance_id, user_id),
    INDEX idx_instance_damage (instance_id, damage_dealt DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ワールドボスダメージログ';

-- ===============================================
-- ワールドボス報酬配布ログ
-- ===============================================
CREATE TABLE IF NOT EXISTS world_boss_reward_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instance_id BIGINT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rank_position INT UNSIGNED NOT NULL,
    total_damage BIGINT UNSIGNED NOT NULL,
    reward_coins INT UNSIGNED NOT NULL DEFAULT 0,
    reward_crystals INT UNSIGNED NOT NULL DEFAULT 0,
    reward_diamonds INT UNSIGNED NOT NULL DEFAULT 0,
    reward_resources JSON,
    reward_troops JSON,
    distributed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instance_id) REFERENCES world_boss_instances(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_instance (instance_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ワールドボス報酬配布ログ';

-- ===============================================
-- 初期放浪モンスターデータ
-- ===============================================
INSERT IGNORE INTO wandering_monsters (monster_key, name, icon, description, min_level, max_level, base_attack, base_defense, base_health, level_scaling, reward_coins_min, reward_coins_max, reward_crystals_min, reward_crystals_max, reward_diamonds_min, reward_diamonds_max, soldier_drop_chance) VALUES
('wild_wolf', '野生の狼', '🐺', '荒野をうろつく危険な狼', 1, 10, 5, 3, 50, 1.10, 10, 50, 0, 1, 0, 0, 5.00),
('goblin', 'ゴブリン', '👹', '弱いが狡猾な小悪魔', 1, 15, 8, 4, 80, 1.12, 20, 80, 0, 2, 0, 0, 8.00),
('orc', 'オーク', '👿', '力強い野蛮な戦士', 5, 25, 15, 10, 200, 1.15, 50, 200, 1, 5, 0, 1, 10.00),
('troll', 'トロール', '🧌', '巨大で再生能力を持つ怪物', 10, 35, 25, 20, 500, 1.18, 100, 400, 2, 8, 0, 2, 12.00),
('dragon_whelp', '幼竜', '🐲', '成長途中のドラゴン', 15, 50, 40, 30, 1000, 1.20, 200, 800, 5, 15, 1, 3, 15.00),
('lich', 'リッチ', '💀', '強力な死霊術師', 25, 60, 50, 25, 800, 1.22, 300, 1000, 8, 20, 1, 5, 18.00),
('ancient_golem', '古代ゴーレム', '🗿', '太古の魔法で動く石像', 30, 70, 35, 60, 1500, 1.25, 400, 1500, 10, 30, 2, 6, 20.00),
('elder_dragon', '古竜', '🐉', '強大な力を持つ古きドラゴン', 40, 100, 80, 50, 3000, 1.30, 800, 3000, 20, 50, 5, 10, 25.00);

-- ===============================================
-- 放浪モンスターのドロップ品設定
-- ===============================================
-- ゴブリンのドロップ
INSERT IGNORE INTO wandering_monster_drops (monster_id, resource_type_id, troop_type_id, drop_chance, amount_min, amount_max) 
SELECT wm.id, rt.id, NULL, 20.00, 5, 20
FROM wandering_monsters wm, civilization_resource_types rt
WHERE wm.monster_key = 'goblin' AND rt.resource_key = 'food';

INSERT IGNORE INTO wandering_monster_drops (monster_id, resource_type_id, troop_type_id, drop_chance, amount_min, amount_max) 
SELECT wm.id, rt.id, NULL, 15.00, 3, 15
FROM wandering_monsters wm, civilization_resource_types rt
WHERE wm.monster_key = 'goblin' AND rt.resource_key = 'wood';

-- オークのドロップ
INSERT IGNORE INTO wandering_monster_drops (monster_id, resource_type_id, troop_type_id, drop_chance, amount_min, amount_max) 
SELECT wm.id, rt.id, NULL, 25.00, 10, 50
FROM wandering_monsters wm, civilization_resource_types rt
WHERE wm.monster_key = 'orc' AND rt.resource_key = 'iron';

INSERT IGNORE INTO wandering_monster_drops (monster_id, resource_type_id, troop_type_id, drop_chance, amount_min, amount_max) 
SELECT wm.id, NULL, tt.id, 10.00, 1, 3
FROM wandering_monsters wm, civilization_troop_types tt
WHERE wm.monster_key = 'orc' AND tt.troop_key = 'warrior';

-- トロールのドロップ
INSERT IGNORE INTO wandering_monster_drops (monster_id, resource_type_id, troop_type_id, drop_chance, amount_min, amount_max) 
SELECT wm.id, rt.id, NULL, 30.00, 20, 100
FROM wandering_monsters wm, civilization_resource_types rt
WHERE wm.monster_key = 'troll' AND rt.resource_key = 'stone';

INSERT IGNORE INTO wandering_monster_drops (monster_id, resource_type_id, troop_type_id, drop_chance, amount_min, amount_max) 
SELECT wm.id, NULL, tt.id, 15.00, 1, 5
FROM wandering_monsters wm, civilization_troop_types tt
WHERE wm.monster_key = 'troll' AND tt.troop_key = 'spearman';

-- 古竜のドロップ
INSERT IGNORE INTO wandering_monster_drops (monster_id, resource_type_id, troop_type_id, drop_chance, amount_min, amount_max) 
SELECT wm.id, rt.id, NULL, 40.00, 50, 200
FROM wandering_monsters wm, civilization_resource_types rt
WHERE wm.monster_key = 'elder_dragon' AND rt.resource_key = 'gold';

INSERT IGNORE INTO wandering_monster_drops (monster_id, resource_type_id, troop_type_id, drop_chance, amount_min, amount_max) 
SELECT wm.id, NULL, tt.id, 20.00, 1, 3
FROM wandering_monsters wm, civilization_troop_types tt
WHERE wm.monster_key = 'elder_dragon' AND tt.troop_key = 'knight';

-- ===============================================
-- 初期ワールドボスデータ
-- ===============================================
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('titan_lv10', '巨神タイタン Lv10', '🦾', '大地を揺るがす巨大な巨人', 10, 10, 10, 100000, 50, 30, 24),
('hydra_lv20', '九頭竜ヒュドラ Lv20', '🐍', '無数の首を持つ恐るべき蛇竜', 20, 20, 25, 500000, 100, 50, 24),
('phoenix_lv30', '不死鳥フェニックス Lv30', '🔥', '炎の中から蘇る不死の鳥', 30, 30, 50, 1500000, 150, 80, 24),
('kraken_lv40', '海魔クラーケン Lv40', '🦑', '深海から現れる巨大なイカ', 40, 40, 100, 5000000, 200, 100, 24),
('behemoth_lv50', '魔獣ベヒモス Lv50', '🦏', '世界を破壊する伝説の獣', 50, 50, 200, 15000000, 300, 150, 24),
('chaos_dragon_lv60', '混沌龍カオス Lv60', '🐉', '混沌をもたらす最強のドラゴン', 60, 60, 400, 50000000, 500, 200, 24),
('god_of_war_lv70', '戦神マルス Lv70', '⚔️', '戦争を司る神', 70, 70, 600, 100000000, 700, 300, 24),
('world_eater_lv80', '世界喰いジョルムンガンド Lv80', '🌍', '世界を飲み込む巨大な蛇', 80, 80, 1000, 300000000, 1000, 500, 24),
('void_lord_lv90', '虚無王 Lv90', '🌑', '虚無の次元から来た支配者', 90, 90, 2000, 1000000000, 1500, 800, 24),
('cosmic_entity_lv100', '宇宙創造神 Lv100', '🌌', '宇宙を創造した超越的存在', 100, 100, 5000, 5000000000, 3000, 1500, 24);

-- ===============================================
-- ワールドボス報酬設定
-- ===============================================
-- Lv10 タイタンの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 10000, 100, 10, NULL, NULL FROM world_bosses WHERE boss_key = 'titan_lv10';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 5000, 50, 5, NULL, NULL FROM world_bosses WHERE boss_key = 'titan_lv10';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 2000, 20, 2, NULL, NULL FROM world_bosses WHERE boss_key = 'titan_lv10';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 500, 5, 1, NULL, NULL FROM world_bosses WHERE boss_key = 'titan_lv10';

-- Lv20 ヒュドラの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 25000, 250, 25, NULL, NULL FROM world_bosses WHERE boss_key = 'hydra_lv20';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 12000, 120, 12, NULL, NULL FROM world_bosses WHERE boss_key = 'hydra_lv20';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 5000, 50, 5, NULL, NULL FROM world_bosses WHERE boss_key = 'hydra_lv20';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 1000, 10, 2, NULL, NULL FROM world_bosses WHERE boss_key = 'hydra_lv20';

-- Lv50 ベヒモスの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 200000, 2000, 200, NULL, NULL FROM world_bosses WHERE boss_key = 'behemoth_lv50';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 100000, 1000, 100, NULL, NULL FROM world_bosses WHERE boss_key = 'behemoth_lv50';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 50000, 500, 50, NULL, NULL FROM world_bosses WHERE boss_key = 'behemoth_lv50';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 10000, 100, 10, NULL, NULL FROM world_bosses WHERE boss_key = 'behemoth_lv50';

-- Lv100 宇宙創造神の報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 5000000, 50000, 5000, NULL, NULL FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 2500000, 25000, 2500, NULL, NULL FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 1000000, 10000, 1000, NULL, NULL FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 250000, 2500, 250, NULL, NULL FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100';

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'Wandering monsters and World boss schema applied successfully' AS status;
