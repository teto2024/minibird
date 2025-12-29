-- ===============================================
-- バトルログ拡張スキーマ
-- 放浪モンスター・ワールドボス戦闘の詳細ログを記録
-- ===============================================

USE microblog;

-- ===============================================
-- 放浪モンスター戦闘ターンログ
-- ===============================================
CREATE TABLE IF NOT EXISTS wandering_monster_turn_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    battle_log_id BIGINT UNSIGNED NOT NULL COMMENT 'wandering_monster_battle_logs.id',
    turn_number INT UNSIGNED NOT NULL,
    actor_side ENUM('attacker', 'defender', 'both') NOT NULL,
    action_type VARCHAR(50) NOT NULL DEFAULT 'attack',
    attacker_hp_before INT NOT NULL,
    attacker_hp_after INT NOT NULL,
    defender_hp_before INT NOT NULL,
    defender_hp_after INT NOT NULL,
    log_message TEXT NOT NULL,
    status_effects JSON,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (battle_log_id) REFERENCES wandering_monster_battle_logs(id) ON DELETE CASCADE,
    INDEX idx_battle_log (battle_log_id),
    INDEX idx_turn (battle_log_id, turn_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='放浪モンスター戦闘ターンログ';

-- ===============================================
-- ワールドボス戦闘ターンログ
-- ===============================================
CREATE TABLE IF NOT EXISTS world_boss_turn_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instance_id BIGINT UNSIGNED NOT NULL COMMENT 'world_boss_instances.id',
    user_id INT UNSIGNED NOT NULL,
    attack_number INT UNSIGNED NOT NULL COMMENT 'このユーザーの何回目の攻撃か',
    turn_number INT UNSIGNED NOT NULL,
    actor_side ENUM('attacker', 'defender', 'both') NOT NULL,
    action_type VARCHAR(50) NOT NULL DEFAULT 'attack',
    attacker_hp_before INT NOT NULL,
    attacker_hp_after INT NOT NULL,
    defender_hp_before BIGINT NOT NULL,
    defender_hp_after BIGINT NOT NULL,
    log_message TEXT NOT NULL,
    status_effects JSON,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instance_id) REFERENCES world_boss_instances(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_instance_user (instance_id, user_id),
    INDEX idx_attack (instance_id, user_id, attack_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ワールドボス戦闘ターンログ';

-- ===============================================
-- ワールドボス報酬に資源データを追加
-- ===============================================

-- Lv10 タイタンの報酬に資源追加
UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 5000}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'titan_lv10') AND rank_start = 1;

UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 2500}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'titan_lv10') AND rank_start = 2;

UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 1000}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'titan_lv10') AND rank_start = 4;

UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 250}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'titan_lv10') AND rank_start = 11;

-- Lv20 ヒュドラの報酬に資源追加
UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 12500}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 6250}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'hydra_lv20') AND rank_start = 1;

UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 6000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 3000}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'hydra_lv20') AND rank_start = 2;

UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 2500}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 1250}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'hydra_lv20') AND rank_start = 4;

UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 500}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'hydra_lv20') AND rank_start = 11;

-- Lv50 ベヒモスの報酬に大量資源追加
UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 100000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 50000}, {"resource_type_id": 3, "resource_key": "stone", "name": "石材", "icon": "🪨", "amount": 25000}, {"resource_type_id": 4, "resource_key": "iron", "name": "鉄", "icon": "⚙️", "amount": 10000}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'behemoth_lv50') AND rank_start = 1;

UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 50000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 25000}, {"resource_type_id": 3, "resource_key": "stone", "name": "石材", "icon": "🪨", "amount": 12500}, {"resource_type_id": 4, "resource_key": "iron", "name": "鉄", "icon": "⚙️", "amount": 5000}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'behemoth_lv50') AND rank_start = 2;

UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 25000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 12500}, {"resource_type_id": 3, "resource_key": "stone", "name": "石材", "icon": "🪨", "amount": 6250}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'behemoth_lv50') AND rank_start = 4;

UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 5000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 2500}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'behemoth_lv50') AND rank_start = 11;

-- Lv100 宇宙創造神の報酬に超大量資源追加
UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 2500000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 1250000}, {"resource_type_id": 3, "resource_key": "stone", "name": "石材", "icon": "🪨", "amount": 625000}, {"resource_type_id": 4, "resource_key": "iron", "name": "鉄", "icon": "⚙️", "amount": 312500}, {"resource_type_id": 5, "resource_key": "gold", "name": "金", "icon": "🪙", "amount": 156250}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100') AND rank_start = 1;

UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 1250000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 625000}, {"resource_type_id": 3, "resource_key": "stone", "name": "石材", "icon": "🪨", "amount": 312500}, {"resource_type_id": 4, "resource_key": "iron", "name": "鉄", "icon": "⚙️", "amount": 156250}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100') AND rank_start = 2;

UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 500000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 250000}, {"resource_type_id": 3, "resource_key": "stone", "name": "石材", "icon": "🪨", "amount": 125000}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100') AND rank_start = 4;

UPDATE world_boss_rewards 
SET reward_resources = '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 125000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 62500}]'
WHERE boss_id = (SELECT id FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100') AND rank_start = 11;

-- ===============================================
-- 残りのボスにもデフォルト資源報酬を追加
-- ===============================================

-- Lv30 フェニックスの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 50000, 500, 50, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 25000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 12500}]', NULL FROM world_bosses WHERE boss_key = 'phoenix_lv30';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 25000, 250, 25, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 12000}]', NULL FROM world_bosses WHERE boss_key = 'phoenix_lv30';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 10000, 100, 10, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 5000}]', NULL FROM world_bosses WHERE boss_key = 'phoenix_lv30';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 2000, 20, 2, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 1000}]', NULL FROM world_bosses WHERE boss_key = 'phoenix_lv30';

-- Lv40 クラーケンの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 100000, 1000, 100, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 50000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 25000}, {"resource_type_id": 3, "resource_key": "stone", "name": "石材", "icon": "🪨", "amount": 12500}]', NULL FROM world_bosses WHERE boss_key = 'kraken_lv40';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 50000, 500, 50, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 25000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 12500}]', NULL FROM world_bosses WHERE boss_key = 'kraken_lv40';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 25000, 250, 25, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 12500}]', NULL FROM world_bosses WHERE boss_key = 'kraken_lv40';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 5000, 50, 5, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 2500}]', NULL FROM world_bosses WHERE boss_key = 'kraken_lv40';

-- Lv60 カオスドラゴンの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 400000, 4000, 400, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 200000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 100000}, {"resource_type_id": 3, "resource_key": "stone", "name": "石材", "icon": "🪨", "amount": 50000}, {"resource_type_id": 4, "resource_key": "iron", "name": "鉄", "icon": "⚙️", "amount": 25000}]', NULL FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 200000, 2000, 200, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 100000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 50000}]', NULL FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 100000, 1000, 100, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 50000}]', NULL FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 20000, 200, 20, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 10000}]', NULL FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60';

-- Lv70 戦神マルスの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 600000, 6000, 600, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 300000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 150000}, {"resource_type_id": 3, "resource_key": "stone", "name": "石材", "icon": "🪨", "amount": 75000}, {"resource_type_id": 4, "resource_key": "iron", "name": "鉄", "icon": "⚙️", "amount": 37500}]', NULL FROM world_bosses WHERE boss_key = 'god_of_war_lv70';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 300000, 3000, 300, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 150000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 75000}]', NULL FROM world_bosses WHERE boss_key = 'god_of_war_lv70';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 150000, 1500, 150, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 75000}]', NULL FROM world_bosses WHERE boss_key = 'god_of_war_lv70';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 30000, 300, 30, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 15000}]', NULL FROM world_bosses WHERE boss_key = 'god_of_war_lv70';

-- Lv80 ジョルムンガンドの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 1000000, 10000, 1000, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 500000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 250000}, {"resource_type_id": 3, "resource_key": "stone", "name": "石材", "icon": "🪨", "amount": 125000}, {"resource_type_id": 4, "resource_key": "iron", "name": "鉄", "icon": "⚙️", "amount": 62500}]', NULL FROM world_bosses WHERE boss_key = 'world_eater_lv80';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 500000, 5000, 500, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 250000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 125000}]', NULL FROM world_bosses WHERE boss_key = 'world_eater_lv80';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 250000, 2500, 250, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 125000}]', NULL FROM world_bosses WHERE boss_key = 'world_eater_lv80';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 50000, 500, 50, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 25000}]', NULL FROM world_bosses WHERE boss_key = 'world_eater_lv80';

-- Lv90 虚無王の報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 2000000, 20000, 2000, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 1000000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 500000}, {"resource_type_id": 3, "resource_key": "stone", "name": "石材", "icon": "🪨", "amount": 250000}, {"resource_type_id": 4, "resource_key": "iron", "name": "鉄", "icon": "⚙️", "amount": 125000}]', NULL FROM world_bosses WHERE boss_key = 'void_lord_lv90';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 1000000, 10000, 1000, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 500000}, {"resource_type_id": 2, "resource_key": "wood", "name": "木材", "icon": "🪵", "amount": 250000}]', NULL FROM world_bosses WHERE boss_key = 'void_lord_lv90';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 500000, 5000, 500, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 250000}]', NULL FROM world_bosses WHERE boss_key = 'void_lord_lv90';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 100000, 1000, 100, '[{"resource_type_id": 1, "resource_key": "food", "name": "食料", "icon": "🌾", "amount": 50000}]', NULL FROM world_bosses WHERE boss_key = 'void_lord_lv90';

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'Battle log enhancement schema applied successfully' AS status;
